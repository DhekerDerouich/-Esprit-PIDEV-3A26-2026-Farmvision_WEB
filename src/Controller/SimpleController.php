<?php
namespace App\Controller;

use App\Repository\EquipementRepository;
use App\Repository\MaintenanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SimpleController extends AbstractController
{
    #[Route('/simple', name: 'simple')]
    public function index(EquipementRepository $equipementRepo, MaintenanceRepository $maintenanceRepo): Response
    {
        $equipements = $equipementRepo->findAll();
        $maintenances = $maintenanceRepo->findAll();
        
        $html = '<!DOCTYPE html>
        <html>
        <head><title>FarmVision</title>
        <style>
            body { font-family: Arial; margin: 20px; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #2d6a4f; color: white; }
            .success { color: green; }
            .warning { color: orange; }
            .danger { color: red; }
        </style>
        </head>
        <body>
        <h1>FarmVision - Gestion des équipements</h1>
        
        <h2>📊 Équipements (' . count($equipements) . ')</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Nom</th><th>Type</th><th>État</th><th>Date d\'achat</th><th>Âge</th></tr>
            </thead>
            <tbody>';
        
        foreach ($equipements as $e) {
            $dateAchat = $e->getDateAchat() ? $e->getDateAchat()->format('d/m/Y') : '-';
            $etatClass = match($e->getEtat()) {
                'Fonctionnel' => 'success',
                'En panne' => 'danger',
                default => 'warning'
            };
            $html .= sprintf('
                <tr>
                    <td>%d</td>
                    <td><strong>%s</strong></td>
                    <td>%s</td>
                    <td class="%s">%s</td>
                    <td>%s</td>
                    <td>%d ans</td>
                </tr>',
                $e->getId(),
                $e->getNom(),
                $e->getType(),
                $etatClass,
                $e->getEtat(),
                $dateAchat,
                $e->getAge()
            );
        }
        
        $html .= '</tbody>
        </table>
        
        <h2>🔧 Maintenances (' . count($maintenances) . ')</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Équipement</th><th>Type</th><th>Date</th><th>Statut</th><th>Coût</th></tr>
            </thead>
            <tbody>';
        
        foreach ($maintenances as $m) {
            $html .= sprintf('
                <tr>
                    <td>%d</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%.2f DT</td>
                </tr>',
                $m->getId(),
                $m->getEquipement()?->getNom(),
                $m->getTypeMaintenance(),
                $m->getDateMaintenance()?->format('d/m/Y'),
                $m->getStatut(),
                $m->getCoutFloat()
            );
        }
        
        $html .= '</tbody>
        </table>
        </body>
        </html>';
        
        return new Response($html);
    }
}