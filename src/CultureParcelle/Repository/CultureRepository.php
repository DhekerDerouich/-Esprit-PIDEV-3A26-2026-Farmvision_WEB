<?php
namespace App\CultureParcelle\Repository;

use App\Entity\Culture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CultureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Culture::class);
    }

    public function search(?string $nom, ?string $type): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($nom) {
            $qb->andWhere('c.nomCulture LIKE :nom')
               ->setParameter('nom', '%' . $nom . '%');
        }

        if ($type && $type !== 'all') {
            $qb->andWhere('c.typeCulture = :type')
               ->setParameter('type', $type);
        }

        return $qb->orderBy('c.dateSemis', 'DESC')->getQuery()->getResult();
    }

    public function findAllTypes(): array
    {
        return $this->createQueryBuilder('c')
            ->select('DISTINCT c.typeCulture')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
