<?php
// src/Controller/Front/MarketplaceController.php

namespace App\Controller\Front;

use App\Repository\MarketplaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/marketplace')]
class MarketplaceController extends AbstractController
{
    #[Route('/', name: 'front_marketplace_index', methods: ['GET'])]
    public function index(Request $request, MarketplaceRepository $repository): Response
    {
        $search = $request->query->get('search', '');
        $statut = $request->query->get('statut', 'all');
        
        $marketplaces = $repository->search($search, $statut);
        $stats = $repository->getStatistics();
        
        return $this->render('front/marketplace/index.html.twig', [
            'marketplaces' => $marketplaces,
            'stats' => $stats,
            'search' => $search,
            'selectedStatut' => $statut,
        ]);
    }
    
    #[Route('/{id}', name: 'front_marketplace_show', methods: ['GET'])]
    public function show(int $id, MarketplaceRepository $repository): Response
    {
        $marketplace = $repository->find($id);
        
        if (!$marketplace) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
        
        return $this->render('front/marketplace/show.html.twig', [
            'marketplace' => $marketplace,
        ]);
    }
}