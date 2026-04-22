<?php

namespace App\Service;

use App\Entity\Equipement;
use App\Repository\MaintenanceRepository;
use App\Repository\EquipementRepository;
use Doctrine\ORM\EntityManagerInterface;

class PredictiveMaintenanceService
{
    private const RISK_WEIGHTS = [
        'age' => 0.1,
        'pannes' => 15,
        'correctives' => 10,
        'daysSinceMaintenance' => 0.05,
        'typeFactor' => [
            'Tracteur' => 1.2,
            'Moissonneuse' => 1.5,
            'Pulvérisateur' => 1.0,
            'Charrue' => 0.8,
            'Semoir' => 0.9,
            'Autre' => 1.0
        ]
    ];

    private const RISK_THRESHOLDS = [
        'low' => 30,
        'medium' => 60,
        'high' => 100
    ];

    private const LIFE_EXPECTANCY_YEARS = [
        'Tracteur' => 15,
        'Moissonneuse' => 12,
        'Pulvérisateur' => 10,
        'Charrue' => 20,
        'Semoir' => 12,
        'Autre' => 10
    ];

    private const RECOMMENDATIONS = [
        'low' => [
            'Continuer la maintenance préventive régulière',
            'Surveiller les indicateurs de performance',
            'Planifier inspection dans 90 jours'
        ],
        'medium' => [
            'Accélérer les inspections préventives',
            'Vérifier les pièces d\'usure',
            'Planifier maintenance dans les 30 jours',
            'Augmenter la fréquence des contrôles'
        ],
        'high' => [
            'Intervention urgente recommandée',
            'Remplacement de pièces critiques à prévoir',
            'Maintenance immédiate fortement conseillée',
            'Évaluer le coût de remplacement vs réparation'
        ]
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private MaintenanceRepository $maintenanceRepo,
        private EquipementRepository $equipementRepo
    ) {}

    public function calculateRiskScore(Equipement $equipement): array
    {
        $ageInMonths = $this->getEquipmentAgeInMonths($equipement);
        $pannesCount = $this->getPannesCount($equipement);
        $correctivesCount = $this->getCorrectivesCount($equipement);
        $daysSinceLastMaintenance = $this->getDaysSinceLastMaintenance($equipement);
        $typeFactor = $this->getTypeFactor($equipement);

        $baseScore = (
            ($ageInMonths * self::RISK_WEIGHTS['age']) +
            ($pannesCount * self::RISK_WEIGHTS['pannes']) +
            ($correctivesCount * self::RISK_WEIGHTS['correctives']) +
            ($daysSinceLastMaintenance * self::RISK_WEIGHTS['daysSinceMaintenance'])
        ) * $typeFactor;

        $score = min(100, max(0, round($baseScore)));
        $level = $this->getRiskLevel($score);
        $predictedDaysToFailure = $this->predictDaysToFailure($score, $equipement);
        $recommendations = $this->getRecommendations($level);

        return [
            'equipment_id' => $equipement->getId(),
            'equipment_name' => $equipement->getNom(),
            'equipment_type' => $equipement->getType(),
            'equipment_state' => $equipement->getEtat(),
            'score' => $score,
            'level' => $level,
            'confidence' => $this->calculateConfidence($equipement),
            'factors' => [
                'age_months' => $ageInMonths,
                'pannes_count' => $pannesCount,
                'correctives_count' => $correctivesCount,
                'days_since_maintenance' => $daysSinceLastMaintenance,
                'type_factor' => $typeFactor
            ],
            'predicted_days_to_failure' => $predictedDaysToFailure,
            'predicted_failure_date' => $predictedDaysToFailure > 0 
                ? (new \DateTime())->modify("+{$predictedDaysToFailure} days")->format('Y-m-d')
                : null,
            'recommendations' => $recommendations,
            'urgency' => $this->getUrgency($level),
            'estimated_cost' => $this->estimateMaintenanceCost($equipement, $level)
        ];
    }

    public function getAllEquipmentRiskScores(): array
    {
        $equipments = $this->equipementRepo->findAll();
        $scores = [];

        foreach ($equipments as $equipment) {
            $scores[] = $this->calculateRiskScore($equipment);
        }

        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        return $scores;
    }

    public function getRiskDistribution(): array
    {
        $scores = $this->getAllEquipmentRiskScores();

        $distribution = [
            'low' => 0,
            'medium' => 0,
            'high' => 0
        ];

        foreach ($scores as $score) {
            $distribution[$score['level']]++;
        }

        return [
            'labels' => ['Risque Faible', 'Risque Moyen', 'Risque Élevé'],
            'data' => [$distribution['low'], $distribution['medium'], $distribution['high']],
            'colors' => ['#10b981', '#f59e0b', '#ef4444']
        ];
    }

    public function getPredictionsTimeline(int $days = 30): array
    {
        $scores = $this->getAllEquipmentRiskScores();
        $timeline = [];

        for ($i = 0; $i <= $days; $i++) {
            $date = (new \DateTime())->modify("+{$i} days")->format('Y-m-d');
            $predictionsForDay = [];

            foreach ($scores as $score) {
                if ($score['predicted_days_to_failure'] !== null 
                    && $score['predicted_days_to_failure'] <= $i 
                    && $score['level'] === 'high') {
                    $predictionsForDay[] = [
                        'name' => $score['equipment_name'],
                        'type' => $score['equipment_type'],
                        'score' => $score['score']
                    ];
                }
            }

            if (!empty($predictionsForDay)) {
                $timeline[] = [
                    'date' => $date,
                    'day' => $i,
                    'predictions' => $predictionsForDay,
                    'count' => count($predictionsForDay)
                ];
            }
        }

        return $timeline;
    }

