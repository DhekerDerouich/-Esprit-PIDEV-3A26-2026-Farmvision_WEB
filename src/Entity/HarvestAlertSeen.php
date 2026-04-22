<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'harvest_alert_seen')]
class HarvestAlertSeen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $userId = null;

    #[ORM\Column(type: 'integer')]
    private ?int $cultureId = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $seenAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getCultureId(): ?int
    {
        return $this->cultureId;
    }

    public function setCultureId(int $cultureId): self
    {
        $this->cultureId = $cultureId;
        return $this;
    }

    public function getSeenAt(): ?\DateTimeInterface
    {
        return $this->seenAt;
    }

    public function setSeenAt(\DateTimeInterface $seenAt): self
    {
        $this->seenAt = $seenAt;
        return $this;
    }
}
