<?php
// src/Entity/Equipement.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\EquipementRepository::class)]
#[ORM\Table(name: 'equipement')]
class Equipement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\s\-éèêëàâäôöûüç]+$/',
        message: 'Le nom ne peut contenir que des lettres, chiffres, espaces et tirets'
    )]
    private ?string $nom = null;

    #[ORM\Column(name: 'type', type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Le type est obligatoire')]
    #[Assert\Choice(
        choices: ['Tracteur', 'Moissonneuse', 'Pulvérisateur', 'Charrue', 'Semoir', 'Autre'],
        message: 'Veuillez choisir un type valide'
    )]
    private ?string $type = null;

    #[ORM\Column(name: 'etat', type: 'string', columnDefinition: "enum('Fonctionnel','En panne','Maintenance') DEFAULT 'Fonctionnel'")]
    #[Assert\NotBlank(message: 'L\'état est obligatoire')]
    #[Assert\Choice(
        choices: ['Fonctionnel', 'En panne', 'Maintenance'],
        message: 'Veuillez choisir un état valide'
    )]
    private ?string $etat = 'Fonctionnel';

    #[ORM\Column(name: 'date_achat', type: 'date', nullable: true)]
    #[Assert\LessThanOrEqual(
        value: 'today',
        message: 'La date d\'achat ne peut pas être dans le futur'
    )]
    private ?\DateTimeInterface $dateAchat = null;

    #[ORM\Column(name: 'duree_vie_estimee', type: 'integer', nullable: true)]
    #[Assert\Positive(message: 'La durée de vie doit être positive')]
    #[Assert\LessThanOrEqual(
        value: 50,
        message: 'La durée de vie ne peut pas dépasser {{ compared_value }} ans'
    )]
    #[Assert\GreaterThanOrEqual(
        value: 1,
        message: 'La durée de vie doit être au moins {{ compared_value }} an'
    )]
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

    // GETTERS ET SETTERS
    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): self { $this->nom = $nom; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): self { $this->type = $type; return $this; }
    public function getEtat(): ?string { return $this->etat; }
    public function setEtat(?string $etat): self { $this->etat = $etat; return $this; }
    public function getDateAchat(): ?\DateTimeInterface { return $this->dateAchat; }
    public function setDateAchat(?\DateTimeInterface $dateAchat): self { $this->dateAchat = $dateAchat; $this->updatedAt = new \DateTime(); return $this; }
    public function getDureeVieEstimee(): ?int { return $this->dureeVieEstimee; }
    public function setDureeVieEstimee(?int $dureeVieEstimee): self { $this->dureeVieEstimee = $dureeVieEstimee; return $this; }
    public function getParcelleId(): ?int { return $this->parcelleId; }
    public function setParcelleId(?int $parcelleId): self { $this->parcelleId = $parcelleId; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
    public function getMaintenances(): Collection { return $this->maintenances; }
    public function getAge(): int { if (!$this->dateAchat) return 0; return (new \DateTime())->diff($this->dateAchat)->y; }
    public function getFinGarantie(): ?\DateTimeInterface { 
        if (!$this->dateAchat || !$this->dureeVieEstimee) return null; 
        $fin = clone $this->dateAchat; 
        if ($fin instanceof \DateTime) {
            $fin->modify('+' . $this->dureeVieEstimee . ' years');
        }
        return $fin; 
    }
    public function isSousGarantie(): bool { $fin = $this->getFinGarantie(); return $fin ? $fin > new \DateTime() : false; }
}