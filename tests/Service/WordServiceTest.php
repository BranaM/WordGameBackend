<?php

namespace App\Tests\Service;

use App\Entity\WordRecord;
use App\Exception\WordAlreadyExistsException;
use App\Repository\WordRecordRepository;
use App\Service\WordService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class WordServiceTest extends TestCase
{
    private WordService $wordService;
    private $wordRepoMock;
    private $emMock;

    protected function setUp(): void
    {
        // Mock Repository
        $this->wordRepoMock = $this->createMock(WordRecordRepository::class);

        // Kreiramo WordService sa mockovima
        $this->wordService = new WordService($this->wordRepoMock);

        // Override dictionary sa malim test setom reči
        $reflection = new \ReflectionClass($this->wordService);
        $property = $reflection->getProperty('dictionary');
        $property->setAccessible(true);
        $property->setValue($this->wordService, array_flip(['cat', 'dog', 'level', 'madam', 'apple']));
    }

    public function testIsEnglishWord(): void
    {
        $this->assertTrue($this->wordService->isEnglishWord('cat'));
        $this->assertTrue($this->wordService->isEnglishWord('CAT')); // case insensitive
        $this->assertFalse($this->wordService->isEnglishWord('xyz'));
    }

    public function testCalculateAndSaveScoreNewWord(): void
    {
        // Simuliramo da reč ne postoji u bazi
        $this->wordRepoMock
            ->method('findByWord')
            ->willReturn(null);

        // Mock za persist i flush da samo prolazi
        $this->emMock->expects($this->once())->method('persist');
        $this->emMock->expects($this->once())->method('flush');

        $score = $this->wordService->calculateAndSave('apple');
        $this->assertIsInt($score);
        $this->assertGreaterThan(0, $score);
    }

    public function testCalculateAndSaveScoreWordAlreadyExists(): void
{
    // Simuliramo da reč već postoji u bazi
    $this->wordRepoMock
        ->method('saveWordScore')
        ->willThrowException(new WordAlreadyExistsException('level'));

    // Očekujemo da se baci exception
    $this->expectException(WordAlreadyExistsException::class);
    $this->expectExceptionMessage("The word 'level' already exists in the database.");

    // Pozivamo servis koji treba da reaguje na exception iz repozitorijuma
    $this->wordService->calculateAndSave('level');
}

}


