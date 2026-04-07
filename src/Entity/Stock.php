<?php
// src/Entity/Stock.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\StockRepository;

#[ORM\Entity(repositoryClass: StockRepository::class)]
#[ORM\Table(name: 'stock')]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_stock', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'id_utilisateur', type: 'integer', nullable: true)]
    private ?int $idUtilisateur = null;

    #[ORM\Column(name: 'nom_produit', type: 'string', length: 100)]
    #[Assert\NotBlank(message: "Le nom du produit est obligatoire")]
    private ?string $nomProduit = null;

    #[ORM\Column(name: 'type_produit', type: 'string', length: 50, nullable: true)]
    private ?string $typeProduit = null;

    #[ORM\Column(name: 'quantite', type: 'float')]
    #[Assert\NotBlank(message: "La quantité est obligatoire")]
    #[Assert\Positive(message: "La quantité doit être positive")]
    private ?float $quantite = null;

    #[ORM\Column(name: 'unite', type: 'string', length: 20, nullable: true)]
    private ?string $unite = null;

    #[ORM\Column(name: 'date_entree', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateEntree = null;

    #[ORM\Column(name: 'date_expiration', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateExpiration = null;

    #[ORM\Column(name: 'statut', type: 'string', length: 30, nullable: true)]
    private ?string $statut = 'Disponible';

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->dateEntree = new \DateTime();
        $this->statut = 'Disponible';
    }

    // GETTERS
    public function getId(): ?int { return $this->id; }
    public function getIdUtilisateur(): ?int { return $this->idUtilisateur; }
    public function getNomProduit(): ?string { return $this->nomProduit; }
    public function getTypeProduit(): ?string { return $this->typeProduit; }
    public function getQuantite(): ?float { return $this->quantite; }
    public function getUnite(): ?string { return $this->unite; }
    public function getDateEntree(): ?\DateTimeInterface { return $this->dateEntree; }
    public function getDateExpiration(): ?\DateTimeInterface { return $this->dateExpiration; }
    public function getStatut(): ?string { return $this->statut; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    // SETTERS
    public function setIdUtilisateur(?int $idUtilisateur): self { $this->idUtilisateur = $idUtilisateur; return $this; }
    public function setNomProduit(?string $nomProduit): self { $this->nomProduit = $nomProduit; return $this; }
    public function setTypeProduit(?string $typeProduit): self { $this->typeProduit = $typeProduit; return $this; }
    public function setQuantite(?float $quantite): self { $this->quantite = $quantite; return $this; }
    public function setUnite(?string $unite): self { $this->unite = $unite; return $this; }
    public function setDateEntree(?\DateTimeInterface $dateEntree): self { $this->dateEntree = $dateEntree; return $this; }
    public function setDateExpiration(?\DateTimeInterface $dateExpiration): self { $this->dateExpiration = $dateExpiration; return $this; }
    public function setStatut(?string $statut): self { $this->statut = $statut; return $this; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    // MÉTHODES UTILITAIRES
    public function isExpired(): bool
    {
        if (!$this->dateExpiration) return false;
        return $this->dateExpiration < new \DateTime();
    }

    public function getJoursAvantExpiration(): int
    {
        if (!$this->dateExpiration) return 0;
        $now = new \DateTime();
        if ($this->dateExpiration < $now) return 0;
        return $now->diff($this->dateExpiration)->days;
    }
}