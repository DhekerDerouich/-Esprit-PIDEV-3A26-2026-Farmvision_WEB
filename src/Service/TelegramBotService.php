<?php
// src/Service/TelegramBotService.php

namespace App\Service;

use App\Repository\EquipementRepository;
use App\Repository\MaintenanceRepository;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramBotService
{
    private ?Api $telegram = null;
    private EquipementRepository $equipementRepo;
    private MaintenanceRepository $maintenanceRepo;

    public function __construct(
        EquipementRepository $equipementRepo,
        MaintenanceRepository $maintenanceRepo
    ) {
        $this->equipementRepo = $equipementRepo;
        $this->maintenanceRepo = $maintenanceRepo;
        
        $token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
        if (!empty($token)) {
            try {
                $this->telegram = new Api($token);
            } catch (TelegramSDKException $e) {
                // Log error if needed
            }
        }
    }

    public function getTelegram(): ?Api
    {
        return $this->telegram;
    }

    public function sendMessage(int $chatId, string $message): void
    {
        if (!$this->telegram) return;
        
        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
        } catch (TelegramSDKException $e) {
            // Log error
        }
    }

    public function sendMaintenanceAlert(int $chatId, array $maintenance): void
    {
        $message = "🔔 <b>ALERTE MAINTENANCE</b>\n\n";
        $message .= "🚜 <b>Équipement:</b> {$maintenance['equipement']}\n";
        $message .= "🛠️ <b>Type:</b> {$maintenance['type']}\n";
        $message .= "📅 <b>Date:</b> {$maintenance['date']}\n";
        $message .= "⏰ <b>Jours restants:</b> {$maintenance['jours']} jour(s)\n";
        
        if (!empty($maintenance['description'])) {
            $message .= "📝 <b>Description:</b> {$maintenance['description']}\n";
        }
        
        $message .= "\n⚠️ Une action est requise !";
        
        $this->sendMessage($chatId, $message);
    }

    public function processCommand(string $message, int $chatId): string
    {
        $message = mb_strtolower(trim($message), 'UTF-8');
        
        // Commande /start
        if ($message === '/start') {
            return "👋 <b>Bienvenue sur FarmVision Bot !</b>\n\n" .
                   "Je suis votre assistant agricole. Voici ce que je peux faire :\n\n" .
                   "/equipements - 📋 Liste des équipements\n" .
                   "/urgent - ⚠️ Maintenances urgentes\n" .
                   "/panne - 🔴 Équipements en panne\n" .
                   "/stats - 📊 Statistiques générales\n" .
                   "/aide - 🆘 Aide\n\n" .
                   "Envoyez-moi un message pour discuter !";
        }
        
        // Commande /aide
        if ($message === '/aide' || $message === '/help') {
            return "🆘 <b>Commandes disponibles :</b>\n\n" .
                   "/equipements - Voir tous les équipements\n" .
                   "/urgent - Voir les maintenances urgentes\n" .
                   "/panne - Voir les équipements en panne\n" .
                   "/stats - Voir les statistiques\n" .
                   "/aide - Afficher cette aide\n\n" .
                   "💡 Vous pouvez aussi me poser des questions en français !";
        }
        
        // Commande /equipements
        if ($message === '/equipements') {
            $equipements = $this->equipementRepo->findAll();
            if (count($equipements) > 0) {
                $response = "📋 <b>Liste des équipements :</b>\n\n";
                foreach ($equipements as $e) {
                    $statusIcon = match($e->getEtat()) {
                        'Fonctionnel' => '✅',
                        'En panne' => '🔴',
                        default => '🔧'
                    };
                    $response .= "{$statusIcon} <b>{$e->getNom()}</b> - {$e->getType()}\n";
                }
                return $response;
            }
            return "📭 Aucun équipement enregistré.";
        }
        
        // Commande /urgent
        if ($message === '/urgent') {
            $today = new \DateTime();
            $maintenances = $this->maintenanceRepo->findAll();
            $urgentes = [];
            
            foreach ($maintenances as $m) {
                if ($m->getStatut() === 'Planifiée') {
                    $diff = $today->diff($m->getDateMaintenance())->days;
                    if ($diff <= 7 && $diff >= 0) {
                        $urgentes[] = $m;
                    }
                }
            }
            
            if (count($urgentes) > 0) {
                $response = "⚠️ <b>Maintenances urgentes (J-7) :</b>\n\n";
                foreach ($urgentes as $m) {
                    $diff = $today->diff($m->getDateMaintenance())->days;
                    $response .= "• <b>{$m->getEquipement()->getNom()}</b>\n";
                    $response .= "  📅 {$m->getDateMaintenance()->format('d/m/Y')} (J-{$diff})\n";
                    $response .= "  🛠️ {$m->getTypeMaintenance()}\n\n";
                }
                return $response;
            }
            return "✅ Aucune maintenance urgente à signaler.";
        }
        
        // Commande /panne
        if ($message === '/panne') {
            $equipements = $this->equipementRepo->findBy(['etat' => 'En panne']);
            if (count($equipements) > 0) {
                $response = "🔴 <b>Équipements en panne :</b>\n\n";
                foreach ($equipements as $e) {
                    $response .= "• <b>{$e->getNom()}</b> - {$e->getType()}\n";
                }
                return $response;
            }
            return "✅ Aucun équipement en panne.";
        }
        
        // Commande /stats
        if ($message === '/stats') {
            $equipements = $this->equipementRepo->findAll();
            $stats = $this->maintenanceRepo->getStatistics();
            $fonctionnels = $this->equipementRepo->findBy(['etat' => 'Fonctionnel']);
            $enPanne = $this->equipementRepo->findBy(['etat' => 'En panne']);
            $enMaintenance = $this->equipementRepo->findBy(['etat' => 'Maintenance']);
            
            return "📊 <b>STATISTIQUES FARMVISION</b>\n\n" .
                   "🚜 Équipements : <b>" . count($equipements) . "</b>\n" .
                   "   ✅ Fonctionnels : <b>" . count($fonctionnels) . "</b>\n" .
                   "   🔴 En panne : <b>" . count($enPanne) . "</b>\n" .
                   "   🔧 En maintenance : <b>" . count($enMaintenance) . "</b>\n\n" .
                   "🛠️ Maintenances : <b>{$stats['total']}</b>\n" .
                   "   ⏰ Planifiées : <b>{$stats['planifiees']}</b>\n" .
                   "   ✅ Réalisées : <b>{$stats['realisees']}</b>\n\n" .
                   "💰 Coût total : <b>" . number_format($stats['coutTotal'], 2) . " DT</b>";
        }
        
        // Questions en langage naturel
        if (preg_match('/(combien|nombre).*(équipement|machine)/i', $message)) {
            $total = count($this->equipementRepo->findAll());
            return "📊 Vous avez <b>$total</b> équipement(s) dans votre parc agricole.";
        }
        
        if (preg_match('/(urgent|bientôt).*(maintenance)/i', $message)) {
            $today = new \DateTime();
            $maintenances = $this->maintenanceRepo->findAll();
            $urgentes = [];
            
            foreach ($maintenances as $m) {
                if ($m->getStatut() === 'Planifiée') {
                    $diff = $today->diff($m->getDateMaintenance())->days;
                    if ($diff <= 7 && $diff >= 0) {
                        $urgentes[] = $m->getEquipement()->getNom();
                    }
                }
            }
            
            if (count($urgentes) > 0) {
                return "⚠️ Maintenances urgentes : " . implode(', ', $urgentes);
            }
            return "✅ Aucune maintenance urgente.";
        }
        
        if (preg_match('/(panne|cassé)/i', $message)) {
            $equipements = $this->equipementRepo->findBy(['etat' => 'En panne']);
            if (count($equipements) > 0) {
                $names = array_map(fn($e) => $e->getNom(), $equipements);
                return "🔴 Équipements en panne : " . implode(', ', $names);
            }
            return "✅ Aucun équipement en panne.";
        }
        
        return "🤔 Je n'ai pas compris.\n\nUtilisez /aide pour voir les commandes disponibles.";
    }
}
