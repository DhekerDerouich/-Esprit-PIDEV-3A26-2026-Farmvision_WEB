<?php
// src/Repository/UtilisateurRepository.php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.type_role = :role')
            ->setParameter('role', $role)
            ->getQuery()
            ->getResult();
    }

    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.activated = 1')
            ->getQuery()
            ->getResult();
    }
}