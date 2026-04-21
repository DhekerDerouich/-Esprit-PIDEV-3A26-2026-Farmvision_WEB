<?php
// src/Controller/Front/MarketplaceController.php

namespace App\Controller\Front;

use App\Repository\MarketplaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/marketplace')]
class MarketplaceController extends AbstractController
{
    #[Route('/', name: 'front_marketplace_index', methods: ['GET'])]
    public function index(Request $request, MarketplaceRepository $repository, SessionInterface $session): Response
    {
        $search = $request->query->get('search', '');
        $statut = $request->query->get('statut', 'all');
        
        $marketplaces = $repository->search($search, $statut);
        $stats = $repository->getStatistics();
        
        $cart = $session->get('cart', []);
        $cartCount = array_sum(array_column($cart, 'quantity'));
        
        return $this->render('front/marketplace/index.html.twig', [
            'marketplaces' => $marketplaces,
            'stats' => $stats,
            'search' => $search,
            'selectedStatut' => $statut,
            'cartCount' => $cartCount,
        ]);
    }
    
    #[Route('/{id}', name: 'front_marketplace_show', methods: ['GET'])]
    public function show(int $id, MarketplaceRepository $repository, SessionInterface $session): Response
    {
        $marketplace = $repository->find($id);
        
        if (!$marketplace) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
        
        $cart = $session->get('cart', []);
        $cartCount = array_sum(array_column($cart, 'quantity'));
        
        return $this->render('front/marketplace/show.html.twig', [
            'marketplace' => $marketplace,
            'cartCount' => $cartCount,
        ]);
    }
    
    #[Route('/cart/add/{id}', name: 'front_marketplace_cart_add', methods: ['POST'])]
    public function addToCart(int $id, Request $request, MarketplaceRepository $repository, SessionInterface $session): Response
    {
        $marketplace = $repository->find($id);
        
        if (!$marketplace) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
        
        $quantity = max(1, (int) $request->request->get('quantity', 1));
        
        $cart = $session->get('cart', []);
        
        if (isset($cart[$id])) {
            $cart[$id]['quantity'] += $quantity;
        } else {
            $cart[$id] = [
                'id' => $marketplace->getIdMarketplace(),
                'nom' => $marketplace->getStock()->getNomProduit(),
                'prix' => $marketplace->getPrixUnitaire(),
                'quantity' => $quantity,
            ];
        }
        
        $session->set('cart', $cart);
        
        $this->addFlash('success', "{$marketplace->getStock()->getNomProduit()} ajouté au panier !");
        
        return $this->redirectToRoute('front_marketplace_cart');
    }
    
    #[Route('/cart', name: 'front_marketplace_cart', methods: ['GET'])]
    public function cart(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        
        $cartItems = [];
        $total = 0;
        
        foreach ($cart as $id => $item) {
            $cartItems[] = $item;
            $total += $item['prix'] * $item['quantity'];
        }
        
        return $this->render('front/cart/index.html.twig', [
            'cartItems' => $cartItems,
            'cartTotal' => $total,
            'cartCount' => array_sum(array_column($cart, 'quantity')),
        ]);
    }
    
    #[Route('/cart/remove/{id}', name: 'front_marketplace_cart_remove', methods: ['POST'])]
    public function removeFromCart(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        
        if (isset($cart[$id])) {
            unset($cart[$id]);
            $session->set('cart', $cart);
            $this->addFlash('success', 'Produit retiré du panier !');
        }
        
        return $this->redirectToRoute('front_marketplace_cart');
    }
    
    #[Route('/cart/clear', name: 'front_marketplace_cart_clear', methods: ['POST'])]
    public function clearCart(SessionInterface $session): Response
    {
        $session->set('cart', []);
        $this->addFlash('success', 'Panier vidé !');
        
        return $this->redirectToRoute('front_marketplace_index');
    }
}