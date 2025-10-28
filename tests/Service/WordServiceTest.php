<?php

namespace App\Tests\Service;

use App\Entity\WordRecord;
use App\Repository\WordRecordRepository;
use App\Service\WordService;
use PHPUnit\Framework\TestCase;

class WordServiceTest extends TestCase
{
    private WordService $wordService;
    private $wordRepoMock;

    protected function setUp(): void
    {
        // Mock Repository
        $this->wordRepoMock = $this->createMock(WordRecordRepository::class);

        // Create WordService with mocks
        $this->wordService = new WordService($this->wordRepoMock);

        // Override dictionary with small test set of words
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
        // Create a mock WordRecord
        $wordRecord = new WordRecord();
        $wordRecord->setWord('apple')
                   ->setScore(4)
                   ->setCreatedAt(new \DateTimeImmutable());

        // Simulate upsert creating a new word
        $this->wordRepoMock
            ->expects($this->once())
            ->method('upsertWordScore')
            ->with('apple', $this->anything())
            ->willReturn($wordRecord);

        $score = $this->wordService->calculateAndSave('apple');
        $this->assertIsInt($score);
        $this->assertGreaterThan(0, $score);
    }

    public function testCalculateAndSaveScoreWordAlreadyExists(): void
    {
        // Create a mock existing WordRecord
        $existingRecord = new WordRecord();
        $existingRecord->setWord('level')
                       ->setScore(5)
                       ->setCreatedAt(new \DateTimeImmutable());

        // Simulate upsert returning existing word
        $this->wordRepoMock
            ->expects($this->once())
            ->method('upsertWordScore')
            ->with('level', $this->anything())
            ->willReturn($existingRecord);

        $score = $this->wordService->calculateAndSave('level');
        $this->assertIsInt($score);
        $this->assertEquals(5, $score);
    }

}


