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

    public function search(?string $localisation, ?float $surfaceMin, ?float $surfaceMax): array
    {
        $qb = $this->createQueryBuilder('p');

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

        return $qb->orderBy('p.surface', 'ASC')->getQuery()->getResult();
    }
}
