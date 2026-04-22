<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiAIService
{
    // OpenRouter API endpoint
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const MODEL = 'openai/gpt-oss-120b:free'; // Free GPT model
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey
    ) {}

    /**
     * Get AI advice for a culture
     */
    public function getCultureAdvice(array $cultureData): array
    {
        $prompt = $this->buildCulturePrompt($cultureData);
        return $this->generateContent($prompt);
    }

    /**
     * Get AI advice for a parcelle
     */
    public function getParcelleAdvice(array $parcelleData, ?array $weatherData = null): array
    {
        $prompt = $this->buildParcellePrompt($parcelleData, $weatherData);
        return $this->generateContent($prompt);
    }

    /**
     * Get general farming advice based on multiple cultures
     */
    public function getGeneralAdvice(array $cultures, array $parcelles): array
    {
        $prompt = $this->buildGeneralPrompt($cultures, $parcelles);
        return $this->generateContent($prompt);
    }

    /**
     * Chat with AI assistant (conversational)
     */
    public function chat(string $userMessage, string $userContext, array $history = []): array
    {
        // Build system prompt with user context
        $systemPrompt = "Tu es un assistant agricole expert. Tu aides les agriculteurs avec des conseils pratiques sur leurs cultures et parcelles. " .
            "Réponds UNIQUEMENT aux questions liées à l'agriculture, les cultures, les parcelles, l'irrigation, la fertilisation, les maladies des plantes, etc. " .
            "Si on te pose une question hors sujet (politique, religion, actualités, etc.), réponds poliment que tu ne peux aider que sur des sujets agricoles.\n\n" .
            $userContext;

        // Build messages array for conversation
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Add conversation history (last 10 messages to avoid token limits)
        $recentHistory = array_slice($history, -10);
        foreach ($recentHistory as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content']
            ];
        }

        return $this->generateChat($messages);
    }

    /**
     * Build prompt for culture advice
     */
    private function buildCulturePrompt(array $culture): string
    {
        $now = new \DateTime();
        $dateSemis = $culture['dateSemis'] ?? null;
        $dateRecolte = $culture['dateRecolte'] ?? null;
        
        $daysUntilHarvest = null;
        if ($dateRecolte) {
            $daysUntilHarvest = $now->diff($dateRecolte)->days;
            if ($dateRecolte < $now) {
                $daysUntilHarvest = -$daysUntilHarvest;
            }
        }

        return sprintf(
            "Tu es un expert agricole. Analyse cette culture et donne des conseils pratiques en français.\n\n" .
            "Culture: %s\n" .
            "Type: %s\n" .
            "Date de semis: %s\n" .
            "Date de récolte prévue: %s\n" .
            "Jours jusqu'à la récolte: %s\n\n" .
            "Donne 3-4 conseils concrets et pratiques pour optimiser cette culture. " .
            "Sois bref (2-3 phrases par conseil). " .
            "Concentre-toi sur: irrigation, fertilisation, protection contre maladies, et timing de récolte.",
            $culture['nom'] ?? 'Non spécifié',
            $culture['type'] ?? 'Non spécifié',
            $dateSemis ? $dateSemis->format('d/m/Y') : 'Non spécifié',
            $dateRecolte ? $dateRecolte->format('d/m/Y') : 'Non spécifié',
            $daysUntilHarvest !== null ? ($daysUntilHarvest >= 0 ? $daysUntilHarvest . ' jours' : 'Dépassée de ' . abs($daysUntilHarvest) . ' jours') : 'Non calculé'
        );
    }

    /**
     * Build prompt for parcelle advice
     */
    private function buildParcellePrompt(array $parcelle, ?array $weather): string
    {
        $weatherInfo = '';
        if ($weather) {
            $weatherInfo = sprintf(
                "\nMétéo actuelle:\n" .
                "- Température: %s°C\n" .
                "- Conditions: %s\n" .
                "- Humidité: %s%%\n" .
                "- Vent: %s km/h\n",
                $weather['temperature'] ?? 'N/A',
                $weather['description'] ?? 'N/A',
                $weather['humidity'] ?? 'N/A',
                $weather['wind_speed'] ?? 'N/A'
            );
        }

        return sprintf(
            "Tu es un expert agricole. Analyse cette parcelle et donne des conseils pratiques en français.\n\n" .
            "Parcelle: %s\n" .
            "Surface: %s hectares\n" .
            "Localisation: %s\n" .
            "%s\n" .
            "Donne 3-4 conseils concrets pour optimiser l'utilisation de cette parcelle. " .
            "Sois bref (2-3 phrases par conseil). " .
            "Concentre-toi sur: gestion du sol, rotation des cultures, irrigation, et optimisation de l'espace.",
            $parcelle['localisation'] ?? 'Non spécifié',
            $parcelle['surface'] ?? 'Non spécifié',
            isset($parcelle['latitude'], $parcelle['longitude']) 
                ? sprintf('GPS: %.4f, %.4f', $parcelle['latitude'], $parcelle['longitude'])
                : 'Coordonnées non disponibles',
            $weatherInfo
        );
    }

    /**
     * Build prompt for general advice
     */
    private function buildGeneralPrompt(array $cultures, array $parcelles): string
    {
        $totalSurface = array_sum(array_column($parcelles, 'surface'));
        $cultureTypes = array_unique(array_column($cultures, 'type'));
        
        return sprintf(
            "Tu es un expert agricole. Analyse cette exploitation et donne des conseils stratégiques en français.\n\n" .
            "Exploitation:\n" .
            "- Nombre de parcelles: %d\n" .
            "- Surface totale: %.2f hectares\n" .
            "- Nombre de cultures: %d\n" .
            "- Types de cultures: %s\n\n" .
            "Donne 4-5 conseils stratégiques pour optimiser l'exploitation. " .
            "Sois bref (2-3 phrases par conseil). " .
            "Concentre-toi sur: diversification, rotation, planification, et rentabilité.",
            count($parcelles),
            $totalSurface,
            count($cultures),
            implode(', ', $cultureTypes)
        );
    }

    /**
     * Generate content using OpenRouter API
     */
    private function generateContent(string $prompt): array
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'https://farmvision.local',
                    'X-Title' => 'FarmVision AI Assistant',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1000,
                ]
            ]);

            $data = $response->toArray();
            
            if (isset($data['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'advice' => $data['choices'][0]['message']['content']
                ];
            }

            return [
                'success' => false,
                'error' => 'Réponse invalide de l\'API'
            ];

        } catch (\Exception $e) {
            // Better error message with details
            $errorMsg = $e->getMessage();
            
            // Check for common errors
            if (strpos($errorMsg, '401') !== false) {
                $errorMsg = 'Clé API invalide. Vérifiez votre clé OpenRouter.';
            } elseif (strpos($errorMsg, '403') !== false) {
                $errorMsg = 'Accès refusé. Vérifiez les permissions de votre clé API.';
            } elseif (strpos($errorMsg, '429') !== false) {
                $errorMsg = 'Limite de requêtes atteinte. Veuillez réessayer dans quelques instants.';
            } elseif (strpos($errorMsg, '500') !== false) {
                $errorMsg = 'Erreur serveur OpenRouter. Veuillez réessayer.';
            }
            
            return [
                'success' => false,
                'error' => $errorMsg
            ];
        }
    }

    /**
     * Generate chat response using OpenRouter API
     */
    private function generateChat(array $messages): array
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'https://farmvision.local',
                    'X-Title' => 'FarmVision AI Assistant',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 1000,
                ]
            ]);

            $data = $response->toArray();
            
            if (isset($data['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'response' => $data['choices'][0]['message']['content']
                ];
            }

            return [
                'success' => false,
                'error' => 'Réponse invalide de l\'API'
            ];

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            
            if (strpos($errorMsg, '401') !== false) {
                $errorMsg = 'Clé API invalide.';
            } elseif (strpos($errorMsg, '429') !== false) {
                $errorMsg = 'Limite de requêtes atteinte. Réessayez dans quelques instants.';
            }
            
            return [
                'success' => false,
                'error' => $errorMsg
            ];
        }
    }
}
