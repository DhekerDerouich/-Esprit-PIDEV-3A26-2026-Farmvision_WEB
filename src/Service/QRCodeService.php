<?php

namespace App\Service;

use App\Entity\Equipement;
use App\Entity\Utilisateur;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\RequestStack;

class QRCodeService
{
    private RequestStack $requestStack;
    private string $baseUrl;

    public function __construct(RequestStack $requestStack, string $appBaseUrl = 'http://localhost:8000')
    {
        $this->requestStack = $requestStack;
        $this->baseUrl = $appBaseUrl;
    }

    private function getBaseUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            return $request->getSchemeAndHttpHost();
        }
        return $this->baseUrl;
    }

    public function generateBase64QR(Equipement $equipement): string
    {
        $contenu = $this->construireContenuEquipement($equipement);
        return $this->generateQrCodeDataUri($contenu, 300);
    }

    public function generateUserQrCodeBase64(Utilisateur $user): string
    {
        $profileUrl = sprintf(
            '%s/profile/public/%d',
            $this->getBaseUrl(),
            $user->getId()
        );
        return $this->generateQrCodeDataUri($profileUrl, 300);
    }

    public function generateVCardBase64(Utilisateur $user): string
    {
        $vcard = $this->generateVCardContent($user);
        return $this->generateQrCodeDataUri($vcard, 400);
    }

    private function generateQrCodeDataUri(string $data, int $size = 300): string
    {
        $qrCode = new QrCode(
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 10
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $result->getDataUri();
    }

    private function generateVCardContent(Utilisateur $user): string
    {
        $vcard = "BEGIN:VCARD\n";
        $vcard .= "VERSION:3.0\n";
        $vcard .= "N:" . $user->getNom() . ";" . $user->getPrenom() . ";;;\n";
        $vcard .= "FN:" . $user->getPrenom() . " " . $user->getNom() . "\n";

        if ($user->getEmail()) {
            $vcard .= "EMAIL:" . $user->getEmail() . "\n";
        }

        if ($user->getTelephone()) {
            $vcard .= "TEL:" . $user->getTelephone() . "\n";
        }

        if ($user->getAdresse()) {
            $vcard .= "ADR:;;" . $user->getAdresse() . ";;;\n";
        }

        $roleLabel = match($user->getTypeRole()) {
            'ADMINISTRATEUR' => 'Administrateur FarmVision',
            'RESPONSABLE_EXPLOITATION' => 'Responsable exploitation',
            'AGRICULTEUR' => 'Agriculteur',
            default => 'Utilisateur'
        };
        $vcard .= "ORG:FarmVision\n";
        $vcard .= "TITLE:" . $roleLabel . "\n";
        $vcard .= "END:VCARD";

        return $vcard;
    }

    private function construireContenuEquipement(Equipement $equipement): string
    {
        $finGarantie = $equipement->getFinGarantie();
        return sprintf(
            "FARMVISION-EQUIPEMENT\n" .
            "ID: %d\n" .
            "Nom: %s\n" .
            "Type: %s\n" .
            "État: %s\n" .
            "Date achat: %s\n" .
            "Durée vie: %d ans\n" .
            "Fin garantie: %s\n" .
            "Âge: %d ans\n" .
            "URL: farmvision://equipement/%d",
            $equipement->getId(),
            $equipement->getNom(),
            $equipement->getType(),
            $equipement->getEtat(),
            $equipement->getDateAchat()?->format('d/m/Y') ?? 'N/A',
            $equipement->getDureeVieEstimee() ?? 0,
            $finGarantie?->format('d/m/Y') ?? 'N/A',
            $equipement->getAge(),
            $equipement->getId()
        );
    }
}
