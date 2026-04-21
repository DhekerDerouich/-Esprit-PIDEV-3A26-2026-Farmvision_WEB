<?php
// src/Controller/Front/EquipementController.php

namespace App\Controller\Front;

use App\Entity\Equipement;
use App\Repository\EquipementRepository;
use App\Service\CarboneService;
use App\Service\QRCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/equipements')]
class EquipementController extends AbstractController
{
    #[Route('/', name: 'front_equipement_index', methods: ['GET'])]
    public function index(Request $request, EquipementRepository $repository, CarboneService $carboneService): Response
    {
        $keyword = $request->query->get('search', '');
        $type = $request->query->get('type', 'all');
        $etat = $request->query->get('etat', 'all');
        
        // Recherche avec filtres
        if (!empty($keyword) || ($type !== 'all') || ($etat !== 'all')) {
            $equipements = $repository->search($keyword, $type, $etat);
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
        
        return $this->render('front/equipement/index.html.twig', [
            'equipements' => $equipements,
            'stats' => $stats,
            'types' => $types,
            'search' => $keyword,
            'selectedType' => $type,
            'selectedEtat' => $etat,
        ]);
    }
    
    #[Route('/{id}', name: 'front_equipement_show', methods: ['GET'])]
    public function show(int $id, EquipementRepository $repository, CarboneService $carboneService): Response
    {
        $equipement = $repository->find($id);
        
        if (!$equipement) {
            throw $this->createNotFoundException('Équipement non trouvé');
        }
        
        $co2Data = $carboneService->getCO2ByEquipement($equipement);
        
        return $this->render('front/equipement/show.html.twig', [
            'equipement' => $equipement,
            'co2Data' => $co2Data,
        ]);
    }
    
    #[Route('/new', name: 'front_equipement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        // Seul l'agriculteur peut ajouter un équipement
        $this->denyAccessUnlessGranted('ROLE_AGRICULTEUR');
        
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom'));
            $type = $request->request->get('type');
            $etat = $request->request->get('etat');
            $dateAchat = $request->request->get('date_achat');
            $dureeVie = $request->request->get('duree_vie_estimee');
            
            // Validation
            if (empty($nom)) {
                $errors['nom'] = 'Le nom est obligatoire';
            }
            if (empty($type)) {
                $errors['type'] = 'Le type est obligatoire';
            }
            
            if (count($errors) === 0) {
                $equipement = new Equipement();
                $equipement->setNom($nom);
                $equipement->setType($type);
                $equipement->setEtat($etat ?? 'Fonctionnel');
                
                if ($dateAchat) {
                    $equipement->setDateAchat(new \DateTime($dateAchat));
                }
                if ($dureeVie) {
                    $equipement->setDureeVieEstimee((int)$dureeVie);
                }
                
                $em->persist($equipement);
                $em->flush();
                
                $this->addFlash('success', 'Équipement ajouté avec succès !');
                return $this->redirectToRoute('front_equipement_index');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('front/equipement/new.html.twig', [
            'errors' => $errors,
            'old' => $request->request->all(),
        ]);
    }
    
    #[Route('/{id}/edit', name: 'front_equipement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Equipement $equipement, EntityManagerInterface $em): Response
    {
        // Seul l'agriculteur peut modifier un équipement
        $this->denyAccessUnlessGranted('ROLE_AGRICULTEUR');
        
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom'));
            $type = $request->request->get('type');
            $etat = $request->request->get('etat');
            $dateAchat = $request->request->get('date_achat');
            $dureeVie = $request->request->get('duree_vie_estimee');
            
            if (empty($nom)) {
                $errors['nom'] = 'Le nom est obligatoire';
            }
            
            if (count($errors) === 0) {
                $equipement->setNom($nom);
                $equipement->setType($type);
                $equipement->setEtat($etat);
                
                if ($dateAchat) {
                    $equipement->setDateAchat(new \DateTime($dateAchat));
                }
                if ($dureeVie) {
                    $equipement->setDureeVieEstimee((int)$dureeVie);
                }
                
                $em->flush();
                
                $this->addFlash('success', 'Équipement modifié avec succès !');
                return $this->redirectToRoute('front_equipement_index');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('front/equipement/edit.html.twig', [
            'equipement' => $equipement,
            'errors' => $errors,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'front_equipement_delete', methods: ['POST'])]
    public function delete(Request $request, Equipement $equipement, EntityManagerInterface $em): Response
    {
        // Seul l'agriculteur peut supprimer un équipement
        $this->denyAccessUnlessGranted('ROLE_AGRICULTEUR');
        
        if ($this->isCsrfTokenValid('delete' . $equipement->getId(), $request->request->get('_token'))) {
            $em->remove($equipement);
            $em->flush();
            $this->addFlash('success', 'Équipement supprimé avec succès !');
        }
        
        return $this->redirectToRoute('front_equipement_index');
    }
    
    #[Route('/{id}/qr', name: 'front_equipement_qr', methods: ['GET'])]
    public function showQR(Equipement $equipement, QRCodeService $qrService): Response
    {
        $qrCode = $qrService->generateBase64QR($equipement);
        
        return $this->render('front/equipement/qr.html.twig', [
            'equipement' => $equipement,
            'qrCode' => $qrCode,
        ]);
    }

    #[Route('/{id}/qr/download', name: 'front_equipement_qr_download', methods: ['GET'])]
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