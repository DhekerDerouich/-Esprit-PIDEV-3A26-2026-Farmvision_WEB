<?php
// src/Controller/Admin/MaintenanceController.php

namespace App\Controller\Admin;

use App\Entity\Maintenance;
use App\Repository\EquipementRepository;
use App\Repository\MaintenanceRepository;
use App\Service\MeteoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/maintenances')]
class MaintenanceController extends AbstractController
{
    #[Route('/', name: 'admin_maintenance_index')]
    public function index(MaintenanceRepository $repository, MeteoService $meteoService): Response
    {
        $maintenances = $repository->findBy([], ['dateMaintenance' => 'DESC']);
        foreach ($maintenances as $m) { 
            $m->joursRestants = $m->getJoursRestants(); 
        }
        
        // Coordonnées de la Tunisie
        $coordonnees = ['lat' => 36.8065, 'lng' => 10.1815];
        $soleil = $meteoService->getSunriseSunset($coordonnees['lat'], $coordonnees['lng']);
        $meteo = $meteoService->getWeatherForecast($coordonnees['lat'], $coordonnees['lng']);
        
        return $this->render('admin/maintenance/index.html.twig', [
            'maintenances' => $maintenances,
            'stats' => $repository->getStatistics(),
            'soleil' => $soleil,
            'meteo' => $meteo,
        ]);
    }
    
    #[Route('/new', name: 'admin_maintenance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, EquipementRepository $equipementRepo, ValidatorInterface $validator, MeteoService $meteoService): Response
    {
        $maintenance = new Maintenance();
        $errors = [];
        
        // Données météo pour la planification
        $coordonnees = ['lat' => 36.8065, 'lng' => 10.1815];
        $soleil = $meteoService->getSunriseSunset($coordonnees['lat'], $coordonnees['lng']);
        
        if ($request->isMethod('POST')) {
            $equipementId = $request->request->get('equipement_id');
            $equipement = $equipementRepo->find($equipementId);
            
            if ($equipement) {
                $maintenance->setEquipement($equipement);
            }
            
            $maintenance->setTypeMaintenance($request->request->get('type_maintenance'));
            $maintenance->setDescription(trim($request->request->get('description')));
            
            $dateMaintenance = $request->request->get('date_maintenance');
            if ($dateMaintenance) {
                $maintenance->setDateMaintenance(new \DateTime($dateMaintenance));
            }
            
            $cout = $request->request->get('cout');
            if ($cout !== null && $cout !== '') {
                $maintenance->setCout((float)$cout);
            }
            
            $maintenance->setStatut($request->request->get('statut'));
            
            // Validation PHP avec Assert
            $violations = $validator->validate($maintenance);
            
            if (count($violations) === 0) {
                $em->persist($maintenance);
                $em->flush();
                $this->addFlash('success', 'Maintenance planifiée avec succès !');
                return $this->redirectToRoute('admin_maintenance_index');
            }
            
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
                $this->addFlash('error', $violation->getMessage());
            }
        }
        
        return $this->render('admin/maintenance/new.html.twig', [
            'equipements' => $equipementRepo->findAll(),
            'maintenance' => $maintenance,
            'errors' => $errors,
            'soleil' => $soleil,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_maintenance_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Maintenance $maintenance, EntityManagerInterface $em, EquipementRepository $equipementRepo, ValidatorInterface $validator, MeteoService $meteoService): Response
    {
        $errors = [];
        
        $coordonnees = ['lat' => 36.8065, 'lng' => 10.1815];
        $soleil = $meteoService->getSunriseSunset($coordonnees['lat'], $coordonnees['lng']);
        
        if ($request->isMethod('POST')) {
            $equipementId = $request->request->get('equipement_id');
            $equipement = $equipementRepo->find($equipementId);
            
            if ($equipement) {
                $maintenance->setEquipement($equipement);
            }
            
            $maintenance->setTypeMaintenance($request->request->get('type_maintenance'));
            $maintenance->setDescription(trim($request->request->get('description')));
            
            $dateMaintenance = $request->request->get('date_maintenance');
            if ($dateMaintenance) {
                $maintenance->setDateMaintenance(new \DateTime($dateMaintenance));
            }
            
            $cout = $request->request->get('cout');
            if ($cout !== null && $cout !== '') {
                $maintenance->setCout((float)$cout);
            } else {
                $maintenance->setCout(null);
            }
            
            $maintenance->setStatut($request->request->get('statut'));
            
            $violations = $validator->validate($maintenance);
            
            if (count($violations) === 0) {
                $em->flush();
                $this->addFlash('success', 'Maintenance modifiée avec succès !');
                return $this->redirectToRoute('admin_maintenance_index');
            }
            
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
                $this->addFlash('error', $violation->getMessage());
            }
        }
        
        return $this->render('admin/maintenance/edit.html.twig', [
            'maintenance' => $maintenance,
            'equipements' => $equipementRepo->findAll(),
            'errors' => $errors,
            'soleil' => $soleil,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_maintenance_delete', methods: ['POST'])]
    public function delete(Request $request, Maintenance $maintenance, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $maintenance->getId(), $request->request->get('_token'))) {
            $em->remove($maintenance);
            $em->flush();
            $this->addFlash('success', 'Maintenance supprimée avec succès !');
        }
        return $this->redirectToRoute('admin_maintenance_index');
    }
    
    #[Route('/{id}/show', name: 'admin_maintenance_show', methods: ['GET'])]
    public function show(Maintenance $maintenance, MeteoService $meteoService): Response
    {
        $coordonnees = ['lat' => 36.8065, 'lng' => 10.1815];
        $soleil = $meteoService->getSunriseSunset($coordonnees['lat'], $coordonnees['lng'], $maintenance->getDateMaintenance()->format('Y-m-d'));
        
        return $this->render('admin/maintenance/show.html.twig', [
            'maintenance' => $maintenance,
            'soleil' => $soleil,
        ]);
    }
    
    #[Route('/{id}/complete', name: 'admin_maintenance_complete', methods: ['POST'])]
    public function complete(Request $request, Maintenance $maintenance, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('complete' . $maintenance->getId(), $request->request->get('_token'))) {
            if ($maintenance->getStatut() === 'Réalisée') {
                $this->addFlash('error', 'Cette maintenance est déjà réalisée');
            } else {
                $maintenance->setStatut('Réalisée');
                $em->flush();
                $this->addFlash('success', 'Maintenance marquée comme réalisée !');
            }
        }
        return $this->redirectToRoute('admin_maintenance_index');
    }
}