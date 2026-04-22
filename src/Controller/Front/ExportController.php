<?php

namespace App\Controller\Front;

use App\Service\ExportPDFService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/export')]
#[IsGranted('ROLE_USER')]
class ExportController extends AbstractController
{
    #[Route('/monthly/{year}/{month}', name: 'front_export_monthly_pdf')]
    public function exportMonthly(int $year, int $month, ExportPDFService $exportService): Response
    {
        $userId = $this->getUser()->getId();
        
        $pdfContent = $exportService->exportMonthlyReport($userId, $year, $month);
        
        $monthName = $this->getMonthName($month);
        $filename = "rapport_{$monthName}_{$year}.pdf";
        
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\""
        ]);
    }
    
    #[Route('/yearly/{year}', name: 'front_export_yearly_pdf')]
    public function exportYearly(int $year, ExportPDFService $exportService): Response
    {
        $userId = $this->getUser()->getId();
        
        $pdfContent = $exportService->exportYearlyReport($userId, $year);
        
        $filename = "rapport_annuel_{$year}.pdf";
        
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\""
        ]);
    }
    
    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        return $months[$month];
}
}