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
        $this->wordRepoMock = $this->createMock(WordRecordRepository::class);
        $this->wordService = new WordService($this->wordRepoMock);

        $reflection = new \ReflectionClass($this->wordService);
        $property = $reflection->getProperty('dictionary');
        $property->setAccessible(true);
        $property->setValue($this->wordService, array_flip(['carrot', 'game', 'level', 'door', 'window']));
    }

    public function testIsEnglishWordReturnsTrue(): void
    {
        $this->assertTrue($this->wordService->isEnglishWord('carrot'));
        $this->assertTrue($this->wordService->isEnglishWord('window'));
    }

    public function testIsEnglishWordReturnsFalse(): void
    {
        $this->assertFalse($this->wordService->isEnglishWord('nxxdcnsl'));
        $this->assertFalse($this->wordService->isEnglishWord('ncdsk'));
    }

    public function testIsEnglishWordCaseInsensitive(): void
    {
        $this->assertTrue($this->wordService->isEnglishWord('CARROT'));
        $this->assertTrue($this->wordService->isEnglishWord('CArroT'));
    }

    public function testIsEnglishWordHandlesWhitespace(): void
    {
        $this->assertTrue($this->wordService->isEnglishWord('  carrot  '));
    }

    public function testProcessWordWithValidWord(): void
    {
        $wordRecord = new WordRecord();
        $wordRecord->setWord('game')
            ->setScore(4)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->wordRepoMock
            ->expects($this->once())
            ->method('upsertWordScore')
            ->with('game', 4)
            ->willReturn($wordRecord);

        $result = $this->wordService->processWord('game');

        $this->assertTrue($result->isValid);
        $this->assertEquals(4, $result->score);
        $this->assertEquals('Word saved successfully.', $result->message);
    }

    public function testProcessWordWithInvalidWord(): void
    {
        $this->wordRepoMock
            ->expects($this->never())
            ->method('upsertWordScore');

        $result = $this->wordService->processWord('nhdj');

        $this->assertFalse($result->isValid);
        $this->assertEquals(0, $result->score);
        $this->assertEquals('Word is not a valid English word.', $result->message);
    }

    public function testProcessWordNormalizesInput(): void
    {
        $wordRecord = new WordRecord();
        $wordRecord->setWord('game')
            ->setScore(4)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->wordRepoMock
            ->expects($this->once())
            ->method('upsertWordScore')
            ->with('game', 4)
            ->willReturn($wordRecord);

        $result = $this->wordService->processWord('  GAME  ');
        $this->assertTrue($result->isValid);
    }

    public function testProcessWordCalculatesScoreWithPalindromeBonus(): void
    {
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

    public function testGetRankedWordsReturnsOrderedList(): void
    {
        $word1 = new WordRecord();
        $word1->setWord('level')->setScore(6)->setCreatedAt(new \DateTimeImmutable());

        $word2 = new WordRecord();
        $word2->setWord('carrot')->setScore(4)->setCreatedAt(new \DateTimeImmutable());

        $word3 = new WordRecord();
        $word3->setWord('game')->setScore(3)->setCreatedAt(new \DateTimeImmutable());

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
