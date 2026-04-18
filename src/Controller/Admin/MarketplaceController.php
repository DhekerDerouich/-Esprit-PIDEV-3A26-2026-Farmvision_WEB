<?php
// src/Controller/Admin/MarketplaceController.php

namespace App\Controller\Admin;

use App\Entity\Marketplace;
use App\Repository\MarketplaceRepository;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/marketplace')]
class MarketplaceController extends AbstractController
{
    #[Route('/', name: 'admin_marketplace_index', methods: ['GET'])]
    public function index(Request $request, MarketplaceRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $search = $request->query->get('search', '');
        $statut = $request->query->get('statut', 'all');
        
        $marketplaces = $repository->search($search, $statut);
        $stats = $repository->getStatistics();
        
        return $this->render('admin/marketplace/index.html.twig', [
            'marketplaces' => $marketplaces,
            'stats' => $stats,
            'search' => $search,
            'selectedStatut' => $statut,
        ]);
    }
    
    #[Route('/new', name: 'admin_marketplace_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, StockRepository $stockRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $stocks = $stockRepo->findAvailableForMarketplace();
        
        $formData = [];
        
        if ($request->isMethod('POST')) {
            $stockId = $request->request->get('stock_id', '');
            $prixUnitaire = $request->request->get('prix_unitaire', '');
            $quantiteEnVente = $request->request->get('quantite_en_vente', '');
            $description = trim($request->request->get('description', ''));
            
            $formData = [
                'stock_id' => $stockId,
                'prix_unitaire' => $prixUnitaire,
                'quantite_en_vente' => $quantiteEnVente,
                'description' => $description,
            ];
            
            $errors = [];
            
            if (empty($stockId)) {
                $errors[] = "Veuillez sélectionner un produit.";
            } else {
                $stock = $stockRepo->find($stockId);
                if (!$stock) {
                    $errors[] = "Le produit sélectionné n'existe pas.";
                } elseif ($stock->getQuantite() < $quantiteEnVente) {
                    $errors[] = "La quantité en vente ne peut pas dépasser la quantité disponible en stock ({$stock->getQuantite()} {$stock->getUnite()}).";
                }
            }
            
            if (empty($prixUnitaire) || !is_numeric($prixUnitaire) || $prixUnitaire <= 0) {
                $errors[] = "Le prix unitaire doit être un nombre positif.";
            }
            
            if (empty($quantiteEnVente) || !is_numeric($quantiteEnVente) || $quantiteEnVente <= 0) {
                $errors[] = "La quantité en vente doit être un nombre positif.";
            }
            
            if (empty($errors)) {
                $stock = $stockRepo->find($stockId);
                
                if ($stock) {
                    $marketplace = new Marketplace();
                    $marketplace->setStock($stock);
                    $marketplace->setPrixUnitaire((float)$prixUnitaire);
                    $marketplace->setQuantiteEnVente((float)$quantiteEnVente);
                    $marketplace->setDescription($description ?: null);
                    $marketplace->setStatut('En vente');
                    
                    $em->persist($marketplace);
                    $em->flush();
                    
                    $this->addFlash('success', 'Produit mis en vente avec succès !');
                    return $this->redirectToRoute('admin_marketplace_index');
                }
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }
        
        return $this->render('admin/marketplace/new.html.twig', [
            'stocks' => $stocks,
            'formData' => $formData
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_marketplace_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Marketplace $marketplace, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($request->isMethod('POST')) {
            $prixUnitaire = $request->request->get('prix_unitaire', '');
            $quantiteEnVente = $request->request->get('quantite_en_vente', '');
            $description = trim($request->request->get('description', ''));
            $statut = $request->request->get('statut', 'En vente');
            
            $errors = [];
            
            if (empty($prixUnitaire) || !is_numeric($prixUnitaire) || $prixUnitaire <= 0) {
                $errors[] = "Le prix unitaire doit être un nombre positif.";
            }
            
            if (empty($quantiteEnVente) || !is_numeric($quantiteEnVente) || $quantiteEnVente <= 0) {
                $errors[] = "La quantité en vente doit être un nombre positif.";
            }
            
            $stockDisponible = $marketplace->getStock()->getQuantite();
            if ($quantiteEnVente > $stockDisponible) {
                $errors[] = "La quantité en vente ({$quantiteEnVente}) ne peut pas dépasser la quantité disponible en stock ({$stockDisponible}).";
            }
            
            if (empty($errors)) {
                $marketplace->setPrixUnitaire((float)$prixUnitaire);
                $marketplace->setQuantiteEnVente((float)$quantiteEnVente);
                $marketplace->setDescription($description ?: null);
                $marketplace->setStatut($statut);
                
                $em->flush();
                $this->addFlash('success', 'Offre modifiée avec succès !');
                return $this->redirectToRoute('admin_marketplace_index');
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }
        
        return $this->render('admin/marketplace/edit.html.twig', [
            'marketplace' => $marketplace,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_marketplace_delete', methods: ['POST'])]
    public function delete(Request $request, Marketplace $marketplace, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete' . $marketplace->getId(), $request->request->get('_token'))) {
            $em->remove($marketplace);
            $em->flush();
            $this->addFlash('success', 'Offre supprimée avec succès !');
        }
        
        return $this->redirectToRoute('admin_marketplace_index');
    }
    
    #[Route('/{id}', name: 'admin_marketplace_show', methods: ['GET'])]
    public function show(Marketplace $marketplace): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/marketplace/show.html.twig', [
            'marketplace' => $marketplace,
        ]);
    }
}