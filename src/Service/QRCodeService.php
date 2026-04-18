<?php
namespace App\Service;

use App\Entity\Equipement;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class QRCodeService
{
    public function generateBase64QR(Equipement $equipement): string
    {
        $contenu = $this->construireContenu($equipement);
        
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($contenu)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();
        
        return $result->getDataUri();
    }
    
    private function construireContenu(Equipement $equipement): string
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