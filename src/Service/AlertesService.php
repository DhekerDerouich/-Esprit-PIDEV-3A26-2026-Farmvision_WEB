<?php
// src/Service/AlertesService.php

namespace App\Service;

use App\Repository\MaintenanceRepository;
use App\Repository\EquipementRepository;

class AlertesService
{
    private $maintenanceRepository;
    private $equipementRepository;
    private $meteoService;

    public function __construct(
        MaintenanceRepository $maintenanceRepository,
        EquipementRepository $equipementRepository,
        ?MeteoService $meteoService = null
    ) {
        $this->maintenanceRepository = $maintenanceRepository;
        $this->equipementRepository = $equipementRepository;
        $this->meteoService = $meteoService;
    }

    /**
     * Récupérer toutes les alertes
     */
    public function getToutesLesAlertes(): array
    {
        $alertes = [];
        $today = new \DateTime();
        
        $maintenances = $this->maintenanceRepository->findAll();
        $equipements = $this->equipementRepository->findAll();
        
        // ==================== 1. ALERTES MAINTENANCES PROCHES ====================
        foreach ($maintenances as $m) {
            if ($m->getStatut() === 'Planifiée' && $m->getDateMaintenance() >= $today) {
                $joursRestants = $today->diff($m->getDateMaintenance())->days;
                
                if ($joursRestants <= 7) {
                    $type = $joursRestants <= 2 ? 'URGENT' : ($joursRestants <= 4 ? 'WARNING' : 'INFO');
                    $couleur = $this->getCouleurParType($type);
                    
                    $alertes[] = [
                        'id' => 'maint_' . $m->getId(),
                        'titre' => 'Maintenance imminente',
                        'message' => sprintf('%s - Maintenance %s dans %d jours', 
                            $m->getEquipement()->getNom(), 
                            $m->getTypeMaintenance(), 
                            $joursRestants),
                        'type' => $type,
                        'couleur' => $couleur,
                        'categorie' => 'MAINTENANCE',
                        'icone' => '🔧',
                        'priorite' => $type === 'URGENT' ? 1 : ($type === 'WARNING' ? 2 : 3),
                        'lien' => $this->getLienMaintenance($m->getId()),
                        'date' => $m->getDateMaintenance(),
                        'jours_restants' => $joursRestants
                    ];
                }
            }
        }
        
        // ==================== 2. ALERTES MAINTENANCES DÉPASSÉES ====================
        foreach ($maintenances as $m) {
            if ($m->getStatut() === 'Planifiée' && $m->getDateMaintenance() < $today) {
                $joursDepasses = $m->getDateMaintenance()->diff($today)->days;
                $type = $joursDepasses > 3 ? 'URGENT' : 'WARNING';
                $couleur = $this->getCouleurParType($type);
                
                $alertes[] = [
                    'id' => 'maint_dep_' . $m->getId(),
                    'titre' => 'Maintenance en retard',
                    'message' => sprintf('%s - Maintenance en retard de %d jours', 
                        $m->getEquipement()->getNom(), $joursDepasses),
                    'type' => $type,
                    'couleur' => $couleur,
                    'categorie' => 'RETARD',
                    'icone' => '⏰',
                    'priorite' => $type === 'URGENT' ? 1 : 2,
                    'lien' => $this->getLienMaintenance($m->getId()),
                    'date' => $m->getDateMaintenance(),
                    'jours_retard' => $joursDepasses
                ];
            }
        }
        
        // ==================== 3. ALERTES GARANTIES EXPIRÉES ====================
        foreach ($equipements as $e) {
            if ($e->getDateAchat() && !$e->isSousGarantie() && $e->getAge() > 0) {
                $moisExpire = $e->getAge() * 12;
                $type = $moisExpire > 12 ? 'WARNING' : 'INFO';
                $couleur = $this->getCouleurParType($type);
                
                $alertes[] = [
                    'id' => 'garantie_' . $e->getId(),
                    'titre' => 'Garantie expirée',
                    'message' => sprintf('%s - Garantie expirée depuis %d mois', 
                        $e->getNom(), $moisExpire),
                    'type' => $type,
                    'couleur' => $couleur,
                    'categorie' => 'GARANTIE',
                    'icone' => '🛡️',
                    'priorite' => 2,
                    'lien' => $this->getLienEquipement($e->getId()),
                    'date' => $e->getDateAchat()
                ];
            }
        }
        
        // ==================== 4. ALERTES ÉQUIPEMENTS EN PANNE ====================
        foreach ($equipements as $e) {
            if ($e->getEtat() === 'En panne') {
                $alertes[] = [
                    'id' => 'panne_' . $e->getId(),
                    'titre' => '🚨 Équipement en panne',
                    'message' => sprintf('%s est actuellement en panne - Intervention requise immédiatement', $e->getNom()),
                    'type' => 'URGENT',
                    'couleur' => '#dc2626',
                    'categorie' => 'PANNE',
                    'icone' => '🔴',
                    'priorite' => 1,
                    'lien' => $this->getLienNouvelleMaintenance($e->getId()),
                    'date' => new \DateTime()
                ];
            }
        }
        
        // ==================== 5. ALERTES ÉQUIPEMENTS EN MAINTENANCE ====================
        foreach ($equipements as $e) {
            if ($e->getEtat() === 'Maintenance') {
                $alertes[] = [
                    'id' => 'maintenance_en_cours_' . $e->getId(),
                    'titre' => 'Équipement en maintenance',
                    'message' => sprintf('%s est actuellement en maintenance', $e->getNom()),
                    'type' => 'INFO',
                    'couleur' => '#f59e0b',
                    'categorie' => 'MAINTENANCE_EN_COURS',
                    'icone' => '🔧',
                    'priorite' => 3,
                    'lien' => $this->getLienEquipement($e->getId()),
                    'date' => new \DateTime()
                ];
            }
        }
        
        // ==================== 6. ALERTES ÉQUIPEMENTS VIEILLISSANTS ====================
        foreach ($equipements as $e) {
            $dureeVie = $e->getDureeVieEstimee();
            if ($dureeVie && $e->getAge() > ($dureeVie * 0.8)) {
                $pourcentVie = ($e->getAge() / $dureeVie) * 100;
                $type = $pourcentVie > 90 ? 'WARNING' : 'INFO';
                $couleur = $this->getCouleurParType($type);
                
                $alertes[] = [
                    'id' => 'vieillissement_' . $e->getId(),
                    'titre' => '📊 Équipement vieillissant',
                    'message' => sprintf('%s a atteint %.0f%% de sa durée de vie estimée (%d/%d ans). Prévoir un remplacement.', 
                        $e->getNom(), $pourcentVie, $e->getAge(), $dureeVie),
                    'type' => $type,
                    'couleur' => $couleur,
                    'categorie' => 'PERFORMANCE',
                    'icone' => '📊',
                    'priorite' => $type === 'WARNING' ? 2 : 3,
                    'lien' => $this->getLienEquipement($e->getId()),
                    'date' => new \DateTime()
                ];
            }
        }
        
        // Trier par priorité (1 = plus urgent)
        usort($alertes, fn($a, $b) => $a['priorite'] <=> $b['priorite']);
        
        return $alertes;
    }
    
