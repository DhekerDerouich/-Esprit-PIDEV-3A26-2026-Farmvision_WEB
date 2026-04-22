# 🚀 Nouvelles Fonctionnalités FarmVision

Ce document décrit les nouvelles fonctionnalités ajoutées au projet FarmVision.

## 📦 Services Ajoutés

### 1. 🌤️ MeteoService
Service d'intégration avec les APIs météo pour optimiser la planification des maintenances.

**APIs utilisées :**
- **Open-Meteo API** : Prévisions météo (température, humidité, vent, précipitations)
- **Sunrise-Sunset API** : Heures de lever/coucher du soleil

**Méthodes :**
- `getSunriseSunset(float $lat, float $lng, ?string $date = null): array`
- `getWeatherForecast(float $lat, float $lng): array`

**Utilisation :**
```php
$meteoService->getSunriseSunset(36.8065, 10.1815); // Tunisie
$meteoService->getWeatherForecast(36.8065, 10.1815);
```

---

### 2. 🌱 CarboneService
Calcul de l'empreinte carbone des équipements agricoles.

**Fonctionnalités :**
- Calcul du CO2 par équipement basé sur :
  - Type d'équipement (facteurs d'émission)
  - Âge de l'équipement
  - Nombre de maintenances
  - Heures d'utilisation estimées
- Niveaux de CO2 : Minime, Très faible, Faible, Modéré, Élevé, Très élevé
- Recommandations personnalisées

**Méthodes :**
- `getEstimationCO2(): array` - Estimation globale
- `getCO2ByEquipement(Equipement $equipement): array` - Par équipement

**Facteurs d'émission (kg CO2/heure) :**
- Tracteur : 25.5
- Moissonneuse : 32.0
- Pulvérisateur : 15.2
- Charrue : 18.7
- Semoir : 12.5
- Autre : 20.0

---

### 3. 🤖 TelegramBotService
Bot Telegram intelligent pour la gestion à distance.

**Commandes disponibles :**
- `/start` - Message de bienvenue
- `/equipements` - Liste tous les équipements
- `/urgent` - Maintenances urgentes (J-7)
- `/panne` - Équipements en panne
- `/stats` - Statistiques complètes
- `/aide` - Aide

**Fonctionnalités :**
- Traitement en langage naturel (questions en français)
- Alertes automatiques de maintenance (J-7 et J-1)
- Notifications en temps réel
- Support HTML pour formatage des messages

**Questions en langage naturel supportées :**
- "Combien d'équipements ?"
- "Maintenances urgentes ?"
- "Équipements en panne ?"

---

## 🛠️ Commandes Console

### Telegram

#### 1. Obtenir votre Chat ID
```bash
php bin/console app:telegram:get-chat-id
```
Envoyez `/start` à votre bot sur Telegram, puis exécutez cette commande.

#### 2. Démarrer le bot en mode polling (développement)
```bash
php bin/console app:telegram:poll
```
Le bot écoute les messages en continu. Appuyez sur Ctrl+C pour arrêter.

#### 3. Envoyer les alertes de maintenance
```bash
php bin/console app:telegram:send-alerts
```
Envoie automatiquement les alertes pour les maintenances à J-7 et J-1.

#### 4. Configurer le webhook (production)
```bash
php bin/console app:telegram:set-webhook
```

---

## ⚙️ Configuration

### 1. Variables d'environnement (.env ou .env.local)

```env
###> telegram bot ###
TELEGRAM_BOT_TOKEN=votre_token_ici
TELEGRAM_BOT_USERNAME=VotreBotUsername
TELEGRAM_ADMIN_CHAT_ID=votre_chat_id_ici
###< telegram bot ###
```

### 2. Obtenir un token Telegram
1. Ouvrez Telegram et cherchez **@BotFather**
2. Envoyez `/newbot`
3. Suivez les instructions
4. Copiez le token fourni dans `.env`

### 3. Obtenir votre Chat ID
```bash
php bin/console app:telegram:get-chat-id
```

---

## 📊 Contrôleurs Mis à Jour

### EquipementController
**Nouvelles fonctionnalités :**
- Affichage de l'empreinte CO2 par équipement
- Génération de QR codes
- Téléchargement de QR codes en PNG

**Nouvelles routes :**
- `GET /admin/equipements/{id}/qr` - Afficher le QR code
- `GET /admin/equipements/{id}/qr/download` - Télécharger le QR code

