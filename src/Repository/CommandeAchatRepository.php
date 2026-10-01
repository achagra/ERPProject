<?php

namespace App\Repository;

use App\Entity\CommandeAchat;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommandeAchat>
 */
class CommandeAchatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeAchat::class);
    }

    /**
     * @return CommandeAchat[]
     */
    public function findByCreatedBy(Users $createdBy): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.createdBy = :createdBy')
            ->setParameter('createdBy', $createdBy)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}