<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mock')]
class MockXmlProviderController extends AbstractController
{
    private array $xmlBaseData = [
        [
            'id' => 5,
            'type' => 'text',
            'title_template' => 'Understanding {query} Fundamentals',
            'content_template' => 'Deep dive into fundamentals and core concepts of {query}.',
            'base_reading_time' => 10,
            'base_reactions' => 80,
            'category' => 'Fundamentals',
            'tags' => ['fundamentals', 'theory'],
            'published_days_ago' => 4
        ],
        [
            'id' => 6,
            'type' => 'video',
            'title_template' => '{query} Best Practices Video Guide',
            'content_template' => 'Learn industry best practices for {query} development and architecture.',
            'base_views' => 90000,
            'base_likes' => 2800,
            'category' => 'Best Practices',
            'tags' => ['best-practices', 'guide'],
            'published_days_ago' => 1
        ],
        [
            'id' => 7,
            'type' => 'text',
            'title_template' => '{query} Performance Optimization',
            'content_template' => 'Complete guide to optimizing {query} applications for better performance.',
            'base_reading_time' => 15,
            'base_reactions' => 120,
            'category' => 'Performance',
            'tags' => ['performance', 'optimization'],
            'published_days_ago' => 6
        ]
    ];

    #[Route('/xml', name: 'mock_xml_provider', methods: ['GET'])]
    public function xmlProvider(Request $request): Response
    {
        $query = $request->query->get('q', 'development');
        $limit = $request->query->getInt('limit', 10);
        $timestamp = (new \DateTime())->format('Y-m-d H:i:s');

        $items = '';
        $itemCount = 0;

        foreach ($this->xmlBaseData as $base) {
            if ($itemCount >= $limit) break;

            $title = str_replace('{query}', ucfirst($query), $base['title_template']);
            $content = str_replace('{query}', $query, $base['content_template']);
            $publishedDate = (new \DateTime("-{$base['published_days_ago']} days"))->format('Y-m-d\TH:i:s\Z');

            $tags = '';
            foreach (array_merge($base['tags'], [strtolower($query)]) as $tag) {
                $tags .= "<tag>{$tag}</tag>";
            }

            if ($base['type'] === 'video') {
                $dynamicViews = $base['base_views'] + rand(500, 8000);
                $dynamicLikes = $base['base_likes'] + rand(50, 600);

                $items .= <<<XML
        <item>
            <id>{$base['id']}</id>
            <title>{$title}</title>
            <content>{$content}</content>
            <type>video</type>
            <views>{$dynamicViews}</views>
            <likes>{$dynamicLikes}</likes>
            <reading_time></reading_time>
            <reactions></reactions>
            <category>{$base['category']}</category>
            <tags>{$tags}</tags>
            <url>https://example.com/xml-video-{$base['id']}-{$query}</url>
            <published_at>{$publishedDate}</published_at>
        </item>
XML;
            } else {
                $dynamicReadingTime = $base['base_reading_time'] + rand(-2, 5);
                $dynamicReactions = $base['base_reactions'] + rand(10, 50);

                // Minimum 1 dakika okuma süresi
                if ($dynamicReadingTime < 1) {
                    $dynamicReadingTime = 1;
                }

                $items .= <<<XML
        <item>
            <id>{$base['id']}</id>
            <title>{$title}</title>
            <content>{$content}</content>
            <type>text</type>
            <views></views>
            <likes></likes>
            <reading_time>{$dynamicReadingTime}</reading_time>
            <reactions>{$dynamicReactions}</reactions>
            <category>{$base['category']}</category>
            <tags>{$tags}</tags>
            <url>https://example.com/xml-article-{$base['id']}-{$query}</url>
            <published_at>{$publishedDate}</published_at>
        </item>
XML;
            }
            $itemCount++;
        }

        $mockXmlData = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<response>
    <status>success</status>
    <query>{$query}</query>
    <provider>xml_mock</provider>
    <timestamp>{$timestamp}</timestamp>
    <note>Dynamic data - scores will change on each request</note>
    <items>
{$items}
    </items>
    <total>{$itemCount}</total>
</response>
XML;

        $response = new Response($mockXmlData);
        $response->headers->set('Content-Type', 'application/xml');
        return $response;
    }
}