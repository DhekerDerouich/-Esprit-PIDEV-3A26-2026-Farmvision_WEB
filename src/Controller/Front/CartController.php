<?php
// src/Controller/Front/CartController.php

namespace App\Controller\Front;

use App\Entity\Invoice;
use App\Entity\Marketplace;
use App\Repository\MarketplaceRepository;
use App\Service\CartService;
use App\Service\InvoiceService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('/', name: 'front_cart_index')]
    public function index(CartService $cartService): Response
    {
        $cart = $cartService->getCurrentCart();
        
        return $this->render('front/cart/index.html.twig', [
            'cart' => $cart,
            'total' => $cart->getTotal()
        ]);
    }
    
    #[Route('/add/{id}', name: 'front_cart_add', methods: ['POST'])]
    public function add(
        Marketplace $marketplace,
        Request $request,
        CartService $cartService
    ): Response {
        $quantity = (float) $request->request->get('quantity', 1);
        
        try {
            $cartService->addItem($marketplace, $quantity);
            $this->addFlash('success', 'Produit ajouté au panier');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }
        
        return $this->redirectToRoute('front_marketplace_show', ['id' => $marketplace->getId()]);
    }
    
    #[Route('/update/{id}', name: 'front_cart_update', methods: ['POST'])]
    public function update(int $id, Request $request, CartService $cartService): Response
    {
        $quantity = (float) $request->request->get('quantity', 0);
        
        try {
            $cartService->updateItem($id, $quantity);
            $this->addFlash('success', 'Panier mis à jour');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }
        
        return $this->redirectToRoute('front_cart_index');
    }
    
    #[Route('/remove/{id}', name: 'front_cart_remove', methods: ['POST'])]
    public function remove(int $id, CartService $cartService): Response
    {
        $cartService->removeItem($id);
        $this->addFlash('success', 'Produit retiré du panier');
        
        return $this->redirectToRoute('front_cart_index');
    }
    
    #[Route('/checkout', name: 'front_cart_checkout')]
    public function checkout(CartService $cartService, StripeService $stripeService): Response
    {
        $cart = $cartService->getCurrentCart();
        
        if ($cart->getItems()->count() === 0) {
            $this->addFlash('error', 'Votre panier est vide');
            return $this->redirectToRoute('front_cart_index');
        }
        
        $items = [];
        foreach ($cart->getItems() as $item) {
            $items[] = [
                'name' => $item->getMarketplace()->getStock()->getNomProduit(),
                'description' => $item->getMarketplace()->getDescription(),
                'price' => $item->getPrice(),
                'quantity' => $item->getQuantity(),
                'cart_id' => $cart->getId(),
            ];
        }
        
        $successUrl = $this->generateUrl('front_cart_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl = $this->generateUrl('front_cart_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $checkoutSession = $stripeService->createCheckoutSession($items, $successUrl, $cancelUrl);
        
        return $this->redirect($checkoutSession->url);
    }
    
    #[Route('/success', name: 'front_cart_success')]
    public function success(
        Request $request,
        CartService $cartService,
        StripeService $stripeService,
        InvoiceService $invoiceService,
        MailerInterface $mailer,
        EntityManagerInterface $em
    ): Response {
        $sessionId = $request->query->get('session_id');
        
        if (!$sessionId) {
            return $this->redirectToRoute('front_cart_index');
        }
        
        $session = $stripeService->retrieveSession($sessionId);
        
        if (!$session || $session->payment_status !== 'paid') {
            $this->addFlash('error', 'Paiement non confirmé');
            return $this->redirectToRoute('front_cart_index');
        }
        
        $cart = $cartService->getCurrentCart();
        
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        $customerEmail = $user ? $user->getEmail() : $session->customer_details->email;
        $customerName = $user ? $user->getNom() : ($session->customer_details->name ?? null);
        
        // Générer la facture
        $invoice = $invoiceService->generateInvoice($cart, $session, $customerEmail, $customerName);
        
        // Envoyer l'email avec la facture
        $email = (new Email())
            ->from('no-reply@farmvision.com')
            ->to($customerEmail)
            ->subject('Votre facture FarmVision #' . $invoice->getInvoiceNumber())
            ->html($this->renderView('emails/invoice.html.twig', [
                'invoice' => $invoice,
                'cart' => $cart,
            ]));
        
        // Attacher le PDF si disponible
        if ($invoice->getPdfPath() && file_exists($invoice->getPdfPath())) {
            $email->attachFromPath($invoice->getPdfPath(), 'facture.pdf', 'application/pdf');
        }
        
        $mailer->send($email);
        
        // Mettre à jour les stocks
        foreach ($cart->getItems() as $item) {
            $marketplace = $item->getMarketplace();
            $newQuantity = $marketplace->getQuantiteEnVente() - $item->getQuantity();
            $marketplace->setQuantiteEnVente($newQuantity);
            
            if ($newQuantity <= 0) {
                $marketplace->setStatut('Vendu');
            }
            
            // Mettre à jour le stock principal
            $stock = $marketplace->getStock();
            $stock->setQuantite($stock->getQuantite() - $item->getQuantity());
            if ($stock->getQuantite() <= 0) {
                $stock->setStatut('Épuisé');
            }
            $stock->setUpdatedAt(new \DateTime());
        }
        
        // Vider le panier
        $cartService->clearCart();
        $em->flush();
        
        $this->addFlash('success', 'Paiement réussi ! La facture vous a été envoyée par email.');
        
        return $this->render('front/cart/success.html.twig', [
            'invoice' => $invoice,
        ]);
    }
}