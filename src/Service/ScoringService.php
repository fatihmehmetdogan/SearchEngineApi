<?php

namespace App\Service;

class ScoringService
{
    /**
     * Calculate final score based on content type and metrics
     */
    public function calculateScore(array $content): float
    {
        $baseScore = $this->calculateBaseScore($content);
        $typeMultiplier = $this->getTypeMultiplier($content['type']);
        $freshnessScore = $this->calculateFreshnessScore($content['created_at']);
        $engagementScore = $this->calculateEngagementScore($content);

        return ($baseScore * $typeMultiplier) + $freshnessScore + $engagementScore;
    }

    /**
     * Calculate base score based on content type
     */
    private function calculateBaseScore(array $content): float
    {
        if ($content['type'] === 'video') {
            return ($content['views'] / 1000) + ($content['likes'] / 100);
        }
        return $content['reading_time'] + ($content['reactions'] / 50);
    }

    /**
     * Get multiplier based on content type
     */
    private function getTypeMultiplier(string $type): float
    {
        return $type === 'video' ? 1.5 : 1.0;
    }

    /**
     * Calculate freshness score based on content age
     */
    private function calculateFreshnessScore(\DateTimeInterface $createdAt): int
    {
        $now = new \DateTime();
        $diff = $now->diff($createdAt);
        
        if ($diff->days <= 7) return 5;
        if ($diff->days <= 30) return 3;
        if ($diff->days <= 90) return 1;
        return 0;
    }

    /**
     * Calculate engagement score based on content type
     */
    private function calculateEngagementScore(array $content): float
    {
        if ($content['type'] === 'video') {
            return ($content['views'] > 0) ? ($content['likes'] / $content['views']) * 10 : 0;
        }
        return ($content['reading_time'] > 0) ? ($content['reactions'] / $content['reading_time']) * 5 : 0;
    }
}