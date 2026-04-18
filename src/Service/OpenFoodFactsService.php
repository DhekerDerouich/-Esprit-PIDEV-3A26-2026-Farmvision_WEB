<?php
// src/Service/OpenFoodFactsService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenFoodFactsService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function enrichProductData(string $barcode): array
    {
        try {
            $response = $this->httpClient->request('GET', "https://world.openfoodfacts.org/api/v0/product/{$barcode}.json");
            
            if ($response->getStatusCode() !== 200) {
                return ['success' => false, 'error' => 'API non disponible'];
            }
            
            $data = $response->toArray();
            
            if ($data['status'] !== 1) {
                return ['success' => false, 'error' => 'Produit non trouvé'];
            }
            
            $product = $data['product'];
            
            return [
                'success' => true,
                'nom_produit' => $product['product_name'] ?? null,
                'type_produit' => $this->extractCategory($product['categories_tags'] ?? []),
                'description' => $product['generic_name'] ?? null,
                'image_url' => $product['image_url'] ?? null,
                'marque' => $product['brands'] ?? null,
                'origine' => $product['origins'] ?? null
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function extractCategory(array $categories): ?string
    {
        foreach ($categories as $category) {
            if (strpos($category, 'vegetables') !== false) return 'Légume';
            if (strpos($category, 'fruits') !== false) return 'Fruit';
            if (strpos($category, 'cereals') !== false) return 'Céréale';
        }
        return null;
    }
}