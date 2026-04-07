<?php

namespace App\Entity;

use App\CultureParcelle\Repository\CultureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CultureRepository::class)]
#[ORM\Table(name: 'culture')]
class Culture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idCulture', type: 'integer', length: 11)]
    private ?int $idCulture = null;

    #[ORM\Column(name: 'nomCulture', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le nom de la culture est obligatoire')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/', message: 'Le nom ne doit contenir que des lettres')]
    private ?string $nomCulture = null;

    #[ORM\Column(name: 'typeCulture', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le type de culture est obligatoire')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le type doit contenir au moins {{ limit }} caractères', maxMessage: 'Le type ne peut pas dépasser {{ limit }} caractères')]
    private ?string $typeCulture = null;

    #[ORM\Column(name: 'dateSemis', type: 'date')]
    #[Assert\NotNull(message: 'La date de semis est obligatoire')]
    #[Assert\LessThanOrEqual('today', message: 'La date de semis ne peut pas être dans le futur')]
    private ?\DateTimeInterface $dateSemis = null;

    #[ORM\Column(name: 'dateRecolte', type: 'date', nullable: true)]
    #[Assert\GreaterThan(propertyPath: 'dateSemis', message: 'La date de récolte doit être après la date de semis')]
    private ?\DateTimeInterface $dateRecolte = null;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: true, length: 11)]
    private ?int $user_id = null;

    public function getIdCulture(): ?int
    {
        return $this->idCulture;
    }

    public function setIdCulture(?int $idCulture): self
    {
        $this->idCulture = $idCulture;
        return $this;
    }

    public function getNomCulture(): ?string
    {
        return $this->nomCulture;
    }

    public function setNomCulture(?string $nomCulture): self
    {
        $this->nomCulture = $nomCulture;
        return $this;
    }

    public function getTypeCulture(): ?string
    {
        return $this->typeCulture;
    }

    public function setTypeCulture(?string $typeCulture): self
    {
        $this->typeCulture = $typeCulture;
        return $this;
    }

    public function getDateSemis(): ?\DateTimeInterface
    {
        return $this->dateSemis;
    }

    public function setDateSemis(?\DateTimeInterface $dateSemis): self
    {
        $this->dateSemis = $dateSemis;
        return $this;
    }

    public function getDateRecolte(): ?\DateTimeInterface
    {
        return $this->dateRecolte;
    }

    public function setDateRecolte(?\DateTimeInterface $dateRecolte): self
    {
        $this->dateRecolte = $dateRecolte;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

}
