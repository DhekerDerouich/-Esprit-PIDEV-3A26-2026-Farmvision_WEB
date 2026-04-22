<?php

namespace App\Service;

use App\Repository\DepenseRepository;
use App\Repository\RevenuRepository;

class KPIService
{
    private $depenseRepo;
    private $revenuRepo;
    
    public function __construct(DepenseRepository $depenseRepo, RevenuRepository $revenuRepo)
    {
        $this->depenseRepo = $depenseRepo;
        $this->revenuRepo = $revenuRepo;
    }
    
    /**
     * Calcule tous les KPI pour un utilisateur
     */
    public function calculateKPIs(int $userId, array $depenses, array $revenus): array
    {
        $totalDepenses = array_sum(array_map(fn($d) => $d->getMontant(), $depenses));
        $totalRevenus = array_sum(array_map(fn($r) => $r->getMontant(), $revenus));
        $balance = $totalRevenus - $totalDepenses;
        
        // Marge brute
        $margeBrute = $totalRevenus - $totalDepenses;
        $margeBrutePercentage = $totalRevenus > 0 ? ($margeBrute / $totalRevenus) * 100 : 0;
        
        // Ratio dépenses/revenus
        $ratioDepensesRevenus = $totalRevenus > 0 ? ($totalDepenses / $totalRevenus) * 100 : 0;
        
        // Évolution mensuelle
        $evolution = $this->calculateMonthlyEvolution($userId);
        
        // Dépense moyenne par jour
        $depenseMoyenneJour = $this->calculateAveragePerDay($depenses);
        
        // Top catégories
        $topCategories = $this->getTopCategories($depenses);
        
        // Score de santé financière (0-100)
        $santeScore = $this->calculateHealthScore($margeBrutePercentage, $ratioDepensesRevenus, $balance);
        
        // Économies potentielles
        $economiesPotentielles = $this->calculatePotentialSavings($depenses);
        
        return [
            'marge_brute' => round($margeBrute, 2),
            'marge_brute_percentage' => round($margeBrutePercentage, 1),
            'ratio_depenses_revenus' => round($ratioDepensesRevenus, 1),
            'evolution' => $evolution,
            'depense_moyenne_jour' => round($depenseMoyenneJour, 2),
            'top_categories' => $topCategories,
            'sante_score' => $santeScore,
            'sante_niveau' => $this->getHealthLevel($santeScore),
            'economies_potentielles' => $economiesPotentielles,
            'balance' => $balance,
            'total_depenses' => $totalDepenses,
            'total_revenus' => $totalRevenus,
        ];
    }
    
    /**
     * Calcule l'évolution mensuelle
     */
    private function calculateMonthlyEvolution(int $userId): array
    {
        $now = new \DateTime();
        $currentMonth = (int)$now->format('m');
        $currentYear = (int)$now->format('Y');
        
        $lastMonth = $currentMonth - 1;
        $lastYear = $currentYear;
        if ($lastMonth == 0) {
            $lastMonth = 12;
            $lastYear--;
        }
        
        $revenusCurrent = $this->revenuRepo->getTotalByMonth($currentYear, $currentMonth, $userId);
        $revenusLast = $this->revenuRepo->getTotalByMonth($lastYear, $lastMonth, $userId);
        $depensesCurrent = $this->depenseRepo->getTotalByMonth($currentYear, $currentMonth, $userId);
        $depensesLast = $this->depenseRepo->getTotalByMonth($lastYear, $lastMonth, $userId);
        
        $revenusEvolution = $revenusLast > 0 ? (($revenusCurrent - $revenusLast) / $revenusLast) * 100 : 0;
        $depensesEvolution = $depensesLast > 0 ? (($depensesCurrent - $depensesLast) / $depensesLast) * 100 : 0;
        
        return [
            'revenus' => round($revenusEvolution, 1),
            'depenses' => round($depensesEvolution, 1),
            'revenus_positive' => $revenusEvolution >= 0,
            'depenses_positive' => $depensesEvolution <= 0,
        ];
    }
    
    /**
     * Calcule la dépense moyenne par jour
     */
    private function calculateAveragePerDay(array $depenses): float
    {
        if (count($depenses) == 0) return 0;
        
        $dates = array_map(fn($d) => $d->getDateDepense()->format('Y-m-d'), $depenses);
        $uniqueDays = count(array_unique($dates));
        
        if ($uniqueDays == 0) return 0;
        
        $total = array_sum(array_map(fn($d) => $d->getMontant(), $depenses));
        return $total / $uniqueDays;
    }
    
