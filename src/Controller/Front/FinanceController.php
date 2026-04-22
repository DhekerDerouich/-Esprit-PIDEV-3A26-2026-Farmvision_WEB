<?php

namespace App\Controller\Front;

use App\Entity\Depense;
use App\Entity\Revenu;
use App\Service\FinanceChatbotService;
use App\Service\KPIService;
use App\Service\AIRecommendationService;
use App\Service\AIDataService;
use App\Repository\DepenseRepository;
use App\Repository\RevenuRepository;
use App\Service\SmsService;
use App\Service\CurrencyService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\AnomalyDetectionService;  
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/finance')]
#[IsGranted('ROLE_USER')]
class FinanceController extends AbstractController
{
#[Route('/', name: 'front_finance_dashboard')]
public function dashboard(
    DepenseRepository $depenseRepo, 
    RevenuRepository $revenuRepo,
    CurrencyService $currency,
    KPIService $kpiService,
    AIRecommendationService $aiService,
    AIDataService $aiDataService
): Response {
    $userId = $this->getUser()->getId();
    
    // Récupérer UNIQUEMENT les transactions de l'utilisateur connecté
    $depenses = $depenseRepo->findBy(['userId' => $userId], ['dateDepense' => 'DESC']);
    $revenus = $revenuRepo->findBy(['userId' => $userId], ['dateRevenu' => 'DESC']);
    
    // Calculer les totaux en TND
    $totalDepensesTND = 0;
    foreach ($depenses as $depense) {
        $totalDepensesTND += $depense->getMontant();
    }
    
    $totalRevenusTND = 0;
    foreach ($revenus as $revenu) {
        $totalRevenusTND += $revenu->getMontant();
    }
    
    $balanceTND = $totalRevenusTND - $totalDepensesTND;
    
    // CONVERSION VERS EUR ET USD
    $totalDepensesEUR = $currency->convert($totalDepensesTND, 'EUR');
    $totalDepensesUSD = $currency->convert($totalDepensesTND, 'USD');
    
    $totalRevenusEUR = $currency->convert($totalRevenusTND, 'EUR');
    $totalRevenusUSD = $currency->convert($totalRevenusTND, 'USD');
    
    $balanceEUR = $currency->convert($balanceTND, 'EUR');
    $balanceUSD = $currency->convert($balanceTND, 'USD');
    
    // Taux de change avec variation
    $eurRate = $currency->getRateWithChange('EUR');
    $usdRate = $currency->getRateWithChange('USD');
    
    // Dépenses et revenus du mois
    $now = new \DateTime();
    $firstDayOfMonth = new \DateTime($now->format('Y-m-01'));
    
    $depensesCeMoisTND = 0;
    foreach ($depenses as $depense) {
        if ($depense->getDateDepense() >= $firstDayOfMonth) {
            $depensesCeMoisTND += $depense->getMontant();
        }
    }
    
    $revenusCeMoisTND = 0;
    foreach ($revenus as $revenu) {
        if ($revenu->getDateRevenu() >= $firstDayOfMonth) {
            $revenusCeMoisTND += $revenu->getMontant();
        }
    }
    
    $depensesCeMoisEUR = $currency->convert($depensesCeMoisTND, 'EUR');
    $revenusCeMoisEUR = $currency->convert($revenusCeMoisTND, 'EUR');
    
    // Transactions récentes (5 dernières) avec conversion
    $allTransactions = [];
    foreach ($depenses as $depense) {
        $allTransactions[] = [
            'type' => 'depense',
            'date' => $depense->getDateDepense(),
            'montant_tnd' => $depense->getMontant(),
            'montant_eur' => $currency->convert($depense->getMontant(), 'EUR'),
            'categorie' => $depense->getTypeDepense(),
            'description' => $depense->getDescription(),
            'id' => $depense->getId(),
        ];
    }
    foreach ($revenus as $revenu) {
        $allTransactions[] = [
            'type' => 'revenu',
            'date' => $revenu->getDateRevenu(),
            'montant_tnd' => $revenu->getMontant(),
            'montant_eur' => $currency->convert($revenu->getMontant(), 'EUR'),
            'categorie' => $revenu->getSource(),
            'description' => $revenu->getDescription(),
            'id' => $revenu->getIdRevenu(),
        ];
    }
    
    usort($allTransactions, function($a, $b) {
        return $b['date'] <=> $a['date'];
    });
    
    $recentTransactions = array_slice($allTransactions, 0, 5);
    
    // ============================================
    // KPI - Indicateurs de Performance
    // ============================================
    $kpis = $kpiService->calculateKPIs($userId, $depenses, $revenus);
    
    // Données pour les graphiques
    $chartData = $kpiService->getChartData($userId);
    $categoryData = $kpiService->getCategoryDistribution($userId);
    
    // ============================================
    // IA - Prédiction et Recommandations
    // ============================================
    $historicalData = $aiDataService->getLast3MonthsData($userId);
    $aiAnalysis = $aiService->analyzeAndPredict($historicalData);
    
    return $this->render('front/finance/dashboard.html.twig', [
        // Totaux TND
        'totalDepensesTND' => $totalDepensesTND,
        'totalRevenusTND' => $totalRevenusTND,
        'balanceTND' => $balanceTND,
        // Totaux EUR
        'totalDepensesEUR' => $totalDepensesEUR,
        'totalRevenusEUR' => $totalRevenusEUR,
        'balanceEUR' => $balanceEUR,
        // Totaux USD
        'totalDepensesUSD' => $totalDepensesUSD,
        'totalRevenusUSD' => $totalRevenusUSD,
        'balanceUSD' => $balanceUSD,
        // Taux de change
        'eurRate' => $eurRate,
        'usdRate' => $usdRate,
        // Ce mois-ci
        'depensesCeMoisTND' => $depensesCeMoisTND,
        'revenusCeMoisTND' => $revenusCeMoisTND,
        'depensesCeMoisEUR' => $depensesCeMoisEUR,
        'revenusCeMoisEUR' => $revenusCeMoisEUR,
        // Transactions récentes
        'recentTransactions' => $recentTransactions,
        // KPI
        'kpis' => $kpis,
        // Données pour graphiques
        'chartData' => $chartData,
        'categoryData' => $categoryData,
        // IA Analysis
        'aiAnalysis' => $aiAnalysis,
    ]);
}
    
