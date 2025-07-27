<?php

namespace App\Service;

use App\Entity\Document;

class ScoringService
{
    /**
     * Calculate final score based on content type and metrics of a Document entity.
     *
     * @param Document $document The document entity to calculate the score for.
     * @return float The calculated final score.
     */
    public function calculateFinalScore(Document $document): float
    {
        $baseScore = $this->calculateBaseScore($document);
        $typeMultiplier = $this->getTypeMultiplier($document->getType());
        $freshnessScore = $this->calculateFreshnessScore($document->getPublishedAt());
        $engagementScore = $this->calculateEngagementScore($document);

        return ($baseScore * $typeMultiplier) + $freshnessScore + $engagementScore;
    }

    /**
     * Calculate base score based on content type from Document entity.
     *
     * @param Document $document
     * @return float
     */
    private function calculateBaseScore(Document $document): float
    {
        if ($document->getType() === 'video') {
            return ($document->getViews() / 1000) + ($document->getLikes() / 100);
        }
        return ($document->getReadingTime() ?? 0) + (($document->getReactions() ?? 0) / 50);
    }

    /**
     * Get multiplier based on content type.
     *
     * @param string $type
     * @return float
     */
    private function getTypeMultiplier(string $type): float
    {
        return $type === 'video' ? 1.5 : 1.0;
    }

    /**
     * Calculate freshness score based on content age.
     *
     * @param \DateTimeImmutable $publishedAt The publication date of the document.
     * @return int
     */
    private function calculateFreshnessScore(\DateTimeImmutable $publishedAt): int
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($publishedAt);

        $days = abs($diff->days);

        if ($days <= 7) return 5;
        if ($days <= 30) return 3;
        if ($days <= 90) return 1;
        return 0;
    }

    /**
     * Calculate engagement score based on content type from Document entity.
     *
     * @param Document $document
     * @return float
     */
    private function calculateEngagementScore(Document $document): float // Argüman tipi düzeltildi
    {
        if ($document->getType() === 'video') {
            $views = $document->getViews();
            $likes = $document->getLikes();
            return ($views > 0) ? ($likes / $views) * 10 : 0;
        }
        $readingTime = $document->getReadingTime();
        $reactions = $document->getReactions();
        return ($readingTime > 0) ? (($reactions ?? 0) / $readingTime) * 5 : 0;
    }
}