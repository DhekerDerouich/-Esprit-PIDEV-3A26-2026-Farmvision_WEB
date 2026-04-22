<?php
// src/Controller/Admin/AlertesController.php

namespace App\Controller\Admin;

use App\Service\AlertesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/api/alertes')]
class AlertesController extends AbstractController
{
    #[Route('', name: 'admin_alertes_api', methods: ['GET'])]
    public function index(AlertesService $alertesService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $alertes      = $alertesService->getToutesLesAlertes();
        $stats        = $alertesService->getStatistiquesAlertes();

        return $this->json([
            'success' => true,
            'alertes' => $alertes,
            'stats'   => $stats,
            'total'   => $stats['total'],
            'urgentes'=> $stats['urgentes'],
        ]);
    }
}
