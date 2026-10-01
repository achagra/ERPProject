<?php

namespace App\Repository;

use App\Entity\Entrepot;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Entrepot>
 */
class EntrepotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entrepot::class);
    }

    /**
     * @return Entrepot[]
     */
    public function findByCreatedBy(Users $createdBy): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.createdBy = :createdBy')
            ->setParameter('createdBy', $createdBy)
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}