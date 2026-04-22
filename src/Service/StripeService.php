<?php
// src/Service/StripeService.php

namespace App\Service;

use Stripe\Checkout\Session;
use Stripe\Stripe as StripeClient;

class StripeService
{
    private string $secretKey;
    private string $publicKey;

    public function __construct(string $secretKey, string $publicKey)
    {
        $this->secretKey = $secretKey;
        $this->publicKey = $publicKey;
        StripeClient::setApiKey($this->secretKey);
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function isConfigured(): bool
    {
        return !empty($this->secretKey)
            && $this->secretKey !== 'your_stripe_secret_key'
            && str_starts_with($this->secretKey, 'sk_');
    }

    public function createCheckoutSession(array $items, string $successUrl, string $cancelUrl): Session
    {
        $lineItems = [];
        foreach ($items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name'        => $item['name'],
                        'description' => !empty($item['description']) ? $item['description'] : null,
                    ],
                    'unit_amount' => (int) round($item['price'] * 100),
                ],
                'quantity' => (int) $item['quantity'],
            ];
        }

        // Stripe requires {CHECKOUT_SESSION_ID} placeholder in success_url
        $successUrlWithSession = $successUrl
            . (str_contains($successUrl, '?') ? '&' : '?')
            . 'session_id={CHECKOUT_SESSION_ID}';

        return Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => $lineItems,
            'mode'                 => 'payment',
            'success_url'          => $successUrlWithSession,
            'cancel_url'           => $cancelUrl,
        ]);
    }

    public function retrieveSession(string $sessionId): ?Session
    {
        try {
            return Session::retrieve($sessionId);
        } catch (\Exception $e) {
            return null;
        }
    }
}