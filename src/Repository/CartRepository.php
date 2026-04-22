<?php
// src/Repository/CartRepository.php

namespace App\Repository;

use App\Entity\Cart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    public function findActiveBySessionId(string $sessionId): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->where('c.sessionId = :sessionId')
            ->andWhere('c.status = :status')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySessionId(string $sessionId): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->where('c.sessionId = :sessionId')
            ->orderBy('c.createdAt', 'DESC')
            ->setParameter('sessionId', $sessionId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function cleanupOldCarts(int $days = 7): int
    {
        $date = new \DateTime('-' . $days . ' days');
        
        return $this->createQueryBuilder('c')
            ->delete()
            ->where('c.status = :status')
            ->andWhere('c.updatedAt <= :date OR c.updatedAt IS NULL')
            ->setParameter('status', 'active')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}