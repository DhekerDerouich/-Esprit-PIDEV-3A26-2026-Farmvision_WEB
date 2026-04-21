<?php
// src/Service/QRCodeService.php

namespace App\Service;

use App\Entity\Equipement;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class QRCodeService
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Génère un QR Code en base64 pour un équipement
     */
    public function generateBase64QR(Equipement $equipement): string
    {
        return $this->generateQRCode($equipement);
    }

    /**
     * Génère un QR Code via API externe
     */
    public function generateQRCode(Equipement $equipement): string
    {
        $contenu = sprintf(
            "FARMVISION - Equipement\nID: %d\nNom: %s\nType: %s\nEtat: %s\nURL: /equipement/%d",
            $equipement->getId(),
            $equipement->getNom(),
            $equipement->getType(),
            $equipement->getEtat(),
            $equipement->getId()
        );
        
        // Utiliser l'API gratuite QR Server
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($contenu);
        
        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5
            ]);
            $imageData = $response->getContent();
            return 'data:image/png;base64,' . base64_encode($imageData);
        } catch (\Exception $e) {
            // Fallback : retourner une image SVG par défaut
            return $this->generateFallbackSVG($equipement);
        }
    }
    
    /**
     * Génère un SVG de secours si l'API est indisponible
     */
    private function generateFallbackSVG(Equipement $equipement): string
    {
        $nom = htmlspecialchars($equipement->getNom(), ENT_QUOTES, 'UTF-8');
        $id = $equipement->getId();
        
        $svg = <<<SVG
<svg width="300" height="300" xmlns="http://www.w3.org/2000/svg">
    <rect width="300" height="300" fill="#f3f4f6"/>
    <rect x="50" y="50" width="200" height="200" fill="white" stroke="#d1d5db" stroke-width="2"/>
    <text x="150" y="120" text-anchor="middle" font-family="Arial" font-size="16" font-weight="bold" fill="#374151">QR Code</text>
    <text x="150" y="145" text-anchor="middle" font-family="Arial" font-size="12" fill="#6b7280">{$nom}</text>
    <text x="150" y="165" text-anchor="middle" font-family="Arial" font-size="10" fill="#9ca3af">ID: {$id}</text>
    <text x="150" y="190" text-anchor="middle" font-family="Arial" font-size="10" fill="#ef4444">Service temporairement</text>
    <text x="150" y="205" text-anchor="middle" font-family="Arial" font-size="10" fill="#ef4444">indisponible</text>
</svg>
SVG;
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Génère un QR Code avec logo (alias)
     */
    public function generateQRWithLogo(Equipement $equipement): string
    {
        return $this->generateQRCode($equipement);
    }
}
