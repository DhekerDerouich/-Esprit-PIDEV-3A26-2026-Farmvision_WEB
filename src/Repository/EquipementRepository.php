<?php
// src/Repository/EquipementRepository.php

namespace App\Repository;

use App\Entity\Equipement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EquipementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipement::class);
    }

    public function getStatistics(): array
    {
        $total = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $fonctionnels = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.etat = :etat')
            ->setParameter('etat', 'Fonctionnel')
            ->getQuery()
            ->getSingleScalarResult();

        $enPanne = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.etat = :etat')
            ->setParameter('etat', 'En panne')
            ->getQuery()
            ->getSingleScalarResult();

        $enMaintenance = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.etat = :etat')
            ->setParameter('etat', 'Maintenance')
            ->getQuery()
            ->getSingleScalarResult();

        $sousGarantie = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.dateAchat IS NOT NULL')
            ->andWhere('e.dureeVieEstimee IS NOT NULL')
            ->andWhere('DATE_ADD(e.dateAchat, e.dureeVieEstimee, \'year\') > :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'fonctionnels' => (int) $fonctionnels,
            'enPanne' => (int) $enPanne,
            'enMaintenance' => (int) $enMaintenance,
            'sousGarantie' => (int) $sousGarantie,
        ];
    }

    public function search(string $keyword, ?string $type = null, ?string $etat = null): array
    {
        $qb = $this->createQueryBuilder('e');
        
        if (!empty($keyword)) {
            $qb->andWhere('e.nom LIKE :keyword OR e.type LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }
        
        if (!empty($type) && $type !== 'all') {
            $qb->andWhere('e.type = :type')
               ->setParameter('type', $type);
        }
        
        if (!empty($etat) && $etat !== 'all') {
            $qb->andWhere('e.etat = :etat')
               ->setParameter('etat', $etat);
        }
        
        return $qb->orderBy('e.id', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function getUniqueTypes(): array
    {
        $result = $this->createQueryBuilder('e')
            ->select('DISTINCT e.type')
            ->getQuery()
            ->getResult();
        
        return array_column($result, 'type');
    }
}