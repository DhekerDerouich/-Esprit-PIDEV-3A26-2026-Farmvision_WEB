<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AIRecommendationService
{
    private $httpClient;
    private $logger;
    private $apiKey;
    private $apiUrl;
    private $model;
    
    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $_ENV['OPENROUTER_API_KEY'] ?? '';
        $this->apiUrl = $_ENV['OPENROUTER_API_URL'] ?? 'https://openrouter.ai/api/v1/chat/completions';
        $this->model = $_ENV['OPENROUTER_MODEL'] ?? 'arcee-ai/trinity-large-preview:free';
    }
    
    /**
     * Analyse les données des 3 derniers mois et génère prédiction + recommandations
     */
    public function analyzeAndPredict(array $historicalData): array
    {
        if (empty($this->apiKey)) {
            $this->logger->warning('OpenRouter API key manquante');
            return $this->getMockResponse($historicalData);
        }
        
        $prompt = $this->buildPrompt($historicalData);
        
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'https://farmvision.com',
                    'X-Title' => 'FarmVision',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un expert financier agricole tunisien. Analyse les données et donne des prédictions et recommandations précises en français. Réponds UNIQUEMENT au format JSON, sans texte avant ou après.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1000,
                ],
            ]);
            
            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';
            
            $this->logger->info('OpenRouter API response', ['content' => $content]);
            
            // Extraire le JSON de la réponse
            $jsonContent = $this->extractJson($content);
            
            if ($jsonContent) {
                $result = json_decode($jsonContent, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $result;
                }
            }
            
            return $this->getMockResponse($historicalData);
            
        } catch (\Exception $e) {
            $this->logger->error('OpenRouter API error: ' . $e->getMessage());
            return $this->getMockResponse($historicalData);
        }
    }
    
    /**
     * Construit le prompt pour l'IA
     */
    private function buildPrompt(array $data): string
    {
        $revenus = $data['revenus'];
        $depenses = $data['depenses'];
        $soldes = $data['soldes'];
        $categories = $data['categories'];
        $mois = $data['mois'];
        
        $categoriesStr = '';
        foreach ($categories as $cat => $montant) {
            $categoriesStr .= "- {$cat}: {$montant} DT\n";
        }
        
        return "
        Analyse les données financières agricoles tunisiennes suivantes sur 3 mois:
        
        MOIS: 
        - {$mois[0]}
        - {$mois[1]}
        - {$mois[2]}
        
        REVENUS (DT):
        - {$mois[0]}: {$revenus[0]} DT
        - {$mois[1]}: {$revenus[1]} DT
        - {$mois[2]}: {$revenus[2]} DT
        
        DÉPENSES (DT):
        - {$mois[0]}: {$depenses[0]} DT
        - {$mois[1]}: {$depenses[1]} DT
        - {$mois[2]}: {$depenses[2]} DT
        
        SOLDES (DT):
        - {$mois[0]}: {$soldes[0]} DT
        - {$mois[1]}: {$soldes[1]} DT
        - {$mois[2]}: {$soldes[2]} DT
        
        RÉPARTITION DES DÉPENSES (3 derniers mois):
        {$categoriesStr}
        
        Réponds UNIQUEMENT au format JSON suivant, sans aucun autre texte:
        {
            \"prediction\": {
                \"revenu_prochain_mois\": nombre,
                \"depense_prochain_mois\": nombre,
                \"solde_prochain_mois\": nombre,
                \"confiance\": \"Haute|Moyenne|Faible\",
                \"tendance\": \"Hausse|Baisse|Stable\"
            },
            \"recommandations\": [
                {
                    \"categorie\": \"Carburant|Réparation|Semences|Engrais|Équipement|Main d'œuvre|Vétérinaire|Autre\",
                    \"action\": \"action concrète pour économiser\",
                    \"economie_estimee\": nombre,
                    \"priorite\": \"Haute|Moyenne|Basse\"
                }
            ],
            \"analyse_globale\": \"texte d'analyse de la situation\",
            \"alerte\": \"texte d'alerte si problème détecté, sinon null\"
        }
        
        Sois précis, réaliste et donne des conseils actionnables pour un agriculteur tunisien.
        ";
    }
    
    /**
     * Extrait le JSON de la réponse
     */
    private function extractJson(string $content): ?string
    {
        // Cherche un objet JSON
        preg_match('/\{[^{}]*\{(?:[^{}]|(?R))*\}[^{}]*\}/s', $content, $matches);
        
        if (isset($matches[0])) {
            return $matches[0];
        }
        
        // Cherche un objet JSON simple
        preg_match('/\{.*\}/s', $content, $matches);
        return $matches[0] ?? null;
    }
    
    /**
     * Réponse mockée (quand l'API n'est pas configurée ou en cas d'erreur)
     */
    private function getMockResponse(array $data): array
    {
        $revenus = $data['revenus'];
        $moyenneRevenus = array_sum($revenus) / count($revenus);
        $tendance = $revenus[2] - $revenus[0];
        
        $prediction = max(0, $moyenneRevenus + ($tendance / 3));
        
        $recommandations = [];
        
        // Analyser les catégories
        $categories = $data['categories'];
        if (!empty($categories)) {
            arsort($categories);
            $topCategory = key($categories);
            $topAmount = current($categories);
            
            $categoryAdvice = [
                'Carburant' => 'Utilisez l\'éco-conduite et comparez les prix entre stations-service',
                'Réparation' => 'Faites l\'entretien préventif régulier pour éviter les grosses réparations',
                'Semences' => 'Achetez en groupe avec d\'autres agriculteurs pour bénéficier de remises',
                'Engrais' => 'Faites une analyse de sol pour optimiser les quantités',
                'Équipement' => 'Envisagez la location pour les équipements peu utilisés',
                'Main d\'œuvre' => 'Optimisez la planification des tâches',
                'Vétérinaire' => 'Prévention et vaccination régulière',
            ];
            
            $recommandations[] = [
                'categorie' => $topCategory,
                'action' => $categoryAdvice[$topCategory] ?? 'Analysez ce poste de dépense pour trouver des économies',
                'economie_estimee' => round($topAmount * 0.15, 2),
                'priorite' => $topAmount > 1000 ? 'Haute' : ($topAmount > 500 ? 'Moyenne' : 'Basse'),
            ];
        }
        
        if (empty($recommandations)) {
            $recommandations[] = [
                'categorie' => 'Général',
                'action' => 'Continuez à suivre vos dépenses régulièrement pour optimiser votre budget',
                'economie_estimee' => 0,
                'priorite' => 'Basse',
            ];
        }
        
        $analyse = "Sur les 3 derniers mois, vos revenus sont en " . ($tendance > 0 ? "hausse" : ($tendance < 0 ? "baisse" : "stabilité")) . 
                   ". Votre solde moyen est de " . round(array_sum($soldes) / 3, 2) . " DT.";
        
        $alerte = null;
        if ($soldes[2] < 0) {
            $alerte = "⚠️ Votre solde est négatif. Réduisez vos dépenses urgentes.";
        } elseif ($tendance < -100) {
            $alerte = "⚠️ Tendance baissière des revenus. Diversifiez vos sources de revenus.";
        }
        
        return [
            'prediction' => [
                'revenu_prochain_mois' => round($prediction, 2),
                'depense_prochain_mois' => round($data['depenses'][2] * 0.95, 2),
                'solde_prochain_mois' => round($prediction - ($data['depenses'][2] * 0.95), 2),
                'confiance' => count($revenus) >= 3 ? 'Moyenne' : 'Faible',
                'tendance' => $tendance > 0 ? 'Hausse' : ($tendance < 0 ? 'Baisse' : 'Stable'),
            ],
            'recommandations' => $recommandations,
            'analyse_globale' => $analyse,
            'alerte' => $alerte,
        ];
    }
}