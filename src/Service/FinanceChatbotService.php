<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class FinanceChatbotService
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
     * Pose une question au chatbot (uniquement finances)
     */
    public function askQuestion(string $question, array $userData): array
    {
        // 1. Vérifier si la question est liée aux finances
        $isFinanceRelated = $this->isFinanceRelated($question);
        
        if (!$isFinanceRelated) {
            return [
                'success' => false,
                'answer' => "❌ Je suis un assistant financier spécialisé dans l'agriculture. Je ne peux répondre qu'aux questions sur :\n\n" .
                           "💰 Revenus et dépenses\n" .
                           "📊 Budget et économies\n" .
                           "🌾 Prix des récoltes\n" .
                           "📈 Prédictions financières\n" .
                           "💡 Conseils d'économie\n\n" .
                           "Posez-moi une question sur vos finances !",
                'blocked' => true
            ];
        }
        
        // 2. Si la clé API n'est pas configurée, utiliser le mode offline
        if (empty($this->apiKey)) {
            return $this->getOfflineResponse($question, $userData);
        }
        
        // 3. Appeler l'API OpenRouter
        $prompt = $this->buildPrompt($question, $userData);
        
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
                            'content' => "Tu es un conseiller financier agricole tunisien. Tu ne réponds qu'aux questions sur les finances agricoles. Si la question n'est pas liée aux finances, réponds poliment que tu ne peux pas aider. Sois court, précis et donne des conseils actionnables. Réponds en français.",
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ],
            ]);
            
            $data = $response->toArray();
            $answer = $data['choices'][0]['message']['content'] ?? "Désolé, je n'ai pas compris. Pouvez-vous reformuler ?";
            
            return [
                'success' => true,
                'answer' => $answer,
                'blocked' => false
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Chatbot API error: ' . $e->getMessage());
            return $this->getOfflineResponse($question, $userData);
        }
    }
    
    /**
     * Vérifie si la question est liée aux finances
     */
    private function isFinanceRelated(string $question): bool
    {
        $questionLower = strtolower($question);
        
        // Mots-clés autorisés (finances agricoles)
        $financeKeywords = [
            // Revenus
            'revenu', 'vente', 'recette', 'gagner', 'argent', 'prix', 'tarif',
            // Dépenses
            'depense', 'achat', 'cout', 'dépens', 'economie', 'économie',
            // Budget
            'budget', 'tresorerie', 'solde', 'bilan', 'compte',
            // Prédictions
            'prediction', 'prevision', 'futur', 'prochain', 'estimation',
            // Conseils
            'conseil', 'economiser', 'reduire', 'optimiser', 'améliorer',
            // Agricole
            'recolte', 'semence', 'engrais', 'carburant', 'tracteur', 'irrigation',
            'marché', 'client', 'fournisseur', 'cooperative',
            // Chiffres
            'dinar', 'dt', 'tnd', 'prix', 'montant',
        ];
        
        foreach ($financeKeywords as $keyword) {
            if (strpos($questionLower, $keyword) !== false) {
                return true;
            }
        }
        
        // Mots-clés interdits (bloquer explicitement)
        $blockedKeywords = [
            'météo', 'temps', 'pluie', 'soleil', 'orages',
            'football', 'sport', 'match', 'équipe',
            'politique', 'élection', 'président', 'gouvernement',
            'recette cuisine', 'plat', 'manger', 'cuisiner',
            'voyage', 'vacances', 'avion', 'hôtel',
            'santé', 'maladie', 'médicament', 'hôpital',
            'informatique', 'programmation', 'code', 'ordinateur',
        ];
        
        foreach ($blockedKeywords as $keyword) {
            if (strpos($questionLower, $keyword) !== false) {
                return false;
            }
        }
        
        // Par défaut, bloquer les questions trop courtes ou sans contexte
        if (strlen($question) < 5) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Construit le prompt pour l'API
     */
    private function buildPrompt(string $question, array $userData): string
    {
        $prompt = "L'utilisateur a les données financières suivantes :\n";
        $prompt .= "- Revenus totaux: {$userData['totalRevenus']} DT\n";
        $prompt .= "- Dépenses totales: {$userData['totalDepenses']} DT\n";
        $prompt .= "- Solde: {$userData['balance']} DT\n";
        $prompt .= "- Dépenses ce mois: {$userData['depensesCeMois']} DT\n";
        $prompt .= "- Revenus ce mois: {$userData['revenusCeMois']} DT\n";
        
        if (!empty($userData['topCategorie'])) {
            $prompt .= "- Plus grosse catégorie de dépense: {$userData['topCategorie']}\n";
        }
        
        $prompt .= "\nQuestion de l'utilisateur: {$question}\n\n";
        $prompt .= "Réponds de manière courte, précise et utile (max 3-4 phrases). Donne des conseils actionnables pour un agriculteur tunisien.";
        
        return $prompt;
    }
    
    /**
     * Réponse hors ligne (quand l'API n'est pas disponible)
     */
    private function getOfflineResponse(string $question, array $userData): array
    {
        $questionLower = strtolower($question);
        
        // Réponses prédéfinies
        if (strpos($questionLower, 'solde') !== false || strpos($questionLower, 'balance') !== false) {
            $balance = $userData['balance'];
            $advice = $balance >= 0 ? "Bon équilibre ! Continuez ainsi." : "Attention, votre solde est négatif. Réduisez vos dépenses.";
            return [
                'success' => true,
                'answer' => "💰 Votre solde actuel est de {$balance} DT. {$advice}",
                'blocked' => false
            ];
        }
        
        if (strpos($questionLower, 'depense') !== false && strpos($questionLower, 'total') !== false) {
            return [
                'success' => true,
                'answer' => "💸 Vos dépenses totales sont de {$userData['totalDepenses']} DT. " .
                           "Ce mois-ci, vous avez dépensé {$userData['depensesCeMois']} DT.",
                'blocked' => false
            ];
        }
        
        if (strpos($questionLower, 'revenu') !== false || strpos($questionLower, 'gagne') !== false) {
            return [
                'success' => true,
                'answer' => "💰 Vos revenus totaux sont de {$userData['totalRevenus']} DT. " .
                           "Ce mois-ci, vous avez gagné {$userData['revenusCeMois']} DT.",
                'blocked' => false
            ];
        }
        
        if (strpos($questionLower, 'econom') !== false) {
            return [
                'success' => true,
                'answer' => "💡 Pour économiser, concentrez-vous sur votre plus gros poste de dépense : {$userData['topCategorie']}. " .
                           "Comparez les fournisseurs et achetez en groupe.",
                'blocked' => false
            ];
        }
        
        if (strpos($questionLower, 'conseil') !== false) {
            return [
                'success' => true,
                'answer' => "📊 Conseil financier : Suivez vos dépenses chaque semaine, fixez un budget par catégorie, " .
                           "et comparez les prix avant d'acheter. Une petite économie quotidienne fait une grande différence !",
                'blocked' => false
            ];
        }
        
        return [
            'success' => true,
            'answer' => "🤖 Je suis votre assistant financier. Posez-moi des questions sur :\n" .
                       "• Votre solde ('Quel est mon solde ?')\n" .
                       "• Vos dépenses ('Total de mes dépenses')\n" .
                       "• Vos revenus ('Combien j'ai gagné ?')\n" .
                       "• Des conseils ('Des conseils pour économiser ?')",
            'blocked' => false
        ];
    }
}