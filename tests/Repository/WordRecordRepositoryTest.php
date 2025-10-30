<?php

namespace App\Tests\Repository;

use App\Entity\WordRecord;
use App\Repository\WordRecordRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WordRecordRepositoryTest extends KernelTestCase
{
    private $entityManager;
    private WordRecordRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(WordRecord::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\WordRecord')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->createQuery('DELETE FROM App\Entity\WordRecord')->execute();
        $this->entityManager->close();
        $this->entityManager = null;
    }


    public function testFindByWordReturnsExistingWord(): void
    {
        $word = new WordRecord();
        $word->setWord('book')
            ->setScore(3)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($word);
        $this->entityManager->flush();

        $found = $this->repository->findByWord('book');

        $this->assertNotNull($found);
        $this->assertEquals('book', $found->getWord());
        $this->assertEquals(3, $found->getScore());
    }

    public function testFindByWordReturnsNullForNonExistentWord(): void
    {
        $found = $this->repository->findByWord('jacket');

        $this->assertNull($found);
    }

    public function testFindByWordIsCaseSensitive(): void
    {
        $word = new WordRecord();
        $word->setWord('flower')
            ->setScore(4)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($word);
        $this->entityManager->flush();

        $found = $this->repository->findByWord('FLOWER');
        $this->assertNull($found);

        $found = $this->repository->findByWord('flower');
        $this->assertNotNull($found);
    }

    public function testUpsertWordScoreCreatesNewWord(): void
    {
        $result = $this->repository->upsertWordScore('mouse', 5);

        $this->assertNotNull($result);
        $this->assertEquals('mouse', $result->getWord());
        $this->assertEquals(5, $result->getScore());
        $this->assertNotNull($result->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getCreatedAt());
    }

    public function testUpsertWordScoreReturnsExistingWord(): void
    {
        $first = $this->repository->upsertWordScore('book', 3);
        $firstId = $first->getId();

        $second = $this->repository->upsertWordScore('book', 10);
        $secondId = $second->getId();

        $this->assertEquals($firstId, $secondId);
        $this->assertEquals(3, $second->getScore());
    }

    public function testUpsertWordScorePersistsToDatabase(): void
    {
        $this->repository->upsertWordScore('carrot', 5);

        $this->entityManager->clear();

        $found = $this->repository->findByWord('carrot');

        $this->assertNotNull($found);
        $this->assertEquals('carrot', $found->getWord());
        $this->assertEquals(5, $found->getScore());
    }

    public function testGetRankedWordsReturnsEmptyArray(): void
    {
        $result = $this->repository->getRankedWords();

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testGetRankedWordsReturnsOrderedByScoreDescending(): void
    {
        $this->repository->upsertWordScore('door', 3);

        $this->repository->upsertWordScore('level', 6);

        $this->repository->upsertWordScore('chair', 5);

        $result = $this->repository->getRankedWords();

        $this->assertCount(3, $result);
        $this->assertEquals('level', $result[0]->getWord());
        $this->assertEquals(6, $result[0]->getScore());
        $this->assertEquals('chair', $result[1]->getWord());
        $this->assertEquals(5, $result[1]->getScore());
        $this->assertEquals('door', $result[2]->getWord());
        $this->assertEquals(3, $result[2]->getScore());
    }

    public function testGetRankedWordsHandlesEqualScores(): void
    {
        $this->repository->upsertWordScore('chair', 5);
        $this->repository->upsertWordScore('shirt', 5);
        $this->repository->upsertWordScore('mouse', 5);

        $result = $this->repository->getRankedWords();

        $this->assertCount(3, $result);
        foreach ($result as $word) {
            $this->assertEquals(5, $word->getScore());
        }
    }

    public function testGetRankedWordsReturnsMultipleWords(): void
    {
        $this->repository->upsertWordScore('flower', 6);

        $this->repository->upsertWordScore('level', 6);

        $this->repository->upsertWordScore('window', 5);

        $this->repository->upsertWordScore('chair', 5);

        $this->repository->upsertWordScore('game', 4);

        $result = $this->repository->getRankedWords();

        $this->assertCount(5, $result);
    }

    public function testUpsertWordScoreWithZeroScore(): void
    {
        $result = $this->repository->upsertWordScore('a', 1);

        $this->assertEquals('a', $result->getWord());
        $this->assertEquals(1, $result->getScore());
    }

    public function testCreatedAtIsImmutable(): void
    {
        $word = $this->repository->upsertWordScore('world', 5);
        $createdAt = $word->getCreatedAt();

        $this->entityManager->clear();

        $found = $this->repository->findByWord('world');
        $foundCreatedAt = $found->getCreatedAt();

        $this->assertEquals(
            $createdAt->format('Y-m-d H:i:s'),
            $foundCreatedAt->format('Y-m-d H:i:s')
        );
    }

    public function testMultipleUpsertsDontChangeDatabaseState(): void
    {
        $this->repository->upsertWordScore('game', 4);

        $this->repository->upsertWordScore('game', 4);
        $this->repository->upsertWordScore('game', 4);

        $all = $this->repository->getRankedWords();

        $this->assertCount(1, $all);
    }
}