    #[Route('/depenses', name: 'front_finance_depenses')]
    public function depenses(
        Request $request, 
        DepenseRepository $repository, 
        PaginatorInterface $paginator,
        CurrencyService $currency
    ): Response {
        $userId = $this->getUser()->getId();
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', 'all');
        $startDate = $request->query->get('start_date', '');
        $endDate = $request->query->get('end_date', '');
        
        // Construire la requête avec filtre userId
        $queryBuilder = $repository->createQueryBuilder('d')
            ->where('d.userId = :userId')
            ->setParameter('userId', $userId);
        
        if (!empty($search)) {
            $queryBuilder->andWhere('d.typeDepense LIKE :search OR d.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        if ($type !== 'all') {
            $queryBuilder->andWhere('d.typeDepense = :type')
                ->setParameter('type', $type);
        }
        
        if (!empty($startDate)) {
            $queryBuilder->andWhere('d.dateDepense >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }
        
        if (!empty($endDate)) {
            $queryBuilder->andWhere('d.dateDepense <= :endDate')
                ->setParameter('endDate', new \DateTime($endDate . ' 23:59:59'));
        }
        
        $queryBuilder->orderBy('d.dateDepense', 'DESC');
        
        // PAGINATION - 10 éléments par page
        $depenses = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );
        
        // Calcul du total
        $totalTND = 0;
        foreach ($depenses as $depense) {
            $totalTND += $depense->getMontant();
        }
        
        // Conversion des totaux
        $totalEUR = $currency->convert($totalTND, 'EUR');
        $totalUSD = $currency->convert($totalTND, 'USD');
        
        $typeOptions = $repository->getUniqueTypesForUser($userId);
        
        $eurRate = $currency->getRateWithChange('EUR');
        $usdRate = $currency->getRateWithChange('USD');
        
        return $this->render('front/finance/depenses.html.twig', [
            'depenses' => $depenses,
            'totalTND' => $totalTND,
            'totalEUR' => $totalEUR,
            'totalUSD' => $totalUSD,
            'search' => $search,
            'selectedType' => $type,
            'typeOptions' => $typeOptions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'eurRate' => $eurRate,
            'usdRate' => $usdRate,
        ]);
    }
    
 #[Route('/depenses/new', name: 'front_finance_depense_new', methods: ['GET', 'POST'])]
public function newDepense(
    Request $request,
    EntityManagerInterface $em,
    AnomalyDetectionService $anomalyService,
    SmsService $smsService,
    DepenseRepository $depenseRepository
): Response {
    $errors = [];
     $prefillMontant = $request->query->get('montant');
    $prefillDate = $request->query->get('date_depense');
    $prefillType = $request->query->get('type');
    $prefillDescription = $request->query->get('description');

    if ($request->isMethod('POST')) {
        $typeDepense = trim($request->request->get('type_depense'));
        $montant     = (float) $request->request->get('montant');
        $dateDepense = $request->request->get('date_depense');
        $description = trim($request->request->get('description'));

        $typesValides = ['Carburant', 'Réparation', 'Semences', 'Engrais', 'Main d\'œuvre', 'Équipement', 'Vétérinaire', 'Autre'];

        if (empty($typeDepense) || !in_array($typeDepense, $typesValides)) {
            $errors['type_depense'] = 'Type non valide';
        }
        if ($montant <= 0) {
            $errors['montant'] = 'Montant invalide';
        }
        if (empty($dateDepense)) {
            $errors['date_depense'] = 'Date obligatoire';
        }
        if (empty($description)) {
            $errors['description'] = 'Description obligatoire';
        }

        if (count($errors) === 0) {
            $user   = $this->getUser();
            $userId = $user->getId();

            // ✅ FIX #1 — Calculate average BEFORE saving the new expense
            $moyenne = $depenseRepository->getAverageForTypeLast3Months($typeDepense, $userId);

            // Save the expense after getting the average
            $depense = new Depense();
            $depense->setTypeDepense($typeDepense);
            $depense->setMontant($montant);
            $depense->setDateDepense(new \DateTime($dateDepense));
            $depense->setDescription($description);
            $depense->setUserId($userId);

            $em->persist($depense);
            $em->flush();

            if ($moyenne <= 0) {
                $this->addFlash('warning', "⚠️ Pas assez de données historiques pour détecter une anomalie.");
                $this->addFlash('success', '✅ Dépense ajoutée avec succès.');
                return $this->redirectToRoute('front_finance_depenses');
            }

            $seuil = round($moyenne * 1.5, 2);

            // Get user phone for SMS alert
            $userPhone = $user->getTelephone();

            // Anomaly detection
            $anomaly = $anomalyService->isAnomaly($typeDepense, $montant, $userId);

            if ($anomaly['is_anomaly']) {
                $this->addFlash('warning', "⚠️ Dépense anormale détectée (+{$anomaly['pourcentage']}% au-dessus de la moyenne)");
                $this->addFlash('warning', "💡 Conseil: {$anomaly['advice']}");

                // ✅ FIX #3 — Guard properly and add full error context
                if (!empty($userPhone)) {
                    $anomaly['type']   = $typeDepense;
                    $anomaly['montant'] = $montant;

                    $result = $smsService->sendAnomalyAlert($userPhone, $anomaly);

                    if ($result['success']) {
                        $this->addFlash('success', '📱 Alerte envoyée au ' . $userPhone);
                    } else {
                        $this->addFlash('error', '❌ Échec envoi: ' . ($result['error'] ?? 'Erreur inconnue'));
                    }
                } else {
                    $this->addFlash('error', '❌ SMS non envoyé — Ajoutez un numéro de téléphone dans votre profil.');
                }
            } else {
                $this->addFlash('success', '✅ Dépense ajoutée avec succès.');
            }

            return $this->redirectToRoute('front_finance_depenses');
        }

        foreach ($errors as $error) {
            $this->addFlash('error', $error);
        }
    }

    return $this->render('front/finance/depense_new.html.twig', [
        'errors' => $errors ?? [],
        'old' => $request->request->all(),
        'prefill' => [
            'montant' => $prefillMontant,
            'date' => $prefillDate,
            'type' => $prefillType,
            'description' => $prefillDescription,
        ]
    ]);
}
#[Route('/depenses/add-from-scan', name: 'front_finance_depense_add_from_scan', methods: ['POST'])]
public function addFromScan(
    Request $request,
    EntityManagerInterface $em,
    AnomalyDetectionService $anomalyService,
    SmsService $smsService,
    DepenseRepository $depenseRepository
): Response {
    // Vérifier l'authentification
    $user = $this->getUser();
    if (!$user) {
        return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
    }
    
    $data = json_decode($request->getContent(), true);
    
    if (!$data) {
        return $this->json(['success' => false, 'error' => 'Données invalides'], 400);
    }
    
    $typeDepense = $data['type'] ?? 'Autre';
    $montant = (float) ($data['montant'] ?? 0);
    $dateDepense = $data['date'] ?? date('Y-m-d');
    $description = $data['description'] ?? 'Reçu scanné';
    
    // Validation
    $typesValides = ['Carburant', 'Réparation', 'Semences', 'Engrais', 'Main d\'œuvre', 'Équipement', 'Vétérinaire', 'Autre'];
    
    if (empty($typeDepense) || !in_array($typeDepense, $typesValides)) {
        return $this->json(['success' => false, 'error' => 'Type non valide'], 400);
    }
    
    if ($montant <= 0) {
        return $this->json(['success' => false, 'error' => 'Montant invalide'], 400);
    }
    
    try {
        $userId = $user->getId();
        
        // Créer la dépense
        $depense = new Depense();
        $depense->setTypeDepense($typeDepense);
        $depense->setMontant($montant);
        $depense->setDateDepense(new \DateTime($dateDepense));
        $depense->setDescription($description);
        $depense->setUserId($userId);
        
        $em->persist($depense);
        $em->flush();
        
        // Détection d'anomalie
        $anomaly = $anomalyService->isAnomaly($typeDepense, $montant, $userId);
        
        if ($anomaly['is_anomaly']) {
            $userPhone = $user->getTelephone();
            if ($userPhone) {
                $anomaly['type'] = $typeDepense;
                $anomaly['montant'] = $montant;
                $smsService->sendAnomalyAlert($userPhone, $anomaly);
            }
        }
        
        return $this->json(['success' => true, 'id' => $depense->getId()]);
        
    } catch (\Exception $e) {
        return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
#[Route('/scan-receipt', name: 'front_finance_scan_receipt')]
public function scanReceipt(): Response
{
    return $this->render('front/finance/scan_receipt.html.twig');
}

    
    #[Route('/depenses/{id}/edit', name: 'front_finance_depense_edit', methods: ['GET', 'POST'])]
    public function editDepense(Request $request, Depense $depense, EntityManagerInterface $em): Response
    {
        $userId = $this->getUser()->getId();
        
        // Vérifier que la dépense appartient à l'utilisateur
        if ($depense->getUserId() !== $userId) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette dépense.');
        }
        
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $typeDepense = trim($request->request->get('type_depense'));
            $montant = $request->request->get('montant');
            $dateDepense = $request->request->get('date_depense');
            $description = trim($request->request->get('description'));
            
            $typesValides = ['Carburant', 'Réparation', 'Semences', 'Engrais', 'Main d\'œuvre', 'Équipement', 'Vétérinaire', 'Autre'];
            
            if (empty($typeDepense) || !in_array($typeDepense, $typesValides)) {
                $errors['type_depense'] = 'Type non valide';
            }
            
            if (empty($montant) || !is_numeric($montant) || $montant <= 0) {
                $errors['montant'] = 'Montant invalide';
            }
            
            if (empty($dateDepense)) {
                $errors['date_depense'] = 'La date est obligatoire';
            } else {
                $dateObj = \DateTime::createFromFormat('Y-m-d', $dateDepense);
                if (!$dateObj || $dateObj->format('Y-m-d') !== $dateDepense) {
                    $errors['date_depense'] = 'Date invalide';
                } elseif ($dateObj > new \DateTime()) {
                    $errors['date_depense'] = 'La date ne peut pas être dans le futur';
                }
            }
            
            if (empty($description)) {
                $errors['description'] = 'La description est obligatoire';
            }
            
            if (count($errors) === 0) {
                $depense->setTypeDepense($typeDepense);
                $depense->setMontant((float)$montant);
                $depense->setDateDepense(new \DateTime($dateDepense));
                $depense->setDescription($description);
                
                $em->flush();
                
                $this->addFlash('success', 'Dépense modifiée avec succès !');
                return $this->redirectToRoute('front_finance_depenses');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('front/finance/depense_edit.html.twig', [
            'depense' => $depense,
            'errors' => $errors,
        ]);
    }
    
    #[Route('/depenses/{id}/delete', name: 'front_finance_depense_delete', methods: ['POST'])]
    public function deleteDepense(Request $request, Depense $depense, EntityManagerInterface $em): Response
    {
        $userId = $this->getUser()->getId();
        
        if ($depense->getUserId() !== $userId) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette dépense.');
        }
        
        if ($this->isCsrfTokenValid('delete' . $depense->getIdDepense(), $request->request->get('_token'))) {
            $em->remove($depense);
            $em->flush();
            $this->addFlash('success', 'Dépense supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('front_finance_depenses');
    }
    
    #[Route('/revenus', name: 'front_finance_revenus')]
    public function revenus(
        Request $request, 
        RevenuRepository $repository, 
        PaginatorInterface $paginator,
        CurrencyService $currency
    ): Response {
        $userId = $this->getUser()->getId();
        $search = $request->query->get('search', '');
        $source = $request->query->get('source', 'all');
        $startDate = $request->query->get('start_date', '');
        $endDate = $request->query->get('end_date', '');
        
        $queryBuilder = $repository->createQueryBuilder('r')
            ->where('r.userId = :userId')
            ->setParameter('userId', $userId);
        
        if (!empty($search)) {
            $queryBuilder->andWhere('r.source LIKE :search OR r.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        if ($source !== 'all') {
            $queryBuilder->andWhere('r.source = :source')
                ->setParameter('source', $source);
        }
        
        if (!empty($startDate)) {
            $queryBuilder->andWhere('r.dateRevenu >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }
        
        if (!empty($endDate)) {
            $queryBuilder->andWhere('r.dateRevenu <= :endDate')
                ->setParameter('endDate', new \DateTime($endDate . ' 23:59:59'));
        }
        
        $queryBuilder->orderBy('r.dateRevenu', 'DESC');
        
        $revenus = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );
        
        $totalTND = 0;
        foreach ($revenus as $revenu) {
            $totalTND += $revenu->getMontant();
        }
        
        $totalEUR = $currency->convert($totalTND, 'EUR');
        $totalUSD = $currency->convert($totalTND, 'USD');
        
        $sourceOptions = $repository->getUniqueSourcesForUser($userId);
        
        $eurRate = $currency->getRateWithChange('EUR');
        $usdRate = $currency->getRateWithChange('USD');
        
        return $this->render('front/finance/revenus.html.twig', [
            'revenus' => $revenus,
            'totalTND' => $totalTND,
            'totalEUR' => $totalEUR,
            'totalUSD' => $totalUSD,
            'search' => $search,
            'selectedSource' => $source,
            'sourceOptions' => $sourceOptions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'eurRate' => $eurRate,
            'usdRate' => $usdRate,
        ]);
    }
    
    #[Route('/revenus/new', name: 'front_finance_revenu_new', methods: ['GET', 'POST'])]
    public function newRevenu(Request $request, EntityManagerInterface $em): Response
    {
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $source = trim($request->request->get('source'));
            $montant = (float) $request->request->get('montant');
            $dateRevenu = $request->request->get('date_revenu');
            $description = trim($request->request->get('description'));
            
            $sourcesValides = ['Vente récoltes', 'Vente animaux', 'Subventions', 'Prestations', 'Location matériel', 'Autre'];
            
            if (empty($source) || !in_array($source, $sourcesValides)) {
                $errors['source'] = 'Source non valide';
            }
            if ($montant <= 0) {
                $errors['montant'] = 'Montant invalide';
            }
            if (empty($dateRevenu)) {
                $errors['date_revenu'] = 'Date obligatoire';
            }
            if (empty($description)) {
                $errors['description'] = 'Description obligatoire';
            }
            
            if (count($errors) === 0) {
                $revenu = new Revenu();
                $revenu->setSource($source);
                $revenu->setMontant($montant);
                $revenu->setDateRevenu(new \DateTime($dateRevenu));
                $revenu->setDescription($description);
                $revenu->setUserId($this->getUser()->getId()); // ← LIER À L'UTILISATEUR
                
                $em->persist($revenu);
                $em->flush();
                
                $this->addFlash('success', 'Revenu ajouté avec succès !');
                return $this->redirectToRoute('front_finance_revenus');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('front/finance/revenu_new.html.twig', [
            'errors' => $errors ?? [],
            'old' => $request->request->all(),
        ]);
    }
    
    #[Route('/revenus/{id}/edit', name: 'front_finance_revenu_edit', methods: ['GET', 'POST'])]
    public function editRevenu(Request $request, Revenu $revenu, EntityManagerInterface $em): Response
    {
        $userId = $this->getUser()->getId();
        
        if ($revenu->getUserId() !== $userId) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce revenu.');
        }
        
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $source = trim($request->request->get('source'));
            $montant = $request->request->get('montant');
            $dateRevenu = $request->request->get('date_revenu');
            $description = trim($request->request->get('description'));
            
            $sourcesValides = ['Vente récoltes', 'Vente animaux', 'Subventions', 'Prestations', 'Location matériel', 'Autre'];
            
            if (empty($source) || !in_array($source, $sourcesValides)) {
                $errors['source'] = 'Source non valide';
            }
            
            if (empty($montant) || !is_numeric($montant) || $montant <= 0) {
                $errors['montant'] = 'Montant invalide';
            }
            
            if (empty($dateRevenu)) {
                $errors['date_revenu'] = 'La date est obligatoire';
            } else {
                $dateObj = \DateTime::createFromFormat('Y-m-d', $dateRevenu);
                if (!$dateObj || $dateObj->format('Y-m-d') !== $dateRevenu) {
                    $errors['date_revenu'] = 'Date invalide';
                } elseif ($dateObj > new \DateTime()) {
                    $errors['date_revenu'] = 'La date ne peut pas être dans le futur';
                }
            }
            
            if (empty($description)) {
                $errors['description'] = 'La description est obligatoire';
            }
            
            if (count($errors) === 0) {
                $revenu->setSource($source);
                $revenu->setMontant((float)$montant);
                $revenu->setDateRevenu(new \DateTime($dateRevenu));
                $revenu->setDescription($description);
                
                $em->flush();
                
                $this->addFlash('success', 'Revenu modifié avec succès !');
                return $this->redirectToRoute('front_finance_revenus');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('front/finance/revenu_edit.html.twig', [
            'revenu' => $revenu,
            'errors' => $errors,
        ]);
    }
    
    #[Route('/revenus/{id}/delete', name: 'front_finance_revenu_delete', methods: ['POST'])]
    public function deleteRevenu(Request $request, Revenu $revenu, EntityManagerInterface $em): Response
    {
        $userId = $this->getUser()->getId();
        
        if ($revenu->getUserId() !== $userId) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce revenu.');
        }
        
        if ($this->isCsrfTokenValid('delete' . $revenu->getIdRevenu(), $request->request->get('_token'))) {
            $em->remove($revenu);
            $em->flush();
            $this->addFlash('success', 'Revenu supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('front_finance_revenus');
    }
    #[Route('/chatbot', name: 'front_finance_chatbot', methods: ['POST'])]
public function chatbot(Request $request, FinanceChatbotService $chatbot, DepenseRepository $depenseRepo, RevenuRepository $revenuRepo): Response
{
    $data = json_decode($request->getContent(), true);
    $question = $data['question'] ?? '';
    
    if (empty($question)) {
        return $this->json(['error' => 'Question vide'], 400);
    }
    
    $userId = $this->getUser()->getId();
    
    // Récupérer les données financières de l'utilisateur
    $depenses = $depenseRepo->findBy(['userId' => $userId]);
    $revenus = $revenuRepo->findBy(['userId' => $userId]);
    
    $totalDepenses = array_sum(array_map(fn($d) => $d->getMontant(), $depenses));
    $totalRevenus = array_sum(array_map(fn($r) => $r->getMontant(), $revenus));
    
    $now = new \DateTime();
    $firstDayOfMonth = new \DateTime($now->format('Y-m-01'));
    
    $depensesCeMois = array_sum(array_map(
        fn($d) => $d->getDateDepense() >= $firstDayOfMonth ? $d->getMontant() : 0,
        $depenses
    ));
    
    $revenusCeMois = array_sum(array_map(
        fn($r) => $r->getDateRevenu() >= $firstDayOfMonth ? $r->getMontant() : 0,
        $revenus
    ));
    
    // Trouver la plus grosse catégorie
    $categories = [];
    foreach ($depenses as $depense) {
        $type = $depense->getTypeDepense();
        if (!isset($categories[$type])) {
            $categories[$type] = 0;
        }
        $categories[$type] += $depense->getMontant();
    }
    arsort($categories);
    $topCategorie = key($categories);
    
    $userData = [
        'totalRevenus' => $totalRevenus,
        'totalDepenses' => $totalDepenses,
        'balance' => $totalRevenus - $totalDepenses,
        'depensesCeMois' => $depensesCeMois,
        'revenusCeMois' => $revenusCeMois,
        'topCategorie' => $topCategorie ?? 'Aucune',
    ];
    
    $result = $chatbot->askQuestion($question, $userData);
    
    return $this->json($result);
}

}