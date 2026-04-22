<?php
namespace App\CultureParcelle\Controller;

use App\CultureParcelle\Repository\ParcelleRepository;
use App\CultureParcelle\Service\ParcelleService;
use App\CultureParcelle\Service\WeatherService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parcelles')]
class ParcelleController extends AbstractController
{
    public function __construct(
        private ParcelleService $parcelleService,
        private ParcelleRepository $repository,
        private PaginatorInterface $paginator
    ) {}

    #[Route('/', name: 'front_parcelle_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $localisation = $request->query->get('search', '');
        $surfaceMinRaw = $request->query->get('surface_min', '');
        $surfaceMaxRaw = $request->query->get('surface_max', '');
        $userId = $this->getUser()?->getId();

        $surfaceMin = $surfaceMinRaw !== '' ? (float)$surfaceMinRaw : null;
        $surfaceMax = $surfaceMaxRaw !== '' ? (float)$surfaceMaxRaw : null;

        $parcellesQuery = $this->parcelleService->searchParcelles(
            $localisation ?: null,
            $surfaceMin,
            $surfaceMax,
            $userId
        );
        
        $pagination = $this->paginator->paginate(
            $parcellesQuery,
            $request->query->getInt('page', 1),
            10 // items per page
        );

        return $this->render('@CultureParcelle/parcelle/index.html.twig', [
            'parcelles' => $pagination,
            'search' => $localisation,
            'surfaceMin' => $surfaceMinRaw,
            'surfaceMax' => $surfaceMaxRaw,
        ]);
    }

    #[Route('/new', name: 'front_parcelle_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = [
                'localisation' => $request->request->get('localisation', ''),
                'surface' => $request->request->get('surface', ''),
                'latitude' => $request->request->get('latitude', ''),
                'longitude' => $request->request->get('longitude', ''),
            ];

            $result = $this->parcelleService->createParcelle($data, $this->getUser()?->getId());

            if ($result['success']) {
                $this->addFlash('success', 'Parcelle ajoutée avec succès !');
                return $this->redirectToRoute('front_parcelle_index');
            }

            return $this->render('@CultureParcelle/parcelle/new.html.twig', [
                'errors' => $result['errors'],
                'parcelle' => $result['parcelle'] ?? null,
            ]);
        }

        return $this->render('@CultureParcelle/parcelle/new.html.twig', [
            'errors' => [],
            'parcelle' => null,
        ]);
    }

    #[Route('/{idParcelle}/edit', name: 'front_parcelle_edit', methods: ['GET', 'POST'])]
    public function edit(int $idParcelle, Request $request): Response
    {
        $parcelle = $this->repository->find($idParcelle);
        if (!$parcelle) {
            throw $this->createNotFoundException('Parcelle non trouvée');
        }

        if ($request->isMethod('POST')) {
            $data = [
                'localisation' => $request->request->get('localisation', ''),
                'surface' => $request->request->get('surface', ''),
                'latitude' => $request->request->get('latitude', ''),
                'longitude' => $request->request->get('longitude', ''),
            ];

            $result = $this->parcelleService->updateParcelle($parcelle, $data);

            if ($result['success']) {
                $this->addFlash('success', 'Parcelle modifiée avec succès !');
                return $this->redirectToRoute('front_parcelle_index');
            }

            return $this->render('@CultureParcelle/parcelle/edit.html.twig', [
                'parcelle' => $parcelle,
                'errors' => $result['errors'],
            ]);
        }

        return $this->render('@CultureParcelle/parcelle/edit.html.twig', [
            'parcelle' => $parcelle,
            'errors' => [],
        ]);
    }

    #[Route('/{idParcelle}/delete', name: 'front_parcelle_delete', methods: ['POST'])]
    public function delete(int $idParcelle, Request $request): Response
    {
        $parcelle = $this->repository->find($idParcelle);
        if (!$parcelle) {
            throw $this->createNotFoundException('Parcelle non trouvée');
        }

        if ($this->isCsrfTokenValid('delete_parcelle_' . $idParcelle, $request->request->get('_token'))) {
            $localisation = $parcelle->getLocalisation();
            $this->parcelleService->deleteParcelle($parcelle);
            $this->addFlash('success', 'Parcelle "' . $localisation . '" supprimée avec succès !');
        }

        return $this->redirectToRoute('front_parcelle_index');
    }

    #[Route('/{idParcelle}/weather', name: 'front_parcelle_weather', methods: ['GET'])]
    public function getWeather(int $idParcelle, WeatherService $weatherService): JsonResponse
    {
        $parcelle = $this->repository->find($idParcelle);
        
        if (!$parcelle) {
            return $this->json(['error' => 'Parcelle non trouvée'], 404);
        }

        if (!$parcelle->getLatitude() || !$parcelle->getLongitude()) {
            return $this->json(['error' => 'Coordonnées GPS manquantes'], 400);
        }

        $weatherData = $weatherService->getWeatherByCoordinates(
            $parcelle->getLatitude(),
            $parcelle->getLongitude()
        );

        if (!$weatherData) {
            return $this->json(['error' => 'Impossible de récupérer les données météo'], 503);
        }

        $weatherData['emoji'] = $weatherService->getWeatherEmoji($weatherData['icon']);

        return $this->json($weatherData);
    }

    #[Route('/export/pdf', name: 'front_parcelle_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, \App\Service\PdfGeneratorService $pdfGenerator): Response
    {
        $localisation = $request->query->get('search', '');
        $surfaceMinRaw = $request->query->get('surface_min', '');
        $surfaceMaxRaw = $request->query->get('surface_max', '');
        $userId = $this->getUser()?->getId();

        $surfaceMin = $surfaceMinRaw !== '' ? (float)$surfaceMinRaw : null;
        $surfaceMax = $surfaceMaxRaw !== '' ? (float)$surfaceMaxRaw : null;

        $parcelles = $this->parcelleService->searchParcelles(
            $localisation ?: null,
            $surfaceMin,
            $surfaceMax,
            $userId
        );

        $html = $this->renderView('@CultureParcelle/parcelle/pdf.html.twig', [
            'parcelles' => $parcelles,
            'date' => new \DateTime(),
            'user' => $this->getUser(),
            'search' => $localisation,
            'surfaceMin' => $surfaceMin,
            'surfaceMax' => $surfaceMax
        ]);

        $filename = 'parcelles_' . date('Y-m-d_His') . '.pdf';
        $result = $pdfGenerator->generatePdfResponse($html, $filename);

        return new Response(
            $result['content'],
            200,
            $result['headers']
        );
    }

    #[Route('/{idParcelle}/export/pdf', name: 'front_parcelle_export_single_pdf', methods: ['GET'])]
    public function exportSinglePdf(int $idParcelle, \App\Service\PdfGeneratorService $pdfGenerator): Response
    {
        $parcelle = $this->repository->find($idParcelle);
        if (!$parcelle) {
            throw $this->createNotFoundException('Parcelle non trouvée');
        }

        $html = $this->renderView('@CultureParcelle/parcelle/pdf_single.html.twig', [
            'parcelle' => $parcelle,
            'date' => new \DateTime(),
            'user' => $this->getUser()
        ]);

        $filename = 'parcelle_' . $parcelle->getLocalisation() . '_' . date('Y-m-d') . '.pdf';
        $result = $pdfGenerator->generatePdfResponse($html, $filename);

        return new Response(
            $result['content'],
            200,
            $result['headers']
        );
    }
}
