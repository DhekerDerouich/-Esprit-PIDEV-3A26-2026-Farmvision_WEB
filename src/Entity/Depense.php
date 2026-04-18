<?php

namespace App\Entity;

use App\Repository\DepenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DepenseRepository::class)]
#[ORM\Table(name: 'depense')]
class Depense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idDepense', type: 'integer')]
    private ?int $idDepense = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le montant est obligatoire')]
    #[Assert\Positive(message: 'Le montant doit être positif')]
    private ?float $montant = null;

    #[ORM\Column(name: 'typeDepense', length: 100)]
    #[Assert\NotBlank(message: 'Le type de dépense est obligatoire')]
    private ?string $typeDepense = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 500,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(name: 'dateDepense', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date est obligatoire')]
    private ?\DateTimeInterface $dateDepense = null;

    #[ORM\Column(name: 'created_at', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getIdDepense(): ?int 
    { 
        return $this->idDepense; 
    }
    
    public function getMontant(): ?float 
    { 
        return $this->montant; 
    }
    
    public function setMontant(float $montant): self 
    { 
        $this->montant = $montant; 
        return $this; 
    }
    
    public function getTypeDepense(): ?string 
    { 
        return $this->typeDepense; 
    }
    
    public function setTypeDepense(string $typeDepense): self 
    { 
        $this->typeDepense = $typeDepense; 
        return $this; 
    }
    
    public function getDescription(): ?string 
    { 
        return $this->description; 
    }
    
    public function setDescription(string $description): self 
    { 
        $this->description = $description; 
        return $this; 
    }
    
    public function getDateDepense(): ?\DateTimeInterface 
    { 
        return $this->dateDepense; 
    }
    
    public function setDateDepense(\DateTimeInterface $dateDepense): self 
    { 
        $this->dateDepense = $dateDepense; 
        return $this; 
    }
    
    public function getCreatedAt(): ?\DateTimeImmutable 
    { 
        return $this->createdAt; 
    }
    
    public function setCreatedAt(?\DateTimeImmutable $createdAt): self 
    { 
        $this->createdAt = $createdAt; 
        return $this; 
    }
}