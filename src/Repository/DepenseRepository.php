<?php

namespace App\Repository;

use App\Entity\Depense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DepenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Depense::class);
    }

    public function getStatistics(): array
    {
        $total = $this->createQueryBuilder('d')
            ->select('SUM(d.montant)')
            ->getQuery()
            ->getSingleScalarResult();

        $parType = $this->createQueryBuilder('d')
            ->select('d.typeDepense as type, SUM(d.montant) as total')
            ->groupBy('d.typeDepense')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        $now = new \DateTime();
        $firstDayOfMonth = new \DateTime($now->format('Y-m-01'));
        
        $ceMois = $this->createQueryBuilder('d')
            ->select('SUM(d.montant)')
            ->where('d.dateDepense >= :start')
            ->setParameter('start', $firstDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total ? (float) $total : 0,
            'parType' => $parType,
            'ceMois' => $ceMois ? (float) $ceMois : 0,
        ];
    }

    //  Search with date range
    public function search(?string $keyword = null, ?string $type = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $qb = $this->createQueryBuilder('d');
        
        if (!empty($keyword)) {
            $qb->andWhere('d.typeDepense LIKE :keyword OR d.description LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }
        
        if (!empty($type) && $type !== 'all') {
            $qb->andWhere('d.typeDepense = :type')
               ->setParameter('type', $type);
        }
        
        // DATE RANGE FILTER
        if (!empty($startDate)) {
            $qb->andWhere('d.dateDepense >= :startDate')
               ->setParameter('startDate', new \DateTime($startDate));
        }
        
        if (!empty($endDate)) {
            $qb->andWhere('d.dateDepense <= :endDate')
               ->setParameter('endDate', new \DateTime($endDate . ' 23:59:59'));
        }
        
        return $qb->orderBy('d.dateDepense', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function getUniqueTypes(): array
    {
        $result = $this->createQueryBuilder('d')
            ->select('DISTINCT d.typeDepense as type')
            ->orderBy('type', 'ASC')
            ->getQuery()
            ->getResult();
        
        return array_column($result, 'type');
    }
}