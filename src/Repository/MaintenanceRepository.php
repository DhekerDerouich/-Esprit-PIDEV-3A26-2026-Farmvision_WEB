<?php
// src/Repository/MaintenanceRepository.php

namespace App\Repository;

use App\Entity\Maintenance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MaintenanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Maintenance::class);
    }

    public function getStatistics(): array
    {
        $total = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $planifiees = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.statut = :statut')
            ->setParameter('statut', 'Planifiée')
            ->getQuery()
            ->getSingleScalarResult();

        $realisees = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.statut = :statut')
            ->setParameter('statut', 'Réalisée')
            ->getQuery()
            ->getSingleScalarResult();

        $preventives = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.typeMaintenance = :type')
            ->setParameter('type', 'Préventive')
            ->getQuery()
            ->getSingleScalarResult();

        $correctives = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.typeMaintenance = :type')
            ->setParameter('type', 'Corrective')
            ->getQuery()
            ->getSingleScalarResult();

        $coutTotal = $this->createQueryBuilder('m')
            ->select('COALESCE(SUM(m.cout), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'planifiees' => (int) $planifiees,
            'realisees' => (int) $realisees,
            'preventives' => (int) $preventives,
            'correctives' => (int) $correctives,
            'coutTotal' => (float) $coutTotal,
        ];
    }

    public function findUpcoming(int $limit = 5): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.statut = :statut')
            ->andWhere('m.dateMaintenance >= :today')
            ->setParameter('statut', 'Planifiée')
            ->setParameter('today', new \DateTime())
            ->orderBy('m.dateMaintenance', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function search(string $keyword, ?string $type = null, ?string $statut = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.equipement', 'e');
        
        if (!empty($keyword)) {
            $qb->andWhere('m.description LIKE :keyword OR e.nom LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }
        
        if (!empty($type) && $type !== 'all') {
            $qb->andWhere('m.typeMaintenance = :type')
               ->setParameter('type', $type);
        }
        
        if (!empty($statut) && $statut !== 'all') {
            $qb->andWhere('m.statut = :statut')
               ->setParameter('statut', $statut);
        }
        
        return $qb->orderBy('m.dateMaintenance', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}