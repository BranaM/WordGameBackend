<?php

namespace App\Tests\Service;

use App\Service\WordService;
use PHPUnit\Framework\TestCase;

class WordServiceTest extends TestCase
{
    private WordService $service;

    protected function setUp(): void
    {
        $this->service = new WordService();
    }

    public function testIsEnglishWord(): void
    {
        $this->assertTrue($this->service->isEnglishWord('level'));
        $this->assertFalse($this->service->isEnglishWord('asdfgh'));
    }

    public function testCalculateScorePalindrome(): void
    {
        $score = $this->service->calculateScore('level'); // unique letters 3 + palindrome 3
        $this->assertEquals(6, $score);
    }

    public function testCalculateScoreAlmostPalindrome(): void
    {
        $score = $this->service->calculateScore('levee'); // unique letters 4 + almost palindrome 2
        $this->assertEquals(3, $score);
    }

    public function testCalculateScoreNormalWord(): void
    {
        $score = $this->service->calculateScore('hello'); // unique letters 4
        $this->assertEquals(4, $score);
    }
}
