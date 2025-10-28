<?php

// src/Repository/WordRecordRepository.php

namespace App\Repository;

use App\Entity\WordRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Exception\WordAlreadyExistsException;

class WordRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordRecord::class);
    }

    public function findByWord(string $word): ?WordRecord
    {
        return $this->findOneBy(['word' => strtolower($word)]);
    }

    public function saveWordScore(string $word, int $score): void
    {
        $wordRecord = $this->findByWord($word);
        if ($wordRecord) {
            throw new WordAlreadyExistsException($word);
        }

        $record = new WordRecord();
        $record->setWord($word)
               ->setScore($score)
               ->setCreatedAt(new \DateTimeImmutable());

        $this->_em->persist($record);
        $this->_em->flush();
    }

    public function getRankedWords(): array
    {
        return $this->findBy([], ['score' => 'DESC']);
    }
}