    /**
     * Obtenir les statistiques des alertes
     */
    public function getStatistiquesAlertes(): array
    {
        $alertes = $this->getToutesLesAlertes();
        
        return [
            'total' => count($alertes),
            'urgentes' => count(array_filter($alertes, fn($a) => $a['type'] === 'URGENT')),
            'warnings' => count(array_filter($alertes, fn($a) => $a['type'] === 'WARNING')),
            'infos' => count(array_filter($alertes, fn($a) => $a['type'] === 'INFO')),
        ];
    }
    
    /**
     * Récupérer les alertes urgentes uniquement
     */
    public function getAlertesUrgentes(): array
    {
        $alertes = $this->getToutesLesAlertes();
        return array_filter($alertes, fn($a) => $a['type'] === 'URGENT');
    }
    
    /**
     * Obtenir la couleur selon le type d'alerte
     */
    private function getCouleurParType(string $type): string
    {
        return match($type) {
            'URGENT' => '#dc2626',
            'WARNING' => '#f59e0b',
            default => '#10b981'
        };
    }
    
    /**
     * Lien vers la maintenance
     */
    private function getLienMaintenance(int $id): string
    {
        return '/admin/maintenances/' . $id . '/edit';
    }
    
    /**
     * Lien vers l'équipement
     */
    private function getLienEquipement(int $id): string
    {
        return '/admin/equipements/' . $id;
    }
    
    /**
     * Lien pour créer une nouvelle maintenance
     */
    private function getLienNouvelleMaintenance(int $equipementId): string
    {
        return '/admin/maintenances/new?equipement=' . $equipementId;
    }
}
