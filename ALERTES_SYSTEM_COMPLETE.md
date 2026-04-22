# ✅ Système d'Alertes - Installation Complète

## Date: 21 Avril 2026

---

## 🎯 RÉSUMÉ

Le système d'alertes en temps réel a été installé avec succès dans le projet `main`. Les alertes s'affichent maintenant dans l'interface admin avec une cloche de notification en haut à droite.

---

## ✅ COMPOSANTS INSTALLÉS

### 1. Service AlertesService
**Fichier**: `main/src/Service/AlertesService.php`

**Fonctionnalités**:
- ✅ Détection des maintenances imminentes (< 7 jours)
- ✅ Détection des maintenances en retard
- ✅ Alertes garanties expirées
- ✅ Alertes équipements en panne
- ✅ Alertes équipements en maintenance
- ✅ Alertes équipements vieillissants (> 80% durée de vie)
- ✅ Système de priorités (URGENT, WARNING, INFO)
- ✅ Catégorisation des alertes

### 2. Contrôleur AlertesController
**Fichier**: `main/src/Controller/Admin/AlertesController.php`

**Routes**:
- `GET /admin/alertes/notifications` - Récupérer toutes les alertes
- `POST /admin/alertes/mark-read` - Marquer une alerte comme lue
- `POST /admin/alertes/mark-all-read` - Marquer toutes les alertes comme lues

### 3. Interface Utilisateur
**Fichier**: `main/templates/admin/base.html.twig`

**Éléments ajoutés**:
- ✅ Cloche de notification en haut à droite
- ✅ Badge rouge avec nombre d'alertes non lues
- ✅ Dropdown avec liste des alertes
- ✅ Couleurs selon le type (Rouge=URGENT, Orange=WARNING, Vert=INFO)
- ✅ Icônes pour chaque type d'alerte
- ✅ Bouton "Tout marquer lu"
- ✅ Rechargement automatique toutes les 30 secondes
- ✅ Clic sur alerte pour marquer comme lue et rediriger

---

## 🎨 TYPES D'ALERTES

### 1. URGENT (Rouge 🔴)
- Équipements en panne
- Maintenances en retard > 3 jours
- Maintenances dans < 2 jours

### 2. WARNING (Orange 🟠)
- Maintenances en retard 1-3 jours
- Maintenances dans 3-4 jours
- Garanties expirées > 12 mois
- Équipements > 90% durée de vie

### 3. INFO (Vert 🟢)
- Maintenances dans 5-7 jours
- Équipements en maintenance
- Garanties expirées < 12 mois
- Équipements 80-90% durée de vie

---

## 📊 CATÉGORIES D'ALERTES

| Catégorie | Icône | Description |
|-----------|-------|-------------|
| MAINTENANCE | 🔧 | Maintenances imminentes |
| RETARD | ⏰ | Maintenances en retard |
| PANNE | 🔴 | Équipements en panne |
| GARANTIE | 🛡️ | Garanties expirées |
| MAINTENANCE_EN_COURS | 🔧 | Équipements en maintenance |
| PERFORMANCE | 📊 | Équipements vieillissants |

---

## 🔔 AFFICHAGE DES ALERTES

### Position
- En haut à droite de la barre de navigation admin
- À côté du menu utilisateur

