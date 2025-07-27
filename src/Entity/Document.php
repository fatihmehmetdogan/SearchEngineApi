<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\DocumentRepository::class)]
#[ORM\Table(name: 'documents')]
#[ORM\Index(name: 'idx_title', columns: ['title'])]
#[ORM\Index(name: 'idx_category', columns: ['category'])]
#[ORM\HasLifecycleCallbacks]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['document:read', 'search:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['document:read', 'document:write', 'search:read'])]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Groups(['document:read', 'document:write', 'search:read'])]
    private ?string $content = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Url]
    #[Groups(['document:read', 'document:write', 'search:read'])]
    private ?string $url = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['document:read', 'document:write', 'search:read'])]
    private ?string $category = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['document:read', 'document:write', 'search:read'])]
    private array $tags = [];

    #[ORM\Column(type: 'string', length: 10)]
    #[Assert\Choice(choices: ['video', 'text'])]
    #[Groups(['document:read', 'document:write', 'search:read'])]
    private string $type = 'text';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['document:read', 'document:write'])]
    private int $views = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['document:read', 'document:write'])]
    private int $likes = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?int $readingTime = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?int $reactions = null;

    #[ORM\Column(type: 'float', options: ['default' => 0])]
    #[Groups(['document:read', 'search:read'])]
    private float $finalScore = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['document:read', 'document:write', 'search:read'])]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['document:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['document:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->publishedAt = new \DateTimeImmutable(); // Varsayılan olarak şimdiki zamanı ayarla
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function setViews(?int $views): static
    {
        $this->views = $views ?? 0;
        return $this;
    }


    public function getLikes(): int
    {
        return $this->likes;
    }

    public function setLikes(?int $likes): static
    {
        $this->likes = $likes ?? 0;
        return $this;
    }

    public function getReadingTime(): ?int
    {
        return $this->readingTime;
    }

    public function setReadingTime(?int $readingTime): static
    {
        $this->readingTime = $readingTime;
        return $this;
    }

    public function getReactions(): ?int
    {
        return $this->reactions;
    }

    public function setReactions(?int $reactions): static
    {
        $this->reactions = $reactions;
        return $this;
    }

    public function getFinalScore(): float
    {
        return $this->finalScore;
    }

    public function setFinalScore(float $finalScore): static
    {
        $this->finalScore = $finalScore;
        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}