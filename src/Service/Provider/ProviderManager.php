<?php

namespace App\Service\Provider;

use App\Entity\Document;
use App\Service\ScoringService;
use Psr\Log\LoggerInterface;

class ProviderManager
{
    /**
     * @var ProviderInterface[]
     */
    private array $providers = [];

    // Constructor'a ScoringService ve LoggerInterface'i de inject edelim
    public function __construct(private ScoringService $scoringService, private LoggerInterface $logger)
    {
    }

    public function addProvider(ProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * Fetch content from all registered providers, process it, and return Document entities.
     *
     * @return Document[]
     */
    public function fetchAndProcessAllContent(): array
    {
        $allDocuments = [];

        foreach ($this->providers as $provider) {
            if (!$provider->isAvailable()) {
                $this->logger->warning(sprintf('Provider "%s" is not available, skipping.', $provider->getName()));
                continue;
            }

            try {
                $rawData = $provider->fetchContent(); // Her providerdan veriyi çek

                // Her providerdan gelen veriyi standardize edip Document entity'sine dönüştür
                // Bu kısım providerın formatına göre değişebilir, örnek olarak JSONProvider'dan beklediğimiz formatı baz alalım.
                // Gerçek bir senaryoda bu dönüşüm için Provider'a özgü bir 'Transformer' sınıfı da kullanılabilir.
                if (isset($rawData['data']) && is_array($rawData['data'])) {
                    foreach ($rawData['data'] as $item) {
                        $document = new Document();
                        $document->setTitle($item['title'] ?? 'No Title');
                        $document->setContent($item['content'] ?? 'No Content');
                        $document->setType($item['type'] ?? 'text');
                        $document->setUrl($item['url'] ?? null);
                        $document->setCategory($item['category'] ?? null);
                        $document->setTags($item['tags'] ?? []);

                        // Puanlama için gerekli metrikleri ayarla
                        if ($document->getType() === 'video') {
                            // Null kontrolü eklemeden önce
                            $document->setViews($item['views'] ?? 0);
                            $document->setLikes($item['likes'] ?? 0);
                            $document->setReadingTime(null);
                            $document->setReactions(null);
                        } else {
                            $document->setReadingTime($item['reading_time'] ?? null); // Metin için okuma süresi
                            $document->setReactions($item['reactions'] ?? null); // Metin için reaksiyonlar
                            $document->setViews(null);
                            $document->setLikes(null);
                        }

                        if (isset($item['published_at'])) {
                            try {
                                $document->setPublishedAt(new \DateTimeImmutable($item['published_at']));
                            } catch (\Exception $e) {
                                $this->logger->error('Invalid published_at date from provider', ['provider' => $provider->getName(), 'date' => $item['published_at'], 'error' => $e->getMessage()]);
                                $document->setPublishedAt(new \DateTimeImmutable()); // Fallback
                            }
                        } else {
                            $document->setPublishedAt(new \DateTimeImmutable());
                        }

                        // Final skoru hesapla ve ata
                        $finalScore = $this->scoringService->calculateFinalScore($document);
                        $document->setFinalScore($finalScore);

                        $allDocuments[] = $document;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Error fetching content from provider "%s": %s', $provider->getName(), $e->getMessage()));
            }
        }

        return $allDocuments;
    }
}