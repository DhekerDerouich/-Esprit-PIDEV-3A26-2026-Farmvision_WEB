<?php
// src/Controller/Admin/StockController.php

namespace App\Controller\Admin;

use App\Entity\Stock;
use App\Repository\StockRepository;
use App\Service\ImageUploadService;
use App\Service\OpenFoodFactsService;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/stocks')]
class StockController extends AbstractController
{
    #[Route('/', name: 'admin_stock_index', methods: ['GET'])]
    public function index(Request $request, StockRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = $request->query->get('search', '');
        $type   = $request->query->get('type', 'all');
        $statut = $request->query->get('statut', 'all');

        $stocks = $repository->search($search, $type, $statut);
        $stats  = $repository->getStatistics();
        $types  = $repository->getUniqueTypes();

        // Chart data: stock by type
        $stockByType = $repository->getStockByType();
        $chartLabels = array_column($stockByType, 'type');
        $chartData   = array_column($stockByType, 'total');

        // Expiring soon & low stock for alerts
        $expiringSoon = $repository->findExpiringSoon(30);
        $lowStock     = $repository->findLowStock(10);

        return $this->render('admin/stock/index.html.twig', [
            'stocks'        => $stocks,
            'stats'         => $stats,
            'types'         => $types,
            'search'        => $search,
            'selectedType'  => $type,
            'selectedStatut'=> $statut,
            'chartLabels'   => json_encode($chartLabels),
            'chartData'     => json_encode($chartData),
            'expiringSoon'  => $expiringSoon,
            'lowStock'      => $lowStock,
        ]);
    }

    // ==================== ROUTES API ====================

