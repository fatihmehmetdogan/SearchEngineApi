<?php

namespace App\Service\Provider;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class XmlProvider implements ProviderInterface
{
    private string $apiUrl;
    private int $requestLimit;
    
    public function __construct(
        private HttpClientInterface $client,
        string $apiUrl,
        int $requestLimit,
        private LoggerInterface $logger
    ) {
        $this->apiUrl = $apiUrl;
        $this->requestLimit = $requestLimit;
    }

    public function getName(): string
    {
        return 'xml_provider';
    }

    public function getFormat(): string
    {
        return 'xml';
    }

    public function isAvailable(): bool
    {
        try {
            $response = $this->client->request('HEAD', $this->apiUrl);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->error('XML provider availability check failed', [
                'error' => $e->getMessage(),
                'url' => $this->apiUrl
            ]);
            return false;
        }
    }

    public function getRateLimit(): array
    {
        return [
            'limit' => $this->requestLimit,
            'remaining' => $this->requestLimit
        ];
    }

    /**
     * Fetch content from the XML provider and convert it to a standardized array.
     * This method handles potential XML parsing errors and standardizes the data structure.
     *
     * @param array $filters Optional filters
     * @return array Standardized array data, typically under a 'data' key.
     * @throws \RuntimeException If the XML content is invalid or cannot be parsed.
     */
    public function fetchContent(array $filters = []): array
    {
        try {
            // Default query parametreleri
            $queryParams = array_merge([
                'q' => 'development',
                'limit' => 10,
                'timestamp' => time() // Cache busting için timestamp ekle
            ], $filters);

            $response = $this->client->request('GET', $this->apiUrl, [
                'query' => $queryParams
            ]);

            $xmlString = $response->getContent();

            // XML parsing errors'ı yakalamak için dahili hata kullanımını etkinleştir
            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlString);

            // Eğer XML parse edilemezse hata fırlat
            if ($xmlObject === false) {
                $errors = [];
                foreach (libxml_get_errors() as $error) {
                    $errors[] = trim($error->message);
                }
                libxml_clear_errors();
                $errorMessage = 'Invalid XML received from provider: ' . implode(', ', $errors);
                $this->logger->error($errorMessage, [
                    'provider' => $this->getName(),
                    'raw_xml_preview' => substr($xmlString, 0, 500)
                ]);
                throw new \RuntimeException($errorMessage);
            }
            libxml_clear_errors();

            // XML objesini standardize edilmiş PHP array'ine dönüştür
            $standardizedData = [];

            // XML'de <response> altında <items> ve onun altında <item> var
            if (isset($xmlObject->items->item)) {
                foreach ($xmlObject->items->item as $item) {
                    // Etiketleri doğru şekilde alma
                    $tags = [];
                    if (isset($item->tags->tag)) {
                        foreach ($item->tags->tag as $tag) {
                            $tagValue = trim((string)$tag);
                            if (!empty($tagValue)) {
                                $tags[] = $tagValue;
                            }
                        }
                    }

                    // Type'a göre dynamic metrics'i kontrol et ve logla
                    $type = (string)($item->type ?? 'text');
                    $itemData = [
                        'id'           => (string)($item->id ?? ''),
                        'title'        => (string)($item->title ?? ''),
                        'content'      => (string)($item->content ?? ''),
                        'type'         => $type,
                        'category'     => (string)($item->category ?? ''),
                        'tags'         => $tags,
                        'url'          => (string)($item->url ?? ''),
                        'published_at' => (string)($item->published_at ?? (new \DateTime())->format(\DateTime::ATOM)),
                    ];

                    // Type'a göre metrics'i ayarla ve dynamic data kontrolü yap
                    if ($type === 'video') {
                        $views = (int)($item->views ?? 0);
                        $likes = (int)($item->likes ?? 0);

                        $itemData['views'] = $views;
                        $itemData['likes'] = $likes;
                        $itemData['reading_time'] = null;
                        $itemData['reactions'] = null;

                        // Dynamic data logging
                        $this->logger->debug('XML Provider video item metrics', [
                            'title' => $itemData['title'],
                            'views' => $views,
                            'likes' => $likes,
                            'dynamic_data' => true
                        ]);
                    } else {
                        $readingTime = (int)($item->reading_time ?? 0);
                        $reactions = (int)($item->reactions ?? 0);

                        $itemData['views'] = null;
                        $itemData['likes'] = null;
                        $itemData['reading_time'] = $readingTime;
                        $itemData['reactions'] = $reactions;

                        // Dynamic data logging
                        $this->logger->debug('XML Provider text item metrics', [
                            'title' => $itemData['title'],
                            'reading_time' => $readingTime,
                            'reactions' => $reactions,
                            'dynamic_data' => true
                        ]);
                    }

                    $standardizedData[] = $itemData;
                }
            } else {
                $this->logger->warning('XML Provider did not return expected structure', [
                    'provider' => $this->getName(),
                    'has_items' => isset($xmlObject->items),
                    'root_elements' => $xmlObject->children() !== null ? array_keys((array)$xmlObject->children()) : []
                ]);
            }

            // Log successful fetch with dynamic info
            $this->logger->info('XML Provider fetched content successfully', [
                'provider' => $this->getName(),
                'total_items' => count($standardizedData),
                'query' => $queryParams['q'] ?? 'none',
                'timestamp' => (string)($xmlObject->timestamp ?? 'N/A'),
                'has_dynamic_note' => isset($xmlObject->note) && str_contains((string)$xmlObject->note, 'Dynamic')
            ]);

            // Return in the expected format
            return ['data' => $standardizedData];

        } catch (\Exception $e) {
            $this->logger->error('Error fetching or processing content from XML provider', [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'provider' => $this->getName(),
                'url' => $this->apiUrl
            ]);
            throw $e;
        }
    }
}