<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WordControllerTest extends WebTestCase
{
    public function testWordScoringEndpoint(): void
    {
        $client = static::createClient();

        // Send POST request to your API endpoint
        $client->request('POST', '/word', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'word' => 'level'
        ]));

        $response = $client->getResponse();

        // Check response status
        $this->assertResponseIsSuccessful();

        // // Decode response JSON
        // $data = json_decode($response->getContent(), true);

        // // Check structure and value
        // $this->assertArrayHasKey('score', $data);
        // $this->assertSame(8, $data['score']); // or whatever correct score your logic gives
    }
}
