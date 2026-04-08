<?php
// src/Controller/Admin/StockController.php

namespace App\Controller\Admin;

use App\Entity\Stock;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/stocks')]
class StockController extends AbstractController
{
    #[Route('/', name: 'admin_stock_index', methods: ['GET'])]
    public function index(Request $request, StockRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', 'all');
        $statut = $request->query->get('statut', 'all');
        
        $stocks = $repository->search($search, $type, $statut);
        $stats = $repository->getStatistics();
        $types = $repository->getUniqueTypes();
        
        return $this->render('admin/stock/index.html.twig', [
            'stocks' => $stocks,
            'stats' => $stats,
            'types' => $types,
            'search' => $search,
            'selectedType' => $type,
            'selectedStatut' => $statut,
        ]);
    }
    
    #[Route('/new', name: 'admin_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Créer un tableau pour conserver les données saisies
        $formData = [];
        
        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $nomProduit = trim($request->request->get('nom_produit', ''));
            $typeProduit = trim($request->request->get('type_produit', ''));
            $quantite = $request->request->get('quantite', '');
            $unite = trim($request->request->get('unite', ''));
            $dateExpiration = $request->request->get('date_expiration', '');
            $statut = $request->request->get('statut', 'Disponible');
            
            // Sauvegarder les données pour les réafficher
            $formData = [
                'nom_produit' => $nomProduit,
                'type_produit' => $typeProduit,
                'quantite' => $quantite,
                'unite' => $unite,
                'date_expiration' => $dateExpiration,
                'statut' => $statut,
            ];
            
            // VALIDATION PHP MANUELLE
            $errors = [];
            
            // Validation du nom
            if (empty($nomProduit)) {
                $errors[] = "Le nom du produit est obligatoire.";
            } elseif (strlen($nomProduit) < 2) {
                $errors[] = "Le nom du produit doit contenir au moins 2 caractères.";
            } elseif (strlen($nomProduit) > 100) {
                $errors[] = "Le nom du produit ne peut pas dépasser 100 caractères.";
            } elseif (!preg_match("/^[a-zA-ZÀ-ÿ0-9\s\-]+$/", $nomProduit)) {
                $errors[] = "Le nom ne peut contenir que des lettres, chiffres, espaces et tirets.";
            }
            
