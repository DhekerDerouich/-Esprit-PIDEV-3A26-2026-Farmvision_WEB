<?php
// src/Entity/Equipement.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\EquipementRepository::class)]
#[ORM\Table(name: 'equipement')]
class Equipement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 100)]
    private ?string $nom = null;

    #[ORM\Column(name: 'type', type: 'string', length: 50)]
    private ?string $type = null;

    #[ORM\Column(name: 'etat', type: 'string', columnDefinition: "enum('Fonctionnel','En panne','Maintenance') DEFAULT 'Fonctionnel'")]
    private ?string $etat = 'Fonctionnel';

    #[ORM\Column(name: 'date_achat', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateAchat = null;

    #[ORM\Column(name: 'duree_vie_estimee', type: 'integer', nullable: true)]
    private ?int $dureeVieEstimee = null;

    #[ORM\Column(name: 'parcelle_id', type: 'integer', nullable: true)]
    private ?int $parcelleId = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'equipement', targetEntity: Maintenance::class, cascade: ['remove'])]
    private Collection $maintenances;

    public function __construct()
    {
        $this->maintenances = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->etat = 'Fonctionnel';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): self
    {
        $this->etat = $etat;
        return $this;
    }

    public function getDateAchat(): ?\DateTimeInterface
    {
        return $this->dateAchat;
    }

    public function setDateAchat(?\DateTimeInterface $dateAchat): self
    {
        $this->dateAchat = $dateAchat;
        return $this;
    }

    public function getDureeVieEstimee(): ?int
    {
        return $this->dureeVieEstimee;
    }

    public function setDureeVieEstimee(?int $dureeVieEstimee): self
    {
        $this->dureeVieEstimee = $dureeVieEstimee;
        return $this;
    }

    public function getParcelleId(): ?int
    {
        return $this->parcelleId;
    }

    public function setParcelleId(?int $parcelleId): self
    {
        $this->parcelleId = $parcelleId;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getMaintenances(): Collection
    {
        return $this->maintenances;
    }

    public function getAge(): int
    {
        if (!$this->dateAchat) {
            return 0;
        }
        $now = new \DateTime();
        $diff = $now->diff($this->dateAchat);
        return $diff->y;
    }

    public function getFinGarantie(): ?\DateTimeInterface
    {
        if (!$this->dateAchat || !$this->dureeVieEstimee) {
            return null;
        }
        $finGarantie = clone $this->dateAchat;
        $finGarantie->modify('+' . $this->dureeVieEstimee . ' years');
        return $finGarantie;
    }

    public function isSousGarantie(): bool
    {
        $finGarantie = $this->getFinGarantie();
        if (!$finGarantie) {
            return false;
        }
        return $finGarantie > new \DateTime();
    }
}