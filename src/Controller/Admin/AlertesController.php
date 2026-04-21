<?php
// src/Controller/Admin/AlertesController.php

namespace App\Controller\Admin;

use App\Service\AlertesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/alertes')]
class AlertesController extends AbstractController
{
    #[Route('/notifications', name: 'admin_alertes_notifications', methods: ['GET'])]
    public function getNotifications(AlertesService $alertesService): JsonResponse
    {
        // Récupérer toutes les alertes
        $alertes = $alertesService->getToutesLesAlertes();
        
        // Transformer en format notification
        $notifications = [];
        $session = $this->container->get('request_stack')->getSession();
        $readIds = $session->get('alertes_lues', []);
        
        foreach ($alertes as $alerte) {
            $notifications[] = [
                'id' => $alerte['id'],
                'titre' => $alerte['titre'],
                'message' => $alerte['message'],
                'type' => $alerte['type'],
                'icone' => $alerte['icone'],
                'categorie' => $alerte['categorie'],
                'lien' => $alerte['lien'] ?? '#',
                'date' => isset($alerte['date']) ? $alerte['date']->format('c') : (new \DateTime())->format('c'),
                'read' => in_array($alerte['id'], $readIds)
            ];
        }
        
        return $this->json([
            'alertes' => $notifications,
            'total' => count($notifications),
            'non_lues' => count(array_filter($notifications, fn($n) => !$n['read']))
        ]);
    }
    
    #[Route('/mark-read', name: 'admin_alertes_mark_read', methods: ['POST'])]
    public function markAsRead(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $alertId = $data['id'] ?? null;
        
        if ($alertId) {
            $session = $this->container->get('request_stack')->getSession();
            $readIds = $session->get('alertes_lues', []);
            if (!in_array($alertId, $readIds)) {
                $readIds[] = $alertId;
                $session->set('alertes_lues', $readIds);
            }
        }
        
        return $this->json(['success' => true]);
    }
    
    #[Route('/mark-all-read', name: 'admin_alertes_mark_all_read', methods: ['POST'])]
    public function markAllRead(Request $request): JsonResponse
    {
        $session = $this->container->get('request_stack')->getSession();
        $alertesService = $this->container->get(AlertesService::class);
        $alertes = $alertesService->getToutesLesAlertes();
        
        $allIds = array_column($alertes, 'id');
        $session->set('alertes_lues', $allIds);
        
        return $this->json(['success' => true]);
    }
    
    #[Route('/test', name: 'admin_alertes_test', methods: ['GET'])]
    public function testNotification(): Response
    {
        // Page de test pour vérifier que le contrôleur fonctionne
        return $this->json(['message' => 'Le contrôleur Alertes fonctionne correctement']);
    }
}
