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
     * Belirtilen sorgu ve filtrelerle dokümanlarda arama yapar.
     * LIKE operatörü kullanılır.
     *
     * @param string $query
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search(string $query, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('d');

        if (!empty($query)) {
            $qb->andWhere('d.title LIKE :query OR d.content LIKE :query')
                ->setParameter('query', '%' . $query . '%'); // Kelimenin herhangi bir yerinde geçebilir
        }

        // Filtreleri uygula
        $this->applyFilters($qb, $filters);

        // Sıralama ayarları
        $sortBy = $filters['sort'] ?? 'finalScore'; // Sıralama alanı
        $sortOrder = $filters['order'] ?? 'DESC';  // Sıralama yönü

        // Geçerli sıralama alanlarını kontrol et
        $allowedSortFields = ['finalScore', 'createdAt', 'title', 'type'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'finalScore';
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';

        $qb->orderBy('d.' . $sortBy, $sortOrder);

        return $qb->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Arama sonuçlarının toplam sayısını döner.
     *
     * @param string $query
     * @param array $filters
     * @return int
     */
    public function countSearch(string $query, array $filters = []): int
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)');

        if (!empty($query)) {
            $qb->andWhere('d.title LIKE :query OR d.content LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * QueryBuilder üzerinde filtreleri uygular.
     *
     * @param QueryBuilder $qb
     * @param array $filters Uygulanacak filtreler (type, category, tags, date_from, date_to)
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['type']) && !empty($filters['type']) && in_array($filters['type'], ['video', 'text'])) {
            $qb->andWhere('d.type = :type')
                ->setParameter('type', $filters['type']);
        }
        if (isset($filters['category']) && !empty($filters['category'])) {
            $qb->andWhere('d.category = :category')
                ->setParameter('category', $filters['category']);
        }

        if (isset($filters['tags']) && !empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            foreach ($tags as $index => $tag) {
                // MySQL JSON tipleri için JSON_CONTAINS fonksiyonu kullanılır
                $qb->andWhere("JSON_CONTAINS(d.tags, :tag{$index}) = 1")
                    ->setParameter("tag{$index}", json_encode($tag));
            }
        }

        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $qb->andWhere('d.publishedAt >= :dateFrom')
                ->setParameter('dateFrom', new \DateTimeImmutable($filters['date_from']));
        }

        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $qb->andWhere('d.publishedAt <= :dateTo')
                ->setParameter('dateTo', new \DateTimeImmutable($filters['date_to']));
        }
    }

    /**
     * Belirtilen kategoriye ait dokümanları getirir.
     *
     * @param string $category
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findByCategory(string $category, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.category = :category')
            ->setParameter('category', $category)
            ->orderBy('d.finalScore', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Belirtilen etiketlere ait dokümanları getirir.
     *
     * @param array $tags
     * @param int $limit
     * @param int $offset Başlangıç kaydı (sayfalama için)
     * @return array
     */
    public function findByTags(array $tags, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('d');

        foreach ($tags as $index => $tag) {
            $qb->andWhere("JSON_CONTAINS(d.tags, :tag{$index}) = 1")
                ->setParameter("tag{$index}", json_encode($tag));
        }

        return $qb->orderBy('d.finalScore', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * En popüler kategorileri döner.
     *
     * @param int $limit Döndürülecek kategori sayısı
     * @return array
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
     *  db deki etiketleri döner.
     *
     * @return array Etiketler dizisi
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
            if (!empty($doc['tags']) && is_array($doc['tags'])) {
                // Sadece string tipindeki etiketleri al
                $filteredTags = array_filter($doc['tags'], fn($tag) => is_string($tag));
                $allTags = array_merge($allTags, $filteredTags);
            }
        }

        return array_unique($allTags);
    }
}