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
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getRateLimit(): array
    {
        return [
            'limit' => $this->requestLimit,
            'remaining' => $this->requestLimit // Bu değer gerçek kullanımda güncellenmelidir
        ];
    }

    public function fetchContent(array $filters = []): array
    {
        try {
            $response = $this->client->request('GET', $this->apiUrl, [
                'query' => $filters
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching content from JSON provider', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }
}