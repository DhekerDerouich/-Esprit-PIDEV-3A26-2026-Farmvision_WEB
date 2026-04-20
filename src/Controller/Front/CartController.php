<?php
// src/Controller/Front/CartController.php

namespace App\Controller\Front;

use App\Entity\Marketplace;
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

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('/', name: 'front_cart_index')]
    public function index(CartService $cartService): Response
    {
        $cart = $cartService->getCurrentCart();

        return $this->render('front/cart/index.html.twig', [
            'cart'  => $cart,
            'total' => $cart->getTotal(),
        ]);
    }

    #[Route('/add/{id}', name: 'front_cart_add', methods: ['POST'])]
    public function add(Marketplace $marketplace, Request $request, CartService $cartService): Response
    {
        $quantity = (float) $request->request->get('quantity', 1);

        try {
            $cartService->addItem($marketplace, $quantity);
            $this->addFlash('success', '🛒 Produit ajouté au panier !');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('front_cart_index');
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

        // Guard: Stripe not configured
        if (!$stripeService->isConfigured()) {
            $this->addFlash('error', '⚠️ Le paiement Stripe n\'est pas encore configuré. Ajoutez vos clés API dans le fichier .env');
            return $this->redirectToRoute('front_cart_index');
        }

        $items = [];
        foreach ($cart->getItems() as $item) {
            $items[] = [
                'name'        => $item->getMarketplace()->getStock()->getNomProduit(),
                'description' => $item->getMarketplace()->getDescription() ?? '',
                'price'       => $item->getPrice(),
                'quantity'    => $item->getQuantity(),
            ];
        }

        $successUrl = $this->generateUrl('front_cart_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl  = $this->generateUrl('front_cart_index', [], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $checkoutSession = $stripeService->createCheckoutSession($items, $successUrl, $cancelUrl);
            return $this->redirect($checkoutSession->url);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur Stripe : ' . $e->getMessage());
            return $this->redirectToRoute('front_cart_index');
        }
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

        $user          = $this->getUser();
        /** @var \App\Entity\Utilisateur|null $user */
        $customerEmail = $user ? $user->getEmail() : ($session->customer_details->email ?? 'client@farmvision.com');
        $customerName  = $user ? ($user->getPrenom() . ' ' . $user->getNom()) : ($session->customer_details->name ?? null);

        // Generate invoice
        $invoice = $invoiceService->generateInvoice($cart, $session, $customerEmail, $customerName);

        // Send email with invoice
        try {
            $emailMsg = (new Email())
                ->from('no-reply@farmvision.com')
                ->to($customerEmail)
                ->subject('Votre facture FarmVision #' . $invoice->getInvoiceNumber())
                ->html($this->renderView('front/emails/invoice.html.twig', [
                    'invoice' => $invoice,
                    'cart'    => $cart,
                ]));

            if ($invoice->getPdfPath() && file_exists($invoice->getPdfPath())) {
                $emailMsg->attachFromPath($invoice->getPdfPath(), 'facture.pdf', 'application/pdf');
            }

            $mailer->send($emailMsg);
        } catch (\Exception $e) {
            // Email failure is non-blocking
        }

        // Update stock quantities
        foreach ($cart->getItems() as $item) {
            $marketplace = $item->getMarketplace();
            $newQty      = $marketplace->getQuantiteEnVente() - $item->getQuantity();
            $marketplace->setQuantiteEnVente(max(0, $newQty));

            if ($newQty <= 0) {
                $marketplace->setStatut('Vendu');
            }

            $stock = $marketplace->getStock();
            $stock->setQuantite(max(0, $stock->getQuantite() - $item->getQuantity()));
            if ($stock->getQuantite() <= 0) {
                $stock->setStatut('Épuisé');
            }
            $stock->setUpdatedAt(new \DateTime());
        }

        $cartService->clearCart();
        $em->flush();

        return $this->render('front/cart/success.html.twig', [
            'invoice' => $invoice,
        ]);
    }
}
