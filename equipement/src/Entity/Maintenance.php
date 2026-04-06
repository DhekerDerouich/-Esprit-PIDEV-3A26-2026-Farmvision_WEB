<?php
// src/Entity/Maintenance.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

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
    private ?Equipement $equipement = null;

    #[ORM\Column(name: 'type_maintenance', type: 'string', columnDefinition: "enum('Préventive','Corrective') NOT NULL")]
    private ?string $typeMaintenance = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'date_maintenance', type: 'date')]
    private ?\DateTimeInterface $dateMaintenance = null;

    #[ORM\Column(name: 'cout', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $cout = null;

    #[ORM\Column(name: 'statut', type: 'string', columnDefinition: "enum('Planifiée','Réalisée') DEFAULT 'Planifiée'")]
    private ?string $statut = 'Planifiée';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->statut = 'Planifiée';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEquipement(): ?Equipement
    {
        return $this->equipement;
    }

    public function setEquipement(?Equipement $equipement): self
    {
        $this->equipement = $equipement;
        return $this;
    }

    public function getTypeMaintenance(): ?string
    {
        return $this->typeMaintenance;
    }

    public function setTypeMaintenance(?string $typeMaintenance): self
    {
        $this->typeMaintenance = $typeMaintenance;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDateMaintenance(): ?\DateTimeInterface
    {
        return $this->dateMaintenance;
    }

    public function setDateMaintenance(?\DateTimeInterface $dateMaintenance): self
    {
        $this->dateMaintenance = $dateMaintenance;
        return $this;
    }

    public function getCout(): ?float
    {
        return $this->cout;
    }

    public function setCout(?float $cout): self
    {
        $this->cout = $cout;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getJoursRestants(): int
    {
        if (!$this->dateMaintenance) {
            return 0;
        }
        $now = new \DateTime();
        if ($this->dateMaintenance < $now) {
            return 0;
        }
        return $now->diff($this->dateMaintenance)->days;
    }

    public function getCoutFloat(): float
    {
        return (float) $this->cout;
    }
}