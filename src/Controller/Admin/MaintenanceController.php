<?php
// src/Controller/Admin/MaintenanceController.php

namespace App\Controller\Admin;

use App\Entity\Maintenance;
use App\Repository\EquipementRepository;
use App\Repository\MaintenanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/maintenances')]
class MaintenanceController extends AbstractController
{
    #[Route('/', name: 'admin_maintenance_index')]
    public function index(Request $request, MaintenanceRepository $repository): Response
    {
        $keyword = $request->query->get('search', '');
        $type = $request->query->get('type', 'all');
        $statut = $request->query->get('statut', 'all');
        
        // Recherche avec filtres
        if (!empty($keyword) || ($type !== 'all') || ($statut !== 'all')) {
            $maintenances = $repository->search($keyword, $type, $statut);
        } else {
            $maintenances = $repository->findBy([], ['dateMaintenance' => 'DESC']);
        }
        
        $stats = $repository->getStatistics();
        
        // Ajouter les jours restants pour chaque maintenance
        foreach ($maintenances as $maintenance) {
            $maintenance->joursRestants = $maintenance->getJoursRestants();
        }
        
        return $this->render('admin/maintenance/index.html.twig', [
            'maintenances' => $maintenances,
            'stats' => $stats,
            'search' => $keyword,
            'selectedType' => $type,
            'selectedStatut' => $statut,
        ]);
    }
    
    #[Route('/new', name: 'admin_maintenance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, EquipementRepository $equipementRepo): Response
    {
        $equipements = $equipementRepo->findAll();
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $equipementId = $request->request->get('equipement_id');
            $typeMaintenance = $request->request->get('type_maintenance');
            $description = trim($request->request->get('description'));
            $dateMaintenance = $request->request->get('date_maintenance');
            $cout = $request->request->get('cout');
            $statut = $request->request->get('statut');
            
            // VALIDATION
            if (empty($equipementId)) {
                $errors['equipement_id'] = 'Veuillez sélectionner un équipement';
            } else {
                $equipement = $equipementRepo->find($equipementId);
                if (!$equipement) {
                    $errors['equipement_id'] = 'Équipement non trouvé';
                }
            }
            
            $typesValides = ['Préventive', 'Corrective'];
            if (empty($typeMaintenance) || !in_array($typeMaintenance, $typesValides)) {
                $errors['type_maintenance'] = 'Type de maintenance non valide';
            }
            
            if (!empty($description) && strlen($description) > 1000) {
                $errors['description'] = 'La description ne doit pas dépasser 1000 caractères';
            }
            
            if (empty($dateMaintenance)) {
                $errors['date_maintenance'] = 'La date est obligatoire';
            } else {
                $dateObj = \DateTime::createFromFormat('Y-m-d', $dateMaintenance);
                if (!$dateObj || $dateObj->format('Y-m-d') !== $dateMaintenance) {
                    $errors['date_maintenance'] = 'Date invalide';
                }
            }
            
            if (!empty($cout)) {
                if (!is_numeric($cout)) {
                    $errors['cout'] = 'Le coût doit être un nombre';
                } elseif ($cout < 0) {
                    $errors['cout'] = 'Le coût ne peut pas être négatif';
                } elseif ($cout > 100000) {
                    $errors['cout'] = 'Le coût ne peut pas dépasser 100 000 DT';
                }
            }
            
            $statutsValides = ['Planifiée', 'Réalisée'];
            if (empty($statut) || !in_array($statut, $statutsValides)) {
                $errors['statut'] = 'Statut non valide';
            }
            
            // Vérifier que la date n'est pas trop ancienne pour une maintenance planifiée
            if (isset($dateObj) && $statut === 'Planifiée' && $dateObj < new \DateTime()) {
                $errors['date_maintenance'] = 'Une maintenance planifiée ne peut pas avoir une date passée';
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
                $maintenance->setStatut($statut);
                
                $em->persist($maintenance);
                $em->flush();
                
                $this->addFlash('success', 'Maintenance planifiée avec succès !');
                return $this->redirectToRoute('admin_maintenance_index');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('admin/maintenance/new.html.twig', [
            'equipements' => $equipements,
            'errors' => $errors ?? [],
            'old' => $request->request->all(),
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_maintenance_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Maintenance $maintenance, EntityManagerInterface $em, EquipementRepository $equipementRepo): Response
    {
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
            
            $typesValides = ['Préventive', 'Corrective'];
            if (empty($typeMaintenance) || !in_array($typeMaintenance, $typesValides)) {
                $errors['type_maintenance'] = 'Type de maintenance non valide';
            }
            
            if (!empty($description) && strlen($description) > 1000) {
                $errors['description'] = 'La description ne doit pas dépasser 1000 caractères';
            }
            
            if (empty($dateMaintenance)) {
                $errors['date_maintenance'] = 'La date est obligatoire';
            } else {
                $dateObj = \DateTime::createFromFormat('Y-m-d', $dateMaintenance);
                if (!$dateObj || $dateObj->format('Y-m-d') !== $dateMaintenance) {
                    $errors['date_maintenance'] = 'Date invalide';
                }
            }
            
            if (!empty($cout)) {
                if (!is_numeric($cout)) {
                    $errors['cout'] = 'Le coût doit être un nombre';
                } elseif ($cout < 0) {
                    $errors['cout'] = 'Le coût ne peut pas être négatif';
                } elseif ($cout > 100000) {
                    $errors['cout'] = 'Le coût ne peut pas dépasser 100 000 DT';
                }
            }
            
            $statutsValides = ['Planifiée', 'Réalisée'];
            if (empty($statut) || !in_array($statut, $statutsValides)) {
                $errors['statut'] = 'Statut non valide';
            }
            
            if (count($errors) === 0) {
                $maintenance->setEquipement($equipement);
                $maintenance->setTypeMaintenance($typeMaintenance);
                $maintenance->setDescription($description);
                $maintenance->setDateMaintenance($dateObj);
                if ($cout) {
                    $maintenance->setCout((float)$cout);
                } else {
                    $maintenance->setCout(null);
                }
                $maintenance->setStatut($statut);
                
                $em->flush();
                
                $this->addFlash('success', 'Maintenance modifiée avec succès !');
                return $this->redirectToRoute('admin_maintenance_index');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('admin/maintenance/edit.html.twig', [
            'maintenance' => $maintenance,
            'equipements' => $equipements,
            'errors' => $errors,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_maintenance_delete', methods: ['POST'])]
    public function delete(Request $request, Maintenance $maintenance, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $maintenance->getId(), $request->request->get('_token'))) {
            $em->remove($maintenance);
            $em->flush();
            $this->addFlash('success', 'Maintenance supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('admin_maintenance_index');
    }
    
    #[Route('/{id}/show', name: 'admin_maintenance_show', methods: ['GET'])]
    public function show(Maintenance $maintenance): Response
    {
        return $this->render('admin/maintenance/show.html.twig', [
            'maintenance' => $maintenance,
        ]);
    }
    
    #[Route('/{id}/complete', name: 'admin_maintenance_complete', methods: ['POST'])]
    public function complete(Request $request, Maintenance $maintenance, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('complete' . $maintenance->getId(), $request->request->get('_token'))) {
            // Vérifier que la maintenance n'est pas déjà réalisée
            if ($maintenance->getStatut() === 'Réalisée') {
                $this->addFlash('error', 'Cette maintenance est déjà réalisée');
            } else {
                $maintenance->setStatut('Réalisée');
                $em->flush();
                $this->addFlash('success', 'Maintenance marquée comme réalisée !');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('admin_maintenance_index');
    }
}