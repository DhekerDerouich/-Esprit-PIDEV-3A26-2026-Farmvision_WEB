<?php

namespace App\CultureParcelle\Service;

use App\Entity\Culture;
use App\CultureParcelle\Repository\CultureRepository;

class HarvestAlertService
{
    private $websocketUrl = 'ws://localhost:8080';

    public function __construct(
        private CultureRepository $cultureRepository,
        private \Doctrine\ORM\EntityManagerInterface $em
    ) {}

    /**
     * Check for cultures that need harvest alerts
     * Returns cultures that are within alert threshold (e.g., 7 days before harvest)
     */
    public function getCulturesNeedingHarvestAlert(?int $userId = null, int $daysThreshold = 7): array
    {
        $now = new \DateTime();
        $alertDate = (clone $now)->modify("+{$daysThreshold} days");
        
        $cultures = $this->cultureRepository->findAll();
        $alertCultures = [];
        
        // Get seen alerts for this user
        $seenAlerts = [];
        if ($userId) {
            $seenRecords = $this->em->getRepository(\App\Entity\HarvestAlertSeen::class)
                ->findBy(['userId' => $userId]);
            foreach ($seenRecords as $record) {
                $seenAlerts[$record->getCultureId()] = true;
            }
        }
        
        foreach ($cultures as $culture) {
            if ($userId && $culture->getUserId() !== $userId) {
                continue;
            }
            
            $dateRecolte = $culture->getDateRecolte();
            if ($dateRecolte && $dateRecolte >= $now && $dateRecolte <= $alertDate) {
                $daysUntilHarvest = $now->diff($dateRecolte)->days;
                $cultureId = $culture->getIdCulture();
                
                $alertCultures[] = [
                    'culture' => $culture,
                    'daysUntilHarvest' => $daysUntilHarvest,
                    'isToday' => $daysUntilHarvest === 0,
                    'urgency' => $this->calculateUrgency($daysUntilHarvest),
                    'seen' => isset($seenAlerts[$cultureId])
                ];
            }
        }
        
        return $alertCultures;
    }

    /**
     * Mark alert as seen
     */
    public function markAlertAsSeen(int $userId, int $cultureId): void
    {
        // Check if already marked
        $existing = $this->em->getRepository(\App\Entity\HarvestAlertSeen::class)
            ->findOneBy(['userId' => $userId, 'cultureId' => $cultureId]);
        
        if ($existing) {
            return;
        }
        
        $seen = new \App\Entity\HarvestAlertSeen();
        $seen->setUserId($userId);
        $seen->setCultureId($cultureId);
        $seen->setSeenAt(new \DateTime());
        
        $this->em->persist($seen);
        $this->em->flush();
    }

    /**
     * Mark all alerts as seen for user
     */
    public function markAllAsSeen(int $userId): void
    {
        $alerts = $this->getCulturesNeedingHarvestAlert($userId);
        
        foreach ($alerts as $alert) {
            if (!$alert['seen']) {
                $this->markAlertAsSeen($userId, $alert['culture']->getIdCulture());
            }
        }
    }

    /**
     * Clear seen alerts
     */
    public function clearSeenAlerts(int $userId): void
    {
        $qb = $this->em->createQueryBuilder();
        $qb->delete(\App\Entity\HarvestAlertSeen::class, 's')
           ->where('s.userId = :userId')
           ->setParameter('userId', $userId)
           ->getQuery()
           ->execute();
    }

    /**
     * Send real-time harvest alert via WebSocket
     */
    public function sendHarvestAlert(Culture $culture, int $daysUntilHarvest): bool
    {
        $alertData = [
            'type' => 'harvest_alert',
            'culture' => [
                'id' => $culture->getIdCulture(),
                'nom' => $culture->getNomCulture(),
                'type' => $culture->getTypeCulture(),
                'dateRecolte' => $culture->getDateRecolte()?->format('Y-m-d'),
            ],
            'daysUntilHarvest' => $daysUntilHarvest,
            'urgency' => $this->calculateUrgency($daysUntilHarvest),
            'message' => $this->generateAlertMessage($culture, $daysUntilHarvest),
            'timestamp' => (new \DateTime())->format('c')
        ];
        
        try {
            // Send to WebSocket server
            return $this->sendToWebSocket($culture->getUserId(), $alertData);
        } catch (\Exception $e) {
            error_log("WebSocket error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send message to WebSocket server
     */
    private function sendToWebSocket(int $userId, array $alertData): bool
    {
        try {
            $client = new \WebSocket\Client($this->websocketUrl);
            
            $message = json_encode([
                'type' => 'broadcast_alert',
                'userId' => $userId,
                'alert' => $alertData
            ]);
            
            $client->send($message);
            $client->close();
            
            return true;
        } catch (\Exception $e) {
            // WebSocket server might not be running, log but don't fail
            error_log("Could not send to WebSocket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate urgency level based on days until harvest
     */
    private function calculateUrgency(int $days): string
    {
        return match(true) {
            $days === 0 => 'critical',
            $days <= 2 => 'high',
            $days <= 5 => 'medium',
            default => 'low'
        };
    }

    /**
     * Generate alert message
     */
    private function generateAlertMessage(Culture $culture, int $days): string
    {
        if ($days === 0) {
            return sprintf("🚨 C'est aujourd'hui ! La récolte de %s doit être effectuée.", $culture->getNomCulture());
        } elseif ($days === 1) {
            return sprintf("⚠️ Demain ! La récolte de %s est prévue demain.", $culture->getNomCulture());
        } else {
            return sprintf("📅 Dans %d jours : La récolte de %s est prévue.", $days, $culture->getNomCulture());
        }
    }
}
