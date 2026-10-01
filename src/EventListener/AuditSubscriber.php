<?php

namespace App\EventListener;

use App\Entity\AuditLog;
use App\Entity\Client;
use App\Entity\CommandeAchat;
use App\Entity\CommandeVente;
use App\Entity\Employe;
use App\Entity\Product;
use App\Entity\Users;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuditSubscriber implements EventSubscriber
{
    private const AUDITED_ENTITIES = [Client::class, CommandeAchat::class, CommandeVente::class, Employe::class, Product::class];

    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [Events::onFlush];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $metadata = $entityManager->getClassMetadata(AuditLog::class);
        $actor = $this->tokenStorage->getToken()?->getUser();
        $actor = $actor instanceof Users ? $actor : null;

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($this->isAudited($entity)) {
                $this->persistAudit($entityManager, $metadata, $entity, 'CREATE', null, $this->extractValues($entity, $entityManager->getClassMetadata($entity::class)));
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($this->isAudited($entity)) {
                $changes = $unitOfWork->getEntityChangeSet($entity);
                $this->persistAudit($entityManager, $metadata, $entity, 'UPDATE', $this->formatChanges($changes, 0), $this->formatChanges($changes, 1));
            }
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($this->isAudited($entity)) {
                $this->persistAudit($entityManager, $metadata, $entity, 'DELETE', $this->extractValues($entity, $entityManager->getClassMetadata($entity::class)), null);
            }
        }
    }

    private function persistAudit($entityManager, $metadata, object $entity, string $action, ?array $oldValues, ?array $newValues): void
    {
        $audit = (new AuditLog())
            ->setAction($action)
            ->setEntityType((new \ReflectionClass($entity))->getShortName())
            ->setEntityId(method_exists($entity, 'getId') ? $entity->getId() : null)
            ->setActor($this->currentUser())
            ->setOwner(method_exists($entity, 'getCreatedBy') && $entity->getCreatedBy() instanceof Users ? $entity->getCreatedBy() : $this->currentUser())
            ->setOldValues($oldValues)
            ->setNewValues($newValues);

        $entityManager->persist($audit);
        $entityManager->getUnitOfWork()->computeChangeSet($metadata, $audit);
    }

    private function currentUser(): ?Users
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        return $user instanceof Users ? $user : null;
    }

    private function isAudited(object $entity): bool
    {
        return in_array($entity::class, self::AUDITED_ENTITIES, true);
    }

    private function extractValues(object $entity, object $classMetadata): array
    {
        $values = [];
        foreach ($classMetadata->getFieldNames() as $field) {
            $values[$field] = $this->normalizeValue($classMetadata->getFieldValue($entity, $field));
        }
        foreach ($classMetadata->getAssociationNames() as $association) {
            $value = $classMetadata->getFieldValue($entity, $association);
            if ($value !== null && !is_iterable($value)) {
                $values[$association] = $this->normalizeValue($value);
            }
        }
        return $values;
    }

    private function formatChanges(array $changes, int $index): array
    {
        $values = [];
        foreach ($changes as $field => $change) {
            $values[$field] = $this->normalizeValue($change[$index] ?? null);
        }
        return $values;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Users) {
            return $value->getId();
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_object($value) && method_exists($value, 'getId')) {
            return $value->getId();
        }
        return is_scalar($value) || $value === null ? $value : (string) $value;
    }
}
