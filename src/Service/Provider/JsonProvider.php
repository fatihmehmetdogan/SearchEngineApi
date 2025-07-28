<?php

namespace App\Service\Provider;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class JsonProvider implements ProviderInterface
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
        return 'json_provider';
    }

    public function getFormat(): string
    {
        return 'json';
    }

    public function isAvailable(): bool
    {
        try {
            $response = $this->client->request('HEAD', $this->apiUrl);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->error('JSON provider availability check failed', [
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
     * Fetch content from JSON provider with dynamic data generation
     *
     * @param array $filters Optional filters (q, limit, etc.)
     * @return array Standardized array data
     * @throws \Exception If the request fails
     */
    public function fetchContent(array $filters = []): array
    {
        try {
            // Default query parametreleri
            $queryParams = array_merge([
                'q' => 'programming',
                'limit' => 10,
                'timestamp' => time() // Cache busting için timestamp ekle
            ], $filters);

            $response = $this->client->request('GET', $this->apiUrl, [
                'query' => $queryParams
            ]);

            $data = $response->toArray();

            // Log the successful fetch with dynamic info
            $this->logger->info('JSON Provider fetched content successfully', [
                'provider' => $this->getName(),
                'total_items' => count($data['data'] ?? []),
                'query' => $queryParams['q'] ?? 'none',
                'timestamp' => $data['timestamp'] ?? 'N/A',
                'has_dynamic_data' => isset($data['note']) && str_contains($data['note'], 'Dynamic')
            ]);

            // Ensure we have the expected structure
            if (!isset($data['data']) || !is_array($data['data'])) {
                $this->logger->warning('JSON Provider returned unexpected data structure', [
                    'provider' => $this->getName(),
                    'response_keys' => array_keys($data)
                ]);
                return ['data' => []];
            }

            // Validate and log each item's dynamic metrics
            foreach ($data['data'] as $index => $item) {
                if ($item['type'] === 'video') {
                    $this->logger->debug('JSON Provider video item metrics', [
                        'title' => $item['title'] ?? 'Unknown',
                        'views' => $item['views'] ?? 0,
                        'likes' => $item['likes'] ?? 0,
                        'dynamic_data' => true
                    ]);
                } else {
                    $this->logger->debug('JSON Provider text item metrics', [
                        'title' => $item['title'] ?? 'Unknown',
                        'reading_time' => $item['reading_time'] ?? 0,
                        'reactions' => $item['reactions'] ?? 0,
                        'dynamic_data' => true
                    ]);
                }
            }

            return $data;

        } catch (\Exception $e) {
            $this->logger->error('Error fetching content from JSON provider', [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'provider' => $this->getName(),
                'url' => $this->apiUrl
            ]);
            throw $e;
        }
    }
}