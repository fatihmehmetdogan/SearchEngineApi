<?php

namespace App\Controller;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;
use App\Service\ScoringService;
use Psr\Log\LoggerInterface;

#[Route('/api/documents', name: 'documents_')]
#[OA\Tag(name: 'Documents')]
class DocumentController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private ScoringService $scoringService,
        private LoggerInterface $logger
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Get(
        path: '/',
        summary: 'Tüm dokümanları getir',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Sayfa numarası', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Sayfa başına sonuç', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'category', in: 'query', description: 'Kategoriye göre filtrele', schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Doküman listesi',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Document')),
                        new OA\Property(property: 'meta', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->logger->debug('🔥 Logger test ediliyor!');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;
        $category = $request->query->get('category');

        if ($category) {
            $documents = $this->documentRepository->findByCategory($category, $limit, $offset);
            $totalCount = $this->documentRepository->count(['category' => $category]);
        } else {
            $documents = $this->documentRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
            $totalCount = $this->documentRepository->count([]);
        }

        $data = $this->serializer->normalize($documents, null, ['groups' => ['document:read']]);

        $meta = [
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($totalCount / $limit)
        ];

        return $this->json([
            'data' => $data,
            'meta' => $meta
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[OA\Get(
        path: '/{id}',
        summary: 'Belirli bir dokümanı getir',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Doküman ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Doküman detayları',
                content: new OA\JsonContent(ref: '#/components/schemas/Document')
            ),
            new OA\Response(response: 404, description: 'Doküman bulunamadı')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $document = $this->documentRepository->find($id);
        if (!$document) { /* ... */ }

        if ($document->getType() === 'video') {
            $document->setViews(($document->getViews() ?? 0) + 1); // Views artırılıyor
            $this->logger->info("Video viewed: " . $document->getTitle() . " - New views: " . $document->getViews());
        } else {
            $document->setReactions(($document->getReactions() ?? 0) + 1); // Reaksiyon artırılıyor
            $this->logger->info("Text reacted: " . $document->getTitle() . " - New reactions: " . $document->getReactions());
        }

        $newScore = $this->scoringService->calculateFinalScore($document); // Skoru yeniden hesapla
        $document->setFinalScore($newScore);
        $this->entityManager->flush();

        $this->logger->info("Score updated for " . $document->getTitle() . ": " . $document->getFinalScore());

        $data = $this->serializer->normalize($document, null, ['groups' => ['document:read']]);

        return $this->json($data);
    }

    #[Route('/documents/{id}', name: 'document_detail_html', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showHtml(int $id): Response
    {
        $document = $this->documentRepository->find($id);

        if (!$document) {
            throw $this->createNotFoundException('Doküman bulunamadı.');
        }

        if ($document->getType() === 'video') {
            $document->setViews(($document->getViews() ?? 0) + 1);
        } else {
            $document->setReactions(($document->getReactions() ?? 0) + 1);
        }

        $newScore = $this->scoringService->calculateFinalScore($document);
        $document->setFinalScore($newScore);
        $this->entityManager->flush();

        return $this->render('document/show.html.twig', [
            'document' => $document,
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/',
        summary: 'Yeni doküman oluştur',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content', 'type'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Yeni Makale Başlığı'),
                    new OA\Property(property: 'content', type: 'string', example: 'Bu, yeni bir makalenin içeriğidir.'),
                    new OA\Property(property: 'type', type: 'string', enum: ['video', 'text'], example: 'text', description: 'İçerik türü (video veya text)'),
                    new OA\Property(property: 'url', type: 'string', format: 'url', nullable: true, example: 'https://ornek.com/yeni-makale'),
                    new OA\Property(property: 'category', type: 'string', nullable: true, example: 'Teknoloji'),
                    new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'), nullable: true, example: ['yeni', 'makale', 'symfony']),
                    new OA\Property(property: 'views', type: 'integer', nullable: true, example: 100),
                    new OA\Property(property: 'likes', type: 'integer', nullable: true, example: 10),
                    new OA\Property(property: 'readingTime', type: 'integer', nullable: true, example: 5),
                    new OA\Property(property: 'reactions', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'publishedAt', type: 'string', format: 'date-time', nullable: true, example: '2024-07-28T12:00:00Z'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Doküman oluşturuldu',
                content: new OA\JsonContent(ref: '#/components/schemas/Document')
            ),
            new OA\Response(response: 400, description: 'Doğrulama hatası')
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $format = $request->getContentTypeFormat(); // json, xml vs.

        $document = $this->serializer->deserialize(
            $request->getContent(),
            Document::class,
            $format, // <<< json veya xml otomatik desteklenir
            ['groups' => ['document:write']]
        );

        $errors = $this->validator->validate($document);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $responseData = $this->serializer->normalize($document, null, ['groups' => ['document:read']]);

        return $this->json($responseData, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[OA\Put(
        path: '/{id}',
        summary: 'Bir dokümanı güncelle',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Doküman ID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Güncellenmiş Başlık'),
                    new OA\Property(property: 'content', type: 'string', example: 'Güncellenmiş içerik.'),
                    new OA\Property(property: 'type', type: 'string', enum: ['video', 'text'], example: 'text', description: 'İçerik türü (video veya text)'),
                    new OA\Property(property: 'url', type: 'string', format: 'url', nullable: true, example: 'https://guncel.com/makale'),
                    new OA\Property(property: 'category', type: 'string', nullable: true, example: 'Yeni Kategori'),
                    new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'), nullable: true, example: ['güncel', 'test']),
                    new OA\Property(property: 'views', type: 'integer', nullable: true, example: 200),
                    new OA\Property(property: 'likes', type: 'integer', nullable: true, example: 20),
                    new OA\Property(property: 'readingTime', type: 'integer', nullable: true, example: 7),
                    new OA\Property(property: 'reactions', type: 'integer', nullable: true, example: 5),
                    new OA\Property(property: 'publishedAt', type: 'string', format: 'date-time', nullable: true, example: '2024-07-28T13:00:00Z'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Doküman güncellendi',
                content: new OA\JsonContent(ref: '#/components/schemas/Document')
            ),
            new OA\Response(response: 404, description: 'Doküman bulunamadı'),
            new OA\Response(response: 400, description: 'Doğrulama hatası')
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $document = $this->documentRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Doküman bulunamadı'], Response::HTTP_NOT_FOUND);
        }

        $this->serializer->deserialize(
            $request->getContent(),
            Document::class,
            'json',
            [
                'object_to_populate' => $document, // Mevcut nesneyi doldur
                'groups' => ['document:write']
            ]
        );

        $errors = $this->validator->validate($document);

        if (count(array_filter($errors->getIterator()->getArrayCopy())) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $responseData = $this->serializer->normalize($document, null, ['groups' => ['document:read']]);

        return $this->json($responseData);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[OA\Delete(
        path: '/{id}',
        summary: 'Bir dokümanı sil',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Doküman ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Doküman silindi'),
            new OA\Response(response: 404, description: 'Doküman bulunamadı')
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $document = $this->documentRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Doküman bulunamadı'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($document);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/like', name: 'like', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[OA\Post(
        path: '/{id}/like',
        summary: 'Bir dokümanı beğen',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Doküman ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Doküman beğenildi', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'new_likes', type: 'integer'),
                    new OA\Property(property: 'new_score', type: 'number'),
                    new OA\Property(property: 'new_reactions', type: 'integer', nullable: true) // <<< Bunu ekledim
                ]
            )),
            new OA\Response(response: 404, description: 'Doküman bulunamadı')
        ]
    )]
    public function like(int $id, DocumentRepository $documentRepository, EntityManagerInterface $entityManager, ScoringService $scoringService): JsonResponse
    {
        $document = $documentRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Doküman bulunamadı'], Response::HTTP_NOT_FOUND);
        }

        if ($document->getType() === 'video') {
            $document->setLikes(($document->getLikes() ?? 0) + 1);
            $newLikes = $document->getLikes();
            $newReactions = $document->getReactions();
        } else { // text
            $document->setReactions(($document->getReactions() ?? 0) + 1);
            $newReactions = $document->getReactions();
            $newLikes = $document->getLikes();
        }

        $newScore = $scoringService->calculateFinalScore($document);
        $document->setFinalScore($newScore);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Doküman beğenildi ve puan güncellendi.',
            'new_likes' => $newLikes,
            'new_reactions' => $newReactions,
            'new_score' => $document->getFinalScore()
        ]);
    }

    #[Route('/category/{category}', name: 'by_category', methods: ['GET'])]
    #[OA\Get(
        path: '/category/{category}',
        summary: 'Kategoriye göre dokümanları getir',
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', description: 'Kategori adı', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Sayfa numarası', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Sayfa başına sonuç', schema: new OA\Schema(type: 'integer', default: 20))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Kategorideki dokümanlar',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Document')),
                        new OA\Property(property: 'meta', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function byCategory(Request $request, string $category): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $documents = $this->documentRepository->findByCategory($category, $limit, $offset);
        $totalCount = $this->documentRepository->count(['category' => $category]);

        $data = $this->serializer->normalize($documents, null, ['groups' => ['document:read']]);

        $meta = [
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($totalCount / $limit),
            'category' => $category
        ];

        return $this->json([
            'data' => $data,
            'meta' => $meta
        ]);
    }
}