<?php
// src/Controller/Front/StockController.php

namespace App\Controller\Front;

use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/stocks')]
class StockController extends AbstractController
{
    #[Route('/', name: 'front_stock_index', methods: ['GET'])]
    public function index(Request $request, StockRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', 'all');
        
        $stocks = $repository->search($search, $type);
        $stats = $repository->getStatistics();
        $types = $repository->getUniqueTypes();
        
        return $this->render('front/stock/index.html.twig', [
            'stocks' => $stocks,
            'stats' => $stats,
            'types' => $types,
            'search' => $search,
            'selectedType' => $type,
        ]);
    }
    
    #[Route('/{id}', name: 'front_stock_show', methods: ['GET'])]
    public function show(int $id, StockRepository $repository): Response
    {
        $stock = $repository->find($id);
        
        if (!$stock) {
            throw $this->createNotFoundException('Stock non trouvé');
        }
        
        return $this->render('front/stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }
}