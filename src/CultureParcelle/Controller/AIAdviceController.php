<?php

namespace App\CultureParcelle\Controller;

use App\Service\GeminiAIService;
use App\CultureParcelle\Repository\CultureRepository;
use App\CultureParcelle\Repository\ParcelleRepository;
use App\CultureParcelle\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ai-advice')]
class AIAdviceController extends AbstractController
{
    public function __construct(
        private GeminiAIService $aiService,
        private CultureRepository $cultureRepository,
        private ParcelleRepository $parcelleRepository,
        private WeatherService $weatherService
    ) {}

    #[Route('/culture/{idCulture}', name: 'ai_advice_culture', methods: ['GET'])]
    public function getCultureAdvice(int $idCulture): JsonResponse
    {
        $culture = $this->cultureRepository->find($idCulture);
        
        if (!$culture) {
            return $this->json(['success' => false, 'error' => 'Culture non trouvée'], 404);
        }

        // Check if user owns this culture
        if ($culture->getUserId() !== $this->getUser()?->getId()) {
            return $this->json(['success' => false, 'error' => 'Accès non autorisé'], 403);
        }

        $cultureData = [
            'nom' => $culture->getNomCulture(),
            'type' => $culture->getTypeCulture(),
            'dateSemis' => $culture->getDateSemis(),
            'dateRecolte' => $culture->getDateRecolte()
        ];

        $result = $this->aiService->getCultureAdvice($cultureData);

        return $this->json($result);
    }

    #[Route('/parcelle/{idParcelle}', name: 'ai_advice_parcelle', methods: ['GET'])]
    public function getParcelleAdvice(int $idParcelle): JsonResponse
    {
        $parcelle = $this->parcelleRepository->find($idParcelle);
        
        if (!$parcelle) {
            return $this->json(['success' => false, 'error' => 'Parcelle non trouvée'], 404);
        }

        // Check if user owns this parcelle
        if ($parcelle->getUserId() !== $this->getUser()?->getId()) {
            return $this->json(['success' => false, 'error' => 'Accès non autorisé'], 403);
        }

        $parcelleData = [
            'localisation' => $parcelle->getLocalisation(),
            'surface' => $parcelle->getSurface(),
            'latitude' => $parcelle->getLatitude(),
            'longitude' => $parcelle->getLongitude()
        ];

        // Get weather data if GPS available
        $weatherData = null;
        if ($parcelle->getLatitude() && $parcelle->getLongitude()) {
            $weatherData = $this->weatherService->getWeatherByCoordinates(
                $parcelle->getLatitude(),
                $parcelle->getLongitude()
            );
        }

        $result = $this->aiService->getParcelleAdvice($parcelleData, $weatherData);

        return $this->json($result);
    }

    #[Route('/general', name: 'ai_advice_general', methods: ['GET'])]
    public function getGeneralAdvice(): JsonResponse
    {
        $userId = $this->getUser()?->getId();

        // Get user's cultures
        $cultures = $this->cultureRepository->findBy(['user_id' => $userId]);
        $culturesData = array_map(function($culture) {
            return [
                'nom' => $culture->getNomCulture(),
                'type' => $culture->getTypeCulture()
            ];
        }, $cultures);

        // Get user's parcelles
        $parcelles = $this->parcelleRepository->findBy(['user_id' => $userId]);
        $parcellesData = array_map(function($parcelle) {
            return [
                'localisation' => $parcelle->getLocalisation(),
                'surface' => $parcelle->getSurface()
            ];
        }, $parcelles);

        if (empty($culturesData) && empty($parcellesData)) {
            return $this->json([
                'success' => false,
                'error' => 'Aucune donnée disponible pour générer des conseils'
            ]);
        }

        $result = $this->aiService->getGeneralAdvice($culturesData, $parcellesData);

        return $this->json($result);
    }

    #[Route('/dashboard', name: 'ai_advice_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('@CultureParcelle/ai/dashboard.html.twig');
    }

    #[Route('/chat', name: 'ai_advice_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';
        $history = $data['history'] ?? [];

        if (empty($message)) {
            return $this->json(['success' => false, 'error' => 'Message vide']);
        }

        $userId = $this->getUser()?->getId();

        // Get user's cultures and parcelles for context
        $cultures = $this->cultureRepository->findBy(['user_id' => $userId]);
        $parcelles = $this->parcelleRepository->findBy(['user_id' => $userId]);

        // Build context
        $context = $this->buildUserContext($cultures, $parcelles);

        // Get AI response
        $result = $this->aiService->chat($message, $context, $history);

        return $this->json($result);
    }

    #[Route('/cultures-data', name: 'ai_cultures_data', methods: ['GET'])]
    public function getCulturesData(): JsonResponse
    {
        $userId = $this->getUser()?->getId();
        $cultures = $this->cultureRepository->findBy(['user_id' => $userId]);

        $data = array_map(function($culture) {
            return [
                'nom' => $culture->getNomCulture(),
                'type' => $culture->getTypeCulture(),
                'dateSemis' => $culture->getDateSemis()?->format('d/m/Y'),
                'dateRecolte' => $culture->getDateRecolte()?->format('d/m/Y'),
            ];
        }, $cultures);

        return $this->json($data);
    }

    #[Route('/parcelles-data', name: 'ai_parcelles_data', methods: ['GET'])]
    public function getParcellesData(): JsonResponse
    {
        $userId = $this->getUser()?->getId();
        $parcelles = $this->parcelleRepository->findBy(['user_id' => $userId]);

        $data = array_map(function($parcelle) {
            return [
                'localisation' => $parcelle->getLocalisation(),
                'surface' => $parcelle->getSurface(),
            ];
        }, $parcelles);

        return $this->json($data);
    }

    private function buildUserContext(array $cultures, array $parcelles): string
    {
        $context = "Contexte de l'utilisateur:\n\n";
        
        if (!empty($cultures)) {
            $context .= "Cultures (" . count($cultures) . "):\n";
            foreach ($cultures as $culture) {
                $context .= sprintf(
                    "- %s (%s), semis: %s, récolte: %s\n",
                    $culture->getNomCulture(),
                    $culture->getTypeCulture(),
                    $culture->getDateSemis()?->format('d/m/Y') ?? 'N/A',
                    $culture->getDateRecolte()?->format('d/m/Y') ?? 'N/A'
                );
            }
            $context .= "\n";
        }
        
        if (!empty($parcelles)) {
            $context .= "Parcelles (" . count($parcelles) . "):\n";
            foreach ($parcelles as $parcelle) {
                $context .= sprintf(
                    "- %s, surface: %s ha\n",
                    $parcelle->getLocalisation(),
                    $parcelle->getSurface()
                );
            }
        }
        
        return $context;
    }
}
