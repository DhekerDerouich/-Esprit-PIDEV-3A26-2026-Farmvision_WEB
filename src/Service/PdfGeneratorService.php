<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGeneratorService
{
    public function generatePdfFromHtml(string $html, string $filename = 'document.pdf'): string
    {
        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', realpath(__DIR__ . '/../../public'));
        
        // Initialize Dompdf
        $dompdf = new Dompdf($options);
        
        // Load HTML
        $dompdf->loadHtml($html);
        
        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render PDF
        $dompdf->render();
        
        // Return PDF content
        return $dompdf->output();
    }
    
    public function generatePdfResponse(string $html, string $filename = 'document.pdf'): array
    {
        $pdfContent = $this->generatePdfFromHtml($html, $filename);
        
        return [
            'content' => $pdfContent,
            'headers' => [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        ];
    }
}
