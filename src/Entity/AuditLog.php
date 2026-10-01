<?php

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Index(columns: ['owner_id', 'created_at'])]
#[ORM\Index(columns: ['action', 'created_at'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $action;

    #[ORM\Column(length: 120)]
    private string $entityType;

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Users $actor = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Users $owner = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $oldValues = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $newValues = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getAction(): string { return $this->action; }
    public function setAction(string $action): static { $this->action = $action; return $this; }
    public function getEntityType(): string { return $this->entityType; }
    public function setEntityType(string $entityType): static { $this->entityType = $entityType; return $this; }
    public function getEntityId(): ?int { return $this->entityId; }
    public function setEntityId(?int $entityId): static { $this->entityId = $entityId; return $this; }
    public function getActor(): ?Users { return $this->actor; }
    public function setActor(?Users $actor): static { $this->actor = $actor; return $this; }
    public function getOwner(): ?Users { return $this->owner; }
    public function setOwner(?Users $owner): static { $this->owner = $owner; return $this; }
    public function getOldValues(): ?array { return $this->oldValues; }
    public function setOldValues(?array $oldValues): static { $this->oldValues = $oldValues; return $this; }
    public function getNewValues(): ?array { return $this->newValues; }
    public function setNewValues(?array $newValues): static { $this->newValues = $newValues; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
