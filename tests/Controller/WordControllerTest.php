<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for WordController
 * Tests HTTP endpoints with real requests
 */
class WordControllerTest extends WebTestCase
{
    // ==================== POST /word Tests ====================

    public function testCheckWordWithValidWord(): void
    {
        $client = static::createClient();

        $client->request('POST', '/word', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['word' => 'test'])
        );

        $response = $client->getResponse();
        $this->assertResponseIsSuccessful();

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('word', $data);
        $this->assertArrayHasKey('score', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertTrue($data['success']);
        $this->assertEquals('test', $data['word']);
        $this->assertGreaterThan(0, $data['score']);
    }

    public function testCheckWordWithInvalidWord(): void
    {
        $client = static::createClient();

        $client->request('POST', '/word', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['word' => 'xyznotvalid'])
        );

        $response = $client->getResponse();
        $this->assertResponseIsSuccessful();

        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertEquals(0, $data['score']);
        $this->assertEquals('Word is not a valid English word.', $data['message']);
    }

    public function testCheckWordWithEmptyWord(): void
    {
        $client = static::createClient();

        $client->request('POST', '/word', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['word' => ''])
        );

        $response = $client->getResponse();
        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertEquals('No word provided.', $data['message']);
    }

    public function testCheckWordWithMissingWord(): void
    {
        $client = static::createClient();

        $client->request('POST', '/word', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $response = $client->getResponse();
        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertEquals('No word provided.', $data['message']);
    }

    public function testCheckWordWithWhitespaceOnly(): void
    {
        $client = static::createClient();

        $client->request('POST', '/word', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['word' => '   '])
        );

        $response = $client->getResponse();
        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertEquals('No word provided.', $data['message']);
    }

    public function testCheckWordReturns201ForValidWord(): void
    {
        $client = static::createClient();

        $client->request('POST', '/word', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['word' => 'hello'])
        );

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCheckWordReturns200ForInvalidWord(): void
    {
        $client = static::createClient();

        $client->request('POST', '/word', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['word' => 'zzzznotreal'])
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testCheckWordNormalizesInput(): void
    {
        $client = static::createClient();

        $client->request('POST', '/word', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['word' => '  TEST  '])
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals('test', $data['word']);
    }

    // ==================== GET /words/ranked Tests ====================

    public function testGetRankedWordsReturnsSuccessfully(): void
    {
        $client = static::createClient();

        $client->request('GET', '/words/ranked');

        $response = $client->getResponse();
        $this->assertResponseIsSuccessful();

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertArrayHasKey('words', $data);
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['words']);
        $this->assertIsInt($data['count']);
    }

    public function testGetRankedWordsReturnsCorrectStructure(): void
    {
        $client = static::createClient();

        // First add a word
        $client->request('POST', '/word', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['word' => 'apple'])
        );

        // Then get ranked words
        $client->request('GET', '/words/ranked');

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertGreaterThanOrEqual(1, $data['count']);

        if ($data['count'] > 0) {
            $firstWord = $data['words'][0];
            $this->assertArrayHasKey('id', $firstWord);
            $this->assertArrayHasKey('word', $firstWord);
            $this->assertArrayHasKey('score', $firstWord);
            $this->assertArrayHasKey('createdAt', $firstWord);
        }
    }

    public function testGetRankedWordsOrderedByScoreDescending(): void
    {
        $client = static::createClient();

        $client->request('GET', '/words/ranked');

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        if ($data['count'] > 1) {
            $previousScore = PHP_INT_MAX;
            foreach ($data['words'] as $word) {
                $this->assertLessThanOrEqual($previousScore, $word['score']);
                $previousScore = $word['score'];
            }
        }
    }
}
