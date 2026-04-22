<?php
// src/Controller/Front/CarboneController.php

namespace App\Controller\Front;

use App\Service\CarboneService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/carbone')]
class CarboneController extends AbstractController
{
    #[Route('/', name: 'front_carbone_index')]
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
        
        return $this->render('front/carbone/index.html.twig', [
            'equipements' => $equipementData,
            'totalCO2' => $totalCO2,
            'monthlyCO2' => $monthlyCO2,
            'equipementCount' => count($equipements),
        ]);
    }
}
