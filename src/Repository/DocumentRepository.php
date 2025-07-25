<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * Search documents using full-text search and filters
     */
    public function search(string $query, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('d');

        // Full-text search
        if (!empty($query)) {
            $qb->andWhere('MATCH(d.title, d.content) AGAINST (:query IN BOOLEAN MODE) > 0')
               ->setParameter('query', $this->prepareSearchQuery($query));
        }

        // Apply filters
        $this->applyFilters($qb, $filters);

        // Order by relevance if search query exists, otherwise by creation date
        if (!empty($query)) {
            $qb->addSelect('MATCH(d.title, d.content) AGAINST (:query IN BOOLEAN MODE) as HIDDEN relevance')
               ->orderBy('relevance', 'DESC');
        } else {
            $qb->orderBy('d.createdAt', 'DESC');
        }

        return $qb->setMaxResults($limit)
                  ->setFirstResult($offset)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Count search results
     */
    public function countSearch(string $query, array $filters = []): int
    {
        $qb = $this->createQueryBuilder('d')
                   ->select('COUNT(d.id)');

        if (!empty($query)) {
            $qb->andWhere('MATCH(d.title, d.content) AGAINST (:query IN BOOLEAN MODE) > 0')
               ->setParameter('query', $this->prepareSearchQuery($query));
        }

        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get documents by category
     */
    public function findByCategory(string $category, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('d')
                    ->andWhere('d.category = :category')
                    ->setParameter('category', $category)
                    ->orderBy('d.createdAt', 'DESC')
                    ->setMaxResults($limit)
                    ->setFirstResult($offset)
                    ->getQuery()
                    ->getResult();
    }

    /**
     * Get documents by tags
     */
    public function findByTags(array $tags, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('d');
        
        foreach ($tags as $index => $tag) {
            $qb->andWhere("JSON_CONTAINS(d.tags, :tag{$index}) = 1")
               ->setParameter("tag{$index}", json_encode($tag));
        }

        return $qb->orderBy('d.createdAt', 'DESC')
                  ->setMaxResults($limit)
                  ->setFirstResult($offset)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Get popular categories
     */
    public function getPopularCategories(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
                    ->select('d.category, COUNT(d.id) as doc_count')
                    ->andWhere('d.category IS NOT NULL')
                    ->groupBy('d.category')
                    ->orderBy('doc_count', 'DESC')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    /**
     * Get all unique tags
     */
    public function getAllTags(): array
    {
        $documents = $this->createQueryBuilder('d')
                          ->select('d.tags')
                          ->andWhere('d.tags IS NOT NULL')
                          ->getQuery()
                          ->getResult();

        $allTags = [];
        foreach ($documents as $doc) {
            $allTags = array_merge($allTags, $doc['tags']);
        }

        return array_unique($allTags);
    }

    /**
     * Apply filters to query builder
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['category']) && !empty($filters['category'])) {
            $qb->andWhere('d.category = :category')
               ->setParameter('category', $filters['category']);
        }

        if (isset($filters['tags']) && !empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            foreach ($tags as $index => $tag) {
                $qb->andWhere("JSON_CONTAINS(d.tags, :tag{$index}) = 1")
                   ->setParameter("tag{$index}", json_encode($tag));
            }
        }

        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $qb->andWhere('d.createdAt >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($filters['date_from']));
        }

        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $qb->andWhere('d.createdAt <= :dateTo')
               ->setParameter('dateTo', new \DateTime($filters['date_to']));
        }
    }

    /**
     * Prepare search query for full-text search
     */
    private function prepareSearchQuery(string $query): string
    {
        // Remove special characters and prepare for boolean mode
        $query = preg_replace('/[^\w\s]/', '', $query);
        $words = explode(' ', trim($query));
        $words = array_filter($words, fn($word) => strlen($word) > 2);
        
        return '+' . implode(' +', $words);
    }

    /**
     * Find documents for dashboard with sorting and filtering
     */
    public function findForDashboard(
        int $page = 1,
        string $sort = 'score',
        string $order = 'desc',
        ?string $type = null,
        int $limit = 10
    ): array {
        $qb = $this->createQueryBuilder('d');

        if ($type) {
            $qb->andWhere('d.type = :type')
               ->setParameter('type', $type);
        }

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['title', 'score', 'createdAt', 'type'];
        $sort = in_array($sort, $allowedSortFields) ? $sort : 'score';
        
        // Validate order
        $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';

        $qb->orderBy('d.' . $sort, $order)
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Count total documents for pagination
     */
    public function countForDashboard(?string $type = null): int
    {
        $qb = $this->createQueryBuilder('d')
                   ->select('COUNT(d.id)');

        if ($type) {
            $qb->andWhere('d.type = :type')
               ->setParameter('type', $type);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