            // Validation du type
            if (!empty($typeProduit)) {
                if (strlen($typeProduit) > 50) {
                    $errors[] = "Le type ne peut pas dépasser 50 caractères.";
                } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]*$/", $typeProduit)) {
                    $errors[] = "Le type ne peut contenir que des lettres, espaces et tirets.";
                }
            }
            
            // Validation de la quantité
            if (empty($quantite)) {
                $errors[] = "La quantité est obligatoire.";
            } elseif (!is_numeric($quantite)) {
                $errors[] = "La quantité doit être un nombre.";
            } elseif ($quantite <= 0) {
                $errors[] = "La quantité doit être positive.";
            } elseif ($quantite > 999999) {
                $errors[] = "La quantité ne peut pas dépasser 999999.";
            }
            
            // Validation de l'unité
            if (!empty($unite)) {
                if (strlen($unite) > 20) {
                    $errors[] = "L'unité ne peut pas dépasser 20 caractères.";
                } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s]*$/", $unite)) {
                    $errors[] = "L'unité ne peut contenir que des lettres et espaces.";
                }
            }
            
            // Validation de la date d'expiration
            if (!empty($dateExpiration)) {
                $date = \DateTime::createFromFormat('Y-m-d', $dateExpiration);
                if (!$date || $date->format('Y-m-d') !== $dateExpiration) {
                    $errors[] = "La date d'expiration est invalide. Format attendu: YYYY-MM-DD";
                }
            }
            
            // Validation du statut
            if (!in_array($statut, ['Disponible', 'Épuisé'])) {
                $errors[] = "Le statut doit être 'Disponible' ou 'Épuisé'.";
            }
            
            // S'il n'y a pas d'erreurs, on sauvegarde
            if (empty($errors)) {
                $stock = new Stock();
                $stock->setNomProduit($nomProduit);
                $stock->setTypeProduit($typeProduit ?: null);
                $stock->setQuantite((float)$quantite);
                $stock->setUnite($unite ?: null);
                
                // ID Utilisateur
                $stock->setIdUtilisateur(1); // TODO: Remplacer par l'ID de l'utilisateur connecté
                
                if (!empty($dateExpiration)) {
                    $stock->setDateExpiration(new \DateTime($dateExpiration));
                }
                
                $stock->setStatut($statut);
                
                $em->persist($stock);
                $em->flush();
                
                $this->addFlash('success', 'Stock ajouté avec succès !');
                return $this->redirectToRoute('admin_stock_index');
            } else {
                // Ajouter les erreurs flash
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }
        
        return $this->render('admin/stock/new.html.twig', [
            'formData' => $formData
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stock $stock, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($request->isMethod('POST')) {
            // Récupérer les données
            $nomProduit = trim($request->request->get('nom_produit', ''));
            $typeProduit = trim($request->request->get('type_produit', ''));
            $quantite = $request->request->get('quantite', '');
            $unite = trim($request->request->get('unite', ''));
            $dateExpiration = $request->request->get('date_expiration', '');
            $statut = $request->request->get('statut', 'Disponible');
            
            // VALIDATION PHP
            $errors = [];
            
            if (empty($nomProduit)) {
                $errors[] = "Le nom du produit est obligatoire.";
            } elseif (strlen($nomProduit) < 2) {
                $errors[] = "Le nom du produit doit contenir au moins 2 caractères.";
            } elseif (strlen($nomProduit) > 100) {
                $errors[] = "Le nom du produit ne peut pas dépasser 100 caractères.";
            }
            
            if (!empty($typeProduit) && strlen($typeProduit) > 50) {
                $errors[] = "Le type ne peut pas dépasser 50 caractères.";
            }
            
            if (empty($quantite)) {
                $errors[] = "La quantité est obligatoire.";
            } elseif (!is_numeric($quantite)) {
                $errors[] = "La quantité doit être un nombre.";
            } elseif ($quantite <= 0) {
                $errors[] = "La quantité doit être positive.";
            }
            
            if (!empty($unite) && strlen($unite) > 20) {
                $errors[] = "L'unité ne peut pas dépasser 20 caractères.";
            }
            
            if (!empty($dateExpiration)) {
                $date = \DateTime::createFromFormat('Y-m-d', $dateExpiration);
                if (!$date || $date->format('Y-m-d') !== $dateExpiration) {
                    $errors[] = "La date d'expiration est invalide.";
                }
            }
            
            if (empty($errors)) {
                $stock->setNomProduit($nomProduit);
                $stock->setTypeProduit($typeProduit ?: null);
                $stock->setQuantite((float)$quantite);
                $stock->setUnite($unite ?: null);
                $stock->setStatut($statut);
                
                if (!empty($dateExpiration)) {
                    $stock->setDateExpiration(new \DateTime($dateExpiration));
                } else {
                    $stock->setDateExpiration(null);
                }
                
                $em->flush();
                $this->addFlash('success', 'Stock modifié avec succès !');
                return $this->redirectToRoute('admin_stock_index');
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }
        
        return $this->render('admin/stock/edit.html.twig', [
            'stock' => $stock,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_stock_delete', methods: ['POST'])]
    public function delete(Request $request, Stock $stock, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete' . $stock->getId(), $request->request->get('_token'))) {
            $em->remove($stock);
            $em->flush();
            $this->addFlash('success', 'Stock supprimé avec succès !');
        }
        
        return $this->redirectToRoute('admin_stock_index');
    }
    
    #[Route('/{id}', name: 'admin_stock_show', methods: ['GET'])]
    public function show(Stock $stock): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }
}