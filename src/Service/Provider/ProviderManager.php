<?php

namespace App\Service\Provider;

use App\Entity\Document;
use App\Service\ScoringService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ProviderManager
{
    private array $providers = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScoringService $scoringService,
        private LoggerInterface $logger
    ) {}

    /**
     * Register a provider
     */
    public function addProvider(ProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * Get all registered providers
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get a specific provider
     */
    public function getProvider(string $name): ?ProviderInterface
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * Fetch content from all available providers
     */
    public function fetchFromAllProviders(array $filters = []): array
    {
        $allContent = [];
        $providerStats = [];

        foreach ($this->providers as $providerName => $provider) {
            try {
                if (!$provider->isAvailable()) {
                    $this->logger->warning("Provider {$providerName} is not available");
                    $providerStats[$providerName] = [
                        'status' => 'unavailable',
                        'count' => 0,
                        'error' => 'Provider not available'
                    ];
                    continue;
                }

                $startTime = microtime(true);
                $content = $provider->fetchContent($filters);
                $executionTime = microtime(true) - $startTime;

                $standardizedContent = $this->standardizeContent($content, $provider);
                $allContent = array_merge($allContent, $standardizedContent);

                $providerStats[$providerName] = [
                    'status' => 'success',
                    'count' => count($content),
                    'execution_time' => round($executionTime, 4),
                    'rate_limit' => $provider->getRateLimit()
                ];

                $this->logger->info("Successfully fetched content from {$providerName}", [
                    'count' => count($content),
                    'execution_time' => $executionTime
                ]);

            } catch (\Exception $e) {
                $this->logger->error("Error fetching from provider {$providerName}", [
                    'error' => $e->getMessage()
                ]);

                $providerStats[$providerName] = [
                    'status' => 'error',
                    'count' => 0,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'content' => $allContent,
            'provider_stats' => $providerStats,
            'total_items' => count($allContent)
        ];
    }

    /**
     * Sync content from providers to database
     */
    public function syncContentToDatabase(array $filters = []): array
    {
        $result = $this->fetchFromAllProviders($filters);
        $content = $result['content'];
        
        $processed = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        foreach ($content as $item) {
            try {
                // Check if document already exists (by external ID)
                $existingDocument = $this->entityManager
                    ->getRepository(Document::class)
                    ->findOneBy(['url' => $item['url']]);

                if ($existingDocument) {
                    // Update existing document
                    $this->updateDocumentFromItem($existingDocument, $item);
                    $processed['updated']++;
                } else {
                    // Create new document
                    $document = $this->createDocumentFromItem($item);
                    $this->entityManager->persist($document);
                    $processed['created']++;
                }

            } catch (\Exception $e) {
                $processed['errors'][] = [
                    'item' => $item['title'] ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
                $processed['skipped']++;
                
                $this->logger->error('Error processing content item', [
                    'item' => $item,
                    'error' => $e->getMessage()
                ]);
            }
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Error saving content to database', ['error' => $e->getMessage()]);
            throw $e;
        }

        return array_merge($result, ['processed' => $processed]);
    }

    /**
     * Standardize content from different providers to uniform format
     */
    private function standardizeContent(array $content, ProviderInterface $provider): array
    {
        $standardized = [];

        foreach ($content as $item) {
            try {
                $standardizedItem = [
                    'provider' => $provider->getName(),
                    'provider_format' => $provider->getFormat(),
                    'external_id' => $item['id'] ?? null,
                    'title' => $this->sanitizeString($item['title'] ?? ''),
                    'content' => $this->sanitizeString($item['content'] ?? ''),
                    'url' => $item['url'] ?? null,
                    'type' => $this->standardizeType($item['type'] ?? 'text'),
                    'category' => $this->sanitizeString($item['category'] ?? 'General'),
                    'tags' => $this->standardizeTags($item['tags'] ?? []),
                    'views' => $this->sanitizeInteger($item['views']),
                    'likes' => $this->sanitizeInteger($item['likes']),
                    'reading_time' => $this->sanitizeInteger($item['reading_time']),
                    'reactions' => $this->sanitizeInteger($item['reactions']),
                    'published_at' => $this->standardizeDate($item['published_at'] ?? null),
                    'fetched_at' => new \DateTimeImmutable()
                ];

                // Calculate score using scoring service
                $standardizedItem['score'] = $this->scoringService->calculateScore($standardizedItem);

                $standardized[] = $standardizedItem;

            } catch (\Exception $e) {
                $this->logger->warning('Error standardizing content item', [
                    'item' => $item,
                    'provider' => $provider->getName(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $standardized;
    }

    private function createDocumentFromItem(array $item): Document
    {
        $document = new Document();
        $this->updateDocumentFromItem($document, $item);
        return $document;
    }

    private function updateDocumentFromItem(Document $document, array $item): void
    {
        $document->setTitle($item['title'])
                 ->setContent($item['content'])
                 ->setUrl($item['url'])
                 ->setType($item['type'])
                 ->setCategory($item['category'])
                 ->setTags($item['tags'])
                 ->setViews($item['views'] ?? 0)
                 ->setLikes($item['likes'] ?? 0)
                 ->setReadingTime($item['reading_time'])
                 ->setReactions($item['reactions'])
                 ->setScore($item['score'])
                 ->setUpdatedAt();
    }

    private function sanitizeString(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return trim(strip_tags($value));
    }

    private function sanitizeInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    private function standardizeType(string $type): string
    {
        $type = strtolower(trim($type));
        return in_array($type, ['video', 'text']) ? $type : 'text';
    }

    private function standardizeTags($tags): array
    {
        if (!is_array($tags)) {
            return [];
        }

        return array_filter(
            array_map(fn($tag) => $this->sanitizeString($tag), $tags),
            fn($tag) => !empty($tag)
        );
    }

    private function standardizeDate(?string $date): ?\DateTimeImmutable
    {
        if (!$date) {
            return null;
        }

        try {
            return new \DateTimeImmutable($date);
        } catch (\Exception $e) {
            $this->logger->warning('Invalid date format', ['date' => $date]);
            return null;
        }
    }

    /**
     * Get provider statistics
     */
    public function getProviderStatistics(): array
    {
        $stats = [];

        foreach ($this->providers as $providerName => $provider) {
            $stats[$providerName] = [
                'name' => $provider->getName(),
                'format' => $provider->getFormat(),
                'available' => $provider->isAvailable(),
                'rate_limit' => $provider->getRateLimit()
            ];
        }

        return $stats;
    }
}