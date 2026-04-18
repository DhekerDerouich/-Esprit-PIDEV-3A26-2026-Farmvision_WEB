<?php
// src/Service/CartService.php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Marketplace;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private EntityManagerInterface $em;
    private CartRepository $cartRepository;
    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        RequestStack $requestStack
    ) {
        $this->em = $em;
        $this->cartRepository = $cartRepository;
        $this->requestStack = $requestStack;
    }

    public function getCurrentCart(): Cart
    {
        $session = $this->requestStack->getSession();
        $sessionId = $session->getId();

        if (!$sessionId) {
            $session->start();
            $sessionId = $session->getId();
        }

        $cart = $this->cartRepository->findActiveBySessionId($sessionId);

        if (!$cart) {
            $cart = new Cart();
            $cart->setSessionId($sessionId);
            $this->em->persist($cart);
            $this->em->flush();
        }

        return $cart;
    }

    public function addItem(Marketplace $marketplace, float $quantity = 1): CartItem
    {
        if ($marketplace->getStatut() !== 'En vente') {
            throw new \Exception('Ce produit n\'est plus disponible à la vente.');
        }

        if ($quantity > $marketplace->getQuantiteEnVente()) {
            throw new \Exception('Quantité demandée supérieure au stock disponible.');
        }

        $cart = $this->getCurrentCart();

        // Check if item already in cart
        foreach ($cart->getItems() as $existingItem) {
            if ($existingItem->getMarketplace()->getId() === $marketplace->getId()) {
                $newQty = $existingItem->getQuantity() + $quantity;
                if ($newQty > $marketplace->getQuantiteEnVente()) {
                    throw new \Exception('Quantité totale dépasse le stock disponible.');
                }
                $existingItem->setQuantity($newQty);
                $cart->setUpdatedAt(new \DateTime());
                $this->em->flush();
                return $existingItem;
            }
        }

        $item = new CartItem();
        $item->setMarketplace($marketplace);
        $item->setCart($cart);
        $item->setQuantity($quantity);
        $item->setPrice($marketplace->getPrixUnitaire());

        $cart->addItem($item);
        $cart->setUpdatedAt(new \DateTime());

        $this->em->persist($item);
        $this->em->flush();

        return $item;
    }

    public function updateItem(int $itemId, float $quantity): void
    {
        $cart = $this->getCurrentCart();

        foreach ($cart->getItems() as $item) {
            if ($item->getId() === $itemId) {
                if ($quantity <= 0) {
                    $this->em->remove($item);
                } else {
                    if ($quantity > $item->getMarketplace()->getQuantiteEnVente()) {
                        throw new \Exception('Quantité demandée supérieure au stock disponible.');
                    }
                    $item->setQuantity($quantity);
                }
                $cart->setUpdatedAt(new \DateTime());
                $this->em->flush();
                return;
            }
        }
    }

    public function removeItem(int $itemId): void
    {
        $cart = $this->getCurrentCart();

        foreach ($cart->getItems() as $item) {
            if ($item->getId() === $itemId) {
                $this->em->remove($item);
                $cart->setUpdatedAt(new \DateTime());
                $this->em->flush();
                return;
            }
        }
    }

    public function clearCart(): void
    {
        $cart = $this->getCurrentCart();
        $cart->setStatus('completed');
        $cart->setUpdatedAt(new \DateTime());
        $this->em->flush();
    }

    public function getCartCount(): int
    {
        try {
            $cart = $this->getCurrentCart();
            return $cart->getTotalQuantity();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