---

### MaintenanceController
**Nouvelles fonctionnalités :**
- Intégration des données météo
- Affichage des heures de lever/coucher du soleil
- Recommandations basées sur la météo

**Données ajoutées aux vues :**
- `soleil` : Lever, coucher, durée du jour
- `meteo` : Température, vent, précipitations

---

### CarboneController (nouveau)
**Route :** `/admin/carbone`

**Fonctionnalités :**
- Vue d'ensemble de l'empreinte carbone
- Détails par équipement
- Recommandations écologiques
- Graphiques et statistiques

---

## 📦 Installation des Dépendances

```bash
cd main
composer install
```

**Nouvelles dépendances ajoutées :**
- `irazasyed/telegram-bot-sdk` : ^3.16
- `cboden/ratchet` : ^0.4.4
- `symfony/mercure` : *
- `symfony/cache` : 6.4.*

---

## 🧪 Tests

### Tester le bot Telegram
```bash
# 1. Démarrer le bot
php bin/console app:telegram:poll

# 2. Dans un autre terminal, envoyer un message test
php bin/console app:telegram:test
```

### Tester les alertes
```bash
php bin/console app:telegram:send-alerts
```

---

## 🔄 Automatisation (Cron)

Pour automatiser l'envoi des alertes, ajoutez à votre crontab :

```bash
# Vérifier les alertes tous les jours à 8h00
0 8 * * * cd /chemin/vers/main && php bin/console app:telegram:send-alerts
```

---

## 📱 Utilisation du Bot Telegram

### 1. Configuration initiale
```bash
# Obtenir votre Chat ID
php bin/console app:telegram:get-chat-id

# Ajouter le Chat ID dans .env
TELEGRAM_ADMIN_CHAT_ID=123456789
```

### 2. Démarrer le bot
```bash
php bin/console app:telegram:poll
```

### 3. Interagir avec le bot
Ouvrez Telegram et envoyez :
- `/start` pour commencer
- `/equipements` pour voir vos équipements
- `/urgent` pour les maintenances urgentes
- `/stats` pour les statistiques

Ou posez des questions en français :
- "Combien d'équipements ?"
- "Maintenances urgentes ?"
- "Équipements en panne ?"

---

## 🎨 Templates à Créer

Pour utiliser pleinement ces fonctionnalités, créez les templates suivants :

### 1. Carbone
- `templates/admin/carbone/index.html.twig`

### 2. QR Codes
- `templates/admin/equipement/qr.html.twig`

### 3. Météo dans Maintenance
Mettez à jour :
- `templates/admin/maintenance/index.html.twig`
- `templates/admin/maintenance/new.html.twig`
- `templates/admin/maintenance/edit.html.twig`
- `templates/admin/maintenance/show.html.twig`

---

## 🔐 Sécurité

### Webhook Telegram (Production)
Pour sécuriser le webhook en production :

1. Utilisez HTTPS obligatoirement
2. Configurez le webhook :
```bash
php bin/console app:telegram:set-webhook
```

3. Vérifiez la signature des requêtes dans le contrôleur

---

## 📈 Améliorations Futures

- [ ] Stockage des Chat IDs en base de données
- [ ] Notifications multi-utilisateurs
- [ ] Commandes personnalisées par utilisateur
- [ ] Intégration avec d'autres APIs météo
- [ ] Graphiques d'évolution du CO2
- [ ] Export PDF des rapports carbone
- [ ] Alertes personnalisables (J-3, J-5, etc.)
- [ ] Support multilingue du bot

---

## 🆘 Support

Pour toute question ou problème :
1. Vérifiez que toutes les dépendances sont installées
2. Vérifiez la configuration dans `.env`
3. Consultez les logs : `var/log/dev.log`
4. Testez les commandes console individuellement

---

## 📝 Changelog

### Version 2.0 (Avril 2026)
- ✅ Ajout du service Météo (Open-Meteo + Sunrise-Sunset)
- ✅ Ajout du service Carbone (calcul empreinte CO2)
- ✅ Ajout du bot Telegram avec commandes intelligentes
- ✅ Intégration météo dans les maintenances
- ✅ Affichage CO2 dans les équipements
- ✅ Génération et téléchargement de QR codes
- ✅ Commandes console pour Telegram
- ✅ Alertes automatiques de maintenance

---

**Développé avec ❤️ pour FarmVision**
