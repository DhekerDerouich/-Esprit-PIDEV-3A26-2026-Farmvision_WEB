<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RevenuRepository::class)]
#[ORM\Table(name: 'revenu')]
class Revenu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'idRevenu', type: 'integer', length: 11)]
    private ?int $idRevenu;

    #[ORM\Column(name: 'montant', type: 'decimal')]
    private ?float $montant;

    #[ORM\Column(name: 'source', type: 'string', length: 100)]
    private ?string $source;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(name: 'dateRevenu', type: 'date')]
    private ?\DateTimeInterface $dateRevenu;

    #[ORM\Column(name: 'created_at', type: 'string')]
    private ?string $created_at;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdRevenu(): ?int
    {
        return $this->idRevenu;
    }

    public function setIdRevenu(?int $idRevenu): self
    {
        $this->idRevenu = $idRevenu;
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

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
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

    public function getDateRevenu(): ?\DateTimeInterface
    {
        return $this->dateRevenu;
    }

    public function setDateRevenu(?\DateTimeInterface $dateRevenu): self
    {
        $this->dateRevenu = $dateRevenu;
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
