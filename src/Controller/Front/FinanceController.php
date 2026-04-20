<?php

namespace App\Controller\Front;

use App\Entity\Depense;
use App\Entity\Revenu;
use App\Repository\DepenseRepository;
use App\Repository\RevenuRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function dashboard(DepenseRepository $depenseRepo, RevenuRepository $revenuRepo): Response
    {
        // Get ALL transactions (no user filter)
        $depenses = $depenseRepo->findBy([], ['dateDepense' => 'DESC']);
        $revenus = $revenuRepo->findBy([], ['dateRevenu' => 'DESC']);
        
        // Calculate totals
        $totalDepenses = 0;
        foreach ($depenses as $depense) {
            $totalDepenses += $depense->getMontant();
        }
        
        $totalRevenus = 0;
        foreach ($revenus as $revenu) {
            $totalRevenus += $revenu->getMontant();
        }
        
        $balance = $totalRevenus - $totalDepenses;
        
        // This month totals
        $now = new \DateTime();
        $firstDayOfMonth = new \DateTime($now->format('Y-m-01'));
        
        $depensesCeMois = 0;
        foreach ($depenses as $depense) {
            if ($depense->getDateDepense() >= $firstDayOfMonth) {
                $depensesCeMois += $depense->getMontant();
            }
        }
        
        $revenusCeMois = 0;
        foreach ($revenus as $revenu) {
            if ($revenu->getDateRevenu() >= $firstDayOfMonth) {
                $revenusCeMois += $revenu->getMontant();
            }
        }
        
        // Recent transactions (last 5)
        $allTransactions = [];
        foreach ($depenses as $depense) {
            $allTransactions[] = [
                'type' => 'depense',
                'date' => $depense->getDateDepense(),
                'montant' => $depense->getMontant(),
                'categorie' => $depense->getTypeDepense(),
                'description' => $depense->getDescription(),
                'id' => $depense->getIdDepense(),
            ];
        }
        foreach ($revenus as $revenu) {
            $allTransactions[] = [
                'type' => 'revenu',
                'date' => $revenu->getDateRevenu(),
                'montant' => $revenu->getMontant(),
                'categorie' => $revenu->getSource(),
                'description' => $revenu->getDescription(),
                'id' => $revenu->getIdRevenu(),
            ];
        }
        
        usort($allTransactions, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });
        
        $recentTransactions = array_slice($allTransactions, 0, 5);
        
        return $this->render('front/finance/dashboard.html.twig', [
            'totalDepenses' => $totalDepenses,
            'totalRevenus' => $totalRevenus,
            'balance' => $balance,
            'depensesCeMois' => $depensesCeMois,
            'revenusCeMois' => $revenusCeMois,
            'recentTransactions' => $recentTransactions,
        ]);
    }
    
    #[Route('/depenses', name: 'front_finance_depenses')]
