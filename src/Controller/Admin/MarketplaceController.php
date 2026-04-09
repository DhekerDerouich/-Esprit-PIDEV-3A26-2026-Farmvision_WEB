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
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function new(Request $request, EntityManagerInterface $em, StockRepository $stockRepo, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $stocks = $stockRepo->findAvailableForMarketplace();
        
        if ($request->isMethod('POST')) {
            $stockId = $request->request->get('stock_id');
            $stock = $stockRepo->find($stockId);
            
            if ($stock) {
                $marketplace = new Marketplace();
                $marketplace->setStock($stock);
                $marketplace->setPrixUnitaire((float)$request->request->get('prix_unitaire'));
                $marketplace->setQuantiteEnVente((float)$request->request->get('quantite_en_vente'));
                $marketplace->setDescription($request->request->get('description'));
                $marketplace->setStatut('En vente');
                
                // Validation
                $errors = $validator->validate($marketplace);
                
                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                    return $this->render('admin/marketplace/new.html.twig', ['stocks' => $stocks]);
                }
                
                $em->persist($marketplace);
                $em->flush();
                
                $this->addFlash('success', 'Produit mis en vente avec succès !');
                return $this->redirectToRoute('admin_marketplace_index');
            }
        }
        
        return $this->render('admin/marketplace/new.html.twig', [
            'stocks' => $stocks,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_marketplace_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Marketplace $marketplace, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($request->isMethod('POST')) {
            $marketplace->setPrixUnitaire((float)$request->request->get('prix_unitaire'));
            $marketplace->setQuantiteEnVente((float)$request->request->get('quantite_en_vente'));
            $marketplace->setDescription($request->request->get('description'));
            $marketplace->setStatut($request->request->get('statut'));
            
            // Validation
            $errors = $validator->validate($marketplace);
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('admin/marketplace/edit.html.twig', ['marketplace' => $marketplace]);
            }
            
            $em->flush();
            $this->addFlash('success', 'Offre modifiée avec succès !');
            return $this->redirectToRoute('admin_marketplace_index');
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