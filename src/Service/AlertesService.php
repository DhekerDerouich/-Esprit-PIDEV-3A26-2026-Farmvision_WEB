<?php
namespace App\Service;

use App\Entity\Equipement;
use App\Entity\Maintenance;

class AlertesService
{
    public function getToutesLesAlertes(array $equipements, array $maintenances): array
    {
        $alertes = [];
        $today = new \DateTime();
        
        // Alertes maintenances proches
        foreach ($maintenances as $m) {
            if ($m->getStatut() === 'Planifiée' && $m->getDateMaintenance() >= $today) {
                $joursRestants = $today->diff($m->getDateMaintenance())->days;
                
                if ($joursRestants <= 7) {
                    $type = $joursRestants <= 2 ? 'URGENT' : ($joursRestants <= 4 ? 'WARNING' : 'INFO');
                    $alertes[] = [
                        'id' => 'maint_' . $m->getId(),
                        'titre' => 'Maintenance imminente',
                        'message' => sprintf('%s - Maintenance %s dans %d jours', 
                            $m->getEquipement()->getNom(), 
                            $m->getTypeMaintenance(), 
                            $joursRestants),
                        'type' => $type,
                        'categorie' => 'MAINTENANCE',
                        'date' => $m->getDateMaintenance(),
                        'priorite' => $type === 'URGENT' ? 1 : ($type === 'WARNING' ? 2 : 3),
                        'icone' => '🔧',
                        'couleur' => $type === 'URGENT' ? '#ffebee' : ($type === 'WARNING' ? '#fff3e0' : '#e8f5e8')
                    ];
                }
            }
        }
        
        // Alertes maintenances dépassées
        foreach ($maintenances as $m) {
            if ($m->getStatut() === 'Planifiée' && $m->getDateMaintenance() < $today) {
                $joursDepasses = $m->getDateMaintenance()->diff($today)->days;
                $alertes[] = [
                    'id' => 'maint_dep_' . $m->getId(),
                    'titre' => 'Maintenance dépassée',
                    'message' => sprintf('%s - Maintenance dépassée de %d jours', 
                        $m->getEquipement()->getNom(), $joursDepasses),
                    'type' => $joursDepasses > 3 ? 'URGENT' : 'WARNING',
                    'categorie' => 'RETARD',
                    'date' => $m->getDateMaintenance(),
                    'priorite' => $joursDepasses > 3 ? 1 : 2,
                    'icone' => '⏰',
                    'couleur' => $joursDepasses > 3 ? '#ffebee' : '#fff3e0'
                ];
            }
        }
        
        // Alertes garanties expirées
        foreach ($equipements as $e) {
            if ($e->getDateAchat() && !$e->isSousGarantie() && $e->getAge() > 0) {
                $alertes[] = [
                    'id' => 'garantie_' . $e->getId(),
                    'titre' => 'Garantie expirée',
                    'message' => sprintf('%s - Garantie expirée depuis %d mois', 
                        $e->getNom(), $e->getAge() * 12),
                    'type' => 'WARNING',
                    'categorie' => 'GARANTIE',
                    'date' => $e->getDateAchat(),
                    'priorite' => 2,
                    'icone' => '🛡️',
                    'couleur' => '#fff3e0'
                ];
            }
        }
        
        // Alertes équipements en panne
        foreach ($equipements as $e) {
            if ($e->getEtat() === 'En panne') {
                $alertes[] = [
                    'id' => 'panne_' . $e->getId(),
                    'titre' => 'Équipement en panne',
                    'message' => sprintf('%s est actuellement en panne', $e->getNom()),
                    'type' => 'URGENT',
                    'categorie' => 'PANNE',
                    'date' => new \DateTime(),
                    'priorite' => 1,
                    'icone' => '🔴',
                    'couleur' => '#ffebee'
                ];
            }
        }
        
        // Trier par priorité
        usort($alertes, fn($a, $b) => $a['priorite'] <=> $b['priorite']);
        
        return $alertes;
    }
    
    public function getStatistiquesAlertes(array $equipements, array $maintenances): array
    {
        $alertes = $this->getToutesLesAlertes($equipements, $maintenances);
        
        return [
            'total' => count($alertes),
            'urgentes' => count(array_filter($alertes, fn($a) => $a['type'] === 'URGENT')),
            'warnings' => count(array_filter($alertes, fn($a) => $a['type'] === 'WARNING')),
            'infos' => count(array_filter($alertes, fn($a) => $a['type'] === 'INFO')),
            'maintenances' => count(array_filter($alertes, fn($a) => $a['categorie'] === 'MAINTENANCE')),
            'garanties' => count(array_filter($alertes, fn($a) => $a['categorie'] === 'GARANTIE')),
            'pannes' => count(array_filter($alertes, fn($a) => $a['categorie'] === 'PANNE')),
            'retards' => count(array_filter($alertes, fn($a) => $a['categorie'] === 'RETARD')),
        ];
    }
}