<?php
// src/Controller/Admin/StockController.php

namespace App\Controller\Admin;

use App\Entity\Stock;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/stocks')]
class StockController extends AbstractController
{
    #[Route('/', name: 'admin_stock_index', methods: ['GET'])]
    public function index(Request $request, StockRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', 'all');
        $statut = $request->query->get('statut', 'all');
        
        $stocks = $repository->search($search, $type, $statut);
        $stats = $repository->getStatistics();
        $types = $repository->getUniqueTypes();
        
        return $this->render('admin/stock/index.html.twig', [
            'stocks' => $stocks,
            'stats' => $stats,
            'types' => $types,
            'search' => $search,
            'selectedType' => $type,
            'selectedStatut' => $statut,
        ]);
    }
    
    #[Route('/new', name: 'admin_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($request->isMethod('POST')) {
            $stock = new Stock();
            $stock->setNomProduit($request->request->get('nom_produit'));
            $stock->setTypeProduit($request->request->get('type_produit'));
            $stock->setQuantite((float)$request->request->get('quantite'));
            $stock->setUnite($request->request->get('unite'));
            
            if ($request->request->get('date_expiration')) {
                $stock->setDateExpiration(new \DateTime($request->request->get('date_expiration')));
            }
            
            if ($request->request->get('statut')) {
                $stock->setStatut($request->request->get('statut'));
            }
            
            $em->persist($stock);
            $em->flush();
            
            $this->addFlash('success', 'Stock ajouté avec succès !');
            return $this->redirectToRoute('admin_stock_index');
        }
        
        return $this->render('admin/stock/new.html.twig');
    }
    
    #[Route('/{id}/edit', name: 'admin_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stock $stock, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($request->isMethod('POST')) {
            $stock->setNomProduit($request->request->get('nom_produit'));
            $stock->setTypeProduit($request->request->get('type_produit'));
            $stock->setQuantite((float)$request->request->get('quantite'));
            $stock->setUnite($request->request->get('unite'));
            $stock->setStatut($request->request->get('statut'));
            
            if ($request->request->get('date_expiration')) {
                $stock->setDateExpiration(new \DateTime($request->request->get('date_expiration')));
            }
            
            $em->flush();
            $this->addFlash('success', 'Stock modifié avec succès !');
            return $this->redirectToRoute('admin_stock_index');
        }
        
        return $this->render('admin/stock/edit.html.twig', [
            'stock' => $stock,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_stock_delete', methods: ['POST'])]
    public function delete(Request $request, Stock $stock, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete' . $stock->getId(), $request->request->get('_token'))) {
            $em->remove($stock);
            $em->flush();
            $this->addFlash('success', 'Stock supprimé avec succès !');
        }
        
        return $this->redirectToRoute('admin_stock_index');
    }
    
    #[Route('/{id}', name: 'admin_stock_show', methods: ['GET'])]
    public function show(Stock $stock): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }
}