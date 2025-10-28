<?php

namespace App\Tests\Service;

use App\Entity\WordRecord;
use App\Repository\WordRecordRepository;
use App\Service\WordService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WordService
 * Tests public methods only with mocked repository
 */
class WordServiceTest extends TestCase
{
    private WordService $wordService;
    private $wordRepoMock;

    protected function setUp(): void
    {
        $this->wordRepoMock = $this->createMock(WordRecordRepository::class);
        $this->wordService = new WordService($this->wordRepoMock);

        // Override dictionary with test words
        $reflection = new \ReflectionClass($this->wordService);
        $property = $reflection->getProperty('dictionary');
        $property->setAccessible(true);
        $property->setValue($this->wordService, array_flip(['cat', 'dog', 'level', 'civic', 'apple']));
    }

    // ==================== isEnglishWord() Tests ====================

    public function testIsEnglishWordReturnsTrue(): void
    {
        $this->assertTrue($this->wordService->isEnglishWord('cat'));
        $this->assertTrue($this->wordService->isEnglishWord('dog'));
    }

    public function testIsEnglishWordReturnsFalse(): void
    {
        $this->assertFalse($this->wordService->isEnglishWord('xyz'));
        $this->assertFalse($this->wordService->isEnglishWord('notvalid'));
    }

    public function testIsEnglishWordCaseInsensitive(): void
    {
        $this->assertTrue($this->wordService->isEnglishWord('CAT'));
        $this->assertTrue($this->wordService->isEnglishWord('CaT'));
    }

    public function testIsEnglishWordHandlesWhitespace(): void
    {
        $this->assertTrue($this->wordService->isEnglishWord('  cat  '));
    }

    // ==================== processWord() Tests ====================

    public function testProcessWordWithValidWord(): void
    {
        // cat: 3 unique letters, not palindrome = 3
        $wordRecord = new WordRecord();
        $wordRecord->setWord('cat')
                   ->setScore(3)
                   ->setCreatedAt(new \DateTimeImmutable());

        $this->wordRepoMock
            ->expects($this->once())
            ->method('upsertWordScore')
            ->with('cat', 3)
            ->willReturn($wordRecord);

        $result = $this->wordService->processWord('cat');

        $this->assertTrue($result->isValid);
        $this->assertEquals(3, $result->score);
        $this->assertEquals('Word saved successfully.', $result->message);
    }

    public function testProcessWordWithInvalidWord(): void
    {
        $this->wordRepoMock
            ->expects($this->never())
            ->method('upsertWordScore');

        $result = $this->wordService->processWord('xyz');

        $this->assertFalse($result->isValid);
        $this->assertEquals(0, $result->score);
        $this->assertEquals('Word is not a valid English word.', $result->message);
    }

    public function testProcessWordNormalizesInput(): void
    {
        $wordRecord = new WordRecord();
        $wordRecord->setWord('cat')
                   ->setScore(3)
                   ->setCreatedAt(new \DateTimeImmutable());

        $this->wordRepoMock
            ->expects($this->once())
            ->method('upsertWordScore')
            ->with('cat', 3)
            ->willReturn($wordRecord);

        $result = $this->wordService->processWord('  CAT  ');
        $this->assertTrue($result->isValid);
    }

    public function testProcessWordCalculatesScoreWithPalindromeBonus(): void
    {
        // level: 3 unique letters (l,e,v) + 3 palindrome bonus = 6
        $wordRecord = new WordRecord();
        $wordRecord->setWord('level')
                   ->setScore(6)
                   ->setCreatedAt(new \DateTimeImmutable());

        $this->wordRepoMock
            ->expects($this->once())
            ->method('upsertWordScore')
            ->with('level', 6)
            ->willReturn($wordRecord);

        $result = $this->wordService->processWord('level');

        $this->assertTrue($result->isValid);
        $this->assertEquals(6, $result->score);
    }

    // ==================== getRankedWords() Tests ====================

    public function testGetRankedWordsReturnsOrderedList(): void
    {
        $word1 = new WordRecord();
        $word1->setWord('level')->setScore(6)->setCreatedAt(new \DateTimeImmutable());

        $word2 = new WordRecord();
        $word2->setWord('apple')->setScore(4)->setCreatedAt(new \DateTimeImmutable());

        $word3 = new WordRecord();
        $word3->setWord('cat')->setScore(3)->setCreatedAt(new \DateTimeImmutable());

        $this->wordRepoMock
            ->expects($this->once())
            ->method('getRankedWords')
            ->willReturn([$word1, $word2, $word3]);

        $result = $this->wordService->getRankedWords();

        $this->assertCount(3, $result);
        $this->assertEquals('level', $result[0]->getWord());
        $this->assertEquals(6, $result[0]->getScore());
    }

    public function testGetRankedWordsReturnsEmptyArray(): void
    {
        $this->wordRepoMock
            ->expects($this->once())
            ->method('getRankedWords')
            ->willReturn([]);

        $result = $this->wordService->getRankedWords();

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }
}