    public function getDashboardSummary(): array
    {
        $scores = $this->getAllEquipmentRiskScores();
        $totalEquipment = count($scores);
        
        $highRisk = array_filter($scores, fn($s) => $s['level'] === 'high');
        $mediumRisk = array_filter($scores, fn($s) => $s['level'] === 'medium');
        $lowRisk = array_filter($scores, fn($s) => $s['level'] === 'low');

        $avgScore = $totalEquipment > 0 
            ? round(array_sum(array_column($scores, 'score')) / $totalEquipment, 1)
            : 0;

        $totalEstimatedCost = array_sum(array_column($scores, 'estimated_cost'));

        return [
            'total_equipment' => $totalEquipment,
            'high_risk_count' => count($highRisk),
            'medium_risk_count' => count($mediumRisk),
            'low_risk_count' => count($lowRisk),
            'average_score' => $avgScore,
            'total_estimated_cost' => $totalEstimatedCost,
            'high_risk_percentage' => $totalEquipment > 0 
                ? round((count($highRisk) / $totalEquipment) * 100, 1) 
                : 0,
            'immediate_actions_needed' => count($highRisk),
            'top_risk_equipment' => array_slice($scores, 0, 5)
        ];
    }

    private function getEquipmentAgeInMonths(Equipement $equipment): int
    {
        $dateAchat = $equipment->getDateAchat();
        if (!$dateAchat) {
            return 0;
        }
        
        $now = new \DateTime();
        $diff = $now->diff($dateAchat);
        return ($diff->y * 12) + $diff->m;
    }

    private function getPannesCount(Equipement $equipment): int
    {
        return $this->maintenanceRepo->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.equipement = :equipment')
            ->andWhere('m.typeMaintenance = :type')
            ->andWhere('m.statut = :statut')
            ->setParameter('equipment', $equipment)
            ->setParameter('type', 'Corrective')
            ->setParameter('statut', 'Réalisée')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getCorrectivesCount(Equipement $equipment): int
    {
        return $this->maintenanceRepo->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.equipement = :equipment')
            ->andWhere('m.typeMaintenance = :type')
            ->setParameter('equipment', $equipment)
            ->setParameter('type', 'Corrective')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getDaysSinceLastMaintenance(Equipement $equipment): int
    {
        $lastMaintenance = $this->maintenanceRepo->createQueryBuilder('m')
            ->where('m.equipement = :equipment')
            ->andWhere('m.statut = :statut')
            ->setParameter('equipment', $equipment)
            ->setParameter('statut', 'Réalisée')
            ->orderBy('m.dateMaintenance', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastMaintenance) {
            $dateAchat = $equipment->getDateAchat();
            if ($dateAchat) {
                return (new \DateTime())->diff($dateAchat)->days;
            }
            return 365;
        }

        return (new \DateTime())->diff($lastMaintenance->getDateMaintenance())->days;
    }

    private function getTypeFactor(Equipement $equipment): float
    {
        $type = $equipment->getType();
        return self::RISK_WEIGHTS['typeFactor'][$type] ?? 1.0;
    }

    private function getRiskLevel(int $score): string
    {
        if ($score <= self::RISK_THRESHOLDS['low']) {
            return 'low';
        }
        if ($score <= self::RISK_THRESHOLDS['medium']) {
            return 'medium';
        }
        return 'high';
    }

    private function predictDaysToFailure(int $score, Equipement $equipment): int
    {
        if ($score >= 80) {
            return max(1, 7 - (int)($score / 15));
        }
        if ($score >= 60) {
            return max(7, 30 - (int)($score / 3));
        }
        if ($score >= 40) {
            return max(30, 60 - (int)($score / 2));
        }

        $expectedLife = self::LIFE_EXPECTANCY_YEARS[$equipment->getType()] ?? 10;
        $ageInYears = $this->getEquipmentAgeInMonths($equipment) / 12;
        $remainingLife = max(0, $expectedLife - $ageInYears);

        return (int)($remainingLife * 365 * (1 - $score / 100));
    }

    private function getRecommendations(string $level): array
    {
        return self::RECOMMENDATIONS[$level] ?? [];
    }

    private function getUrgency(string $level): string
    {
        return match($level) {
            'high' => 'URGENT',
            'medium' => 'MODÉRÉ',
            'low' => 'NORMAL',
            default => 'INCONNU'
        };
    }

    private function calculateConfidence(Equipement $equipment): int
    {
        $confidence = 50;

        if ($equipment->getDateAchat()) {
            $confidence += 15;
        }

        $maintenanceCount = $this->maintenanceRepo->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.equipement = :equipment')
            ->setParameter('equipment', $equipment)
            ->getQuery()
            ->getSingleScalarResult();

        $confidence += min(25, $maintenanceCount * 5);

        if ($equipment->getDureeVieEstimee()) {
            $confidence += 10;
        }

        return min(100, $confidence);
    }

    private function estimateMaintenanceCost(Equipement $equipment, string $level): float
    {
        $baseCosts = [
            'Tracteur' => ['low' => 500, 'medium' => 2000, 'high' => 8000],
            'Moissonneuse' => ['low' => 800, 'medium' => 3500, 'high' => 15000],
            'Pulvérisateur' => ['low' => 300, 'medium' => 1200, 'high' => 5000],
            'Charrue' => ['low' => 200, 'medium' => 800, 'high' => 3000],
            'Semoir' => ['low' => 400, 'medium' => 1500, 'high' => 6000],
            'Autre' => ['low' => 200, 'medium' => 800, 'high' => 3000]
        ];

        $type = $equipment->getType();
        $costs = $baseCosts[$type] ?? $baseCosts['Autre'];

        return $costs[$level] ?? $costs['medium'];
    }
}
