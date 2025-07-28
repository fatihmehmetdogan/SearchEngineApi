<?php

namespace App\Controller;

use App\Entity\SearchQuery;
use App\Repository\DocumentRepository;
use App\Repository\SearchQueryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;

#[Route('/api/search', name: 'search_')]
#[OA\Tag(name: 'Search')]
class SearchController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private SearchQueryRepository $searchQueryRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Get(
        path: '/',
        summary: 'Doküman arama',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Arama sorgusu', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', description: 'İçerik türüne göre filtrele (video/text)', schema: new OA\Schema(type: 'string', enum: ['video', 'text'])),
            new OA\Parameter(name: 'category', in: 'query', description: 'Kategoriye göre filtrele', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'tags', in: 'query', description: 'Etiketlere göre filtrele (virgülle ayrılmış)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Sayfa numarası', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Sayfa başına sonuç sayısı', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Sıralama kriteri (finalScore, createdAt, title, type)', schema: new OA\Schema(type: 'string', default: 'finalScore', enum: ['finalScore', 'createdAt', 'title', 'type'])),
            new OA\Parameter(name: 'order', in: 'query', description: 'Sıralama yönü (asc/desc)', schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'date_from', in: 'query', description: 'Tarihten itibaren filtrele (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', description: 'Tarihe kadar filtrele (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Arama sonuçları',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Document')),
                        new OA\Property(property: 'meta', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Geçersiz parametreler')
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        $query = $request->query->get('q', '');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        // Dashboard'dan gelen sıralama parametrelerini al
        $sortBy = $request->query->get('sort', 'finalScore');
        $sortOrder = $request->query->get('order', 'desc');

        // Filtreleri hazırla
        $filters = [
            'sort' => $sortBy,
            'order' => $sortOrder
        ];

        // Filtreleri kontrol et ve uygunsa ekle
        if ($type = $request->query->get('type')) {
            if (!in_array($type, ['video', 'text'])) {
                return $this->json(['error' => 'Geçersiz "type" filtresi. "video" veya "text" olmalı.'], Response::HTTP_BAD_REQUEST);
            }
            $filters['type'] = $type;
        }
        if ($category = $request->query->get('category')) {
            $filters['category'] = $category;
        }
        if ($tags = $request->query->get('tags')) {
            $filters['tags'] = explode(',', $tags);
        }
        if ($dateFrom = $request->query->get('date_from')) {
            try {
                $filters['date_from'] = new \DateTimeImmutable($dateFrom);
            } catch (\Exception $e) {
                return $this->json(['error' => 'Geçersiz date_from formatı. YYYY-MM-DD kullanın.'], Response::HTTP_BAD_REQUEST);
            }
        }
        if ($dateTo = $request->query->get('date_to')) {
            try {
                $filters['date_to'] = new \DateTimeImmutable($dateTo);
            } catch (\Exception $e) {
                return $this->json(['error' => 'Geçersiz date_to formatı. YYYY-MM-DD kullanın.'], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $documents = $this->documentRepository->search($query, $filters, $limit, $offset);
            $totalCount = $this->documentRepository->countSearch($query, $filters);
        } catch (\Exception $e) {
            $this->logger->error('Arama sorgusu yürütülürken hata oluştu: ' . $e->getMessage(), ['exception' => $e, 'query' => $query, 'filters' => $filters]);
            return $this->json(['error' => 'Arama sırasında bir sunucu hatası oluştu.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $executionTime = microtime(true) - $startTime;

        // Arama sorgusunu logla
        $this->logSearchQuery($request, $query, $filters, count($documents), $executionTime);

        $data = $this->serializer->normalize($documents, null, ['groups' => ['search:read']]);

        $meta = [
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($totalCount / $limit),
            'execution_time' => round($executionTime, 4),
            'query' => $query,
            'filters' => $filters
        ];

        return $this->json([
            'data' => $data,
            'meta' => $meta
        ]);
    }

    #[Route('/suggestions', name: 'suggestions', methods: ['GET'])]
    #[OA\Get(
        path: '/suggestions',
        summary: 'Arama önerilerini al',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Kısmi arama sorgusu', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Öneri sayısı', schema: new OA\Schema(type: 'integer', default: 10))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Arama önerileri',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'suggestions', type: 'array', items: new OA\Items(type: 'string'))
                    ]
                )
            )
        ]
    )]
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min(20, max(1, (int) $request->query->get('limit', 10)));

        if (strlen($query) < 2) {
            return $this->json(['suggestions' => []]);
        }

        $documents = $this->documentRepository->createQueryBuilder('d')
            ->select('d.title')
            ->where('d.title LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $suggestions = array_map(fn($doc) => $doc['title'], $documents);

        return $this->json(['suggestions' => $suggestions]);
    }

    #[Route('/popular', name: 'popular', methods: ['GET'])]
    #[OA\Get(
        path: '/popular',
        summary: 'Popüler arama sorgularını al',
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', description: 'Popüler sorgu sayısı', schema: new OA\Schema(type: 'integer', default: 10))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Popüler arama sorguları',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'popular_queries', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            )
        ]
    )]
    public function popularQueries(Request $request): JsonResponse
    {
        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));

        $popularQueries = $this->searchQueryRepository->getPopularQueries($limit);

        return $this->json(['popular_queries' => $popularQueries]);
    }

    #[Route('/categories', name: 'categories', methods: ['GET'])]
    #[OA\Get(
        path: '/categories',
        summary: 'Popüler kategorileri al',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Popüler kategoriler',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            )
        ]
    )]
    public function categories(): JsonResponse
    {
        $categories = $this->documentRepository->getPopularCategories();

        return $this->json(['categories' => $categories]);
    }

    #[Route('/tags', name: 'tags', methods: ['GET'])]
    #[OA\Get(
        path: '/tags',
        summary: 'Tüm mevcut etiketleri al',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Mevcut etiketler',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'))
                    ]
                )
            )
        ]
    )]
    public function tags(): JsonResponse
    {
        $tags = $this->documentRepository->getAllTags();

        return $this->json(['tags' => $tags]);
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    #[OA\Get(
        path: '/stats',
        summary: 'Arama istatistiklerini al',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Arama istatistikleri',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'statistics', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function statistics(): JsonResponse
    {
        $stats = $this->searchQueryRepository->getSearchStatistics();

        return $this->json(['statistics' => $stats]);
    }

    /**
     * Arama sorgusunu analiz için kaydeder.
     */
    private function logSearchQuery(Request $request, string $query, array $filters, int $resultsCount, float $executionTime): void
    {
        if (empty($query)) {
            return;
        }

        $searchQuery = new SearchQuery();
        $searchQuery->setQueryText($query)
            ->setFilters($filters)
            ->setResultsCount($resultsCount)
            ->setExecutionTime($executionTime)
            ->setIpAddress($request->getClientIp())
            ->setUserAgent($request->headers->get('User-Agent'));

        $this->entityManager->persist($searchQuery);
        $this->entityManager->flush();
    }
}