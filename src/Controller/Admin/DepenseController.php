<?php

namespace App\Controller\Admin;

use App\Entity\Depense;
use App\Repository\DepenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/depenses')]
class DepenseController extends AbstractController
{
   #[Route('/', name: 'admin_depense_index')]
public function index(Request $request, DepenseRepository $repository): Response
{
    $search = $request->query->get('search', '');
    $type = $request->query->get('type', 'all');
    $startDate = $request->query->get('start_date', '');
    $endDate = $request->query->get('end_date', '');
    
    // Recherche avec filtres (including date range)
    if (!empty($search) || ($type !== 'all') || !empty($startDate) || !empty($endDate)) {
        $depenses = $repository->search($search, $type, $startDate, $endDate);
    } else {
        $depenses = $repository->findBy([], ['dateDepense' => 'DESC']);
    }
    
    $stats = $repository->getStatistics();
    $typeOptions = $repository->getUniqueTypes();
    
    return $this->render('admin/depense/index.html.twig', [
        'depenses' => $depenses,
        'stats' => $stats,
        'typeOptions' => $typeOptions,
        'search' => $search,
        'selectedType' => $type,
        'startDate' => $startDate,
        'endDate' => $endDate,
    ]);
}
    
    #[Route('/new', name: 'admin_depense_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $em): Response
{
    $errors = [];
    
    if ($request->isMethod('POST')) {
        $typeDepense = trim($request->request->get('type_depense'));
        $montant = $request->request->get('montant');
        $dateDepense = $request->request->get('date_depense');
        $description = trim($request->request->get('description'));
        
        $typesValides = ['Carburant', 'Réparation', 'Semences', 'Engrais', 'Main d\'œuvre', 'Équipement', 'Vétérinaire', 'Autre'];
        if (empty($typeDepense)) {
            $errors['type_depense'] = 'Le type de dépense est obligatoire';
        } elseif (!in_array($typeDepense, $typesValides)) {
            $errors['type_depense'] = 'Type non valide';
        }
        
        if (empty($montant)) {
            $errors['montant'] = 'Le montant est obligatoire';
        } elseif (!is_numeric($montant) || $montant <= 0) {
            $errors['montant'] = 'Le montant doit être un nombre positif';
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
        
        if (count($errors) === 0) {
            $depense = new Depense();
            $depense->setTypeDepense($typeDepense);
            $depense->setMontant((float)$montant);
            $depense->setDateDepense(new \DateTime($dateDepense));
            $depense->setDescription($description);
            
            $em->persist($depense);
            $em->flush();
            
            $this->addFlash('success', 'Dépense ajoutée avec succès !');
            return $this->redirectToRoute('admin_depense_index');
        }
        
        foreach ($errors as $error) {
            $this->addFlash('error', $error);
        }
    }
    
    return $this->render('admin/depense/new.html.twig', [
        'errors' => $errors ?? [],
        'old' => $request->request->all(),
    ]);
}
    
    #[Route('/{idDepense}/edit', name: 'admin_depense_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Depense $depense, EntityManagerInterface $em): Response
    {
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
            
            if (count($errors) === 0) {
                $depense->setTypeDepense($typeDepense);
                $depense->setMontant((float)$montant);
                $depense->setDateDepense(new \DateTime($dateDepense));
                $depense->setDescription($description);
                
                $em->flush();
                
                $this->addFlash('success', 'Dépense modifiée avec succès !');
                return $this->redirectToRoute('admin_depense_index');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('admin/depense/edit.html.twig', [
            'depense' => $depense,
            'errors' => $errors,
        ]);
    }
    
    #[Route('/{idDepense}/delete', name: 'admin_depense_delete', methods: ['POST'])]
    public function delete(Request $request, Depense $depense, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $depense->getIdDepense(), $request->request->get('_token'))) {
            $em->remove($depense);
            $em->flush();
            $this->addFlash('success', 'Dépense supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('admin_depense_index');
    }
    
    #[Route('/{idDepense}', name: 'admin_depense_show')]
    public function show(Depense $depense): Response
    {
        return $this->render('admin/depense/show.html.twig', [
            'depense' => $depense,
        ]);
    }
}