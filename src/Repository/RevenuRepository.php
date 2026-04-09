<?php

namespace App\Repository;

use App\Entity\Revenu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RevenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Revenu::class);
    }

    public function getStatistics(): array
    {
        $total = $this->createQueryBuilder('r')
            ->select('SUM(r.montant)')
            ->getQuery()
            ->getSingleScalarResult();

        $parSource = $this->createQueryBuilder('r')
            ->select('r.source as source, SUM(r.montant) as total')
            ->groupBy('r.source')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        $now = new \DateTime();
        $firstDayOfMonth = new \DateTime($now->format('Y-m-01'));
        
        $ceMois = $this->createQueryBuilder('r')
            ->select('SUM(r.montant)')
            ->where('r.dateRevenu >= :start')
            ->setParameter('start', $firstDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total ? (float) $total : 0,
            'parSource' => $parSource,
            'ceMois' => $ceMois ? (float) $ceMois : 0,
        ];
    }

    // NEW: Search with date range
    public function search(?string $keyword = null, ?string $source = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $qb = $this->createQueryBuilder('r');
        
        if (!empty($keyword)) {
            $qb->andWhere('r.source LIKE :keyword OR r.description LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }
        
        if (!empty($source) && $source !== 'all') {
            $qb->andWhere('r.source = :source')
               ->setParameter('source', $source);
        }
        
        // DATE RANGE FILTER
        if (!empty($startDate)) {
            $qb->andWhere('r.dateRevenu >= :startDate')
               ->setParameter('startDate', new \DateTime($startDate));
        }
        
        if (!empty($endDate)) {
            $qb->andWhere('r.dateRevenu <= :endDate')
               ->setParameter('endDate', new \DateTime($endDate . ' 23:59:59'));
        }
        
        return $qb->orderBy('r.dateRevenu', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function getUniqueSources(): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('DISTINCT r.source as source')
            ->orderBy('source', 'ASC')
            ->getQuery()
            ->getResult();
        
        return array_column($result, 'source');
    }
}