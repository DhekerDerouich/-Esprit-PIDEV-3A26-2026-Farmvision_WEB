<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepenseRepository::class)]
#[ORM\Table(name: 'depense')]
class Depense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'idDepense', type: 'integer', length: 11)]
    private ?int $idDepense;

    #[ORM\Column(name: 'montant', type: 'decimal')]
    private ?float $montant;

    #[ORM\Column(name: 'typeDepense', type: 'string', length: 100)]
    private ?string $typeDepense;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(name: 'dateDepense', type: 'date')]
    private ?\DateTimeInterface $dateDepense;

    #[ORM\Column(name: 'created_at', type: 'string')]
    private ?string $created_at;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdDepense(): ?int
    {
        return $this->idDepense;
    }

    public function setIdDepense(?int $idDepense): self
    {
        $this->idDepense = $idDepense;
        return $this;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(?float $montant): self
    {
        $this->montant = $montant;
        return $this;
    }

    public function getTypeDepense(): ?string
    {
        return $this->typeDepense;
    }

    public function setTypeDepense(?string $typeDepense): self
    {
        $this->typeDepense = $typeDepense;
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

    public function getDateDepense(): ?\DateTimeInterface
    {
        return $this->dateDepense;
    }

    public function setDateDepense(?\DateTimeInterface $dateDepense): self
    {
        $this->dateDepense = $dateDepense;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(?string $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

}
