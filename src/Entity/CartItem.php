<?php
// src/Entity/CartItem.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cart_item')]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'marketplace_id', referencedColumnName: 'id_marketplace', nullable: false)]
    private ?Marketplace $marketplace = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cart $cart = null;

    #[ORM\Column(type: 'float')]
    private ?float $quantity = null;

    #[ORM\Column(type: 'float')]
    private ?float $price = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $addedAt = null;

    public function __construct()
    {
        $this->addedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getMarketplace(): ?Marketplace { return $this->marketplace; }
    public function setMarketplace(?Marketplace $marketplace): self { $this->marketplace = $marketplace; return $this; }

    public function getCart(): ?Cart { return $this->cart; }
    public function setCart(?Cart $cart): self { $this->cart = $cart; return $this; }

    public function getQuantity(): ?float { return $this->quantity; }
    public function setQuantity(float $quantity): self { $this->quantity = $quantity; return $this; }

    public function getPrice(): ?float { return $this->price; }
    public function setPrice(float $price): self { $this->price = $price; return $this; }

    public function getAddedAt(): ?\DateTimeInterface { return $this->addedAt; }
    public function setAddedAt(\DateTimeInterface $addedAt): self { $this->addedAt = $addedAt; return $this; }

    public function getTotal(): float
    {
        return ($this->quantity ?? 0) * ($this->price ?? 0);
    }
}
