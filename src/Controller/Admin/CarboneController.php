<?php
// src/Controller/Admin/CarboneController.php

namespace App\Controller\Admin;

use App\Service\CarboneService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/carbone')]
class CarboneController extends AbstractController
{
    #[Route('/', name: 'admin_carbone_index')]
    public function index(CarboneService $carboneService): Response
    {
        $equipements = $carboneService->getAllEquipementsCO2();
        
        $totalCO2 = 0;
        $monthlyCO2 = 0;
        $equipementData = [];
        
        foreach ($equipements as $equipement) {
            $co2Data = $carboneService->getCO2ByEquipement($equipement);
            $totalCO2 += $co2Data['total'];
            $monthlyCO2 += $co2Data['monthly'];
            
            $equipementData[] = [
                'equipement' => $equipement,
                'co2Data' => $co2Data,
            ];
        }
        
        return $this->render('admin/carbone/index.html.twig', [
            'equipements' => $equipementData,
            'totalCO2' => $totalCO2,
            'monthlyCO2' => $monthlyCO2,
            'equipementCount' => count($equipements),
        ]);
    }
}