public function depenses(DepenseRepository $repository, Request $request): Response
{
    $search = $request->query->get('search', '');
    $type = $request->query->get('type', 'all');
    $startDate = $request->query->get('start_date', '');
    $endDate = $request->query->get('end_date', '');
    
    // Search with date range
    $depenses = $repository->search($search, $type, $startDate, $endDate);
    
    $total = 0;
    foreach ($depenses as $depense) {
        $total += $depense->getMontant();
    }
    
    $typeOptions = $repository->getUniqueTypes();
    
    return $this->render('front/finance/depenses.html.twig', [
        'depenses' => $depenses,
        'total' => $total,
        'search' => $search,
        'selectedType' => $type,
        'typeOptions' => $typeOptions,
        'startDate' => $startDate,
        'endDate' => $endDate,
    ]);
}
    
    #[Route('/depenses/new', name: 'front_finance_depense_new', methods: ['GET', 'POST'])]
    public function newDepense(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $depense = new Depense();
            // NO user set
            $depense->setTypeDepense($request->request->get('type_depense'));
            $depense->setMontant($request->request->get('montant'));
            $depense->setDateDepense(new \DateTime($request->request->get('date_depense')));
            $depense->setDescription($request->request->get('description'));
            
            $em->persist($depense);
            $em->flush();
            
            $this->addFlash('success', 'Dépense ajoutée avec succès !');
            return $this->redirectToRoute('front_finance_depenses');
        }
        
        return $this->render('front/finance/depense_new.html.twig');
    }
    
    #[Route('/depenses/{id}/edit', name: 'front_finance_depense_edit', methods: ['GET', 'POST'])]
    public function editDepense(Request $request, Depense $depense, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $depense->setTypeDepense($request->request->get('type_depense'));
            $depense->setMontant($request->request->get('montant'));
            $depense->setDateDepense(new \DateTime($request->request->get('date_depense')));
            $depense->setDescription($request->request->get('description'));
            
            $em->flush();
            
            $this->addFlash('success', 'Dépense modifiée avec succès !');
            return $this->redirectToRoute('front_finance_depenses');
        }
        
        return $this->render('front/finance/depense_edit.html.twig', [
            'depense' => $depense,
        ]);
    }
    
    #[Route('/depenses/{id}/delete', name: 'front_finance_depense_delete', methods: ['POST'])]
    public function deleteDepense(Request $request, Depense $depense, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $depense->getIdDepense(), $request->request->get('_token'))) {
            $em->remove($depense);
            $em->flush();
            $this->addFlash('success', 'Dépense supprimée avec succès !');
        }
        
        return $this->redirectToRoute('front_finance_depenses');
    }
    
   #[Route('/revenus', name: 'front_finance_revenus')]
public function revenus(RevenuRepository $repository, Request $request): Response
{
    $search = $request->query->get('search', '');
    $source = $request->query->get('source', 'all');
    $startDate = $request->query->get('start_date', '');
    $endDate = $request->query->get('end_date', '');
    
    // Search with date range
    $revenus = $repository->search($search, $source, $startDate, $endDate);
    
    $total = 0;
    foreach ($revenus as $revenu) {
        $total += $revenu->getMontant();
    }
    
    $sourceOptions = $repository->getUniqueSources();
    
    return $this->render('front/finance/revenus.html.twig', [
        'revenus' => $revenus,
        'total' => $total,
        'search' => $search,
        'selectedSource' => $source,
        'sourceOptions' => $sourceOptions,
        'startDate' => $startDate,
        'endDate' => $endDate,
    ]);
}
    #[Route('/revenus/new', name: 'front_finance_revenu_new', methods: ['GET', 'POST'])]
    public function newRevenu(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $revenu = new Revenu();
            // NO user set
            $revenu->setSource($request->request->get('source'));
            $revenu->setMontant($request->request->get('montant'));
            $revenu->setDateRevenu(new \DateTime($request->request->get('date_revenu')));
            $revenu->setDescription($request->request->get('description'));
            
            $em->persist($revenu);
            $em->flush();
            
            $this->addFlash('success', 'Revenu ajouté avec succès !');
            return $this->redirectToRoute('front_finance_revenus');
        }
        
        return $this->render('front/finance/revenu_new.html.twig');
    }
    
    #[Route('/revenus/{id}/edit', name: 'front_finance_revenu_edit', methods: ['GET', 'POST'])]
    public function editRevenu(Request $request, Revenu $revenu, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $revenu->setSource($request->request->get('source'));
            $revenu->setMontant($request->request->get('montant'));
            $revenu->setDateRevenu(new \DateTime($request->request->get('date_revenu')));
            $revenu->setDescription($request->request->get('description'));
            
            $em->flush();
            
            $this->addFlash('success', 'Revenu modifié avec succès !');
            return $this->redirectToRoute('front_finance_revenus');
        }
        
        return $this->render('front/finance/revenu_edit.html.twig', [
            'revenu' => $revenu,
        ]);
    }
    
    #[Route('/revenus/{id}/delete', name: 'front_finance_revenu_delete', methods: ['POST'])]
    public function deleteRevenu(Request $request, Revenu $revenu, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $revenu->getIdRevenu(), $request->request->get('_token'))) {
            $em->remove($revenu);
            $em->flush();
            $this->addFlash('success', 'Revenu supprimé avec succès !');
        }
        
        return $this->redirectToRoute('front_finance_revenus');
    }
}