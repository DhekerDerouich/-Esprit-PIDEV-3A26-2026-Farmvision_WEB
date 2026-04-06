<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CultureRepository::class)]
#[ORM\Table(name: 'culture')]
class Culture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'idCulture', type: 'integer', length: 11)]
    private ?int $idCulture;

    #[ORM\Column(name: 'nomCulture', type: 'string', length: 100)]
    private ?string $nomCulture;

    #[ORM\Column(name: 'typeCulture', type: 'string', length: 100)]
    private ?string $typeCulture;

    #[ORM\Column(name: 'dateSemis', type: 'date')]
    private ?\DateTimeInterface $dateSemis;

    #[ORM\Column(name: 'dateRecolte', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateRecolte;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: true, length: 11)]
    private ?int $user_id;

    public function getId(): ?int
    {
        return $this->id;
    }

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
