<?php

namespace App\Tests\Repository;

use App\Entity\WordRecord;
use App\Repository\WordRecordRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for WordRecordRepository
 * Tests database operations with real database
 */
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

        // Clean up database before each test
        $this->entityManager->createQuery('DELETE FROM App\Entity\WordRecord')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up after test
        $this->entityManager->createQuery('DELETE FROM App\Entity\WordRecord')->execute();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    // ==================== findByWord() Tests ====================

    public function testFindByWordReturnsExistingWord(): void
    {
        // cat: c, a, t = 3 unique letters = 3
        $word = new WordRecord();
        $word->setWord('cat')
             ->setScore(3)
             ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($word);
        $this->entityManager->flush();

        $found = $this->repository->findByWord('cat');

        $this->assertNotNull($found);
        $this->assertEquals('cat', $found->getWord());
        $this->assertEquals(3, $found->getScore());
    }

    public function testFindByWordReturnsNullForNonExistentWord(): void
    {
        $found = $this->repository->findByWord('dog');

        $this->assertNull($found);
    }

    public function testFindByWordIsCaseSensitive(): void
    {
        // apple: a, p, l, e = 4 unique letters = 4
        $word = new WordRecord();
        $word->setWord('apple')
             ->setScore(4)
             ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($word);
        $this->entityManager->flush();

        // Search for uppercase (should not find since we expect normalized input)
        $found = $this->repository->findByWord('APPLE');
        $this->assertNull($found);

        // Search for lowercase (should find)
        $found = $this->repository->findByWord('apple');
        $this->assertNotNull($found);
    }

    // ==================== upsertWordScore() Tests ====================

    public function testUpsertWordScoreCreatesNewWord(): void
    {
        // hello: h, e, l, o = 4 unique letters = 4
        $result = $this->repository->upsertWordScore('hello', 4);

        $this->assertNotNull($result);
        $this->assertEquals('hello', $result->getWord());
        $this->assertEquals(4, $result->getScore());
        $this->assertNotNull($result->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getCreatedAt());
    }

    public function testUpsertWordScoreReturnsExistingWord(): void
    {
        // dog: d, o, g = 3 unique letters = 3
        $first = $this->repository->upsertWordScore('dog', 3);
        $firstId = $first->getId();

        // Try to insert again with different score
        $second = $this->repository->upsertWordScore('dog', 10);
        $secondId = $second->getId();

        // Should return same record with original score
        $this->assertEquals($firstId, $secondId);
        $this->assertEquals(3, $second->getScore());
    }

    public function testUpsertWordScorePersistsToDatabase(): void
    {
        // test: t, e, s = 3 unique letters = 3
        $this->repository->upsertWordScore('test', 3);

        // Clear entity manager to force fresh database query
        $this->entityManager->clear();

        $found = $this->repository->findByWord('test');

        $this->assertNotNull($found);
        $this->assertEquals('test', $found->getWord());
        $this->assertEquals(3, $found->getScore());
    }

    // ==================== getRankedWords() Tests ====================

    public function testGetRankedWordsReturnsEmptyArray(): void
    {
        $result = $this->repository->getRankedWords();

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testGetRankedWordsReturnsOrderedByScoreDescending(): void
    {
        // cat: c, a, t = 3 unique letters = 3
        $this->repository->upsertWordScore('cat', 3);

        // level: l, e, v = 3 unique letters + palindrome = 6
        $this->repository->upsertWordScore('level', 6);

        // apple: a, p, l, e = 4 unique letters = 4
        $this->repository->upsertWordScore('apple', 4);

        $result = $this->repository->getRankedWords();

        $this->assertCount(3, $result);
        $this->assertEquals('level', $result[0]->getWord());
        $this->assertEquals(6, $result[0]->getScore());
        $this->assertEquals('apple', $result[1]->getWord());
        $this->assertEquals(4, $result[1]->getScore());
        $this->assertEquals('cat', $result[2]->getWord());
        $this->assertEquals(3, $result[2]->getScore());
    }

    public function testGetRankedWordsHandlesEqualScores(): void
    {
        // cat: 3, dog: 3, pig: 3
        $this->repository->upsertWordScore('cat', 3);
        $this->repository->upsertWordScore('dog', 3);
        $this->repository->upsertWordScore('pig', 3);

        $result = $this->repository->getRankedWords();

        $this->assertCount(3, $result);
        foreach ($result as $word) {
            $this->assertEquals(3, $word->getScore());
        }
    }

    public function testGetRankedWordsReturnsMultipleWords(): void
    {
        // civic: c, i, v = 3 unique letters + palindrome = 6
        $this->repository->upsertWordScore('civic', 6);

        // level: l, e, v = 3 unique letters + palindrome = 6
        $this->repository->upsertWordScore('level', 6);

        // hello: h, e, l, o = 4 unique letters = 4
        $this->repository->upsertWordScore('hello', 4);

        // apple: a, p, l, e = 4 unique letters = 4
        $this->repository->upsertWordScore('apple', 4);

        // cat: c, a, t = 3 unique letters = 3
        $this->repository->upsertWordScore('cat', 3);

        $result = $this->repository->getRankedWords();

        $this->assertCount(5, $result);
    }

    // ==================== Edge Cases ====================

    public function testUpsertWordScoreWithZeroScore(): void
    {
        // Test with score 0 (edge case)
        $result = $this->repository->upsertWordScore('a', 1);

        $this->assertEquals('a', $result->getWord());
        $this->assertEquals(1, $result->getScore());
    }

    public function testCreatedAtIsImmutable(): void
    {
        // world: w, o, r, l, d = 5 unique letters = 5
        $word = $this->repository->upsertWordScore('world', 5);
        $createdAt = $word->getCreatedAt();

        // Clear and fetch again
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
        // First upsert
        $this->repository->upsertWordScore('hello', 4);

        // Try multiple times
        $this->repository->upsertWordScore('hello', 4);
        $this->repository->upsertWordScore('hello', 4);

        $all = $this->repository->getRankedWords();

        // Should only have one record
        $this->assertCount(1, $all);
    }
}
