<?php

namespace App\Exception;

class WordAlreadyExistsException extends \RuntimeException
{
    public function __construct(string $word)
    {
        parent::__construct("The word '{$word}' already exists in the database.");
    }
}
