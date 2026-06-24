<?php

namespace App\Entity;

use App\Repository\EmployeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployeRepository::class)]
class Employe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 100)]
    private ?string $departement = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $createdBy = null;

    // ================= ID =================
    public function getId(): ?int { return $this->id; }

    // ================= NOM =================
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    // ================= PRENOM =================
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    // ================= EMAIL =================
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    // ================= TELEPHONE =================
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    // ================= DEPARTEMENT =================
    public function getDepartement(): ?string { return $this->departement; }
    public function setDepartement(string $departement): static { $this->departement = $departement; return $this; }

    // ================= CREATED BY =================
    public function getCreatedBy(): ?Users { return $this->createdBy; }
    public function setCreatedBy(?Users $createdBy): static { $this->createdBy = $createdBy; return $this; }
}