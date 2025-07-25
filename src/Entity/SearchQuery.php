<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'search_queries')]
#[ORM\Index(name: 'idx_query', columns: ['query_text'])]
#[ORM\Index(name: 'idx_created', columns: ['created_at'])]
class SearchQuery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['query:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 500)]
    #[Groups(['query:read', 'query:write'])]
    private ?string $queryText = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['query:read', 'query:write'])]
    private array $filters = [];

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['query:read'])]
    private int $resultsCount = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4, nullable: true)]
    #[Groups(['query:read'])]
    private ?string $executionTime = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    #[Groups(['query:read'])]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['query:read'])]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['query:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQueryText(): ?string
    {
        return $this->queryText;
    }

    public function setQueryText(string $queryText): static
    {
        $this->queryText = $queryText;
        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters(array $filters): static
    {
        $this->filters = $filters;
        return $this;
    }

    public function getResultsCount(): int
    {
        return $this->resultsCount;
    }

    public function setResultsCount(int $resultsCount): static
    {
        $this->resultsCount = $resultsCount;
        return $this;
    }

    public function getExecutionTime(): ?float
    {
        return $this->executionTime !== null ? (float)$this->executionTime : null;
    }

    public function setExecutionTime(?float $executionTime): static
    {
        $this->executionTime = $executionTime !== null ? (string)$executionTime : null;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
