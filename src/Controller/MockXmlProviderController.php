<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/mock')]
class MockXmlProviderController extends AbstractController
{
    #[Route('/xml', name: 'mock_xml_provider', methods: ['GET'])]
    public function xmlProvider(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response></response>');
        $xml->addChild('status', 'success');
        $xml->addChild('query', htmlspecialchars($query));
        $xml->addChild('provider', 'xml_mock');

        $items = $xml->addChild('items');

        // Mock XML data
        $mockData = [
            [
                'id' => 4,
                'title' => "Understanding {$query} Fundamentals",
                'content' => "Deep dive into {$query} fundamentals and core concepts...",
                'type' => 'text',
                'views' => null,
                'likes' => null,
                'reading_time' => 12,
                'reactions' => 89,
                'category' => 'Fundamentals',
                'tags' => ['fundamentals', strtolower($query), 'theory'],
                'url' => "https://example.com/{$query}-fundamentals",
                'published_at' => '2024-01-10T08:00:00Z'
            ],
            [
                'id' => 5,
                'title' => "{$query} Best Practices Video",
                'content' => "Learn industry best practices for {$query} development...",
                'type' => 'video',
                'views' => 95000,
                'likes' => 3200,
                'reading_time' => null,
                'reactions' => null,
                'category' => 'Best Practices',
                'tags' => ['best-practices', strtolower($query), 'industry'],
                'url' => "https://example.com/{$query}-best-practices",
                'published_at' => '2024-01-30T16:45:00Z'
            ]
        ];

        $filteredData = array_slice($mockData, 0, $limit);

        foreach ($filteredData as $data) {
            $item = $items->addChild('item');
            foreach ($data as $key => $value) {
                if ($key === 'tags') {
                    $tagsElement = $item->addChild('tags');
                    foreach ($value as $tag) {
                        $tagsElement->addChild('tag', htmlspecialchars($tag));
                    }
                } else {
                    $item->addChild($key, htmlspecialchars($value));
                }
            }
        }

        $xml->addChild('total', count($filteredData));

        $response = new Response($xml->asXML());
        $response->headers->set('Content-Type', 'application/xml');

        return $response;
    }
}

