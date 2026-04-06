<?php
// src/Controller/Front/MaintenanceController.php

namespace App\Controller\Front;

use App\Repository\MaintenanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/maintenances')]
class MaintenanceController extends AbstractController
{
    #[Route('/', name: 'front_maintenance_index', methods: ['GET'])]
    public function index(Request $request, MaintenanceRepository $repository): Response
    {
        $keyword = $request->query->get('search', '');
        $type = $request->query->get('type', 'all');
        $statut = $request->query->get('statut', 'all');
        
        // Recherche avec filtres
        if (!empty($keyword) || ($type !== 'all') || ($statut !== 'all')) {
            $maintenances = $repository->search($keyword, $type, $statut);
        } else {
            $maintenances = $repository->findBy([], ['dateMaintenance' => 'ASC']);
        }
        
        $stats = $repository->getStatistics();
        
        // Ajouter les jours restants
        foreach ($maintenances as $maintenance) {
            $maintenance->joursRestants = $maintenance->getJoursRestants();
        }
        
        return $this->render('front/maintenance/index.html.twig', [
            'maintenances' => $maintenances,
            'stats' => $stats,
            'search' => $keyword,
            'selectedType' => $type,
            'selectedStatut' => $statut,
        ]);
    }
}