<?php

namespace App\Controller;

use App\Service\WordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class WordController extends AbstractController
{
    public function __construct(private WordService $wordService) {}

    #[Route('/word', name: 'check_word', methods: ['POST'])]
    public function checkWord(Request $request): JsonResponse
    {
         $data = json_decode($request->getContent(), true);

        if (!isset($data['word']) || empty(trim($data['word']))) {
            return $this->json([
                'success' => false,
                'message' => 'No word provided.'
            ], 400);
        }

        $word = trim($data['word']);

        // Check if word is in the dictionary
        if (!$this->wordService->isEnglishWord($word)) {
            return $this->json([
                'success' => false,
                'message' => 'Word is not a valid English word.'
            ], 200);
        }

        // Calculate score using updated service
        $score = $this->wordService->calculateScore($word);

        return $this->json([
            'success' => true,
            'word' => $word,
            'score' => $score
        ]);
    }
}

