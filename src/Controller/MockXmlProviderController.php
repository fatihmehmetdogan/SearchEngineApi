<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mock')]
class MockXmlProviderController extends AbstractController
{
    #[Route('/xml', name: 'mock_xml_provider', methods: ['GET'])]
    public function xmlProvider(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);

        // Mock XML data - XML formatına dikkat edin
        $mockXmlData = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<response>
    <status>success</status>
    <query>{$query}</query>
    <provider>xml_mock</provider>
    <items>
        <item>
            <id>4</id>
            <title>Understanding Fundamentals</title>
            <content>Deep dive into fundamentals and core concepts of modern programming.</content>
            <type>text</type>
            <views></views>
            <likes></likes>
            <reading_time>12</reading_time>
            <reactions>89</reactions>
            <category>Fundamentals</category>
            <tags>
                <tag>fundamentals</tag>
                <tag>programming</tag>
                <tag>theory</tag>
            </tags>
            <url>https://example.com/understanding-fundamentals</url>
            <published_at>2024-01-10T08:00:00Z</published_at>
        </item>
        <item>
            <id>5</id>
            <title>Best Practices Video Guide</title>
            <content>Learn industry best practices for development and software architecture.</content>
            <type>video</type>
            <views>95000</views>
            <likes>3200</likes>
            <reading_time></reading_time>
            <reactions></reactions>
            <category>Best Practices</category>
            <tags>
                <tag>best-practices</tag>
                <tag>software</tag>
                <tag>guide</tag>
            </tags>
            <url>https://example.com/best-practices-video</url>
            <published_at>2024-01-30T16:45:00Z</published_at>
        </item>
    </items>
    <total>2</total>
</response>
XML;

        $response = new Response($mockXmlData);
        $response->headers->set('Content-Type', 'application/xml');
        return $response;
    }
}