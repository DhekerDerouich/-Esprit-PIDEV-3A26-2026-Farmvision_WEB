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

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.dateRevenu', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStatisticsForUser(int $userId): array
    {
        $total = $this->createQueryBuilder('r')
            ->select('SUM(r.montant)')
            ->where('r.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        $parSource = $this->createQueryBuilder('r')
            ->select('r.source as source, SUM(r.montant) as total')
            ->where('r.userId = :userId')
            ->setParameter('userId', $userId)
            ->groupBy('r.source')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        $now = new \DateTime();
        $firstDayOfMonth = new \DateTime($now->format('Y-m-01'));
        
        $ceMois = $this->createQueryBuilder('r')
            ->select('SUM(r.montant)')
            ->where('r.userId = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('r.dateRevenu >= :start')
            ->setParameter('start', $firstDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total ? (float) $total : 0,
            'parSource' => $parSource,
            'ceMois' => $ceMois ? (float) $ceMois : 0,
        ];
    }

    public function searchForUser(int $userId, ?string $keyword = null, ?string $source = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.userId = :userId')
            ->setParameter('userId', $userId);
        
        if (!empty($keyword)) {
            $qb->andWhere('r.source LIKE :keyword OR r.description LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }
        
        if (!empty($source) && $source !== 'all') {
            $qb->andWhere('r.source = :source')
               ->setParameter('source', $source);
        }
        
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

public function getUniqueSourcesForUser(int $userId): array
{
    $revenus = $this->findBy(['userId' => $userId]);
    
    $sources = [];
    foreach ($revenus as $revenu) {
        if (!in_array($revenu->getSource(), $sources)) {
            $sources[] = $revenu->getSource();
        }
    }
    
    sort($sources);
    return $sources;
}
public function getTotalByMonth(int $year, int $month, int $userId): float
{
    $start = new \DateTime("{$year}-{$month}-01");
    $end = new \DateTime("{$year}-{$month}-" . cal_days_in_month(CAL_GREGORIAN, $month, $year));
    $end->setTime(23, 59, 59);
    
    $result = $this->createQueryBuilder('r')
        ->select('SUM(r.montant)')
        ->where('r.userId = :userId')
        ->setParameter('userId', $userId)
        ->andWhere('r.dateRevenu BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getSingleScalarResult();
    
    return $result ? (float)$result : 0;
}
public function findByMonth(int $userId, int $year, int $month): array
{
    $start = new \DateTime("{$year}-{$month}-01");
    $end = new \DateTime("{$year}-{$month}-" . cal_days_in_month(CAL_GREGORIAN, $month, $year));
    $end->setTime(23, 59, 59);
    
    return $this->createQueryBuilder('r')
        ->where('r.userId = :userId')
        ->setParameter('userId', $userId)
        ->andWhere('r.dateRevenu BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->orderBy('r.dateRevenu', 'DESC')
        ->getQuery()
        ->getResult();
}
public function findByUserAndMonth(int $userId, int $year, int $month): array
{
    $start = new \DateTime("{$year}-{$month}-01");
    $end = new \DateTime("{$year}-{$month}-" . cal_days_in_month(CAL_GREGORIAN, $month, $year));
    $end->setTime(23, 59, 59);
    
    return $this->createQueryBuilder('r')
        ->where('r.userId = :userId')
        ->setParameter('userId', $userId)
        ->andWhere('r.dateRevenu BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->orderBy('r.dateRevenu', 'DESC')
        ->getQuery()
        ->getResult();
}

public function findByUserAndYear(int $userId, int $year): array
{
    $start = new \DateTime("{$year}-01-01");
    $end = new \DateTime("{$year}-12-31 23:59:59");
    
    return $this->createQueryBuilder('r')
        ->where('r.userId = :userId')
        ->setParameter('userId', $userId)
        ->andWhere('r.dateRevenu BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->orderBy('r.dateRevenu', 'DESC')
        ->getQuery()
        ->getResult();
}
}