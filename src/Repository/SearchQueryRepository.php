<?php

namespace App\Repository;

use App\Entity\SearchQuery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SearchQueryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchQuery::class);
    }

    /**
     * Get most popular search queries
     */
    public function getPopularQueries(int $limit = 10): array
    {
        return $this->createQueryBuilder('sq')
                    ->select('sq.queryText, COUNT(sq.id) as search_count, AVG(sq.resultsCount) as avg_results')
                    ->groupBy('sq.queryText')
                    ->orderBy('search_count', 'DESC')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    /**
     * Get search statistics
     */
    public function getSearchStatistics(): array
    {
        $qb = $this->createQueryBuilder('sq');
        
        return [
            'total_searches' => $qb->select('COUNT(sq.id)')
                                  ->getQuery()
                                  ->getSingleScalarResult(),
            'avg_execution_time' => $qb->select('AVG(sq.executionTime)')
                                      ->getQuery()
                                      ->getSingleScalarResult(),
            'avg_results_count' => $qb->select('AVG(sq.resultsCount)')
                                     ->getQuery()
                                     ->getSingleScalarResult(),
            'searches_today' => $this->getSearchesCount('today'),
            'searches_this_week' => $this->getSearchesCount('this week'),
            'searches_this_month' => $this->getSearchesCount('this month')
        ];
    }

    /**
     * Get searches count for specific period
     */
    private function getSearchesCount(string $period): int
    {
        $date = new \DateTime();
        
        switch ($period) {
            case 'today':
                $date->setTime(0, 0, 0);
                break;
            case 'this week':
                $date->modify('monday this week')->setTime(0, 0, 0);
                break;
            case 'this month':
                $date->modify('first day of this month')->setTime(0, 0, 0);
                break;
        }

        return (int) $this->createQueryBuilder('sq')
                          ->select('COUNT(sq.id)')
                          ->andWhere('sq.createdAt >= :date')
                          ->setParameter('date', $date)
                          ->getQuery()
                          ->getSingleScalarResult();
    }

    /**
     * Get recent searches
     */
    public function getRecentSearches(int $limit = 20): array
    {
        return $this->createQueryBuilder('sq')
                    ->orderBy('sq.createdAt', 'DESC')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }
}
