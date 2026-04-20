<?php
// src/Repository/StockRepository.php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    public function search(?string $keyword = null, ?string $type = null, ?string $statut = null): array
    {
        $qb = $this->createQueryBuilder('s');
        
        if (!empty($keyword)) {
            $qb->andWhere('s.nomProduit LIKE :keyword OR s.typeProduit LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }
        
        if (!empty($type) && $type !== 'all') {
            $qb->andWhere('s.typeProduit = :type')
               ->setParameter('type', $type);
        }
        
        if (!empty($statut) && $statut !== 'all') {
            $qb->andWhere('s.statut = :statut')
               ->setParameter('statut', $statut);
        }
        
        return $qb->orderBy('s.id', 'DESC')->getQuery()->getResult();
    }

    public function getStatistics(): array
    {
        $total = $this->createQueryBuilder('s')->select('COUNT(s.id)')->getQuery()->getSingleScalarResult() ?? 0;
        $valeurTotale = $this->createQueryBuilder('s')->select('SUM(s.quantite)')->getQuery()->getSingleScalarResult() ?? 0;
        
        return [
            'total' => (int) $total,
            'valeurTotale' => (float) $valeurTotale,
            'disponibles' => (int) ($this->createQueryBuilder('s')->select('COUNT(s.id)')->where('s.statut = :statut')->setParameter('statut', 'Disponible')->getQuery()->getSingleScalarResult() ?? 0),
            'epuises' => (int) ($this->createQueryBuilder('s')->select('COUNT(s.id)')->where('s.statut = :statut')->setParameter('statut', 'Épuisé')->getQuery()->getSingleScalarResult() ?? 0),
        ];
    }

    public function getUniqueTypes(): array
    {
        $result = $this->createQueryBuilder('s')->select('DISTINCT s.typeProduit')->where('s.typeProduit IS NOT NULL')->getQuery()->getResult();
        return array_column($result, 'typeProduit');
    }

    public function findAvailableForMarketplace(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.statut = :statut')
            ->andWhere('s.quantite > 0')
            ->setParameter('statut', 'Disponible')
            ->orderBy('s.nomProduit', 'ASC')
            ->getQuery()
            ->getResult();
    }
}