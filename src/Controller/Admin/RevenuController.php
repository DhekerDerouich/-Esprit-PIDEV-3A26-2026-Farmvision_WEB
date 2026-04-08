<?php

namespace App\Controller\Admin;

use App\Entity\Revenu;
use App\Repository\RevenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/revenus')]
class RevenuController extends AbstractController
{
    #[Route('/', name: 'admin_revenu_index')]
public function index(Request $request, RevenuRepository $repository): Response
{
    $search = $request->query->get('search', '');
    $source = $request->query->get('source', 'all');
    $startDate = $request->query->get('start_date', '');
    $endDate = $request->query->get('end_date', '');
    
    // Recherche avec filtres (including date range)
    if (!empty($search) || ($source !== 'all') || !empty($startDate) || !empty($endDate)) {
        $revenus = $repository->search($search, $source, $startDate, $endDate);
    } else {
        $revenus = $repository->findBy([], ['dateRevenu' => 'DESC']);
    }
    
    $stats = $repository->getStatistics();
    $sourceOptions = $repository->getUniqueSources();
    
    return $this->render('admin/revenu/index.html.twig', [
        'revenus' => $revenus,
        'stats' => $stats,
        'sourceOptions' => $sourceOptions,
        'search' => $search,
        'selectedSource' => $source,
        'startDate' => $startDate,
        'endDate' => $endDate,
    ]);
}
    
    #[Route('/new', name: 'admin_revenu_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $source = trim($request->request->get('source'));
            $montant = $request->request->get('montant');
            $dateRevenu = $request->request->get('date_revenu');
            $description = trim($request->request->get('description'));
            
            $sourcesValides = ['Vente récoltes', 'Vente animaux', 'Subventions', 'Prestations', 'Location matériel', 'Autre'];
            if (empty($source)) {
                $errors['source'] = 'La source est obligatoire';
            } elseif (!in_array($source, $sourcesValides)) {
                $errors['source'] = 'Source non valide';
            }
            
            if (empty($montant)) {
                $errors['montant'] = 'Le montant est obligatoire';
            } elseif (!is_numeric($montant) || $montant <= 0) {
                $errors['montant'] = 'Le montant doit être un nombre positif';
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
            
            if (count($errors) === 0) {
                $revenu = new Revenu();
                $revenu->setSource($source);
                $revenu->setMontant((float)$montant);
                $revenu->setDateRevenu(new \DateTime($dateRevenu));
                $revenu->setDescription($description);
                
                $em->persist($revenu);
                $em->flush();
                
                $this->addFlash('success', 'Revenu ajouté avec succès !');
                return $this->redirectToRoute('admin_revenu_index');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('admin/revenu/new.html.twig', [
            'errors' => $errors ?? [],
            'old' => $request->request->all(),
        ]);
    }
    
    #[Route('/{idRevenu}/edit', name: 'admin_revenu_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Revenu $revenu, EntityManagerInterface $em): Response
    {
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
            
            if (count($errors) === 0) {
                $revenu->setSource($source);
                $revenu->setMontant((float)$montant);
                $revenu->setDateRevenu(new \DateTime($dateRevenu));
                $revenu->setDescription($description);
                
                $em->flush();
                
                $this->addFlash('success', 'Revenu modifié avec succès !');
                return $this->redirectToRoute('admin_revenu_index');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('admin/revenu/edit.html.twig', [
            'revenu' => $revenu,
            'errors' => $errors,
        ]);
    }
    
    #[Route('/{idRevenu}/delete', name: 'admin_revenu_delete', methods: ['POST'])]
    public function delete(Request $request, Revenu $revenu, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $revenu->getIdRevenu(), $request->request->get('_token'))) {
            $em->remove($revenu);
            $em->flush();
            $this->addFlash('success', 'Revenu supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('admin_revenu_index');
    }
    
    #[Route('/{idRevenu}', name: 'admin_revenu_show')]
    public function show(Revenu $revenu): Response
    {
        return $this->render('admin/revenu/show.html.twig', [
            'revenu' => $revenu,
        ]);
    }
}