    /**
     * Récupère les top catégories
     */
    private function getTopCategories(array $depenses): array
    {
        $categories = [];
        foreach ($depenses as $depense) {
            $type = $depense->getTypeDepense();
            if (!isset($categories[$type])) {
                $categories[$type] = 0;
            }
            $categories[$type] += $depense->getMontant();
        }
        
        arsort($categories);
        return array_slice($categories, 0, 3, true);
    }
    
    /**
     * Calcule le score de santé financière
     */
    private function calculateHealthScore(float $margePercentage, float $ratioDepenses, float $balance): int
    {
        $score = 50; // Base
        
        // Marge bénéficiaire (max +30)
        if ($margePercentage > 30) $score += 30;
        elseif ($margePercentage > 20) $score += 25;
        elseif ($margePercentage > 10) $score += 20;
        elseif ($margePercentage > 0) $score += 15;
        elseif ($margePercentage > -10) $score -= 10;
        else $score -= 20;
        
        // Ratio dépenses/revenus (max +20)
        if ($ratioDepenses < 50) $score += 20;
        elseif ($ratioDepenses < 70) $score += 10;
        elseif ($ratioDepenses > 90) $score -= 15;
        elseif ($ratioDepenses > 100) $score -= 25;
        
        // Solde (max +10)
        if ($balance > 10000) $score += 10;
        elseif ($balance > 5000) $score += 7;
        elseif ($balance > 1000) $score += 5;
        elseif ($balance < 0) $score -= 15;
        elseif ($balance < -5000) $score -= 25;
        
        return max(0, min(100, $score));
    }
    
    private function getHealthLevel(int $score): array
    {
        if ($score >= 80) return ['text' => 'Excellente', 'color' => '#10b981', 'icon' => '🟢'];
        if ($score >= 60) return ['text' => 'Bonne', 'color' => '#52b788', 'icon' => '🟡'];
        if ($score >= 40) return ['text' => 'Moyenne', 'color' => '#f59e0b', 'icon' => '🟠'];
        return ['text' => 'Critique', 'color' => '#ef4444', 'icon' => '🔴'];
    }
    
    /**
     * Calcule les économies potentielles
     */
    private function calculatePotentialSavings(array $depenses): array
    {
        $topCategories = $this->getTopCategories($depenses);
        $savings = [];
        
        foreach ($topCategories as $category => $amount) {
            $savings[$category] = [
                'montant' => $amount,
                'suggestion' => $this->getSuggestionForCategory($category),
                'economie_possible' => round($amount * 0.15, 2), // 15% d'économie possible
            ];
        }
        
        return $savings;
    }
    
    private function getSuggestionForCategory(string $category): string
    {
        $suggestions = [
            'Carburant' => 'Utilisez des techniques d\'éco-conduite et comparez les prix des stations',
            'Réparation' => 'Faites l\'entretien préventif régulier pour éviter les grosses réparations',
            'Semences' => 'Achetez en gros avec d\'autres agriculteurs pour des meilleurs prix',
            'Engrais' => 'Optimisez les quantités via une analyse de sol',
            'Équipement' => 'Envisagez la location pour les équipements peu utilisés',
            'Main d\'œuvre' => 'Optimisez la planification des tâches',
            'Vétérinaire' => 'Prévention et vaccination régulière',
        ];
        
        return $suggestions[$category] ?? 'Analysez ce poste de dépense pour trouver des économies';
    }
    
    /**
     * Récupère les données pour les graphiques
     */
    public function getChartData(int $userId): array
    {
        $months = [];
        $depensesData = [];
        $revenusData = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("-$i months");
            $monthName = $date->format('M Y');
            $year = (int)$date->format('Y');
            $month = (int)$date->format('m');
            
            $months[] = $monthName;
            $depensesData[] = $this->depenseRepo->getTotalByMonth($year, $month, $userId);
            $revenusData[] = $this->revenuRepo->getTotalByMonth($year, $month, $userId);
        }
        
        return [
            'months' => $months,
            'depenses' => $depensesData,
            'revenus' => $revenusData,
        ];
    }
    
    /**
     * Récupère la répartition par catégorie pour les graphiques
     */
    public function getCategoryDistribution(int $userId): array
    {
        $depenses = $this->depenseRepo->findBy(['userId' => $userId]);
        $categories = [];
        
        foreach ($depenses as $depense) {
            $type = $depense->getTypeDepense();
            if (!isset($categories[$type])) {
                $categories[$type] = 0;
            }
            $categories[$type] += $depense->getMontant();
        }
        
        arsort($categories);
        return $categories;
    }
}