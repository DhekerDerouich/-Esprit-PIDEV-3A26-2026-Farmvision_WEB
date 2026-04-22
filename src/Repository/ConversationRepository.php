<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function findDirectConversation(Utilisateur $user1, Utilisateur $user2): ?Conversation
    {
        $conversations = $this->createQueryBuilder('c')
            ->andWhere('c.type = :type')
            ->setParameter('type', 'direct')
            ->getQuery()
            ->getResult();

        foreach ($conversations as $conv) {
            $participants = $conv->getParticipants();
            if ($participants->contains($user1) && $participants->contains($user2)) {
                return $conv;
            }
        }
        return null;
    }

    public function findByUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.participants', 'p')
            ->addSelect('p')
            ->andWhere('p.id = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('c.lastMessageAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}