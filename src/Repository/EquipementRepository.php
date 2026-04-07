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

        return [
            'total' => (int) $total,
            'fonctionnels' => (int) $fonctionnels,
            'enPanne' => (int) $enPanne,
            'enMaintenance' => (int) $enMaintenance,
            'sousGarantie' => 0,
        ];
    }

    // Méthode de recherche pour les équipements
    public function search(string $keyword, ?string $type = null, ?string $etat = null): array
    {
        $qb = $this->createQueryBuilder('e');
        
        // Recherche par mot-clé (nom ou type)
        if (!empty($keyword)) {
            $qb->andWhere('e.nom LIKE :keyword OR e.type LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }
        
        // Filtre par type
        if (!empty($type) && $type !== 'all') {
            $qb->andWhere('e.type = :type')
               ->setParameter('type', $type);
        }
        
        // Filtre par état
        if (!empty($etat) && $etat !== 'all') {
            $qb->andWhere('e.etat = :etat')
               ->setParameter('etat', $etat);
        }
        
        return $qb->orderBy('e.id', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    // Obtenir les types uniques pour le filtre
    public function getUniqueTypes(): array
    {
        $result = $this->createQueryBuilder('e')
            ->select('DISTINCT e.type')
            ->getQuery()
            ->getResult();
        
        return array_column($result, 'type');
    }
}