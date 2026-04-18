<?php
namespace App\Controller\Admin;

use App\Repository\EquipementRepository;
use App\Repository\MaintenanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin-simple')]
class SimpleDashboardController extends AbstractController
{
    #[Route('/', name: 'admin_simple')]
    public function index(EquipementRepository $equipementRepo, MaintenanceRepository $maintenanceRepo): Response
    {
        $equipements = $equipementRepo->findAll();
        $maintenances = $maintenanceRepo->findAll();
        
        $stats = [
            'totalEquipements' => count($equipements),
            'fonctionnels' => count(array_filter($equipements, fn($e) => $e->getEtat() === 'Fonctionnel')),
            'maintenancesPlanifiees' => count(array_filter($maintenances, fn($m) => $m->getStatut() === 'Planifiée')),
        ];
        
        return $this->json($stats);
    }
}