<?php
// src/Controller/Admin/VoiceChatController.php

namespace App\Controller\Admin;

use App\Repository\EquipementRepository;
use App\Repository\MaintenanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/voice-chat')]
class VoiceChatController extends AbstractController
{
    #[Route('/', name: 'admin_voice_chat')]
    public function index(): Response
    {
        return $this->render('admin/voice_chat/index.html.twig');
    }
    
    #[Route('/ask', name: 'admin_voice_chat_ask', methods: ['POST'])]
    public function ask(
        Request $request,
        EquipementRepository $equipementRepo,
        MaintenanceRepository $maintenanceRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $message = mb_strtolower(trim($data['message'] ?? ''), 'UTF-8');
        
        $response = $this->processQuery($message, $equipementRepo, $maintenanceRepo);
        
        return $this->json(['response' => $response]);
    }
    
    private function processQuery(
        string $message,
        EquipementRepository $equipementRepo,
        MaintenanceRepository $maintenanceRepo
    ): string {
        
        // ==================== COMPTER LES ÉQUIPEMENTS ====================
        if (preg_match('/(combien|nombre|nb|count|quantité|total).*(équipement|machine|engin|matériel|matos)/i', $message) ||
            preg_match('/\b(nombre|combien)\b/i', $message) ||
            ($message === 'nombre' || $message === 'nb' || $message === 'count')) {
            
            $total = count($equipementRepo->findAll());
            if ($total == 0) {
                return "Vous n'avez aucun équipement enregistré pour le moment. Utilisez la section 'Équipements' pour en ajouter.";
            }
            return "Vous avez actuellement $total équipement(s) dans votre parc agricole.";
        }
        
        // ==================== LISTE DES ÉQUIPEMENTS ====================
        if (preg_match('/(liste|quels sont|affiche|montre|tous les).*(équipement|machine|engin|matériel)/i', $message) ||
            preg_match('/\b(équipements|machines|engins)\b/i', $message)) {
            
            $equipements = $equipementRepo->findAll();
            if (count($equipements) > 0) {
                $names = array_map(fn($e) => $e->getNom(), $equipements);
                if (count($names) <= 5) {
                    return "Voici vos équipements : " . implode(', ', $names) . ".";
                } else {
                    $premiers = array_slice($names, 0, 5);
                    return "Voici vos équipements : " . implode(', ', $premiers) . " et " . (count($names) - 5) . " autres. Consultez la section 'Équipements' pour la liste complète.";
                }
            }
            return "Vous n'avez aucun équipement enregistré. Commencez par ajouter vos équipements dans la section 'Équipements'.";
        }
        
        // ==================== MAINTENANCES URGENTES ====================
        if (preg_match('/(urgent|bientôt|imminente|prochaine|à venir).*(maintenance|intervention|révision)/i', $message) ||
            preg_match('/\b(maintenances? urgentes?|urgent)\b/i', $message)) {
            
            $today = new \DateTime();
            $maintenances = $maintenanceRepo->findAll();
            $urgentes = [];
            $dates = [];
            
            foreach ($maintenances as $m) {
                if ($m->getStatut() === 'Planifiée') {
                    $diff = $today->diff($m->getDateMaintenance())->days;
                    if ($diff <= 7 && $diff >= 0) {
                        $urgentes[] = $m->getEquipement()->getNom();
                        $dates[] = $m->getDateMaintenance()->format('d/m/Y');
                    }
                }
            }
            
            if (count($urgentes) > 0) {
                $response = "⚠️ Voici les maintenances urgentes dans les 7 jours : ";
                for ($i = 0; $i < count($urgentes); $i++) {
                    $response .= $urgentes[$i] . " (" . $dates[$i] . ")";
                    if ($i < count($urgentes) - 1) $response .= ", ";
                }
                return $response;
            }
            return "✅ Aucune maintenance urgente à signaler pour le moment. Tout est sous contrôle !";
        }
        
        // ==================== ÉQUIPEMENTS EN PANNE ====================
        if (preg_match('/(panne|cassé|hors service|défectueux|ne marche)/i', $message)) {
            $equipements = $equipementRepo->findBy(['etat' => 'En panne']);
            if (count($equipements) > 0) {
                $names = array_map(fn($e) => $e->getNom(), $equipements);
                return "🔴 Les équipements suivants sont actuellement en panne : " . implode(', ', $names) . ". Veuillez planifier une maintenance corrective.";
            }
            return "✅ Aucun équipement en panne pour le moment. Tous vos équipements sont fonctionnels !";
        }
        
        // ==================== ÉQUIPEMENTS EN MAINTENANCE ====================
        if (preg_match('/(maintenance|réparation|entretien).*(cours|en cours)/i', $message) ||
            preg_match('/\b(en maintenance)\b/i', $message)) {
            
            $equipements = $equipementRepo->findBy(['etat' => 'Maintenance']);
            if (count($equipements) > 0) {
                $names = array_map(fn($e) => $e->getNom(), $equipements);
                return "🔧 Les équipements suivants sont en cours de maintenance : " . implode(', ', $names);
            }
            return "✅ Aucun équipement en maintenance actuellement.";
        }
        
        // ==================== COÛTS ====================
        if (preg_match('/(coût|prix|argent|dépense|facture).*(maintenance|entretien|réparation)/i', $message) ||
            preg_match('/\b(coût|prix|combien coûte)\b/i', $message)) {
            
            $stats = $maintenanceRepo->getStatistics();
            $coutTotal = $stats['coutTotal'];
            $coutMoyen = $stats['total'] > 0 ? $coutTotal / $stats['total'] : 0;
            
            return "💰 Le coût total des maintenances est de " . number_format($coutTotal, 2) . " dinars. " .
                   "La moyenne par maintenance est de " . number_format($coutMoyen, 2) . " dinars.";
        }
        
        // ==================== STATISTIQUES GÉNÉRALES ====================
        if (preg_match('/(statistique|résumé|synthèse|bilan|situation)/i', $message)) {
            $nbEquipements = count($equipementRepo->findAll());
            $stats = $maintenanceRepo->getStatistics();
            $fonctionnels = $equipementRepo->findBy(['etat' => 'Fonctionnel']);
            $enPanne = $equipementRepo->findBy(['etat' => 'En panne']);
            
            return "📊 Voici votre bilan : " .
                   "$nbEquipements équipement(s) au total, " .
                   count($fonctionnels) . " fonctionnel(s), " .
                   count($enPanne) . " en panne, " .
                   $stats['planifiees'] . " maintenance(s) planifiée(s), " .
                   $stats['realisees'] . " réalisée(s). " .
                   "Coût total : " . number_format($stats['coutTotal'], 2) . " DT.";
        }
        
        // ==================== TYPE SPÉCIFIQUE D'ÉQUIPEMENT ====================
        $typesEquipements = [
            'tracteur' => 'Tracteur',
            'moissonneuse' => 'Moissonneuse',
            'pulvérisateur' => 'Pulvérisateur',
            'charrue' => 'Charrue',
            'semoir' => 'Semoir'
        ];
        
        foreach ($typesEquipements as $mot => $type) {
            if (preg_match('/\b' . $mot . 's?\b/i', $message)) {
                $equipements = $equipementRepo->findBy(['type' => $type]);
                if (count($equipements) > 0) {
                    $names = array_map(fn($e) => $e->getNom(), $equipements);
                    return "Vous avez " . count($equipements) . " $mot(s) : " . implode(', ', $names);
                }
                return "Vous n'avez pas de $mot enregistré dans votre parc.";
            }
        }
        
        // ==================== GARANTIE ====================
        if (preg_match('/(garantie|garanti|sous garantie)/i', $message)) {
            $equipements = $equipementRepo->findAll();
            $sousGarantie = 0;
            $horsGarantie = 0;
            $nomsSousGarantie = [];
            foreach ($equipements as $e) {
                if ($e->isSousGarantie()) {
                    $sousGarantie++;
                    $nomsSousGarantie[] = $e->getNom();
                } else {
                    $horsGarantie++;
                }
            }
            
            if ($sousGarantie > 0) {
                return "🛡️ Vous avez $sousGarantie équipement(s) sous garantie : " . implode(', ', $nomsSousGarantie) . ". Et $horsGarantie équipement(s) hors garantie.";
            }
            return "🛡️ Vous avez $sousGarantie équipement(s) sous garantie et $horsGarantie hors garantie.";
        }
        
        // ==================== ACCUEIL / AIDE ====================
        if (preg_match('/(bonjour|salut|coucou|hello|hi|hey)/i', $message)) {
            return "👋 Bonjour ! Je suis votre assistant vocal FarmVision. Je peux vous renseigner sur vos équipements, les maintenances, les pannes et les coûts. Que voulez-vous savoir ?";
        }
        
        if (preg_match('/(aide|help|que faire|comment|peux-tu|fonctionne|instruction)/i', $message)) {
            return "💡 Voici ce que je peux faire pour vous :\n\n" .
                   "• 📊 Compter vos équipements (ex: 'nombre d'équipements')\n" .
                   "• ⚠️ Lister les maintenances urgentes (ex: 'maintenances urgentes')\n" .
                   "• 🔴 Voir les équipements en panne (ex: 'équipements en panne')\n" .
                   "• 💰 Connaître les coûts de maintenance (ex: 'coût des maintenances')\n" .
                   "• 📋 Afficher la liste de vos équipements (ex: 'liste des équipements')\n" .
                   "• 🛡️ Vérifier les garanties (ex: 'équipements sous garantie')\n" .
                   "• 📈 Obtenir des statistiques globales (ex: 'bilan')\n\n" .
                   "Essayez : 'nombre', 'maintenances urgentes', 'équipements en panne'";
        }
        
        // ==================== RÉPONSE PAR DÉFAUT ====================
        return "🤔 Je n'ai pas bien compris votre question.\n\n" .
               "💡 Voici ce que je peux faire :\n" .
               "• Compter vos équipements (tapez 'nombre' ou 'combien')\n" .
               "• Lister les maintenances urgentes (tapez 'urgent')\n" .
               "• Voir les équipements en panne (tapez 'panne')\n" .
               "• Connaître les coûts (tapez 'coût')\n" .
               "• Afficher la liste des équipements (tapez 'liste')\n\n" .
               "Exemples : 'nombre', 'maintenances urgentes', 'équipements en panne'";
    }
}
