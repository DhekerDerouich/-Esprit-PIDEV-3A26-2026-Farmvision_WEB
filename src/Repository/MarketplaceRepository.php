<?php
// src/Repository/MarketplaceRepository.php

namespace App\Repository;

use App\Entity\Marketplace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MarketplaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Marketplace::class);
    }

    public function search(?string $keyword = null, ?string $statut = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.stock', 's');
        
        if (!empty($keyword)) {
            $qb->andWhere('s.nomProduit LIKE :keyword OR m.description LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }
        
        if (!empty($statut) && $statut !== 'all') {
            $qb->andWhere('m.statut = :statut')
               ->setParameter('statut', $statut);
        }
        
        return $qb->orderBy('m.id', 'DESC')->getQuery()->getResult();
    }

    public function getStatistics(): array
    {
        $total = $this->createQueryBuilder('m')->select('COUNT(m.id)')->getQuery()->getSingleScalarResult() ?? 0;
        $chiffreAffaires = $this->createQueryBuilder('m')->select('SUM(m.prixUnitaire * m.quantiteEnVente)')->where('m.statut = :statut')->setParameter('statut', 'Vendu')->getQuery()->getSingleScalarResult() ?? 0;
        
        return [
            'total' => (int) $total,
            'enVente' => (int) ($this->createQueryBuilder('m')->select('COUNT(m.id)')->where('m.statut = :statut')->setParameter('statut', 'En vente')->getQuery()->getSingleScalarResult() ?? 0),
            'vendus' => (int) ($this->createQueryBuilder('m')->select('COUNT(m.id)')->where('m.statut = :statut')->setParameter('statut', 'Vendu')->getQuery()->getSingleScalarResult() ?? 0),
            'chiffreAffaires' => (float) $chiffreAffaires,
        ];
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.stock', 's')
            ->where('m.statut = :statut')
            ->andWhere('m.quantiteEnVente > 0')
            ->setParameter('statut', 'En vente')
            ->orderBy('m.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}