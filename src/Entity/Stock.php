<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockRepository::class)]
#[ORM\Table(name: 'stock')]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'id_stock', type: 'integer', length: 11)]
    private ?int $id_stock;

    #[ORM\Column(name: 'id_utilisateur', type: 'integer', length: 11)]
    private ?int $id_utilisateur;

    #[ORM\Column(name: 'nom_produit', type: 'string', length: 100)]
    private ?string $nom_produit;

    #[ORM\Column(name: 'type_produit', type: 'string', nullable: true, length: 50)]
    private ?string $type_produit;

    #[ORM\Column(name: 'quantite', type: 'string')]
    private ?string $quantite;

    #[ORM\Column(name: 'unite', type: 'string', nullable: true, length: 20)]
    private ?string $unite;

    #[ORM\Column(name: 'date_entree', type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_entree;

    #[ORM\Column(name: 'date_expiration', type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_expiration;

    #[ORM\Column(name: 'statut', type: 'string', nullable: true, length: 30)]
    private ?string $statut;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIdUtilisateur(): ?int
    {
        return $this->id_utilisateur;
    }

    public function setIdUtilisateur(?int $id_utilisateur): self
    {
        $this->id_utilisateur = $id_utilisateur;
        return $this;
    }

    public function getNomProduit(): ?string
    {
        return $this->nom_produit;
    }

    public function setNomProduit(?string $nom_produit): self
    {
        $this->nom_produit = $nom_produit;
        return $this;
    }

    public function getTypeProduit(): ?string
    {
        return $this->type_produit;
    }

    public function setTypeProduit(?string $type_produit): self
    {
        $this->type_produit = $type_produit;
        return $this;
    }

    public function getQuantite(): ?string
    {
        return $this->quantite;
    }

    public function setQuantite(?string $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(?string $unite): self
    {
        $this->unite = $unite;
        return $this;
    }

    public function getDateEntree(): ?\DateTimeInterface
    {
        return $this->date_entree;
    }

    public function setDateEntree(?\DateTimeInterface $date_entree): self
    {
        $this->date_entree = $date_entree;
        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->date_expiration;
    }

    public function setDateExpiration(?\DateTimeInterface $date_expiration): self
    {
        $this->date_expiration = $date_expiration;
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

}
