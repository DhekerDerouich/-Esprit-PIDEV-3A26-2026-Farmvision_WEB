<?php

namespace App\Entity;

use App\CultureParcelle\Repository\ParcelleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParcelleRepository::class)]
#[ORM\Table(name: 'parcelle')]
class Parcelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idParcelle', type: 'integer', length: 11)]
    private ?int $idParcelle = null;

    #[ORM\Column(name: 'surface', type: 'float')]
    #[Assert\NotNull(message: 'La surface est obligatoire')]
    #[Assert\Positive(message: 'La surface doit être un nombre positif')]
    #[Assert\LessThanOrEqual(value: 10000, message: 'La surface ne peut pas dépasser 10 000 ha')]
    private ?float $surface = null;

    #[ORM\Column(name: 'localisation', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'La localisation est obligatoire')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'La localisation doit contenir au moins {{ limit }} caractères', maxMessage: 'La localisation ne peut pas dépasser {{ limit }} caractères')]
    private ?string $localisation = null;

    #[ORM\Column(name: 'latitude', type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(name: 'longitude', type: 'float', nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: true, length: 11)]
    private ?int $user_id = null;

    public function getIdParcelle(): ?int
    {
        return $this->idParcelle;
    }

    public function setIdParcelle(?int $idParcelle): self
    {
        $this->idParcelle = $idParcelle;
        return $this;
    }

    public function getSurface(): ?float
    {
        return $this->surface;
    }

    public function setSurface(?float $surface): self
    {
        $this->surface = $surface;
        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): self
    {
        $this->localisation = $localisation;
        return $this;
    }

    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(?float $latitude): self { $this->latitude = $latitude; return $this; }

    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(?float $longitude): self { $this->longitude = $longitude; return $this; }

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
