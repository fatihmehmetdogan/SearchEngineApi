<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/mock')]
class MockJsonProviderController extends AbstractController
{
    #[Route('/json', name: 'mock_json_provider', methods: ['GET'])]
    public function jsonProvider(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);

        // Mock JSON data
        $mockData = [
            [
                'id' => 1,
                'title' => "Complete Guide to {$query}",
                'content' => "This comprehensive tutorial covers everything about {$query}...",
                'type' => 'video',
                'views' => 50000,
                'likes' => 1200,
                'reading_time' => null,
                'reactions' => null,
                'category' => 'Tutorial',
                'tags' => ['tutorial', strtolower($query), 'beginner'],
                'url' => "https://example.com/video-{$query}-guide",
                'published_at' => '2024-01-15T10:00:00Z'
            ],
            [
                'id' => 2,
                'title' => "Advanced {$query} Techniques",
                'content' => "Learn advanced techniques and best practices for {$query}...",
                'type' => 'video',
                'views' => 75000,
                'likes' => 2100,
                'reading_time' => null,
                'reactions' => null,
                'category' => 'Advanced',
                'tags' => ['advanced', strtolower($query), 'tutorial'],
                'url' => "https://example.com/advanced-{$query}",
                'published_at' => '2024-01-20T14:30:00Z'
            ],
            [
                'id' => 3,
                'title' => "{$query} Quick Reference",
                'content' => "A quick reference guide for {$query} with examples and tips...",
                'type' => 'text',
                'views' => null,
                'likes' => null,
                'reading_time' => 5,
                'reactions' => 45,
                'category' => 'Reference',
                'tags' => ['reference', strtolower($query), 'quick'],
                'url' => "https://example.com/reference-{$query}",
                'published_at' => '2024-01-25T09:15:00Z'
            ]
        ];

        // Filter by limit
        $filteredData = array_slice($mockData, 0, $limit);

        return $this->json([
            'status' => 'success',
            'data' => $filteredData,
            'total' => count($filteredData),
            'query' => $query,
            'provider' => 'json_mock'
        ]);
    }
}

