<?php
// src/Controller/Admin/DashboardController.php

namespace App\Controller\Admin;

use App\Repository\EquipementRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        EquipementRepository $equipementRepo, 
        MaintenanceRepository $maintenanceRepo,
        UtilisateurRepository $utilisateurRepo
    ): Response
    {
        $equipements = $equipementRepo->findAll();
        $maintenances = $maintenanceRepo->findAll();
        
        // Statistiques équipements
        $statsEquipements = $equipementRepo->getStatistics();
        
        // Statistiques maintenances
        $statsMaintenances = $maintenanceRepo->getStatistics();
        
        // Statistiques utilisateurs
        $statsUtilisateurs = [
            'total' => $utilisateurRepo->createQueryBuilder('u')->select('COUNT(u.id)')->getQuery()->getSingleScalarResult(),
            'admins' => $utilisateurRepo->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :role')->setParameter('role', 'ADMINISTRATEUR')->getQuery()->getSingleScalarResult(),
            'responsables' => $utilisateurRepo->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :role')->setParameter('role', 'RESPONSABLE_EXPLOITATION')->getQuery()->getSingleScalarResult(),
            'agriculteurs' => $utilisateurRepo->createQueryBuilder('u')->select('COUNT(u.id)')->where('u.type_role = :role')->setParameter('role', 'AGRICULTEUR')->getQuery()->getSingleScalarResult(),
        ];
        
        // Maintenances à venir
        $aujourdhui = new \DateTime();
        $maintenancesPlanifiees = array_filter($maintenances, fn($m) => $m->getStatut() === 'Planifiée');
        $upcoming = array_filter($maintenancesPlanifiees, fn($m) => $m->getDateMaintenance() >= $aujourdhui);
        usort($upcoming, fn($a, $b) => $a->getDateMaintenance() <=> $b->getDateMaintenance());
        $upcomingMaintenances = array_slice($upcoming, 0, 5);
        
        // Derniers équipements ajoutés
        $derniersEquipements = array_slice($equipements, -5, 5, true);
        
        return $this->render('admin/dashboard/index.html.twig', [
            'statsEquipements' => $statsEquipements,
            'statsMaintenances' => $statsMaintenances,
            'statsUtilisateurs' => $statsUtilisateurs,
            
            'upcomingMaintenances' => $upcomingMaintenances,
            'derniersEquipements' => $derniersEquipements,
        ]);
    }
}