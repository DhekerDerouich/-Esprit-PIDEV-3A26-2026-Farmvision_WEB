<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Repository\EquipementRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\RevenuRepository;
use App\Repository\DepenseRepository;
use App\Service\AlertesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        EquipementRepository    $equipementRepo,
        MaintenanceRepository   $maintenanceRepo,
        UtilisateurRepository   $utilisateurRepo,
        RevenuRepository        $revenuRepo,
        DepenseRepository       $depenseRepo,
        AlertesService          $alertesService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // ── Stats équipements / maintenances / finance (inchangé) ──
        $statsEquipements  = $equipementRepo->getStatistics();
        $statsMaintenances = $maintenanceRepo->getStatistics();
        $statsRevenus      = $revenuRepo->getStatistics();
        $statsDepenses     = $depenseRepo->getStatistics();

        // ── Stats utilisateurs ──
        $allUsers = $utilisateurRepo->findAll();
        $now      = new \DateTimeImmutable();

        $statsUtilisateurs = [
            'total'        => count($allUsers),
            'admins'       => count(array_filter($allUsers, fn($u) => $u->getTypeRole() === 'ADMINISTRATEUR')),
            'responsables' => count(array_filter($allUsers, fn($u) => $u->getTypeRole() === 'RESPONSABLE_EXPLOITATION')),
            'agriculteurs' => count(array_filter($allUsers, fn($u) => $u->getTypeRole() === 'AGRICULTEUR')),
            'actifs'       => count(array_filter($allUsers, fn($u) => $u->isActivated())),
            'bannis'       => count(array_filter($allUsers, fn($u) => $u->getBanStatus() !== null && $u->isBanned())),
        ];

        // ── Inscriptions 12 derniers mois ──
        $inscriptionsMois = [];
        for ($i = 11; $i >= 0; $i--) {
            $mois  = (new \DateTime())->modify("-{$i} months");
            $label = $mois->format('M Y');
            $inscriptionsMois[$label] = 0;
        }
        foreach ($allUsers as $u) {
            if ($u->getDateCreation()) {
                $key = $u->getDateCreation()->format('M Y');
                if (isset($inscriptionsMois[$key])) {
                    $inscriptionsMois[$key]++;
                }
            }
        }

        // ── Prédiction IA (régression linéaire) ──
        $valeurs = array_values($inscriptionsMois);
        $n       = count($valeurs);
        $sumX = $sumY = $sumXY = $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumX  += $i; $sumY  += $valeurs[$i];
            $sumXY += $i * $valeurs[$i]; $sumX2 += $i * $i;
        }
        $denom     = ($n * $sumX2 - $sumX * $sumX);
        $slope     = $denom !== 0 ? ($n * $sumXY - $sumX * $sumY) / $denom : 0;
        $intercept = ($sumY - $slope * $sumX) / $n;

        $predictions = [];
        for ($i = 0; $i < 6; $i++) {
            $mois  = (new \DateTime())->modify('+' . ($i + 1) . ' months');
            $predictions[$mois->format('M Y')] = max(0, (int) round($slope * ($n + $i) + $intercept));
        }

        // ── Stats genre ──
        $genres = ['M' => 0, 'F' => 0, 'A' => 0, 'NC' => 0];
        foreach ($allUsers as $u) {
            $g = $u->getGenre();
            if ($g === 'M')      $genres['M']++;
            elseif ($g === 'F')  $genres['F']++;
            elseif ($g === 'A')  $genres['A']++;
            else                 $genres['NC']++;
        }

        // ── Stats tranche d'âge ──
        $tranches = ['<18' => 0, '18-25' => 0, '26-35' => 0, '36-50' => 0, '51-65' => 0, '>65' => 0, 'NC' => 0];
        foreach ($allUsers as $u) {
            $age = $u->getAge();
            if ($age === null)    $tranches['NC']++;
            elseif ($age < 18)   $tranches['<18']++;
            elseif ($age <= 25)  $tranches['18-25']++;
            elseif ($age <= 35)  $tranches['26-35']++;
            elseif ($age <= 50)  $tranches['36-50']++;
            elseif ($age <= 65)  $tranches['51-65']++;
            else                 $tranches['>65']++;
        }

        // ── Utilisateurs bannis actifs ──
        $bannis = array_values(array_filter($allUsers, fn($u) => $u->isBanned()));
        usort($bannis, fn($a, $b) =>
            ($b->getBannedAt() ?? new \DateTimeImmutable('1970-01-01'))
            <=> ($a->getBannedAt() ?? new \DateTimeImmutable('1970-01-01'))
        );
        $bannis = array_slice($bannis, 0, 10);

        // ── Derniers inscrits ──
        $sorted = $allUsers;
        usort($sorted, fn($a, $b) =>
            ($b->getDateCreation() <=> $a->getDateCreation())
        );
        $derniersInscrits = array_slice($sorted, 0, 5);

        // ── Taux de croissance ──
        $tauxCroissance = null;
        if ($n >= 2 && $valeurs[$n - 2] > 0) {
            $tauxCroissance = round((($valeurs[$n - 1] - $valeurs[$n - 2]) / $valeurs[$n - 2]) * 100, 1);
        }

        // ── Chart data finances ──
        $chartDataFinance = $this->getMonthlyFinanceData($revenuRepo, $depenseRepo);

        // ── Alertes ──
        $alertes = $alertesService->getToutesLesAlertes();
        $statsAlertes = $alertesService->getStatistiquesAlertes();
        $alertesUrgentes = $alertesService->getAlertesUrgentes();

        // Prépare labels/valeurs séparés — évite |values (filtre inexistant en Twig natif)
        $inscriptionsMoisLabels = array_keys($inscriptionsMois);
        $inscriptionsMoisValues = array_values($inscriptionsMois);
        $predictionsLabels      = array_keys($predictions);
        $predictionsValues      = array_values($predictions);
        $genresValues           = array_values($genres);
        $tranchesLabels         = array_keys($tranches);
        $tranchesValues         = array_values($tranches);

        return $this->render('admin/dashboard/index.html.twig', [
            // stats générales
            'statsEquipements'    => $statsEquipements,
            'statsMaintenances'   => $statsMaintenances,
            'statsUtilisateurs'   => $statsUtilisateurs,
            'statsRevenus'        => $statsRevenus,
            'statsDepenses'       => $statsDepenses,
            // alertes
            'alertes'             => $alertes,
            'statsAlertes'        => $statsAlertes,
            'alertesUrgentes'     => $alertesUrgentes,
            // users IA
            'inscriptionsMois'       => $inscriptionsMois,
            'inscriptionsMoisLabels' => $inscriptionsMoisLabels,
            'inscriptionsMoisValues' => $inscriptionsMoisValues,
            'predictions'            => $predictions,
            'predictionsLabels'      => $predictionsLabels,
            'predictionsValues'      => $predictionsValues,
            'genres'                 => $genres,
            'genresValues'           => $genresValues,
            'tranches'               => $tranches,
            'tranchesLabels'         => $tranchesLabels,
            'tranchesValues'         => $tranchesValues,
            'bannis'              => $bannis,
            'derniersInscrits'    => $derniersInscrits,
            'tauxCroissance'      => $tauxCroissance,
            'tendance'            => $slope > 0 ? 'hausse' : ($slope < 0 ? 'baisse' : 'stable'),
            'previsionProchainMois' => $predictionsValues[0] ?? 0,
            // charts
            'chartDataFinance'    => $chartDataFinance,
        ]);
    }

    // ── Ban / Unban depuis le dashboard ──

    #[Route('/dashboard/ban/{id}', name: 'admin_dashboard_ban', methods: ['POST'])]
    public function ban(
        Request $request,
        Utilisateur $user,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($user === $this->getUser()) {
            $this->addFlash('error', '❌ Vous ne pouvez pas vous bannir vous-même.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $duration = $request->request->get('ban_duration', '24h');
        $reason   = trim($request->request->get('ban_reason', ''));

        if (empty($reason)) {
            $this->addFlash('error', '❌ La raison du ban est obligatoire.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $user->setBanReason($reason);
        $user->setBannedAt(new \DateTimeImmutable());

        if ($duration === 'permanent') {
            $user->setBanStatus('permanent');
            $user->setBanExpiresAt(null);
        } else {
            $user->setBanStatus('temporary');
            $user->setBanExpiresAt(match ($duration) {
                '1min' => new \DateTimeImmutable('+1 minute'),
                '1h'   => new \DateTimeImmutable('+1 hour'),
                '24h'  => new \DateTimeImmutable('+24 hours'),
                '7d'   => new \DateTimeImmutable('+7 days'),
                '30d'  => new \DateTimeImmutable('+30 days'),
                default => new \DateTimeImmutable('+24 hours'),
            });
        }

        $em->flush();
        $this->addFlash('success', "✅ {$user->getPrenom()} {$user->getNom()} a été banni.");
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/dashboard/unban/{id}', name: 'admin_dashboard_unban', methods: ['POST'])]
    public function unban(Utilisateur $user, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user->setBanStatus(null);
        $user->setBanReason(null);
        $user->setBanExpiresAt(null);
        $user->setBannedAt(null);
        $em->flush();
        $this->addFlash('success', "✅ {$user->getPrenom()} {$user->getNom()} a été débanni.");
        return $this->redirectToRoute('admin_dashboard');
    }

    private function getMonthlyFinanceData(RevenuRepository $revenuRepo, DepenseRepository $depenseRepo): array
    {
        $months = []; $revenusData = []; $depensesData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("-{$i} months");
            $months[] = $date->format('M Y');
            $start = (clone $date)->modify('first day of this month');
            $end   = (clone $date)->modify('last day of this month');
            $revenusData[]  = (float) $revenuRepo->createQueryBuilder('r')
                ->select('COALESCE(SUM(r.montant),0)')->where('r.dateRevenu >= :s AND r.dateRevenu <= :e')
                ->setParameter('s', $start)->setParameter('e', $end)->getQuery()->getSingleScalarResult();
            $depensesData[] = (float) $depenseRepo->createQueryBuilder('d')
                ->select('COALESCE(SUM(d.montant),0)')->where('d.dateDepense >= :s AND d.dateDepense <= :e')
                ->setParameter('s', $start)->setParameter('e', $end)->getQuery()->getSingleScalarResult();
        }
        return ['labels' => $months, 'revenus' => $revenusData, 'depenses' => $depensesData];
    }
}