<?php

namespace App\Entity;

use App\Repository\RevenuRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RevenuRepository::class)]
#[ORM\Table(name: 'revenu')]
class Revenu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idRevenu', type: 'integer')]
    private ?int $idRevenu = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le montant est obligatoire')]
    #[Assert\Positive(message: 'Le montant doit être positif')]
    private ?float $montant = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La source est obligatoire')]
    private ?string $source = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 500,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(name: 'dateRevenu', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date est obligatoire')]
    private ?\DateTimeInterface $dateRevenu = null;

    #[ORM\Column(name: 'created_at', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getIdRevenu(): ?int 
    { 
        return $this->idRevenu; 
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
    
    public function getSource(): ?string 
    { 
        return $this->source; 
    }
    
    public function setSource(string $source): self 
    { 
        $this->source = $source; 
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
    
    public function getDateRevenu(): ?\DateTimeInterface 
    { 
        return $this->dateRevenu; 
    }
    
    public function setDateRevenu(\DateTimeInterface $dateRevenu): self 
    { 
        $this->dateRevenu = $dateRevenu; 
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