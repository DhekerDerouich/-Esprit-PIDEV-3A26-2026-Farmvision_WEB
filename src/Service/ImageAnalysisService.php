<?php
// src/Service/ImageAnalysisService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageAnalysisService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private bool $enabled;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->enabled = !empty($apiKey) && $apiKey !== 'your_openai_api_key_here';
    }

    public function analyzeProductImage(string $imagePath): array
    {
        if (!file_exists($imagePath)) {
            return [
                'success' => false,
                'error' => 'Fichier image non trouvé',
                'nom_produit' => null,
                'type_produit' => null,
                'qualite' => 'Non analysé',
                'suggestions' => []
            ];
        }

        // Mode simulation (par défaut)
        $filename = basename($imagePath);
        $suggestedName = $this->guessProductFromFilename($filename);
        
        return [
            'success' => true,
            'nom_produit' => $suggestedName,
            'type_produit' => $this->guessTypeFromName($suggestedName),
            'qualite' => 'Bonne',
            'suggestions' => ['Conserver au frais', 'Utiliser dans les 7 jours', 'Vérifier la fraîcheur'],
            'mode' => 'simulation'
        ];
    }

    private function guessProductFromFilename(string $filename): string
    {
        $filename = strtolower($filename);
        $products = [
            'tomate' => 'Tomate', 'pomme' => 'Pomme', 'banane' => 'Banane',
            'carotte' => 'Carotte', 'salade' => 'Salade', 'concombre' => 'Concombre',
            'aubergine' => 'Aubergine', 'courgette' => 'Courgette', 'orange' => 'Orange',
            'citron' => 'Citron', 'fraise' => 'Fraise', 'raisin' => 'Raisin'
        ];
        foreach ($products as $key => $name) {
            if (strpos($filename, $key) !== false) return $name;
        }
        return 'Produit agricole';
    }

    private function guessTypeFromName(string $name): string
    {
        $fruits = ['Pomme', 'Banane', 'Orange', 'Citron', 'Fraise', 'Raisin'];
        $legumes = ['Tomate', 'Carotte', 'Salade', 'Concombre', 'Aubergine', 'Courgette'];
        if (in_array($name, $fruits)) return 'Fruit';
        if (in_array($name, $legumes)) return 'Légume';
        return 'Autre';
    }

    public function predictSales(string $productName, array $historicalData): array
    {
        return [
            'predictions' => [rand(80, 150), rand(100, 180), rand(120, 200)],
            'confidence' => 0.85,
            'tendances' => 'hausse',
            'mode' => 'simulation'
        ];
    }
}