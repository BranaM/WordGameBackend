<?php

namespace App\Service;

class WordResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly int $score,
        public readonly string $message = ''
    ) {}
}
