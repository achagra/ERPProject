<?php

namespace App\Repository;

use App\Entity\AuditLog;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function searchForOwner(Users $owner, ?string $action = null, ?int $actorId = null, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array
    {
        $query = $this->createQueryBuilder('a')
            ->andWhere('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('a.createdAt', 'DESC');

        if ($action) {
            $query->andWhere('a.action = :action')->setParameter('action', $action);
        }
        if ($actorId) {
            $query->andWhere('IDENTITY(a.actor) = :actorId')->setParameter('actorId', $actorId);
        }
        if ($from) {
            $query->andWhere('a.createdAt >= :from')->setParameter('from', $from->setTime(0, 0));
        }
        if ($to) {
            $query->andWhere('a.createdAt < :to')->setParameter('to', $to->modify('+1 day')->setTime(0, 0));
        }

        return $query->getQuery()->getResult();
    }
}
