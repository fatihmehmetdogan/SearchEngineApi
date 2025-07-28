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
                'error' => $e->getMessage()
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
            $response = $this->client->request('GET', $this->apiUrl, [
                'query' => $filters
            ]);

            $xmlString = $response->getContent();

            // XML parsing errors'ı yakalamak için dahili hata kullanımını etkinleştir
            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlString);

            // Eğer XML parse edilemezse hata fırlat
            if ($xmlObject === false) {
                $errors = [];
                foreach (libxml_get_errors() as $error) {
                    $errors[] = $error->message;
                }
                libxml_clear_errors(); // Hataları temizle
                $errorMessage = 'Invalid XML received from provider: ' . implode(', ', $errors);
                $this->logger->error($errorMessage, [
                    'provider' => $this->getName(),
                    'raw_xml' => $xmlString
                ]);
                throw new \RuntimeException($errorMessage);
            }
            libxml_clear_errors(); // İşlem başarılıysa da hataları temizle

            // XML objesini standardize edilmiş PHP array'ine dönüştür
            $standardizedData = [];

            // XML'de <response> altında <items> ve onun altında <item> var.
            // xmlObject->items->item yapısını kullanmalıyız.
            if (isset($xmlObject->items->item)) {
                foreach ($xmlObject->items->item as $item) {
                    // Etiketleri doğru şekilde alma
                    $tags = [];
                    if (isset($item->tags->tag)) {
                        foreach ($item->tags->tag as $tag) {
                            if (!empty((string)$tag)) { // Boş tag elementlerini atla
                                $tags[] = (string)$tag;
                            }
                        }
                    }

                    $standardizedData[] = [
                        'id'           => (string)($item->id ?? ''),
                        'title'        => (string)($item->title ?? ''),
                        'content'      => (string)($item->content ?? ''),
                        'type'         => (string)($item->type ?? 'text'),
                        'views'        => (int)($item->views ?? 0),         // Boş gelirse 0 olacak
                        'likes'        => (int)($item->likes ?? 0),         // Boş gelirse 0 olacak
                        'reading_time' => (int)($item->reading_time ?? 0),   // Boş gelirse 0 olacak
                        'reactions'    => (int)($item->reactions ?? 0),     // Boş gelirse 0 olacak
                        'category'     => (string)($item->category ?? ''),
                        'tags'         => $tags, // Düzeltilmiş etiket alma
                        'url'          => (string)($item->url ?? ''),
                        'published_at' => (string)($item->published_at ?? (new \DateTime())->format(\DateTime::ATOM)),
                    ];
                }
            } else {
                $this->logger->warning('XML Provider did not return "items" or "item" elements under root.', ['provider' => $this->getName(), 'raw_xml_start' => substr($xmlString, 0, 500)]);
            }

            // `AppFixtures`'ın beklediği `{ 'data': [...] }` formatına uygun hale getir
            return ['data' => $standardizedData];


        } catch (\Exception $e) {
            $this->logger->error('Error fetching or processing content from XML provider', [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'provider' => $this->getName()
            ]);
            throw $e; // Hatayı tekrar fırlat, üst katman yakalasın
        }
    }
}