<?php

namespace App\Service;

use App\Repository\RevenuRepository;
use App\Repository\DepenseRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class ExportPDFService
{
    public function __construct(
        private RevenuRepository $revenuRepository,
        private DepenseRepository $depenseRepository,
        private Environment $twig,
    ) {}

    public function exportMonthlyReport(int $userId, int $year, int $month): string
    {
        $revenus  = $this->revenuRepository->findByUserAndMonth($userId, $year, $month);
        $depenses = $this->depenseRepository->findByUserAndMonth($userId, $year, $month);

        $totalRevenus  = array_sum(array_map(fn($r) => $r->getMontant(), $revenus));
        $totalDepenses = array_sum(array_map(fn($d) => $d->getMontant(), $depenses));

        $categories = [];
        foreach ($depenses as $depense) {
            $type = $depense->getTypeDepense();
            $categories[$type] = ($categories[$type] ?? 0) + $depense->getMontant();
        }

        $monthNames = [
            1 => 'Janvier',   2 => 'Février',  3 => 'Mars',      4 => 'Avril',
            5 => 'Mai',       6 => 'Juin',      7 => 'Juillet',   8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre',  11 => 'Novembre', 12 => 'Décembre'
        ];

        $html = $this->twig->render('export/monthly_report.html.twig', [
            'month_name'      => $monthNames[$month],
            'year'            => $year,
            'date_generation' => new \DateTime(),
            'revenus'         => $revenus,
            'depenses'        => $depenses,
            'totalRevenus'    => $totalRevenus,
            'totalDepenses'   => $totalDepenses,
            'balance'         => $totalRevenus - $totalDepenses,
            'categories'      => $categories,
        ]);

        return $this->generatePdf($html);
    }

    public function exportYearlyReport(int $userId, int $year): string
    {
        $revenus  = $this->revenuRepository->findByUserAndYear($userId, $year);
        $depenses = $this->depenseRepository->findByUserAndYear($userId, $year);

        $totalRevenus  = array_sum(array_map(fn($r) => $r->getMontant(), $revenus));
        $totalDepenses = array_sum(array_map(fn($d) => $d->getMontant(), $depenses));

        $categories = [];
        foreach ($depenses as $depense) {
            $type = $depense->getTypeDepense();
            $categories[$type] = ($categories[$type] ?? 0) + $depense->getMontant();
        }

        $html = $this->twig->render('export/monthly_report.html.twig', [
            'month_name'      => 'Rapport Annuel',
            'year'            => $year,
            'date_generation' => new \DateTime(),
            'revenus'         => $revenus,
            'depenses'        => $depenses,
            'totalRevenus'    => $totalRevenus,
            'totalDepenses'   => $totalDepenses,
            'balance'         => $totalRevenus - $totalDepenses,
            'categories'      => $categories,
        ]);

        return $this->generatePdf($html);
    }

    private function generatePdf(string $html): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}