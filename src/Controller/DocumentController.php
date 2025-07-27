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

#[Route('/api/documents', name: 'documents_')]
#[OA\Tag(name: 'Documents')]
class DocumentController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
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

        if (!$document) {
            return $this->json(['error' => 'Doküman bulunamadı'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->normalize($document, null, ['groups' => ['document:read']]);

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/',
        summary: 'Yeni doküman oluştur',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'url', type: 'string', format: 'url'),
                    new OA\Property(property: 'category', type: 'string'),
                    new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'))
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
        $document = $this->serializer->deserialize(
            $request->getContent(),
            Document::class,
            'json',
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
        path: '/{id}', // Relatif yol
        summary: 'Bir dokümanı güncelle',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Doküman ID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'url', type: 'string', format: 'url'),
                    new OA\Property(property: 'category', type: 'string'),
                    new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'))
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

        if (count($errors) > 0) {
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