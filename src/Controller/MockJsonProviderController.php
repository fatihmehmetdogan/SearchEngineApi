<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/mock')]
class MockJsonProviderController extends AbstractController
{
    private array $baseVideoData = [
        [
            'id' => 1,
            'base_views' => 45000,
            'base_likes' => 1000,
            'category' => 'Tutorial',
            'tags' => ['tutorial', 'beginner'],
            'published_days_ago' => 5
        ],
        [
            'id' => 2,
            'base_views' => 70000,
            'base_likes' => 1800,
            'category' => 'Advanced',
            'tags' => ['advanced', 'tutorial'],
            'published_days_ago' => 3
        ]
    ];

    private array $baseTextData = [
        [
            'id' => 3,
            'base_reading_time' => 5,
            'base_reactions' => 40,
            'category' => 'Reference',
            'tags' => ['reference', 'quick'],
            'published_days_ago' => 2
        ],
        [
            'id' => 4,
            'base_reading_time' => 8,
            'base_reactions' => 60,
            'category' => 'Guide',
            'tags' => ['guide', 'comprehensive'],
            'published_days_ago' => 7
        ]
    ];

    #[Route('/json', name: 'mock_json_provider', methods: ['GET'])]
    public function jsonProvider(Request $request): JsonResponse
    {
        $query = $request->query->get('q', 'programming');
        $limit = $request->query->getInt('limit', 10);

        $mockData = [];

        // Video verileri - dinamik olarak üret
        foreach ($this->baseVideoData as $base) {
            $dynamicViews = $base['base_views'] + rand(100, 5000);
            $dynamicLikes = $base['base_likes'] + rand(10, 500);

            $publishedDate = (new \DateTime("-{$base['published_days_ago']} days"))->format('Y-m-d\TH:i:s\Z');

            $mockData[] = [
                'id' => $base['id'],
                'title' => $base['id'] === 1 ? "Complete Guide to {$query}" : "Advanced {$query} Techniques",
                'content' => $base['id'] === 1 ?
                    "This comprehensive tutorial covers everything about {$query}. Learn step by step with practical examples." :
                    "Learn advanced techniques and best practices for {$query}. Deep dive into complex scenarios.",
                'type' => 'video',
                'views' => $dynamicViews,
                'likes' => $dynamicLikes,
                'reading_time' => null,
                'reactions' => null,
                'category' => $base['category'],
                'tags' => array_merge($base['tags'], [strtolower($query)]),
                'url' => "https://example.com/video-{$base['id']}-{$query}",
                'published_at' => $publishedDate
            ];
        }

        // Text verileri - dinamik olarak üret
        foreach ($this->baseTextData as $base) {
            $dynamicReadingTime = $base['base_reading_time'] + rand(-1, 3);
            $dynamicReactions = $base['base_reactions'] + rand(5, 30);

            // Minimum 1 dakika okuma süresi
            if ($dynamicReadingTime < 1) {
                $dynamicReadingTime = 1;
            }

            $publishedDate = (new \DateTime("-{$base['published_days_ago']} days"))->format('Y-m-d\TH:i:s\Z');

            $mockData[] = [
                'id' => $base['id'],
                'title' => $base['id'] === 3 ? "{$query} Quick Reference" : "{$query} Comprehensive Guide",
                'content' => $base['id'] === 3 ?
                    "A quick reference guide for {$query} with examples and tips. Perfect for quick lookups." :
                    "A comprehensive guide covering all aspects of {$query}. Detailed explanations with examples.",
                'type' => 'text',
                'views' => null,
                'likes' => null,
                'reading_time' => $dynamicReadingTime,
                'reactions' => $dynamicReactions,
                'category' => $base['category'],
                'tags' => array_merge($base['tags'], [strtolower($query)]),
                'url' => "https://example.com/article-{$base['id']}-{$query}",
                'published_at' => $publishedDate
            ];
        }

        // Limit uygula
        $filteredData = array_slice($mockData, 0, $limit);

        return $this->json([
            'status' => 'success',
            'data' => $filteredData,
            'total' => count($filteredData),
            'query' => $query,
            'provider' => 'json_mock',
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'note' => 'Dynamic data - scores will change on each request'
        ]);
    }
}