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

    public function createCheckoutSession(array $items, string $successUrl, string $cancelUrl): Session
    {
        $lineItems = [];
        foreach ($items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item['name'],
                        'description' => $item['description'] ?? '',
                    ],
                    'unit_amount' => (int)($item['price'] * 100), // Stripe utilise les cents
                ],
                'quantity' => (int)$item['quantity'],
            ];
        }

        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'cart_id' => $item['cart_id'] ?? '',
            ],
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