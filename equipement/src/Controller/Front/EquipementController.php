<?php
// src/Controller/Front/EquipementController.php

namespace App\Controller\Front;

use App\Repository\EquipementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/equipements')]
class EquipementController extends AbstractController
{
    #[Route('/', name: 'front_equipement_index', methods: ['GET'])]
    public function index(Request $request, EquipementRepository $repository): Response
    {
        $keyword = $request->query->get('search', '');
        $type = $request->query->get('type', 'all');
        $etat = $request->query->get('etat', 'all');
        
        // Recherche avec filtres
        if (!empty($keyword) || ($type !== 'all') || ($etat !== 'all')) {
            $equipements = $repository->search($keyword, $type, $etat);
        } else {
            $equipements = $repository->findAll();
        }
        
        $stats = $repository->getStatistics();
        $types = $repository->getUniqueTypes();
        
        return $this->render('front/equipement/index.html.twig', [
            'equipements' => $equipements,
            'stats' => $stats,
            'types' => $types,
            'search' => $keyword,
            'selectedType' => $type,
            'selectedEtat' => $etat,
        ]);
    }
    
    #[Route('/{id}', name: 'front_equipement_show', methods: ['GET'])]
    public function show(int $id, EquipementRepository $repository): Response
    {
        $equipement = $repository->find($id);
        
        if (!$equipement) {
            throw $this->createNotFoundException('Équipement non trouvé');
        }
        
        return $this->render('front/equipement/show.html.twig', [
            'equipement' => $equipement,
        ]);
    }
}