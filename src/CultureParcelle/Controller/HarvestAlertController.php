<?php

namespace App\CultureParcelle\Controller;

use App\CultureParcelle\Service\HarvestAlertService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/harvest-alerts')]
class HarvestAlertController extends AbstractController
{
    public function __construct(
        private HarvestAlertService $harvestAlertService
    ) {}

    #[Route('', name: 'harvest_alerts_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;
        
        $alerts = $this->harvestAlertService->getCulturesNeedingHarvestAlert($userId);
        
        $response = array_map(function($alert) {
            $culture = $alert['culture'];
            return [
                'id' => $culture->getIdCulture(),
                'nomCulture' => $culture->getNomCulture(),
                'typeCulture' => $culture->getTypeCulture(),
                'dateRecolte' => $culture->getDateRecolte()?->format('Y-m-d'),
                'daysUntilHarvest' => $alert['daysUntilHarvest'],
                'urgency' => $alert['urgency'],
                'isToday' => $alert['isToday'],
                'seen' => $alert['seen']
            ];
        }, $alerts);
        
        // Count only unseen alerts
        $unseenCount = count(array_filter($response, fn($a) => !$a['seen']));
        
        return $this->json([
            'success' => true,
            'count' => count($response),
            'unseenCount' => $unseenCount,
            'alerts' => $response
        ]);
    }

    #[Route('/mark-seen/{cultureId}', name: 'harvest_alerts_mark_seen', methods: ['POST'])]
    public function markSeen(int $cultureId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Not authenticated'], 401);
        }
        
        $this->harvestAlertService->markAlertAsSeen($user->getId(), $cultureId);
        
        return $this->json(['success' => true]);
    }

    #[Route('/mark-all-seen', name: 'harvest_alerts_mark_all_seen', methods: ['POST'])]
    public function markAllSeen(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Not authenticated'], 401);
        }
        
        $this->harvestAlertService->markAllAsSeen($user->getId());
        
        return $this->json(['success' => true]);
    }

    #[Route('/clear-seen', name: 'harvest_alerts_clear_seen', methods: ['POST'])]
    public function clearSeen(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Not authenticated'], 401);
        }
        
        $this->harvestAlertService->clearSeenAlerts($user->getId());
        
        return $this->json(['success' => true]);
    }

    #[Route('/check', name: 'harvest_alerts_check', methods: ['POST'])]
    public function checkAndNotify(): JsonResponse
    {
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;
        
        $alerts = $this->harvestAlertService->getCulturesNeedingHarvestAlert($userId);
        $sentCount = 0;
        
        foreach ($alerts as $alertData) {
            $sent = $this->harvestAlertService->sendHarvestAlert(
                $alertData['culture'],
                $alertData['daysUntilHarvest']
            );
            if ($sent) {
                $sentCount++;
            }
        }
        
        return $this->json([
            'success' => true,
            'totalAlerts' => count($alerts),
            'sentNotifications' => $sentCount
        ]);
    }
}
