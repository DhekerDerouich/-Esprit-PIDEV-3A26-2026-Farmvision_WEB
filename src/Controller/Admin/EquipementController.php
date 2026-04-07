<?php
// src/Controller/Admin/EquipementController.php

namespace App\Controller\Admin;

use App\Entity\Equipement;
use App\Repository\EquipementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/equipements')]
class EquipementController extends AbstractController
{
    #[Route('/', name: 'admin_equipement_index')]
    public function index(Request $request, EquipementRepository $repository): Response
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
        
        $stats = $repository->getStatistics();
        $types = $repository->getUniqueTypes();
        
        return $this->render('admin/equipement/index.html.twig', [
            'equipements' => $equipements,
            'stats' => $stats,
            'types' => $types,
            'search' => $keyword,
            'selectedType' => $type,
            'selectedEtat' => $etat,
        ]);
    }
    
    #[Route('/new', name: 'admin_equipement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $errors = [];
        
        if ($request->isMethod('POST')) {
            // Récupération des données
            $nom = trim($request->request->get('nom'));
            $type = $request->request->get('type');
            $etat = $request->request->get('etat');
            $dateAchat = $request->request->get('date_achat');
            $dureeVie = $request->request->get('duree_vie_estimee');
            
            // VALIDATION
            if (empty($nom)) {
                $errors['nom'] = 'Le nom est obligatoire';
            } elseif (strlen($nom) < 2) {
                $errors['nom'] = 'Le nom doit contenir au moins 2 caractères';
            } elseif (strlen($nom) > 100) {
                $errors['nom'] = 'Le nom ne doit pas dépasser 100 caractères';
            }
            
            $typesValides = ['Tracteur', 'Moissonneuse', 'Pulvérisateur', 'Charrue', 'Semoir', 'Autre'];
            if (empty($type)) {
                $errors['type'] = 'Le type est obligatoire';
            } elseif (!in_array($type, $typesValides)) {
                $errors['type'] = 'Type non valide';
            }
            
            $etatsValides = ['Fonctionnel', 'En panne', 'Maintenance'];
            if (empty($etat)) {
                $errors['etat'] = 'L\'état est obligatoire';
            } elseif (!in_array($etat, $etatsValides)) {
                $errors['etat'] = 'État non valide';
            }
            
            if (!empty($dateAchat)) {
                $dateObj = \DateTime::createFromFormat('Y-m-d', $dateAchat);
                if (!$dateObj || $dateObj->format('Y-m-d') !== $dateAchat) {
                    $errors['date_achat'] = 'Date d\'achat invalide';
                } elseif ($dateObj > new \DateTime()) {
                    $errors['date_achat'] = 'La date d\'achat ne peut pas être dans le futur';
                }
            }
            
            if (!empty($dureeVie)) {
                if (!is_numeric($dureeVie)) {
                    $errors['duree_vie_estimee'] = 'La durée doit être un nombre';
                } elseif ($dureeVie < 1) {
                    $errors['duree_vie_estimee'] = 'La durée doit être au moins 1 an';
                } elseif ($dureeVie > 50) {
                    $errors['duree_vie_estimee'] = 'La durée ne peut pas dépasser 50 ans';
                }
            }
            
            if (count($errors) === 0) {
                $equipement = new Equipement();
                $equipement->setNom($nom);
                $equipement->setType($type);
                $equipement->setEtat($etat);
                
                if ($dateAchat) {
                    $equipement->setDateAchat(new \DateTime($dateAchat));
                }
                
                if ($dureeVie) {
                    $equipement->setDureeVieEstimee((int)$dureeVie);
                }
                
                $em->persist($equipement);
                $em->flush();
                
                $this->addFlash('success', 'Équipement ajouté avec succès !');
                return $this->redirectToRoute('admin_equipement_index');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('admin/equipement/new.html.twig', [
            'errors' => $errors ?? [],
            'old' => $request->request->all(),
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_equipement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Equipement $equipement, EntityManagerInterface $em): Response
    {
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom'));
            $type = $request->request->get('type');
            $etat = $request->request->get('etat');
            $dateAchat = $request->request->get('date_achat');
            $dureeVie = $request->request->get('duree_vie_estimee');
            
            if (empty($nom)) {
                $errors['nom'] = 'Le nom est obligatoire';
            } elseif (strlen($nom) < 2) {
                $errors['nom'] = 'Le nom doit contenir au moins 2 caractères';
            }
            
            $typesValides = ['Tracteur', 'Moissonneuse', 'Pulvérisateur', 'Charrue', 'Semoir', 'Autre'];
            if (empty($type) || !in_array($type, $typesValides)) {
                $errors['type'] = 'Type non valide';
            }
            
            $etatsValides = ['Fonctionnel', 'En panne', 'Maintenance'];
            if (empty($etat) || !in_array($etat, $etatsValides)) {
                $errors['etat'] = 'État non valide';
            }
            
            if (!empty($dateAchat)) {
                $dateObj = \DateTime::createFromFormat('Y-m-d', $dateAchat);
                if (!$dateObj || $dateObj->format('Y-m-d') !== $dateAchat) {
                    $errors['date_achat'] = 'Date d\'achat invalide';
                } elseif ($dateObj > new \DateTime()) {
                    $errors['date_achat'] = 'La date d\'achat ne peut pas être dans le futur';
                }
            }
            
            if (!empty($dureeVie)) {
                if (!is_numeric($dureeVie) || $dureeVie < 1 || $dureeVie > 50) {
                    $errors['duree_vie_estimee'] = 'Durée invalide (1-50 ans)';
                }
            }
            
            if (count($errors) === 0) {
                $equipement->setNom($nom);
                $equipement->setType($type);
                $equipement->setEtat($etat);
                
                if ($dateAchat) {
                    $equipement->setDateAchat(new \DateTime($dateAchat));
                } else {
                    $equipement->setDateAchat(null);
                }
                
                if ($dureeVie) {
                    $equipement->setDureeVieEstimee((int)$dureeVie);
                } else {
                    $equipement->setDureeVieEstimee(null);
                }
                
                $em->flush();
                
                $this->addFlash('success', 'Équipement modifié avec succès !');
                return $this->redirectToRoute('admin_equipement_index');
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
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
        // Vérifier si l'équipement a des maintenances
        if ($equipement->getMaintenances()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cet équipement car il a ' . $equipement->getMaintenances()->count() . ' maintenance(s) associée(s).');
            return $this->redirectToRoute('admin_equipement_index');
        }
        
        if ($this->isCsrfTokenValid('delete' . $equipement->getId(), $request->request->get('_token'))) {
            $em->remove($equipement);
            $em->flush();
            $this->addFlash('success', 'Équipement supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('admin_equipement_index');
    }
    
    #[Route('/{id}', name: 'admin_equipement_show', methods: ['GET'])]
    public function show(Equipement $equipement): Response
    {
        return $this->render('admin/equipement/show.html.twig', [
            'equipement' => $equipement,
        ]);
    }
}