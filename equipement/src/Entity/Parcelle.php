<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParcelleRepository::class)]
#[ORM\Table(name: 'parcelle')]
class Parcelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'idParcelle', type: 'integer', length: 11)]
    private ?int $idParcelle;

    #[ORM\Column(name: 'surface', type: 'float')]
    private ?float $surface;

    #[ORM\Column(name: 'localisation', type: 'string', length: 255)]
    private ?string $localisation;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: true, length: 11)]
    private ?int $user_id;

    public function getId(): ?int
    {
        return $this->id;
    }

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
