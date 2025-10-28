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

    public function testProcessWordValid(): void
    {
        // Create a mock WordRecord
        $wordRecord = new WordRecord();
        $wordRecord->setWord('cat')
                   ->setScore(3)
                   ->setCreatedAt(new \DateTimeImmutable());

        // Simulate upsert creating a new word
        $this->wordRepoMock
            ->expects($this->once())
            ->method('upsertWordScore')
            ->with('cat', $this->anything())
            ->willReturn($wordRecord);

        $result = $this->wordService->processWord('cat');
        $this->assertTrue($result->isValid);
        $this->assertGreaterThan(0, $result->score);
        $this->assertEquals('Word saved successfully.', $result->message);
    }

    public function testProcessWordInvalid(): void
    {
        // Don't mock repository since word is invalid
        $result = $this->wordService->processWord('xyz');
        $this->assertFalse($result->isValid);
        $this->assertEquals(0, $result->score);
        $this->assertEquals('Word is not a valid English word.', $result->message);
    }

}


