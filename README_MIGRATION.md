# 🚜 FarmVision - Système de Gestion Agricole Intelligent

## 🎉 Version 2.0 - Migration Complète

Bienvenue dans FarmVision 2.0 ! Ce projet contient maintenant **toutes les fonctionnalités avancées** migrées depuis `-Esprit-PIDEV-3A26-2026-Farmvision_WEB`.

---

## ⚡ Démarrage Rapide

### 1. Installation
```bash
composer install
```

### 2. Configuration
Copiez `.env.example` vers `.env` et configurez :
```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/farmvision_db"
TELEGRAM_BOT_TOKEN=votre_token_ici
TELEGRAM_ADMIN_CHAT_ID=votre_chat_id_ici
```

### 3. Base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 4. Lancer le serveur
```bash
symfony server:start
```

### 5. Accéder à l'application
```
http://localhost:8000
```

---

## 🌟 Fonctionnalités Principales

### 🎤 Assistant Vocal
**Route :** `/admin/voice-chat`

Posez des questions vocalement ou par texte :
- "Combien d'équipements ?"
- "Maintenances urgentes ?"
- "Équipements en panne ?"
- Et bien plus !

### 🤖 Bot Telegram
**Commandes :**
```bash
# Obtenir votre Chat ID
php bin/console app:telegram:get-chat-id

# Démarrer le bot
php bin/console app:telegram:poll
```

**Dans Telegram :**
- `/start` - Démarrer
- `/equipements` - Liste
- `/urgent` - Maintenances urgentes
- `/stats` - Statistiques

### 🌤️ Météo Intégrée
Données météo automatiques dans les maintenances :
- Prévisions (Open-Meteo)
- Lever/coucher du soleil
- Planification optimisée

### 🌱 Empreinte Carbone
**Route :** `/admin/carbone`

Calculez l'impact environnemental de vos équipements.

### 📱 QR Codes
Générez et téléchargez des QR codes pour vos équipements.

---

## 📚 Documentation

### Guides Complets
- **[FONCTIONNALITES_COMPLETES.md](FONCTIONNALITES_COMPLETES.md)** - Vue d'ensemble
- **[VOICE_CHAT_DOCUMENTATION.md](VOICE_CHAT_DOCUMENTATION.md)** - Assistant vocal
- **[NOUVELLES_FONCTIONNALITES.md](NOUVELLES_FONCTIONNALITES.md)** - Détails techniques
- **[RESUME_MIGRATION.md](RESUME_MIGRATION.md)** - Résumé migration

### Vérification
```bash
php verifier_migration.php
```

---

## 🔧 Configuration Telegram

### 1. Créer un bot
1. Ouvrez Telegram
2. Cherchez **@BotFather**
3. Envoyez `/newbot`
4. Suivez les instructions
5. Copiez le token dans `.env`

### 2. Obtenir votre Chat ID
```bash
# Envoyez d'abord /start à votre bot
php bin/console app:telegram:get-chat-id
```

### 3. Tester
```bash
php bin/console app:telegram:test
```

---

## 🎯 Routes Principales

### Admin
- `/admin/dashboard` - Tableau de bord
- `/admin/equipements` - Gestion équipements
- `/admin/maintenances` - Gestion maintenances
- `/admin/carbone` - Empreinte carbone
- `/admin/voice-chat` - Assistant vocal ⭐

### API
- `POST /telegram/webhook` - Webhook Telegram
- `POST /admin/voice-chat/ask` - Questions assistant vocal

---

## 🧪 Tests

### Voice Chat
```
1. Accédez à /admin/voice-chat
2. Cliquez sur 🎤 ou tapez une question
3. Testez : "nombre", "urgent", "panne"
```

### Telegram
```bash
php bin/console app:telegram:poll
# Dans Telegram : /start
```

### Météo
```
Accédez à /admin/maintenances
Les données météo s'affichent automatiquement
```

---

## 📊 Technologies

### Backend
- **Symfony 6.4** - Framework PHP
- **Doctrine ORM** - Base de données
- **Twig** - Templates

### APIs Externes
- **Open-Meteo** - Prévisions météo
- **Sunrise-Sunset** - Heures soleil
- **Telegram Bot API** - Notifications
- **Web Speech API** - Reconnaissance vocale

### Packages
- `irazasyed/telegram-bot-sdk` - Bot Telegram
- `cboden/ratchet` - WebSockets
- `symfony/mercure` - Temps réel

---

## 🔐 Sécurité

- Authentification Symfony
- CSRF protection
- Validation des entrées
- Pas de stockage audio
- Traitement local des données

---

## 🌐 Compatibilité

### Navigateurs (Voice Chat)
- ✅ Chrome (recommandé)
- ✅ Edge (recommandé)
- ⚠️ Safari (partiel)
- ❌ Firefox (pas de reconnaissance vocale)

### Serveur
- PHP >= 8.1
- MySQL/MariaDB
- Composer

---

## 📈 Statistiques

- **26 fichiers** créés/modifiés
- **8 routes** ajoutées
- **3 services** créés
- **5 contrôleurs** ajoutés/modifiés
- **4 commandes** console
- **3 APIs** intégrées
- **15+ questions** vocales supportées

---

## 🐛 Dépannage

### Voice Chat ne fonctionne pas
- Vérifier permissions microphone
- Utiliser Chrome ou Edge
- Vérifier HTTPS (requis en production)

### Bot Telegram ne répond pas
- Vérifier `TELEGRAM_BOT_TOKEN` dans `.env`
- Démarrer : `php bin/console app:telegram:poll`
- Consulter : `var/log/dev.log`

### Erreur base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

---

## 🚀 Déploiement

### Production
1. Configurer `.env` pour production
2. Exécuter `composer install --no-dev --optimize-autoloader`
3. Configurer webhook Telegram (HTTPS requis)
4. Configurer cron pour alertes :
```bash
0 8 * * * cd /path/to/project && php bin/console app:telegram:send-alerts
```

---

## 📞 Support

### Logs
```bash
tail -f var/log/dev.log
```

### Vérification
```bash
php verifier_migration.php
```

### Documentation
Consultez les fichiers `.md` dans le dossier racine.

---

## 🎯 Roadmap

### Prochaines Fonctionnalités
- [ ] Export conversations Voice Chat
- [ ] Support multilingue
- [ ] Dashboard temps réel
- [ ] Rapports automatiques
- [ ] Application mobile
- [ ] Intégration IoT

---

## 👥 Équipe

Développé pour le projet PIDEV 3A26 - ESPRIT 2026

---

## 📄 Licence

Propriétaire - Tous droits réservés

---

## 🎉 Remerciements

Merci d'utiliser FarmVision ! Pour toute question, consultez la documentation ou les logs.

**Version 2.0 - Avril 2026**

---

## 🔗 Liens Rapides

- [Documentation Complète](FONCTIONNALITES_COMPLETES.md)
- [Guide Voice Chat](VOICE_CHAT_DOCUMENTATION.md)
- [Guide Installation](INSTALLATION_NOUVELLES_FONCTIONNALITES.sh)
- [Résumé Migration](RESUME_MIGRATION.md)

---

**🚜 FarmVision - L'agriculture intelligente à portée de voix !**
