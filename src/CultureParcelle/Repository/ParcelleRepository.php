<?php
namespace App\CultureParcelle\Repository;

use App\Entity\Parcelle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParcelleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parcelle::class);
    }

   public function search(
    ?string $localisation,
    ?float $surfaceMin,
    ?float $surfaceMax,
    ?int $userId = null // 🔥 add user filter
): array {
    $qb = $this->createQueryBuilder('p');

    // Filter by user if provided
    if ($userId) {
        $qb->andWhere('p.user_id = :userId')
           ->setParameter('userId', $userId);
    }

    if ($localisation) {
        $qb->andWhere('p.localisation LIKE :loc')
           ->setParameter('loc', '%' . $localisation . '%');
    }

    if ($surfaceMin !== null) {
        $qb->andWhere('p.surface >= :min')
           ->setParameter('min', $surfaceMin);
    }

    if ($surfaceMax !== null) {
        $qb->andWhere('p.surface <= :max')
           ->setParameter('max', $surfaceMax);
    }

    return $qb->orderBy('p.surface', 'ASC')
              ->getQuery()
              ->getResult();
}
}
