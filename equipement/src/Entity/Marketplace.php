<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: MarketplaceRepository::class)]
#[ORM\Table(name: 'marketplace')]
class Marketplace
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'id_marketplace', type: 'integer', length: 11)]
    private ?int $id_marketplace;

    #[ORM\Column(name: 'id_stock', type: 'integer', length: 11)]
    private ?int $id_stock;

    #[ORM\Column(name: 'prix_unitaire', type: 'string')]
    private ?string $prix_unitaire;

    #[ORM\Column(name: 'quantite_en_vente', type: 'string')]
    private ?string $quantite_en_vente;

    #[ORM\Column(name: 'statut', type: 'string', nullable: true, length: 30)]
    private ?string $statut;

    #[ORM\Column(name: 'date_publication', type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_publication;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_stock', nullable: false)]
    private ?Stock $Stock = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdMarketplace(): ?int
    {
        return $this->id_marketplace;
    }

    public function setIdMarketplace(?int $id_marketplace): self
    {
        $this->id_marketplace = $id_marketplace;
        return $this;
    }

    public function getIdStock(): ?int
    {
        return $this->id_stock;
    }

    public function setIdStock(?int $id_stock): self
    {
        $this->id_stock = $id_stock;
        return $this;
    }

    public function getPrixUnitaire(): ?string
    {
        return $this->prix_unitaire;
    }

    public function setPrixUnitaire(?string $prix_unitaire): self
    {
        $this->prix_unitaire = $prix_unitaire;
        return $this;
    }

    public function getQuantiteEnVente(): ?string
    {
        return $this->quantite_en_vente;
    }

    public function setQuantiteEnVente(?string $quantite_en_vente): self
    {
        $this->quantite_en_vente = $quantite_en_vente;
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

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->date_publication;
    }

    public function setDatePublication(?\DateTimeInterface $date_publication): self
    {
        $this->date_publication = $date_publication;
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

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): self
    {
        $this->stock = $stock;
        return $this;
    }

}
