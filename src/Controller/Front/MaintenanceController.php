<?php
// src/Controller/Front/MaintenanceController.php

namespace App\Controller\Front;

use App\Entity\Maintenance;
use App\Repository\EquipementRepository;
use App\Repository\MaintenanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/maintenances')]
class MaintenanceController extends AbstractController
{
    #[Route('/', name: 'front_maintenance_index', methods: ['GET'])]
    public function index(Request $request, MaintenanceRepository $repository): Response
    {
        $keyword = $request->query->get('search', '');
        $type = $request->query->get('type', 'all');
        $statut = $request->query->get('statut', 'all');
        
        // Recherche avec filtres
        if (!empty($keyword) || ($type !== 'all') || ($statut !== 'all')) {
            $maintenances = $repository->search($keyword, $type, $statut);
        } else {
            $maintenances = $repository->findBy([], ['dateMaintenance' => 'ASC']);
        }
        
        $stats = $repository->getStatistics();
        
        // Ajouter les jours restants
        foreach ($maintenances as $maintenance) {
            $maintenance->joursRestants = $maintenance->getJoursRestants();
        }
        
        return $this->render('front/maintenance/index.html.twig', [
            'maintenances' => $maintenances,
            'stats' => $stats,
            'search' => $keyword,
            'selectedType' => $type,
            'selectedStatut' => $statut,
        ]);
    }
    
    #[Route('/new', name: 'front_maintenance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, EquipementRepository $equipementRepo): Response
    {
        // Seul l'agriculteur peut ajouter une maintenance
        $this->denyAccessUnlessGranted('ROLE_AGRICULTEUR');
        
        $equipements = $equipementRepo->findAll();
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $equipementId = $request->request->get('equipement_id');
            $typeMaintenance = $request->request->get('type_maintenance');
            $description = trim($request->request->get('description'));
            $dateMaintenance = $request->request->get('date_maintenance');
            $cout = $request->request->get('cout');
            $statut = $request->request->get('statut');
            
            // Validation
            if (empty($equipementId)) {
                $errors['equipement_id'] = 'Veuillez sélectionner un équipement';
            } else {
                $equipement = $equipementRepo->find($equipementId);
                if (!$equipement) {
                    $errors['equipement_id'] = 'Équipement non trouvé';
                }
            }
            
            if (empty($typeMaintenance)) {
                $errors['type_maintenance'] = 'Le type de maintenance est obligatoire';
            }
            
            if (empty($dateMaintenance)) {
                $errors['date_maintenance'] = 'La date est obligatoire';
            } else {
                $dateObj = \DateTime::createFromFormat('Y-m-d', $dateMaintenance);
                if (!$dateObj) {
                    $errors['date_maintenance'] = 'Date invalide';
                }
            }
            
            if (!empty($cout) && (!is_numeric($cout) || $cout < 0)) {
                $errors['cout'] = 'Le coût doit être un nombre positif';
            }
            
            if (count($errors) === 0) {
                $maintenance = new Maintenance();
                $maintenance->setEquipement($equipement);
                $maintenance->setTypeMaintenance($typeMaintenance);
                $maintenance->setDescription($description);
                $maintenance->setDateMaintenance($dateObj);
                if ($cout) {
                    $maintenance->setCout((float)$cout);
                }
                $maintenance->setStatut($statut ?? 'Planifiée');
                
                $em->persist($maintenance);
                $em->flush();
                
                $this->addFlash('success', 'Maintenance planifiée avec succès !');
                return $this->redirectToRoute('front_maintenance_index');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('front/maintenance/new.html.twig', [
            'equipements' => $equipements,
            'errors' => $errors,
            'old' => $request->request->all(),
        ]);
    }
    
    #[Route('/{id}/edit', name: 'front_maintenance_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Maintenance $maintenance, EntityManagerInterface $em, EquipementRepository $equipementRepo): Response
    {
        // Seul l'agriculteur peut modifier une maintenance
        $this->denyAccessUnlessGranted('ROLE_AGRICULTEUR');
        
        $equipements = $equipementRepo->findAll();
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $equipementId = $request->request->get('equipement_id');
            $typeMaintenance = $request->request->get('type_maintenance');
            $description = trim($request->request->get('description'));
            $dateMaintenance = $request->request->get('date_maintenance');
            $cout = $request->request->get('cout');
            $statut = $request->request->get('statut');
            
            if (empty($equipementId)) {
                $errors['equipement_id'] = 'Veuillez sélectionner un équipement';
            } else {
                $equipement = $equipementRepo->find($equipementId);
                if (!$equipement) {
                    $errors['equipement_id'] = 'Équipement non trouvé';
                }
            }
            
            if (empty($dateMaintenance)) {
                $errors['date_maintenance'] = 'La date est obligatoire';
            } else {
                $dateObj = \DateTime::createFromFormat('Y-m-d', $dateMaintenance);
                if (!$dateObj) {
                    $errors['date_maintenance'] = 'Date invalide';
                }
            }
            
            if (count($errors) === 0) {
                $maintenance->setEquipement($equipement);
                $maintenance->setTypeMaintenance($typeMaintenance);
                $maintenance->setDescription($description);
                $maintenance->setDateMaintenance($dateObj);
                if ($cout) {
                    $maintenance->setCout((float)$cout);
                }
                $maintenance->setStatut($statut);
                
                $em->flush();
                
                $this->addFlash('success', 'Maintenance modifiée avec succès !');
                return $this->redirectToRoute('front_maintenance_index');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('front/maintenance/edit.html.twig', [
            'maintenance' => $maintenance,
            'equipements' => $equipements,
            'errors' => $errors,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'front_maintenance_delete', methods: ['POST'])]
    public function delete(Request $request, Maintenance $maintenance, EntityManagerInterface $em): Response
    {
        // Seul l'agriculteur peut supprimer une maintenance
        $this->denyAccessUnlessGranted('ROLE_AGRICULTEUR');
        
        if ($this->isCsrfTokenValid('delete' . $maintenance->getId(), $request->request->get('_token'))) {
            $em->remove($maintenance);
            $em->flush();
            $this->addFlash('success', 'Maintenance supprimée avec succès !');
        }
        
        return $this->redirectToRoute('front_maintenance_index');
    }
    
    #[Route('/{id}/complete', name: 'front_maintenance_complete', methods: ['POST'])]
    public function complete(Request $request, Maintenance $maintenance, EntityManagerInterface $em): Response
    {
        // Seul l'agriculteur peut marquer une maintenance comme réalisée
        $this->denyAccessUnlessGranted('ROLE_AGRICULTEUR');
        
        if ($this->isCsrfTokenValid('complete' . $maintenance->getId(), $request->request->get('_token'))) {
            $maintenance->setStatut('Réalisée');
            $em->flush();
            $this->addFlash('success', 'Maintenance marquée comme réalisée !');
        }
        
        return $this->redirectToRoute('front_maintenance_index');
    }
}