<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Twilio\Rest\Client;

class WhatsAppService
{
    private $twilio;
    private $whatsappNumber;
    private $logger;
    private $isConfigured;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        
        $accountSid = $_ENV['TWILIO_ACCOUNT_SID'] ?? null;
        $authToken = $_ENV['TWILIO_AUTH_TOKEN'] ?? null;
        $this->whatsappNumber = $_ENV['TWILIO_WHATSAPP_NUMBER'] ?? null;
        
        if ($accountSid && $authToken && $this->whatsappNumber) {
            try {
                $this->twilio = new Client($accountSid, $authToken);
                $this->isConfigured = true;
                $this->logger->info('WhatsApp Service initialisé');
            } catch (\Exception $e) {
                $this->isConfigured = false;
                $this->logger->error('Erreur Twilio: ' . $e->getMessage());
            }
        } else {
            $this->isConfigured = false;
            $this->logger->warning('WhatsApp non configuré');
        }
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Enlever tous les caractères non numériques
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Si le numéro a 8 chiffres (Tunisie sans indicatif)
        if (strlen($phone) === 8) {
            $phone = '216' . $phone;
        }
        
        // Ajouter le préfixe +
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }

    public function sendMessage(string $to, string $body): array
    {
        if (!$this->isConfigured) {
            return ['success' => false, 'error' => 'WhatsApp non configuré'];
        }

        $formattedTo = $this->formatPhoneNumber($to);

        try {
            $message = $this->twilio->messages->create(
                "whatsapp:{$formattedTo}",
                ['from' => "whatsapp:{$this->whatsappNumber}", 'body' => $body]
            );
            
            $this->logger->info('WhatsApp envoyé', [
                'to' => $formattedTo,
                'sid' => $message->sid,
            ]);
            
            return ['success' => true, 'sid' => $message->sid];
        } catch (\Exception $e) {
            $this->logger->error('Erreur WhatsApp: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendAnomalyAlert(string $to, array $data): array
    {
        $icons = [
            'critique' => '🔴',
            'eleve' => '🟠',
            'moyen' => '🟡',
        ];
        
        $icon = $icons[$data['niveau']] ?? '⚠️';
        
        $message = "{$icon} *FARMVISION - ALERTE DÉPENSE ANORMALE* {$icon}\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💰 *Montant :* {$data['montant']} DT\n";
        $message .= "🏷️ *Catégorie :* {$data['type']}\n";
        $message .= "📅 *Date :* " . date('d/m/Y') . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "📊 *Comparaison :*\n";
        $message .= "➡️ Moyenne (3 mois) : {$data['moyenne']} DT\n";
        $message .= "➡️ Écart : *+{$data['pourcentage']}%*\n";
        $message .= "➡️ Seuil : {$data['seuil']} DT\n\n";
        $message .= "💡 *Conseil :*\n{$data['advice']}\n\n";
        $message .= "📱 FarmVision : https://farmvision.com\n";
        $message .= "_Message automatique_";
        
        return $this->sendMessage($to, $message);
    }

    public function sendTestMessage(string $to): array
    {
        $message = "🌾 *Bienvenue sur FarmVision* 🌾\n\n";
        $message .= "Votre compte est configuré pour recevoir des alertes WhatsApp.\n\n";
        $message .= "Vous serez alerté en cas de :\n";
        $message .= "⚠️ Dépense anormale\n";
        $message .= "💰 Trésorerie basse\n\n";
        $message .= "📱 Connectez-vous : https://farmvision.com";
        
        return $this->sendMessage($to, $message);
    }
}