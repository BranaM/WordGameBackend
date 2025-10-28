<?php

// src/Repository/WordRecordRepository.php

namespace App\Repository;

use App\Entity\WordRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WordRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordRecord::class);
    }

    public function findByWord(string $word): ?WordRecord
    {
        return $this->findOneBy(['word' => $word]);
    }

    public function upsertWordScore(string $word, int $score): WordRecord
    {
        $wordRecord = $this->findByWord($word);
        if ($wordRecord) {
            return $wordRecord;
        }

        $record = new WordRecord();
        $record->setWord($word)
            ->setScore($score)
            ->setCreatedAt(new \DateTimeImmutable());

        $em = $this->getEntityManager();
        $em->persist($record);
        $em->flush();

        return $record;
    }

    public function getRankedWords(): array
    {
        return $this->findBy([], ['score' => 'DESC']);
    }
}

