<?php
// src/Service/CarboneService.php

namespace App\Service;

use App\Entity\Equipement;
use App\Repository\MaintenanceRepository;
use App\Repository\EquipementRepository;

class CarboneService
{
    private $maintenanceRepository;
    private $equipementRepository;
    
    // Facteurs d'émission en kg CO2 par heure d'utilisation
    private const FACTEURS_EMISSION = [
        'Tracteur' => 25.5,
        'Moissonneuse' => 32.0,
        'Pulvérisateur' => 15.2,
        'Charrue' => 18.7,
        'Semoir' => 12.5,
        'Autre' => 20.0
    ];

    public function __construct(
        MaintenanceRepository $maintenanceRepository,
        EquipementRepository $equipementRepository
    ) {
        $this->maintenanceRepository = $maintenanceRepository;
        $this->equipementRepository = $equipementRepository;
    }

    public function getEstimationCO2(): array
    {
        $equipements = $this->equipementRepository->findAll();
        $maintenances = $this->maintenanceRepository->findAll();
        
        if (empty($equipements)) {
            return [
                'total' => 0,
                'mois' => 0,
                'moyenne' => 0,
                'details' => [],
                'recommandations' => ['📌 Ajoutez des équipements pour voir leur empreinte carbone']
            ];
        }
        
        $totalCO2 = 0;
        $details = [];
        
        foreach ($equipements as $e) {
            $facteur = self::FACTEURS_EMISSION[$e->getType()] ?? 20.0;
            
            // Compter les maintenances pour cet équipement
            $maintenancesEquipement = array_filter($maintenances, function($m) use ($e) {
                $equipement = $m->getEquipement();
                return $equipement && $equipement->getId() === $e->getId();
            });
            $nbMaintenances = count($maintenancesEquipement);
            
            // Calcul de l'âge en années
            $dateAchat = $e->getDateAchat();
            if ($dateAchat) {
                $aujourdhui = new \DateTime();
                $age = $aujourdhui->diff($dateAchat)->y;
                $mois = $aujourdhui->diff($dateAchat)->m;
                $agePrecis = $age + ($mois / 12);
            } else {
                $agePrecis = 1; // Valeur par défaut si pas de date d'achat
            }
            
            // Calcul des heures d'utilisation estimées
            // Base: 200 heures par an d'utilisation + 50 heures par maintenance
            $heuresUtilisation = ($agePrecis * 200) + ($nbMaintenances * 50);
            
            // Valeur minimale pour les équipements récents
            if ($heuresUtilisation < 50) {
                $heuresUtilisation = 50;
            }
            
            // Calcul du CO2
            $co2Equipement = $heuresUtilisation * $facteur;
            $totalCO2 += $co2Equipement;
            
            // Déterminer le niveau
            $niveau = $this->getNiveauCO2($co2Equipement);
            
            $details[] = [
                'id' => $e->getId(),
                'nom' => $e->getNom(),
                'type' => $e->getType(),
                'co2' => round($co2Equipement),
                'heures' => round($heuresUtilisation),
                'facteur' => $facteur,
                'age' => round($agePrecis, 1),
                'nb_maintenances' => $nbMaintenances,
                'niveau' => $niveau
            ];
        }
        
        // Trier par CO2 décroissant
        usort($details, fn($a, $b) => $b['co2'] <=> $a['co2']);
        
        // Calculs mensuels
        $totalMois = $totalCO2 / 12;
        
        return [
            'total' => round($totalCO2),
            'mois' => round($totalMois),
            'moyenne' => round($totalCO2 / max(1, count($equipements))),
            'details' => $details,
            'recommandations' => $this->getRecommandations($totalCO2)
        ];
    }
    
    public function getCO2ByEquipement(Equipement $equipement): array
    {
        // Récupérer les maintenances de cet équipement
        $maintenances = $this->maintenanceRepository->findBy(['equipement' => $equipement]);
        $facteur = self::FACTEURS_EMISSION[$equipement->getType()] ?? 20.0;
        $nbMaintenances = count($maintenances);
        
        // Calcul de l'âge précis
        $dateAchat = $equipement->getDateAchat();
        if ($dateAchat) {
            $aujourdhui = new \DateTime();
            $age = $aujourdhui->diff($dateAchat)->y;
            $mois = $aujourdhui->diff($dateAchat)->m;
            $agePrecis = $age + ($mois / 12);
        } else {
            $agePrecis = 1;
        }
        
        // Calcul des heures d'utilisation
        $heuresUtilisation = ($agePrecis * 200) + ($nbMaintenances * 50);
        
        // Valeur minimale
        if ($heuresUtilisation < 50) {
            $heuresUtilisation = 50;
        }
        
        // Calcul du CO2
        $co2Total = $heuresUtilisation * $facteur;
        
        // Déterminer le niveau simple (low, medium, high)
        $level = 'low';
        if ($co2Total > 10000) {
            $level = 'high';
        } elseif ($co2Total > 5000) {
            $level = 'medium';
        }
        
        return [
            'total' => round($co2Total),
            'monthly' => round($co2Total / 12),
            'facteur' => $facteur,
            'heures' => round($heuresUtilisation),
            'age' => round($agePrecis, 1),
            'nb_maintenances' => $nbMaintenances,
            'niveau' => $this->getNiveauCO2($co2Total),
            'level' => $level
        ];
    }
    
    public function getAllEquipementsCO2(): array
    {
        return $this->equipementRepository->findAll();
    }
    
    private function getNiveauCO2(float $co2): array
    {
        if ($co2 > 50000) {
            return ['texte' => 'Très élevé', 'couleur' => '#dc2626', 'icone' => '🔴'];
        } elseif ($co2 > 25000) {
            return ['texte' => 'Élevé', 'couleur' => '#f59e0b', 'icone' => '🟠'];
        } elseif ($co2 > 10000) {
            return ['texte' => 'Modéré', 'couleur' => '#eab308', 'icone' => '🟡'];
        } elseif ($co2 > 5000) {
            return ['texte' => 'Faible', 'couleur' => '#10b981', 'icone' => '🟢'];
        } elseif ($co2 > 1000) {
            return ['texte' => 'Très faible', 'couleur' => '#22c55e', 'icone' => '✅'];
        }
        return ['texte' => 'Minime', 'couleur' => '#86efac', 'icone' => '🌱'];
    }
    
    private function getRecommandations(float $totalCO2): array
    {
        if ($totalCO2 > 50000) {
            return [
                '🔴 Niveau critique - Réduction immédiate nécessaire',
                '📊 Planifiez des maintenances préventives régulières',
                '🔄 Optimisez les cycles de travail',
                '⏰ Évitez les heures de pointe'
            ];
        } elseif ($totalCO2 > 25000) {
            return [
                '🟠 Niveau élevé - Surveillance nécessaire',
                '🔧 Vérifiez l\'état des moteurs',
                '📈 Suivez la consommation par équipement'
            ];
        } elseif ($totalCO2 > 10000) {
            return [
                '🟡 Niveau modéré - Peut être amélioré',
                '📊 Comparez la consommation entre équipements',
                '🔍 Identifiez les équipements les plus énergivores'
            ];
        } elseif ($totalCO2 > 0) {
            return [
                '🟢 Bon niveau - Continuez vos efforts',
                '📈 Maintenez cette tendance positive',
                '♻️ Pensez aux énergies renouvelables'
            ];
        }
        return [
            '📌 Ajoutez des équipements pour calculer votre empreinte carbone',
            '🔧 Enregistrez les maintenances pour un calcul plus précis'
        ];
    }
}
