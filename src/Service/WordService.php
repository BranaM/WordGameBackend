<?php

namespace App\Service;

use App\Entity\WordRecord;
use App\Repository\WordRecordRepository;
use Doctrine\ORM\EntityManagerInterface;

class WordService
{
    private array $dictionary;
    private WordRecordRepository $wordRepo;

    public function __construct(WordRecordRepository $wordRepo)
    {
        $this->wordRepo = $wordRepo;

        $dictionaryPath = __DIR__ . '/../Data/words_alpha.txt';
        if (!file_exists($dictionaryPath)) {
            throw new \RuntimeException("Dictionary file not found at $dictionaryPath");
        }

        $this->dictionary = array_flip(
            array_map('strtolower', file($dictionaryPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
        );
    }

    public function isEnglishWord(string $word): bool
    {
        return isset($this->dictionary[strtolower($word)]);
    }

    public function calculateAndSave(string $word): int
    {
        $wordLower = strtolower($word);
        $wordRecord = $this->wordRepo->findByWord($wordLower);
        if ($wordRecord) {
            return $wordRecord->getScore();
        }

        $score = $this->calculateScore($wordLower);
        $this->wordRepo->saveWordScore($wordLower, $score);
    
        return $score;
    }

    private function calculateScore(string $word): int
    {
        $uniqueLetters = count(array_unique(str_split($word)));
        $score = $uniqueLetters;

        if ($this->isPalindrome($word)) {
            $score += 3;
        } elseif ($this->isAlmostPalindrome($word)) {
            $score += 2;
        }

        return $score;
    }

    private function isPalindrome(string $word): bool
    {
        return $word === strrev($word);
    }

    private function isAlmostPalindrome(string $word): bool
    {
        $len = strlen($word);
        $left = 0;
        $right = $len - 1;

        while ($left < $right) {
            if ($word[$left] !== $word[$right]) {
                $oneRemovedLeft = substr($word, $left + 1, $right - $left);
                $oneRemovedRight = substr($word, $left, $right - $left);

                return $oneRemovedLeft === strrev($oneRemovedLeft) ||
                       $oneRemovedRight === strrev($oneRemovedRight);
            }
            $left++;
            $right--;
        }

        return false;
    }
}
