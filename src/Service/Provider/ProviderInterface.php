<?php

namespace App\Service\Provider;

interface ProviderInterface
{
    /**
     * Fetch content from the provider
     *
     * @param array $filters Optional filters
     * @return array Raw data from provider
     */
    public function fetchContent(array $filters = []): array;

    /**
     * Get provider name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get provider format (json/xml)
     *
     * @return string
     */
    public function getFormat(): string;

    /**
     * Check if provider is available
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Get rate limit information
     *
     * @return array
     */
    public function getRateLimit(): array;
}