<?php

namespace App\Service;

use App\Repository\DepenseRepository;
use App\Repository\RevenuRepository;

class AIDataService
{
    private $depenseRepo;
    private $revenuRepo;
    
    public function __construct(DepenseRepository $depenseRepo, RevenuRepository $revenuRepo)
    {
        $this->depenseRepo = $depenseRepo;
        $this->revenuRepo = $revenuRepo;
    }
    
    /**
     * Récupère les données des 3 derniers mois pour l'IA
     */
    public function getLast3MonthsData(int $userId): array
    {
        $data = [];
        $months = [];
        $revenusData = [];
        $depensesData = [];
        $soldesData = [];
        $allCategories = [];
        
        for ($i = 2; $i >= 0; $i--) {
            $date = new \DateTime("-$i months");
            $monthName = $date->format('F Y');
            $year = (int)$date->format('Y');
            $month = (int)$date->format('m');
            
            $months[] = $monthName;
            
            $revenus = $this->revenuRepo->getTotalByMonth($year, $month, $userId);
            $depenses = $this->depenseRepo->getTotalByMonth($year, $month, $userId);
            
            $revenusData[] = $revenus;
            $depensesData[] = $depenses;
            $soldesData[] = $revenus - $depenses;
            
            // Récupérer les catégories du dernier mois
            if ($i == 0) {
                $depensesLastMonth = $this->depenseRepo->findByMonth($userId, $year, $month);
                foreach ($depensesLastMonth as $depense) {
                    $type = $depense->getTypeDepense();
                    if (!isset($allCategories[$type])) {
                        $allCategories[$type] = 0;
                    }
                    $allCategories[$type] += $depense->getMontant();
                }
            }
        }
        
        return [
            'mois' => $months,
            'revenus' => $revenusData,
            'depenses' => $depensesData,
            'soldes' => $soldesData,
            'categories' => $allCategories,
        ];
    }
}