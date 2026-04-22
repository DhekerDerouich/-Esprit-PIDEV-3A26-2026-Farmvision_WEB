<?php
// src/Service/SmsService.php

namespace App\Service;

class SmsService
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private bool $enabled;
    private string $logDir;

    public function __construct(string $accountSid, string $authToken, string $fromNumber, bool $enabled = false)
    {
        $this->accountSid = $accountSid;
        $this->authToken  = $authToken;
        $this->fromNumber = $fromNumber;
        $this->enabled    = $enabled;
        $this->logDir     = __DIR__ . '/../../var/log';

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }

    public function sendAlert(string $toNumber, string $productName, float $quantity, string $type = 'low_stock'): array
    {
        $messages = [
            'low_stock' => "⚠️ ALERTE STOCK: '{$productName}' est presque épuisé! Stock: {$quantity} unités.",
            'expired'   => "⚠️ ALERTE EXPIRATION: '{$productName}' expire dans {$quantity} jours!",
            'restock'   => "✅ RÉAPPROVISIONNEMENT: '{$productName}' réapprovisionné. Stock: {$quantity} unités.",
        ];

        $message = $messages[$type] ?? $messages['low_stock'];

        // Log every attempt
        $logMessage = sprintf("[%s] SMS à %s: %s\n", date('Y-m-d H:i:s'), $toNumber, $message);
        file_put_contents($this->logDir . '/sms.log', $logMessage, FILE_APPEND);

        if ($this->enabled && class_exists(\Twilio\Rest\Client::class)) {
            try {
                $client = new \Twilio\Rest\Client($this->accountSid, $this->authToken);
                $client->messages->create($toNumber, [
                    'from' => $this->fromNumber,
                    'body' => $message,
                ]);

                return [
                    'success' => true,
                    'message' => "SMS envoyé à $toNumber",
                    'content' => $message,
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error'   => $e->getMessage(),
                    'content' => $message,
                ];
            }
        }

        // Simulation mode
        return [
            'success' => true,
            'message' => "SMS (simulation) envoyé à $toNumber",
            'content' => $message,
        ];
    }

    public function sendCustom(string $toNumber, string $message): array
    {
        $logMessage = sprintf("[%s] SMS custom à %s: %s\n", date('Y-m-d H:i:s'), $toNumber, $message);
        file_put_contents($this->logDir . '/sms.log', $logMessage, FILE_APPEND);

        if ($this->enabled && class_exists(\Twilio\Rest\Client::class)) {
            try {
                $client = new \Twilio\Rest\Client($this->accountSid, $this->authToken);
                $client->messages->create($toNumber, [
                    'from' => $this->fromNumber,
                    'body' => $message,
                ]);

                return ['success' => true, 'message' => "SMS envoyé à $toNumber"];
            } catch (\Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return ['success' => true, 'message' => "SMS (simulation) envoyé à $toNumber"];
    }

    public function sendAnomalyAlert(string $toNumber, array $anomaly): array
    {
        $type    = $anomaly['type']    ?? 'Dépense';
        $montant = $anomaly['montant'] ?? 0;
        $moyenne = $anomaly['moyenne'] ?? 0;
        $pct     = $anomaly['pourcentage'] ?? 0;

        $message = sprintf(
            "⚠️ ANOMALIE DÉTECTÉE — FarmVision\n" .
            "Type: %s\n" .
            "Montant: %.2f DT (%.0f%% au-dessus de la moyenne)\n" .
            "Moyenne habituelle: %.2f DT\n" .
            "Vérifiez votre tableau de bord.",
            $type, $montant, $pct, $moyenne
        );

        return $this->sendCustom($toNumber, $message);
    }
}
