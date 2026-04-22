<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class CurrencyService
{
    private $httpClient;
    private $cache;
    
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->cache = new FilesystemAdapter();
    }
    
    /**
     * Convertir un montant de TND vers une autre devise
     */
    public function convert(float $amount, string $to = 'EUR'): float
    {
        $rate = $this->getExchangeRate($to);
        return round($amount * $rate, 2);
    }
    
    /**
     * Obtenir le taux de change pour une devise spécifique
     */
    public function getExchangeRate(string $currency): float
    {
        $rates = $this->getAllRates();
        return $rates[$currency] ?? 1;
    }
    
    /**
     * Récupérer tous les taux de change depuis l'API (avec cache)
     */
    public function getAllRates(): array
    {
        // Cache pendant 1 heure pour ne pas surcharger l'API
        return $this->cache->get('exchange_rates_tnd', function (ItemInterface $item) {
            $item->expiresAfter(3600); // Cache 1 heure
            
            try {
                // API gratuite exchangerate-api.com
                $response = $this->httpClient->request('GET', 'https://api.exchangerate-api.com/v4/latest/TND');
                $data = $response->toArray();
                
                return $data['rates'] ?? ['EUR' => 0.30, 'USD' => 0.32];
            } catch (\Exception $e) {
                // Fallback en cas d'erreur API
                return [
                    'EUR' => 0.30,  // 1 TND ≈ 0.30 EUR
                    'USD' => 0.32,  // 1 TND ≈ 0.32 USD
                    'GBP' => 0.26,
                    'CAD' => 0.43,
                ];
            }
        });
    }
    
    /**
     * Obtenir le taux avec variation (hausse/baisse)
     */
    public function getRateWithChange(string $currency): array
    {
        $rates = $this->getAllRates();
        
        // Taux simulés précédents (pour la variation)
        $previousRates = $this->getPreviousRates();
        
        $currentRate = $rates[$currency] ?? 1;
        $previousRate = $previousRates[$currency] ?? $currentRate;
        
        $change = round((($currentRate - $previousRate) / $previousRate) * 100, 2);
        
        return [
            'rate' => $currentRate,
            'change' => $change,
            'is_up' => $change > 0,
            'is_down' => $change < 0,
        ];
    }
    
    /**
     * Taux précédents (simulés pour la démo)
     */
    private function getPreviousRates(): array
    {
        // Simule des taux d'il y a 24h
        return [
            'EUR' => 0.298,
            'USD' => 0.318,
            'GBP' => 0.258,
            'CAD' => 0.428,
        ];
    }
    
    /**
     * Convertir un montant avec toutes les devises
     */
    public function convertAllCurrencies(float $amount): array
    {
        $rates = $this->getAllRates();
        
        return [
            'TND' => $amount,
            'EUR' => round($amount * ($rates['EUR'] ?? 0.30), 2),
            'USD' => round($amount * ($rates['USD'] ?? 0.32), 2),
            'GBP' => round($amount * ($rates['GBP'] ?? 0.26), 2),
            'CAD' => round($amount * ($rates['CAD'] ?? 0.43), 2),
        ];
    }
    
    /**
     * Formater un montant avec devise
     */
    public function formatCurrency(float $amount, string $currency): string
    {
        $symbols = [
            'TND' => 'DT',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'CAD' => 'C$',
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        return number_format($amount, 2, ',', ' ') . ' ' . $symbol;
    }
}