### Badge
- Affiche le nombre d'alertes non lues
- Couleur rouge (#dc2626)
- Disparaît quand toutes les alertes sont lues

### Dropdown
- Largeur: 400px
- Hauteur max: 500px
- Scroll automatique si > 500px
- Affiche jusqu'à toutes les alertes

### Format d'une alerte
```
[Icône] Titre                    [TYPE]
Message détaillé
Temps relatif (ex: "2 h", "3 j")
```

---

## 🔄 FONCTIONNEMENT

### Chargement
1. Page chargée → Appel AJAX à `/admin/alertes/notifications`
2. Réponse JSON avec toutes les alertes
3. Affichage dans le dropdown
4. Badge mis à jour avec nombre non lues

### Rechargement automatique
- Toutes les 30 secondes
- Appel AJAX en arrière-plan
- Mise à jour silencieuse

### Interaction
1. Clic sur cloche → Ouvre/ferme dropdown
2. Clic sur alerte → Marque comme lue + Redirige vers lien
3. Clic sur "Tout marquer lu" → Marque toutes comme lues
4. Clic ailleurs → Ferme dropdown

### Persistance
- Alertes lues stockées en session PHP
- Persistantes pendant la session
- Réinitialisées à la déconnexion

---

## 🎯 EXEMPLES D'ALERTES

### Maintenance imminente
```
🔧 Maintenance imminente                    [URGENT]
Tracteur Massey Ferguson - Maintenance Préventive dans 1 jours
Il y a 2 h
```

### Équipement en panne
```
🔴 🚨 Équipement en panne                   [URGENT]
Moissonneuse John Deere est actuellement en panne - Intervention requise immédiatement
À l'instant
```

### Garantie expirée
```
🛡️ Garantie expirée                        [WARNING]
Pulvérisateur Amazone - Garantie expirée depuis 18 mois
Il y a 3 j
```

### Équipement vieillissant
```
📊 Équipement vieillissant                  [INFO]
Charrue Kuhn a atteint 85% de sa durée de vie estimée (4/5 ans). Prévoir un remplacement.
Il y a 1 j
```

---

## 🛠️ DÉPENDANCES INSTALLÉES

### QR Code
```bash
composer require endroid/qr-code
```
**Version installée**: 5.1.0 (compatible PHP 8.0+)

### Telegram Bot
```bash
composer require irazasyed/telegram-bot-sdk
```
**Version installée**: 3.16.0

---

## 📝 CONFIGURATION

### Variables d'environnement (.env)
```env
# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_CHAT_ID=your_chat_id_here
```

---

## ✅ TESTS À EFFECTUER

### Test 1: Affichage des alertes
1. Aller sur `/admin/dashboard`
2. Vérifier la cloche en haut à droite
3. Cliquer sur la cloche
4. Vérifier que les alertes s'affichent

### Test 2: Badge non lues
1. Vérifier que le badge rouge affiche un nombre
2. Cliquer sur une alerte
3. Vérifier que le badge diminue

### Test 3: Marquer tout lu
1. Cliquer sur "Tout marquer lu"
2. Vérifier que le badge disparaît
3. Vérifier que toutes les alertes n'ont plus le fond vert

### Test 4: Redirection
1. Cliquer sur une alerte
2. Vérifier la redirection vers la page appropriée

### Test 5: Rechargement automatique
1. Attendre 30 secondes
2. Vérifier que les alertes se rechargent

---

## 🎨 PERSONNALISATION

### Modifier la fréquence de rechargement
Dans `admin/base.html.twig`, ligne ~250:
```javascript
// Recharger toutes les 30 secondes
setInterval(loadAlertes, 30000); // 30000 = 30 secondes
```

### Modifier les seuils d'alertes
Dans `AlertesService.php`:
```php
// Maintenances imminentes
if ($joursRestants <= 7) { // Modifier ce nombre

// Équipements vieillissants
if ($dureeVie && $e->getAge() > ($dureeVie * 0.8)) { // Modifier 0.8 (80%)
```

### Ajouter de nouveaux types d'alertes
Dans `AlertesService.php`, méthode `getToutesLesAlertes()`:
```php
// Ajouter votre logique ici
$alertes[] = [
    'id' => 'custom_' . time(),
    'titre' => 'Mon alerte',
    'message' => 'Description',
    'type' => 'WARNING', // URGENT, WARNING, INFO
    'couleur' => '#f59e0b',
    'categorie' => 'CUSTOM',
    'icone' => '⚡',
    'priorite' => 2,
    'lien' => '/admin/custom',
    'date' => new \DateTime()
];
```

---

## 🚀 PROCHAINES ÉTAPES

### Améliorations possibles:
1. ✅ Système d'alertes installé
2. ⏳ Notifications push navigateur
3. ⏳ Envoi d'emails pour alertes urgentes
4. ⏳ Intégration Telegram pour alertes
5. ⏳ Historique des alertes
6. ⏳ Filtres par catégorie
7. ⏳ Recherche dans les alertes

---

## 📊 STATISTIQUES

- **Fichiers créés**: 2
- **Fichiers modifiés**: 1
- **Lignes de code ajoutées**: ~400
- **Routes ajoutées**: 3
- **Types d'alertes**: 6
- **Niveaux de priorité**: 3

---

**Système d'alertes opérationnel! 🎉**
