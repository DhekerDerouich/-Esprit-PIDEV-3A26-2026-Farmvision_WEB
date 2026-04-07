<?php
namespace App\Controller\Front;

use App\Repository\EquipementRepository;
use App\Repository\MaintenanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'front_home')]
    public function index(EquipementRepository $equipementRepo, MaintenanceRepository $maintenanceRepo): Response
    {
        $equipements = $equipementRepo->findBy([], ['id' => 'DESC'], 6);
        $maintenances = $maintenanceRepo->findUpcoming(5);

        $statsEquipements = $equipementRepo->getStatistics();

        return $this->render('front/home/index.html.twig', [
            'equipements' => $equipements,
            'maintenances' => $maintenances,
            'statsEquipements' => $statsEquipements,
        ]);
    }
    #[Route('/about', name: 'front_about')]
    public function about(): Response
    {
        return $this->render('front/about/index.html.twig');
    }
}