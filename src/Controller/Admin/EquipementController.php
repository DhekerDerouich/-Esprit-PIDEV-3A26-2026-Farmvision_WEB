<?php
// src/Controller/Admin/EquipementController.php

namespace App\Controller\Admin;

use App\Entity\Equipement;
use App\Repository\EquipementRepository;
use App\Service\CarboneService;
use App\Service\QRCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/equipements')]
class EquipementController extends AbstractController
{
    #[Route('/', name: 'admin_equipement_index')]
    public function index(Request $request, EquipementRepository $repository, CarboneService $carboneService): Response
    {
        // Récupérer les paramètres de recherche
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', 'all');
        $etat = $request->query->get('etat', 'all');
        
        // Recherche avec filtres
        if (!empty($search) || $type !== 'all' || $etat !== 'all') {
            $equipements = $repository->search($search, $type, $etat);
        } else {
            $equipements = $repository->findAll();
        }
        
        // Ajouter les données CO2 pour chaque équipement
        foreach ($equipements as $equipement) {
            $co2Data = $carboneService->getCO2ByEquipement($equipement);
            $equipement->co2Data = $co2Data;
        }
        
        $stats = $repository->getStatistics();
        $types = $repository->getUniqueTypes();
        
        return $this->render('admin/equipement/index.html.twig', [
            'equipements' => $equipements,
            'stats' => $stats,
            'types' => $types,
            'search' => $search,
            'selectedType' => $type,
            'selectedEtat' => $etat,
        ]);
    }
    
    #[Route('/new', name: 'admin_equipement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $equipement = new Equipement();
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $equipement->setNom(trim($request->request->get('nom')));
            $equipement->setType($request->request->get('type'));
            $equipement->setEtat($request->request->get('etat'));
            
            $dateAchat = $request->request->get('date_achat');
            if ($dateAchat) {
                $equipement->setDateAchat(new \DateTime($dateAchat));
            }
            
            $dureeVie = $request->request->get('duree_vie_estimee');
            if ($dureeVie) {
                $equipement->setDureeVieEstimee((int)$dureeVie);
            }
            
            $violations = $validator->validate($equipement);
            
            if (count($violations) === 0) {
                $em->persist($equipement);
                $em->flush();
                $this->addFlash('success', 'Équipement ajouté avec succès !');
                return $this->redirectToRoute('admin_equipement_index');
            }
            
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
                $this->addFlash('error', $violation->getMessage());
            }
        }
        
        return $this->render('admin/equipement/new.html.twig', [
            'equipement' => $equipement,
            'errors' => $errors,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_equipement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Equipement $equipement, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $equipement->setNom(trim($request->request->get('nom')));
            $equipement->setType($request->request->get('type'));
            $equipement->setEtat($request->request->get('etat'));
            
            $dateAchat = $request->request->get('date_achat');
            if ($dateAchat) {
                $equipement->setDateAchat(new \DateTime($dateAchat));
            } else {
                $equipement->setDateAchat(null);
            }
            
            $dureeVie = $request->request->get('duree_vie_estimee');
            if ($dureeVie) {
                $equipement->setDureeVieEstimee((int)$dureeVie);
            } else {
                $equipement->setDureeVieEstimee(null);
            }
            
            $violations = $validator->validate($equipement);
            
            if (count($violations) === 0) {
                $em->flush();
                $this->addFlash('success', 'Équipement modifié avec succès !');
                return $this->redirectToRoute('admin_equipement_index');
            }
            
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
                $this->addFlash('error', $violation->getMessage());
            }
        }
        
        return $this->render('admin/equipement/edit.html.twig', [
            'equipement' => $equipement,
            'errors' => $errors,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_equipement_delete', methods: ['POST'])]
    public function delete(Request $request, Equipement $equipement, EntityManagerInterface $em): Response
    {
        if ($equipement->getMaintenances()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cet équipement car il a des maintenances associées.');
            return $this->redirectToRoute('admin_equipement_index');
        }
        
        if ($this->isCsrfTokenValid('delete' . $equipement->getId(), $request->request->get('_token'))) {
            $em->remove($equipement);
            $em->flush();
            $this->addFlash('success', 'Équipement supprimé avec succès !');
        }
        
        return $this->redirectToRoute('admin_equipement_index');
    }
    
    #[Route('/{id}', name: 'admin_equipement_show', methods: ['GET'])]
    public function show(Equipement $equipement, CarboneService $carboneService): Response
    {
        $co2Data = $carboneService->getCO2ByEquipement($equipement);
        
        return $this->render('admin/equipement/show.html.twig', [
            'equipement' => $equipement,
            'co2Data' => $co2Data,
        ]);
    }
    
    #[Route('/{id}/qr', name: 'admin_equipement_qr', methods: ['GET'])]
    public function showQR(Equipement $equipement, QRCodeService $qrService): Response
    {
        $qrCode = $qrService->generateBase64QR($equipement);
        
        return $this->render('admin/equipement/qr.html.twig', [
            'equipement' => $equipement,
            'qrCode' => $qrCode,
        ]);
    }

    #[Route('/{id}/qr/download', name: 'admin_equipement_qr_download', methods: ['GET'])]
    public function downloadQR(Equipement $equipement, QRCodeService $qrService): Response
    {
        $qrCode = $qrService->generateBase64QR($equipement);
        $qrImageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $qrCode));
        
        return new Response($qrImageData, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => sprintf('attachment; filename="qr_equipement_%d.png"', $equipement->getId())
        ]);
    }
}