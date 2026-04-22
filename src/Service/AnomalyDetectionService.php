<?php

namespace App\Service;

use App\Repository\DepenseRepository;

class AnomalyDetectionService
{
    private $depenseRepo;
    
    public function __construct(DepenseRepository $depenseRepo)
    {
        $this->depenseRepo = $depenseRepo;
    }
    
    /**
     * Vérifie si une dépense est anormale
     * @param string $type - Type de dépense
     * @param float $montant - Montant de la dépense
     * @param int|null $userId - ID de l'utilisateur (peut être null)
     */
    public function isAnomaly(string $type, float $montant, ?int $userId = null): array
    {
        // Si pas d'userId, on ne peut pas calculer la moyenne
        if (!$userId) {
            return ['is_anomaly' => false, 'message' => 'Utilisateur non identifié'];
        }
        
        $moyenne = $this->depenseRepo->getAverageForTypeLast3Months($type, $userId);
        
        if ($moyenne <= 0) {
            return ['is_anomaly' => false, 'message' => 'Pas assez de données historiques'];
        }
        
        $seuil = $moyenne * 1.5;
        
        if ($montant > $seuil) {
            $pourcentage = round((($montant - $moyenne) / $moyenne) * 100, 1);
            
            $niveau = 'moyen';
            if ($pourcentage >= 100) $niveau = 'critique';
            elseif ($pourcentage >= 75) $niveau = 'eleve';
            
            $advice = match($niveau) {
                'critique' => "⚠️ URGENT : Vérifiez immédiatement cette dépense",
                'eleve' => "🔍 Comparez avec d'autres fournisseurs",
                default => "📊 Essayez de réduire ce poste",
            };
            
            return [
                'is_anomaly' => true,
                'moyenne' => round($moyenne, 2),
                'seuil' => round($seuil, 2),
                'pourcentage' => $pourcentage,
                'niveau' => $niveau,
                'advice' => $advice,
            ];
        }
        
        return ['is_anomaly' => false];
    }
}
