<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api', name: 'api_')]
#[OA\Tag(name: 'Info')]
class ApiController extends AbstractController
{
    #[Route('/', name: 'info', methods: ['GET'])]
    #[OA\Get(
        path: '/api/',
        summary: 'API Bilgileri',
        tags: ['Info'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API bilgileri',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'version', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'endpoints', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function info(): JsonResponse
    {
        return new JsonResponse([
            'name' => 'Search Engine API',
            'version' => '1.0.0',
            'description' => 'Güçlü arama fonksiyonları sunan RESTful API',
            'documentation' => '/api/doc',
            'endpoints' => [
                'search' => [
                    'GET /api/search' => 'Dokuman arama',
                    'GET /api/search/suggestions' => 'Arama önerileri',
                    'GET /api/search/popular' => 'Popüler arama sorguları',
                    'GET /api/search/categories' => 'Popüler kategoriler',
                    'GET /api/search/tags' => 'Mevcut etiketler',
                    'GET /api/search/stats' => 'Arama istatistikleri'
                ],
                'documents' => [
                    'GET /api/documents' => 'Tüm dokomanları listele',
                    'GET /api/documents/{id}' => 'Belirli dokomanı getir',
                    'POST /api/documents' => 'Yeni dokoman oluştur',
                    'PUT /api/documents/{id}' => 'Dokomanı güncelle',
                    'DELETE /api/documents/{id}' => 'Dokomanı sil',
                    'GET /api/documents/category/{category}' => 'Kategoriye göre dokomanlar'
                ]
            ]
        ]);
    }

    #[Route('/health', name: 'api_health', methods: ['GET'])]
    #[OA\Get(
        path: '/api/health',
        summary: 'API Sağlık Kontrolü',
        tags: ['Info'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API sağlık durumu',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'timestamp', type: 'string'),
                        new OA\Property(property: 'services', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'healthy',
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'services' => [
                'api' => 'running',
                'database' => 'connected'
            ]
        ]);
    }
}
