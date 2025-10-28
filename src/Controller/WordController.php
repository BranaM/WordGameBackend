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

        $word = $data['word'];
        $result = $this->wordService->processWord($word);

        $statusCode = $result->isValid ? 201 : 200;

        return $this->json([
            'success' => $result->isValid,
            'word' => trim(strtolower($word)),
            'score' => $result->score,
            'message' => $result->message
        ], $statusCode);
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

