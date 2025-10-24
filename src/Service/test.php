<?php

$dictionaryPath = __DIR__ . '/../Data/words_alpha.txt';
if (file_exists($dictionaryPath)) {
    echo "File found!";
} else {
    echo "File NOT found!";
}
