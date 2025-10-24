<?php

namespace App\Service;

class WordService
{
    private array $dictionary;

    public function __construct()
    {
        // Load the dictionary into memory
        $dictionaryPath = __DIR__ . '/../Data/words_alpha.txt';
        if (!file_exists($dictionaryPath)) {
            throw new \RuntimeException("Dictionary file not found at $dictionaryPath");
        }

        // Load all words into an array
        $this->dictionary = array_flip(
            array_map('strtolower', file($dictionaryPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
        );
        // Using array_flip for faster lookup: isset() is O(1)
    }

    public function isEnglishWord(string $word): bool
    {
        return isset($this->dictionary[strtolower($word)]);
    }

    public function calculateScore(string $word): int
    {
        $word = strtolower($word);
        $uniqueLetters = count(array_unique(str_split($word)));
        $score = $uniqueLetters;

        // Palindrome bonus
        if ($this->isPalindrome($word)) {
            $score += 3;
        } 
        // Almost palindrome bonus (+2)
        elseif ($this->isAlmostPalindrome($word)) {
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
                // Remove either left or right letter and check palindrome
                $oneRemovedLeft = substr($word, $left + 1, $right - $left);
                $oneRemovedRight = substr($word, $left, $right - $left);

                return $oneRemovedLeft === strrev($oneRemovedLeft) ||
                       $oneRemovedRight === strrev($oneRemovedRight);
            }
            $left++;
            $right--;
        }

        // Already a palindrome, so not "almost"
        return false;
    }
}
