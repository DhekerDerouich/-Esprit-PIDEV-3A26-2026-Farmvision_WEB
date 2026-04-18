<?php
// src/Entity/Marketplace.php

namespace App\Entity;

use App\Repository\MarketplaceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MarketplaceRepository::class)]
#[ORM\Table(name: 'marketplace')]
class Marketplace
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_marketplace', type: 'integer')]
    private ?int $idMarketplace = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_stock', referencedColumnName: 'id_stock', nullable: false)]
    private ?Stock $stock = null;

    #[ORM\Column(name: 'prix_unitaire', type: 'float')]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?float $prixUnitaire = null;

    #[ORM\Column(name: 'quantite_en_vente', type: 'float')]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?float $quantiteEnVente = null;

    #[ORM\Column(name: 'statut', type: 'string', length: 30, nullable: true)]
    private ?string $statut = 'En vente';

    #[ORM\Column(name: 'date_publication', type: 'date', nullable: true)]
    private ?\DateTimeInterface $datePublication = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $description = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->datePublication = new \DateTime();
        $this->statut = 'En vente';
    }

    // GETTERS
    public function getIdMarketplace(): ?int { return $this->idMarketplace; }
    public function getId(): ?int { return $this->idMarketplace; } // Compatibilité
    public function getStock(): ?Stock { return $this->stock; }
    public function getPrixUnitaire(): ?float { return $this->prixUnitaire; }
    public function getQuantiteEnVente(): ?float { return $this->quantiteEnVente; }
    public function getStatut(): ?string { return $this->statut; }
    public function getDatePublication(): ?\DateTimeInterface { return $this->datePublication; }
    public function getDescription(): ?string { return $this->description; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    // SETTERS
    public function setStock(?Stock $stock): self { $this->stock = $stock; return $this; }
    public function setPrixUnitaire(?float $prixUnitaire): self { $this->prixUnitaire = $prixUnitaire; return $this; }
    public function setQuantiteEnVente(?float $quantiteEnVente): self { $this->quantiteEnVente = $quantiteEnVente; return $this; }
    public function setStatut(?string $statut): self { $this->statut = $statut; return $this; }
    public function setDatePublication(?\DateTimeInterface $datePublication): self { $this->datePublication = $datePublication; return $this; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getValeurTotale(): float
    {
        return $this->prixUnitaire * $this->quantiteEnVente;
    }
}