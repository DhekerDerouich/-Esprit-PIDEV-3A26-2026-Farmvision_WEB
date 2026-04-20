<?php

namespace App\Controller\Admin;

use App\Service\PredictiveMaintenanceService;
use App\Repository\EquipementRepository;
use App\Entity\Maintenance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/predictive')]
class PredictiveController extends AbstractController
{
    public function __construct(
        private PredictiveMaintenanceService $predictiveService
    ) {}

    #[Route('/', name: 'admin_predictive_index')]
    public function index(): Response
    {
        $summary = $this->predictiveService->getDashboardSummary();
        $riskDistribution = $this->predictiveService->getRiskDistribution();
        $timeline = $this->predictiveService->getPredictionsTimeline(30);
        $allScores = $this->predictiveService->getAllEquipmentRiskScores();

        return $this->render('admin/predictive/index.html.twig', [
            'summary' => $summary,
            'riskDistribution' => $riskDistribution,
            'timeline' => $timeline,
            'allScores' => $allScores,
            'highRiskEquipment' => array_filter($allScores, fn($s) => $s['level'] === 'high'),
            'mediumRiskEquipment' => array_filter($allScores, fn($s) => $s['level'] === 'medium'),
            'lowRiskEquipment' => array_filter($allScores, fn($s) => $s['level'] === 'low'),
        ]);
    }

    #[Route('/equipment/{id}', name: 'admin_predictive_equipment')]
    public function equipmentDetail(int $id, EquipementRepository $equipementRepo): Response
    {
        $equipment = $equipementRepo->find($id);

        if (!$equipment) {
            throw $this->createNotFoundException('Équipement non trouvé');
        }

        $riskScore = $this->predictiveService->calculateRiskScore($equipment);

        return $this->render('admin/predictive/equipment.html.twig', [
            'equipment' => $equipment,
            'prediction' => $riskScore,
        ]);
    }

    #[Route('/api/scores', name: 'admin_predictive_api_scores')]
    public function apiScores(): JsonResponse
    {
        $scores = $this->predictiveService->getAllEquipmentRiskScores();
        $distribution = $this->predictiveService->getRiskDistribution();

        return $this->json([
            'success' => true,
            'scores' => $scores,
            'distribution' => $distribution,
            'summary' => $this->predictiveService->getDashboardSummary(),
        ]);
    }

    #[Route('/api/equipment/{id}', name: 'admin_predictive_api_equipment')]
    public function apiEquipmentDetail(int $id, EquipementRepository $equipementRepo): JsonResponse
    {
        $equipment = $equipementRepo->find($id);

        if (!$equipment) {
            return $this->json(['success' => false, 'error' => 'Équipement non trouvé'], 404);
        }

        $riskScore = $this->predictiveService->calculateRiskScore($equipment);

        return $this->json([
            'success' => true,
            'prediction' => $riskScore,
        ]);
    }

    #[Route('/create-maintenance/{id}', name: 'admin_predictive_create_maintenance', methods: ['POST'])]
    public function createMaintenance(
        int $id,
        Request $request,
        EquipementRepository $equipementRepo,
        EntityManagerInterface $em
    ): Response {
        $equipment = $equipementRepo->find($id);

        if (!$equipment) {
            throw $this->createNotFoundException('Équipement non trouvé');
        }

        $riskScore = $this->predictiveService->calculateRiskScore($equipment);

        $maintenance = new Maintenance();
        $maintenance->setEquipement($equipment);
        $maintenance->setTypeMaintenance('Préventive');
        $maintenance->setDescription('Maintenance préventive générée par IA - Score de risque: ' . $riskScore['score']);
        $maintenance->setStatut('Planifiée');
        $maintenance->setDateMaintenance(new \DateTime('+7 days'));
        $maintenance->setCout($riskScore['estimated_cost'] * 0.5);

        $em->persist($maintenance);
        $em->flush();

        $this->addFlash('success', 'Maintenance préventive créée pour ' . $equipment->getNom());

        return $this->redirectToRoute('admin_predictive_index');
    }
}