    #[Route('/api/test', name: 'admin_stock_api_test', methods: ['GET'])]
    public function apiTest(): JsonResponse
    {
        return $this->json([
            'success'   => true,
            'message'   => 'API fonctionnelle',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/api/analyze/{id}', name: 'admin_stock_api_analyze', methods: ['GET'])]
    public function apiAnalyze(Stock $stock): JsonResponse
    {
        $qualities = ['Excellente', 'Bonne', 'Très bonne'];
        $suggestionsList = [
            'Conserver à température ambiante (15-20°C)',
            'Éviter l\'exposition directe au soleil',
            'Utiliser dans les 7 jours pour une fraîcheur optimale',
            'Vérifier l\'état avant chaque utilisation',
            'Stocker à l\'abri de l\'humidité',
            'Contrôler régulièrement la date d\'expiration',
            'Maintenir une rotation FIFO (premier entré, premier sorti)',
        ];

        shuffle($suggestionsList);
        $selectedSuggestions = array_slice($suggestionsList, 0, 3);

        return $this->json([
            'success'          => true,
            'product_name'     => $stock->getNomProduit(),
            'quality'          => $qualities[array_rand($qualities)],
            'category'         => $stock->getTypeProduit() ?? 'Produit agricole',
            'suggestions'      => $selectedSuggestions,
            'recommended_price'=> round(($stock->getQuantite() / 10) + rand(2, 8), 2),
            'sales_potential'  => rand(65, 98),
            'trend'            => ['hausse', 'stable', 'baisse'][array_rand(['hausse', 'stable', 'baisse'])],
        ]);
    }

    #[Route('/api/send-sms', name: 'admin_stock_api_send_sms', methods: ['POST'])]
    public function apiSendSms(Request $request, SmsService $smsService): JsonResponse
    {
        $phone   = $request->request->get('phone');
        $message = $request->request->get('message');

        if (!$phone) {
            return $this->json(['success' => false, 'error' => 'Numéro de téléphone requis'], 400);
        }

        if (empty($message)) {
            return $this->json(['success' => false, 'error' => 'Message requis'], 400);
        }

        $result = $smsService->sendCustom($phone, $message);

        return $this->json(array_merge($result, [
            'phone'     => $phone,
            'content'   => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ]));
    }

    #[Route('/api/forecast', name: 'admin_stock_api_forecast', methods: ['POST'])]
    public function apiForecast(Request $request, StockRepository $repository): JsonResponse
    {
        $data        = json_decode($request->getContent(), true);
        $productName = $data['product_name'] ?? 'Produit';

        // Use real stock quantity as base if available
        $stocks = $repository->search($productName, 'all', 'all');
        $baseValue = !empty($stocks) ? (float) $stocks[0]->getQuantite() : rand(80, 200);
        $baseValue = max($baseValue, 10);

        $trends = ['hausse', 'stable', 'baisse'];
        $trend  = $trends[array_rand($trends)];

        if ($trend === 'hausse') {
            $predictions = [round($baseValue), round($baseValue * 1.15), round($baseValue * 1.35)];
        } elseif ($trend === 'baisse') {
            $predictions = [round($baseValue), round($baseValue * 0.85), round($baseValue * 0.70)];
        } else {
            $predictions = [round($baseValue), round($baseValue * 1.02), round($baseValue * 1.05)];
        }

        return $this->json([
            'success'      => true,
            'product_name' => $productName,
            'predictions'  => $predictions,
            'confidence'   => round(rand(65, 95) / 100, 2),
            'tendances'    => $trend,
            'periods'      => ['Mois 1', 'Mois 2', 'Mois 3'],
        ]);
    }

    #[Route('/api/enrich-barcode', name: 'admin_stock_enrich_barcode', methods: ['POST'])]
    public function enrichBarcode(Request $request, OpenFoodFactsService $openFoodFacts): JsonResponse
    {
        $barcode = $request->request->get('barcode');
        if (empty($barcode)) {
            return $this->json(['success' => false, 'error' => 'Code-barres requis'], 400);
        }
        return $this->json($openFoodFacts->enrichProductData($barcode));
    }

    // ==================== CRUD ====================

    #[Route('/new', name: 'admin_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ImageUploadService $imageUploader): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $formData = [];

        if ($request->isMethod('POST')) {
            $nomProduit    = trim($request->request->get('nom_produit', ''));
            $typeProduit   = trim($request->request->get('type_produit', ''));
            $quantite      = $request->request->get('quantite', '');
            $unite         = trim($request->request->get('unite', ''));
            $dateExpiration= $request->request->get('date_expiration', '');
            $statut        = $request->request->get('statut', 'Disponible');
            $barcode       = trim($request->request->get('barcode', ''));

            $formData = compact('nomProduit', 'typeProduit', 'quantite', 'unite', 'dateExpiration', 'statut', 'barcode');
            $errors   = [];

            if (empty($nomProduit)) {
                $errors[] = "Le nom du produit est obligatoire.";
            }
            if (empty($quantite) || !is_numeric($quantite) || $quantite <= 0) {
                $errors[] = "La quantité doit être un nombre positif.";
            }

            $imageFile = $request->files->get('image_file');
            $imageName = null;
            if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($imageFile->getMimeType(), $allowed) && $imageFile->getSize() <= 5 * 1024 * 1024) {
                    $imageName = $imageUploader->upload($imageFile);
                } else {
                    $errors[] = "Format ou taille d'image invalide (max 5 Mo, JPEG/PNG/WebP).";
                }
            }

            if (empty($errors)) {
                $stock = new Stock();
                $stock->setNomProduit($nomProduit);
                $stock->setTypeProduit($typeProduit ?: null);
                $stock->setQuantite((float) $quantite);
                $stock->setUnite($unite ?: null);
                $stock->setIdUtilisateur(1);
                $stock->setImageFilename($imageName);
                $stock->setBarcode($barcode ?: null);
                $stock->setUpdatedAt(new \DateTime());
                if (!empty($dateExpiration)) {
                    $stock->setDateExpiration(new \DateTime($dateExpiration));
                }
                $stock->setStatut($statut);

                $em->persist($stock);
                $em->flush();
                $this->addFlash('success', '✅ Stock ajouté avec succès !');
                return $this->redirectToRoute('admin_stock_index');
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('admin/stock/new.html.twig', ['formData' => $formData]);
    }

    #[Route('/{id}/edit', name: 'admin_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stock $stock, EntityManagerInterface $em, ImageUploadService $imageUploader): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $nomProduit    = trim($request->request->get('nom_produit', ''));
            $typeProduit   = trim($request->request->get('type_produit', ''));
            $quantite      = $request->request->get('quantite', '');
            $unite         = trim($request->request->get('unite', ''));
            $dateExpiration= $request->request->get('date_expiration', '');
            $statut        = $request->request->get('statut', 'Disponible');

            $errors = [];
            if (empty($nomProduit)) {
                $errors[] = "Le nom est obligatoire.";
            }
            if (empty($quantite) || !is_numeric($quantite) || $quantite <= 0) {
                $errors[] = "Quantité invalide.";
            }

            $imageFile = $request->files->get('image_file');
            if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($imageFile->getMimeType(), $allowed) && $imageFile->getSize() <= 5 * 1024 * 1024) {
                    if ($stock->getImageFilename()) {
                        $imageUploader->delete($stock->getImageFilename());
                    }
                    $stock->setImageFilename($imageUploader->upload($imageFile));
                    $this->addFlash('success', '📷 Image mise à jour !');
                } else {
                    $errors[] = "Format ou taille d'image invalide.";
                }
            }

            if (empty($errors)) {
                $stock->setNomProduit($nomProduit);
                $stock->setTypeProduit($typeProduit ?: null);
                $stock->setQuantite((float) $quantite);
                $stock->setUnite($unite ?: null);
                $stock->setStatut($statut);
                $stock->setUpdatedAt(new \DateTime());
                if (!empty($dateExpiration)) {
                    $stock->setDateExpiration(new \DateTime($dateExpiration));
                } else {
                    $stock->setDateExpiration(null);
                }

                $em->flush();
                $this->addFlash('success', 'Stock modifié avec succès !');
                return $this->redirectToRoute('admin_stock_index');
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('admin/stock/edit.html.twig', ['stock' => $stock]);
    }

    #[Route('/{id}/delete', name: 'admin_stock_delete', methods: ['POST'])]
    public function delete(Request $request, Stock $stock, EntityManagerInterface $em, ImageUploadService $imageUploader): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($this->isCsrfTokenValid('delete' . $stock->getIdStock(), $request->request->get('_token'))) {
            if ($stock->getImageFilename()) {
                $imageUploader->delete($stock->getImageFilename());
            }
            $em->remove($stock);
            $em->flush();
            $this->addFlash('success', 'Stock supprimé !');
        }
        return $this->redirectToRoute('admin_stock_index');
    }

    #[Route('/{id}', name: 'admin_stock_show', methods: ['GET'])]
    public function show(Stock $stock): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/stock/show.html.twig', ['stock' => $stock]);
    }
}
