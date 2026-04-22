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

    /**
     * Récupère les dépenses d'un utilisateur spécifique
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('d.dateDepense', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère la moyenne des 3 derniers mois pour un type et un utilisateur
     */
    public function getAverageForTypeLast3Months(string $type, int $userId): float
{
    $result = $this->createQueryBuilder('d')
        ->select('AVG(d.montant)')
        ->where('d.typeDepense = :type')
        ->setParameter('type', $type)
        ->andWhere('d.userId = :userId')
        ->setParameter('userId', $userId)
        ->andWhere('d.dateDepense >= :date')
        ->setParameter('date', new \DateTime('-3 months'))
        ->getQuery()
        ->getSingleScalarResult();
    
    return $result ? (float) $result : 0;
}

    /**
     * Récupère les statistiques pour un utilisateur
     */
    public function getStatistics(): array
    {
        $total = (float) ($this->createQueryBuilder('d')
            ->select('SUM(d.montant)')
            ->getQuery()->getSingleScalarResult() ?? 0);

        $count = (int) ($this->createQueryBuilder('d')
            ->select('COUNT(d.idDepense)')
            ->getQuery()->getSingleScalarResult() ?? 0);

        $now = new \DateTime();
        $firstDayOfMonth = new \DateTime($now->format('Y-m-01'));

        $ceMois = (float) ($this->createQueryBuilder('d')
            ->select('SUM(d.montant)')
            ->andWhere('d.dateDepense >= :start')
            ->setParameter('start', $firstDayOfMonth)
            ->getQuery()->getSingleScalarResult() ?? 0);

        return [
            'total'  => $total,
            'count'  => $count,
            'ceMois' => $ceMois,
        ];
    }

    /**
     * Récupère les statistiques pour un utilisateur
     */
    public function getStatisticsForUser(int $userId): array
    {
        $total = $this->createQueryBuilder('d')
            ->select('SUM(d.montant)')
            ->where('d.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        $parType = $this->createQueryBuilder('d')
            ->select('d.typeDepense as type, SUM(d.montant) as total')
            ->where('d.userId = :userId')
            ->setParameter('userId', $userId)
            ->groupBy('d.typeDepense')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        $now = new \DateTime();
        $firstDayOfMonth = new \DateTime($now->format('Y-m-01'));
        
        $ceMois = $this->createQueryBuilder('d')
            ->select('SUM(d.montant)')
            ->where('d.userId = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('d.dateDepense >= :start')
            ->setParameter('start', $firstDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total ? (float) $total : 0,
            'parType' => $parType,
            'ceMois' => $ceMois ? (float) $ceMois : 0,
        ];
    }

    /**
     * Recherche avec filtres pour un utilisateur
     */
    public function searchForUser(int $userId, ?string $keyword = null, ?string $type = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.userId = :userId')
            ->setParameter('userId', $userId);
        
        if (!empty($keyword)) {
            $qb->andWhere('d.typeDepense LIKE :keyword OR d.description LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }
        
        if (!empty($type) && $type !== 'all') {
            $qb->andWhere('d.typeDepense = :type')
               ->setParameter('type', $type);
        }
        
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

 public function getUniqueTypesForUser(int $userId): array
{
    $depenses = $this->findBy(['userId' => $userId]);
    
    $types = [];
    foreach ($depenses as $depense) {
        if (!in_array($depense->getTypeDepense(), $types)) {
            $types[] = $depense->getTypeDepense();
        }
    }
    
    sort($types);
    return $types;
}
public function getTotalByMonth(int $year, int $month, int $userId): float
{
    $start = new \DateTime("{$year}-{$month}-01");
    $end = new \DateTime("{$year}-{$month}-" . cal_days_in_month(CAL_GREGORIAN, $month, $year));
    $end->setTime(23, 59, 59);
    
    $result = $this->createQueryBuilder('d')
        ->select('SUM(d.montant)')
        ->where('d.userId = :userId')
        ->setParameter('userId', $userId)
        ->andWhere('d.dateDepense BETWEEN :start AND :end')
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
    
    return $this->createQueryBuilder('d')
        ->where('d.userId = :userId')
        ->setParameter('userId', $userId)
        ->andWhere('d.dateDepense BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->orderBy('d.dateDepense', 'DESC')
        ->getQuery()
        ->getResult();
}
public function findByUserAndMonth(int $userId, int $year, int $month): array
{
    $start = new \DateTime("{$year}-{$month}-01");
    $end = new \DateTime("{$year}-{$month}-" . cal_days_in_month(CAL_GREGORIAN, $month, $year));
    $end->setTime(23, 59, 59);
    
    return $this->createQueryBuilder('d')
        ->where('d.userId = :userId')
        ->setParameter('userId', $userId)
        ->andWhere('d.dateDepense BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->orderBy('d.dateDepense', 'DESC')
        ->getQuery()
        ->getResult();
}

public function findByUserAndYear(int $userId, int $year): array
{
    $start = new \DateTime("{$year}-01-01");
    $end = new \DateTime("{$year}-12-31 23:59:59");
    
    return $this->createQueryBuilder('d')
        ->where('d.userId = :userId')
        ->setParameter('userId', $userId)
        ->andWhere('d.dateDepense BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->orderBy('d.dateDepense', 'DESC')
        ->getQuery()
        ->getResult();
}
}