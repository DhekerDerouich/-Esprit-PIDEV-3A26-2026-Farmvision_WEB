<?php
// src/Entity/Maintenance.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: \App\Repository\MaintenanceRepository::class)]
#[ORM\Table(name: 'maintenance')]
class Maintenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'maintenances')]
    #[ORM\JoinColumn(name: 'equipement_id', nullable: false)]
    #[Assert\NotNull(message: 'Veuillez sélectionner un équipement')]
    private ?Equipement $equipement = null;

    #[ORM\Column(name: 'type_maintenance', type: 'string', columnDefinition: "enum('Préventive','Corrective') NOT NULL")]
    #[Assert\NotBlank(message: 'Le type de maintenance est obligatoire')]
    #[Assert\Choice(
        choices: ['Préventive', 'Corrective'],
        message: 'Veuillez choisir un type valide'
    )]
    private ?string $typeMaintenance = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La description ne doit pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(name: 'date_maintenance', type: 'date')]
    #[Assert\NotNull(message: 'La date est obligatoire')]
    #[Assert\Type(type: '\DateTimeInterface', message: 'Date invalide')]
    private ?\DateTimeInterface $dateMaintenance = null;

    #[ORM\Column(name: 'cout', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le coût doit être positif ou nul')]
    #[Assert\LessThanOrEqual(
        value: 100000,
        message: 'Le coût ne peut pas dépasser {{ compared_value }} DT'
    )]
    private ?float $cout = null;

    #[ORM\Column(name: 'statut', type: 'string', columnDefinition: "enum('Planifiée','Réalisée') DEFAULT 'Planifiée'")]
    #[Assert\NotBlank(message: 'Le statut est obligatoire')]
    #[Assert\Choice(
        choices: ['Planifiée', 'Réalisée'],
        message: 'Veuillez choisir un statut valide'
    )]
    private ?string $statut = 'Planifiée';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->statut = 'Planifiée';
    }

    // GETTERS ET SETTERS
    public function getId(): ?int { return $this->id; }
    public function getEquipement(): ?Equipement { return $this->equipement; }
    public function setEquipement(?Equipement $equipement): self { $this->equipement = $equipement; return $this; }
    public function getTypeMaintenance(): ?string { return $this->typeMaintenance; }
    public function setTypeMaintenance(?string $typeMaintenance): self { $this->typeMaintenance = $typeMaintenance; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getDateMaintenance(): ?\DateTimeInterface { return $this->dateMaintenance; }
    public function setDateMaintenance(?\DateTimeInterface $dateMaintenance): self { $this->dateMaintenance = $dateMaintenance; return $this; }
    public function getCout(): ?float { return $this->cout; }
    public function setCout(?float $cout): self { $this->cout = $cout; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $statut): self { $this->statut = $statut; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getJoursRestants(): int { if (!$this->dateMaintenance) return 0; $now = new \DateTime(); if ($this->dateMaintenance < $now) return 0; return $now->diff($this->dateMaintenance)->days; }
    public function getCoutFloat(): float { return (float) $this->cout; }

    #[Assert\Callback]
    public function validateDateMaintenance(ExecutionContextInterface $context): void
    {
        if ($this->statut === 'Planifiée' && $this->dateMaintenance && $this->dateMaintenance < new \DateTime()) {
            $context->buildViolation('Une maintenance planifiée ne peut pas avoir une date passée')
                ->atPath('dateMaintenance')
                ->addViolation();
        }
    }
}