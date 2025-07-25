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

#[Route('/documents', name: 'documents_')]
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
        path: '/api/documents',
        summary: 'Get all documents',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Results per page', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'category', in: 'query', description: 'Filter by category', schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of documents',
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

        $data = $this->serializer->serialize($documents, 'json', ['groups' => ['document:read']]);

        $meta = [
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($totalCount / $limit)
        ];

        return new JsonResponse([
            'data' => json_decode($data),
            'meta' => $meta
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[OA\Get(
        path: '/api/documents/{id}',
        summary: 'Get a specific document',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Document ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Document details',
                content: new OA\JsonContent(ref: '#/components/schemas/Document')
            ),
            new OA\Response(response: 404, description: 'Document not found')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $document = $this->documentRepository->find($id);

        if (!$document) {
            return new JsonResponse(['error' => 'Document not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($document, 'json', ['groups' => ['document:read']]);

        return new JsonResponse(json_decode($data));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/documents',
        summary: 'Create a new document',
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
                description: 'Document created',
                content: new OA\JsonContent(ref: '#/components/schemas/Document')
            ),
            new OA\Response(response: 400, description: 'Validation error')
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $document = new Document();
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
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $responseData = $this->serializer->serialize($document, 'json', ['groups' => ['document:read']]);

        return new JsonResponse(json_decode($responseData), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[OA\Put(
        path: '/api/documents/{id}',
        summary: 'Update a document',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Document ID', schema: new OA\Schema(type: 'integer'))
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
                description: 'Document updated',
                content: new OA\JsonContent(ref: '#/components/schemas/Document')
            ),
            new OA\Response(response: 404, description: 'Document not found'),
            new OA\Response(response: 400, description: 'Validation error')
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $document = $this->documentRepository->find($id);

        if (!$document) {
            return new JsonResponse(['error' => 'Document not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Update fields
        if (isset($data['title'])) {
            $document->setTitle($data['title']);
        }
        if (isset($data['content'])) {
            $document->setContent($data['content']);
        }
        if (isset($data['url'])) {
            $document->setUrl($data['url']);
        }
        if (isset($data['category'])) {
            $document->setCategory($data['category']);
        }
        if (isset($data['tags'])) {
            $document->setTags($data['tags']);
        }

        $document->setUpdatedAt();

        $errors = $this->validator->validate($document);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $responseData = $this->serializer->serialize($document, 'json', ['groups' => ['document:read']]);

        return new JsonResponse(json_decode($responseData));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[OA\Delete(
        path: '/api/documents/{id}',
        summary: 'Delete a document',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Document ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Document deleted'),
            new OA\Response(response: 404, description: 'Document not found')
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $document = $this->documentRepository->find($id);

        if (!$document) {
            return new JsonResponse(['error' => 'Document not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($document);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/category/{category}', name: 'by_category', methods: ['GET'])]
    #[OA\Get(
        path: '/api/documents/category/{category}',
        summary: 'Get documents by category',
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', description: 'Category name', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Results per page', schema: new OA\Schema(type: 'integer', default: 20))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Documents in category',
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

        $data = $this->serializer->serialize($documents, 'json', ['groups' => ['document:read']]);

        $meta = [
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($totalCount / $limit),
            'category' => $category
        ];

        return new JsonResponse([
            'data' => json_decode($data),
            'meta' => $meta
        ]);
    }



}
