<?php

namespace App\Controller;

use App\Entity\Document;
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

#[Route('/search', name: 'search_')]
#[OA\Tag(name: 'Search')]
class SearchController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private SearchQueryRepository $searchQueryRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Get(
        path: '/api/search',
        summary: 'Search documents',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Search query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category', in: 'query', description: 'Filter by category', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'tags', in: 'query', description: 'Filter by tags (comma-separated)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Results per page', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'date_from', in: 'query', description: 'Filter from date (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', description: 'Filter to date (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Document')),
                        new OA\Property(property: 'meta', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        $query = $request->query->get('q', '');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        // Prepare filters
        $filters = [];
        if ($category = $request->query->get('category')) {
            $filters['category'] = $category;
        }
        if ($tags = $request->query->get('tags')) {
            $filters['tags'] = explode(',', $tags);
        }
        if ($dateFrom = $request->query->get('date_from')) {
            $filters['date_from'] = $dateFrom;
        }
        if ($dateTo = $request->query->get('date_to')) {
            $filters['date_to'] = $dateTo;
        }

        // Perform search
        $documents = $this->documentRepository->search($query, $filters, $limit, $offset);
        $totalCount = $this->documentRepository->countSearch($query, $filters);
        
        $executionTime = microtime(true) - $startTime;

        // Log search query
        $this->logSearchQuery($request, $query, $filters, count($documents), $executionTime);

        // Prepare response
        $data = $this->serializer->serialize($documents, 'json', ['groups' => ['search:read']]);
        
        $meta = [
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($totalCount / $limit),
            'execution_time' => round($executionTime, 4),
            'query' => $query,
            'filters' => $filters
        ];

        return new JsonResponse([
            'data' => json_decode($data),
            'meta' => $meta
        ]);
    }

    #[Route('/suggestions', name: 'suggestions', methods: ['GET'])]
    #[OA\Get(
        path: '/api/search/suggestions',
        summary: 'Get search suggestions',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Partial search query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Number of suggestions', schema: new OA\Schema(type: 'integer', default: 10))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search suggestions',
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
            return new JsonResponse(['suggestions' => []]);
        }

        // Get suggestions from document titles
        $documents = $this->documentRepository->createQueryBuilder('d')
            ->select('d.title')
            ->where('d.title LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $suggestions = array_map(fn($doc) => $doc['title'], $documents);

        return new JsonResponse(['suggestions' => $suggestions]);
    }

    #[Route('/popular', name: 'popular', methods: ['GET'])]
    #[OA\Get(
        path: '/api/search/popular',
        summary: 'Get popular search queries',
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', description: 'Number of popular queries', schema: new OA\Schema(type: 'integer', default: 10))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Popular search queries',
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

        return new JsonResponse(['popular_queries' => $popularQueries]);
    }

    #[Route('/categories', name: 'categories', methods: ['GET'])]
    #[OA\Get(
        path: '/api/search/categories',
        summary: 'Get popular categories',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Popular categories',
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
        
        return new JsonResponse(['categories' => $categories]);
    }

    #[Route('/tags', name: 'tags', methods: ['GET'])]
    #[OA\Get(
        path: '/api/search/tags',
        summary: 'Get all available tags',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Available tags',
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
        
        return new JsonResponse(['tags' => $tags]);
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    #[OA\Get(
        path: '/api/search/stats',
        summary: 'Get search statistics',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search statistics',
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
        
        return new JsonResponse(['statistics' => $stats]);
    }

    /**
     * Log search query for analytics
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
