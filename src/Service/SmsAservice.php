<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Twilio\Rest\Client;

class SmsAservice
{
    private ?Client $twilio = null;
    private ?string $twilioPhoneNumber;
    private LoggerInterface $logger;
    private bool $isConfigured;

    public function __construct(LoggerInterface $logger, string $accountSid = '', string $authToken = '', string $twilioPhoneNumber = '')
    {
        $this->logger            = $logger;
        $this->twilioPhoneNumber = $twilioPhoneNumber ?: null;

        // Debug: Log what we received (mask sensitive data)
        $this->logger->info('SmsAservice: Initializing', [
            'accountSid' => $accountSid ? substr($accountSid, 0, 10) . '...' : 'EMPTY',
            'authToken' => $authToken ? substr($authToken, 0, 8) . '...' : 'EMPTY',
            'phone' => $twilioPhoneNumber ?: 'EMPTY'
        ]);

        if ($accountSid && $authToken && $twilioPhoneNumber) {
            try {
                $this->twilio       = new Client($accountSid, $authToken);
                $this->isConfigured = true;
                $this->logger->info('SmsAservice: Twilio initialisé avec succès.');
            } catch (\Exception $e) {
                $this->isConfigured = false;
                $this->logger->error('SmsAservice: Erreur init Twilio — ' . $e->getMessage());
            }
        } else {
            $this->isConfigured = false;
            $this->logger->warning('SmsAservice: Variables Twilio manquantes dans .env');
        }
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    // ✅ FIX — Handles all common formats safely (spaces, dashes, +216 prefix, raw 8 digits)
    private function formatPhoneNumber(string $phone): string
    {
        // Strip everything except digits and leading +
        $stripped = preg_replace('/[^0-9]/', '', $phone);

        // Already has country code 216 + 8 digits = 11 digits
        if (strlen($stripped) === 11 && str_starts_with($stripped, '216')) {
            return '+' . $stripped;
        }

        // Raw 8-digit Tunisian number
        if (strlen($stripped) === 8) {
            return '+216' . $stripped;
        }

        // Fallback — trust whatever is there, just ensure leading +
        return '+' . $stripped;
    }

    public function sendMessage(string $to, string $body): array
    {
        if (!$this->isConfigured) {
            $this->logger->error('SmsService: Tentative d\'envoi sans configuration Twilio.');
            return ['success' => false, 'error' => 'SMS non configuré — vérifiez TWILIO_* dans .env'];
        }

        $formattedTo = $this->formatPhoneNumber($to);
        $this->logger->info('SmsService: Envoi SMS', ['to_raw' => $to, 'to_formatted' => $formattedTo]);

        try {
            $message = $this->twilio->messages->create(
                $formattedTo,
                [
                    'from' => $this->twilioPhoneNumber,
                    'body' => $body,
                ]
            );

            $this->logger->info('SmsService: SMS envoyé.', ['sid' => $message->sid, 'to' => $formattedTo]);
            return ['success' => true, 'sid' => $message->sid];

        } catch (\Exception $e) {
            $this->logger->error('SmsService: Échec envoi SMS — ' . $e->getMessage(), ['to' => $formattedTo]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendAnomalyAlert(string $to, array $data): array
    {
        $body = $this->formatAnomalyMessage($data);
        return $this->sendMessage($to, $body);
    }

    

    private function formatAnomalyMessage(array $data): string
{
    return "FarmVision ALERTE: Depense {$data['type']} de {$data['montant']} DT depasse la normale ({$data['moyenne']} DT moy). Ecart: +{$data['pourcentage']}%.";
}
}