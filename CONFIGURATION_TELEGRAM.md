# 🤖 Configuration Telegram - Guide Rapide

## ✅ Étape 1 : Créer un Bot Telegram

### 1. Ouvrir Telegram
- Sur votre téléphone ou ordinateur
- Cherchez **@BotFather**

### 2. Créer le bot
Envoyez ces commandes à BotFather :

```
/newbot
```

### 3. Suivre les instructions
- **Nom du bot** : FarmVision Bot (ou ce que vous voulez)
- **Username** : farmvision2026bot (doit finir par "bot")

### 4. Copier le token
BotFather vous donnera un token comme :
```
8625436381:AAGLx8sAJfK7JtQEadpAixlcwVyp9nbaLEQ
```

---

## ✅ Étape 2 : Configurer .env

Ouvrez le fichier `main/.env` et remplacez :

```env
###> telegram bot ###
TELEGRAM_BOT_TOKEN=8625436381:AAGLx8sAJfK7JtQEadpAixlcwVyp9nbaLEQ
TELEGRAM_BOT_USERNAME=farmvision2026bot
TELEGRAM_ADMIN_CHAT_ID=your_chat_id_here
###< telegram bot ###
```

**Remplacez :**
- `TELEGRAM_BOT_TOKEN` : Votre token de BotFather
- `TELEGRAM_BOT_USERNAME` : Le username de votre bot

---

## ✅ Étape 3 : Obtenir votre Chat ID

### 1. Envoyer un message à votre bot
- Cherchez votre bot dans Telegram (par son username)
- Envoyez `/start`

### 2. Exécuter la commande
```bash
cd main
php bin/console app:telegram:get-chat-id
```

### 3. Copier le Chat ID
Vous verrez quelque chose comme :
```
• Chat ID: 8427250139 - Utilisateur: Votre Nom (@username)
```

### 4. Mettre à jour .env
```env
TELEGRAM_ADMIN_CHAT_ID=8427250139
```

---

## ✅ Étape 4 : Tester le Bot

### Mode Polling (Développement)
```bash
php bin/console app:telegram:poll
```

Le bot écoute maintenant les messages. Laissez cette fenêtre ouverte.

### Tester dans Telegram
Envoyez ces commandes à votre bot :
- `/start` - Message de bienvenue
- `/equipements` - Liste des équipements
- `/stats` - Statistiques
- `/aide` - Aide

### Test d'envoi
Dans un autre terminal :
```bash
php bin/console app:telegram:test
```

Vous devriez recevoir un message sur Telegram !

---

## ✅ Étape 5 : Alertes Automatiques

### Tester manuellement
```bash
php bin/console app:telegram:send-alerts
```

### Automatiser avec Cron (Optionnel)
Ouvrez votre crontab :
```bash
crontab -e
```

Ajoutez cette ligne (alertes tous les jours à 8h) :
```bash
0 8 * * * cd /chemin/vers/main && php bin/console app:telegram:send-alerts
```

---

## 🎯 Commandes Disponibles

### Dans Telegram
- `/start` - Démarrer
- `/equipements` - Liste des équipements
- `/urgent` - Maintenances urgentes (J-7)
- `/panne` - Équipements en panne
- `/stats` - Statistiques complètes
- `/aide` - Aide

### Questions en Français
Vous pouvez aussi poser des questions :
- "Combien d'équipements ?"
- "Maintenances urgentes ?"
- "Équipements en panne ?"

---

## 🐛 Dépannage

### Le bot ne répond pas
1. Vérifiez que `TELEGRAM_BOT_TOKEN` est correct dans `.env`
2. Vérifiez que le bot est démarré : `php bin/console app:telegram:poll`
3. Consultez les logs : `tail -f var/log/dev.log`

### Erreur "Telegram non configuré"
1. Vérifiez que le token est dans `.env`
2. Redémarrez le serveur Symfony
3. Videz le cache : `php bin/console cache:clear`

### Chat ID introuvable
1. Assurez-vous d'avoir envoyé `/start` au bot
2. Attendez quelques secondes
3. Réexécutez : `php bin/console app:telegram:get-chat-id`

---

## 📱 Exemple Complet

### Fichier .env
```env
###> telegram bot ###
TELEGRAM_BOT_TOKEN=8625436381:AAGLx8sAJfK7JtQEadpAixlcwVyp9nbaLEQ
TELEGRAM_BOT_USERNAME=farmvision2026bot
TELEGRAM_ADMIN_CHAT_ID=8427250139
###< telegram bot ###
```

### Démarrer le bot
```bash
cd main
php bin/console app:telegram:poll
```

### Dans Telegram
```
Vous: /start
Bot: 👋 Bienvenue sur FarmVision Bot !
     Je suis votre assistant agricole...

Vous: /equipements
Bot: 📋 Liste des équipements :
     ✅ Tracteur John Deere - Tracteur
     ✅ Moissonneuse Case - Moissonneuse
     ...

Vous: combien d'équipements ?
Bot: 📊 Vous avez 12 équipement(s) dans votre parc agricole.
```

---

## 🎉 C'est Prêt !

Votre bot Telegram est maintenant configuré et opérationnel !

**Prochaines étapes :**
1. ✅ Tester toutes les commandes
2. ✅ Configurer les alertes automatiques
3. ✅ Tester l'assistant vocal : `/admin/voice-chat`

---

**Documentation complète :** `NOUVELLES_FONCTIONNALITES.md`
