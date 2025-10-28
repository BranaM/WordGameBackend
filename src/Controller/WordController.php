<?php

namespace App\Controller;

use App\Service\WordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class WordController extends AbstractController
{
    public function __construct(private WordService $wordService)
    {
    }

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

        if (!$this->wordService->isEnglishWord($word)) {
            return $this->json([
                'success' => false,
                'word' => $word,
                'score' => 0,
                'message' => 'Word is not a valid English word.'
            ], status: 200);
        }

        $score = $this->wordService->calculateAndSave($word);

        return $this->json([
            'success' => true,
            'word' => $word,
            'score' => $score,
            'message' => 'Word saved successfully.'
        ], 201);
    }

    #[Route('/words/ranked', name: 'ranked_words', methods: ['GET'])]
    public function getRankedWords(): JsonResponse
    {
        $words = $this->wordService->getRankedWords();

        return $this->json([
            'success' => true,
            'count' => count($words),
            'words' => array_map(function ($wordRecord) {
                return [
                    'id' => $wordRecord->getId(),
                    'word' => $wordRecord->getWord(),
                    'score' => $wordRecord->getScore(),
                    'createdAt' => $wordRecord->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }, $words),
        ]);
    }
}

