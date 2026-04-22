# 🚀 Guide de Démarrage Rapide

## Vérifier que tout fonctionne

### 1. ✅ Vérifier les dépendances

```bash
# Vérifier QR Code
composer show endroid/qr-code

# Vérifier Telegram Bot
composer show irazasyed/telegram-bot-sdk
```

**Résultat attendu**: Les deux packages doivent être installés

---

### 2. 🔔 Tester le système d'alertes

1. Ouvrir le navigateur
2. Aller sur `http://localhost:8000/admin/dashboard`
3. Regarder en haut à droite → Vous devez voir une **cloche** 🔔
4. Si des alertes existent, un **badge rouge** avec un nombre apparaît
5. Cliquer sur la cloche → Un dropdown s'ouvre avec la liste des alertes

**Alertes automatiques créées:**
- Maintenances dans moins de 7 jours
- Maintenances en retard
- Équipements en panne
- Garanties expirées
- Équipements vieillissants

---

### 3. 🌱 Tester l'empreinte carbone

#### Dashboard CO2
```
Admin: http://localhost:8000/admin/carbone
Front: http://localhost:8000/carbone
```

**Ce que vous devez voir:**
- 4 cartes statistiques (Total CO2, Mensuel, Arbres, Équipements)
- Graphique en barres (admin) ou donut (front)
- Liste des équipements avec leur CO2

#### Widget CO2 sur équipement
```
Admin: http://localhost:8000/admin/equipements/1
Front: http://localhost:8000/equipements/1
```

**Ce que vous devez voir:**
- Widget vert avec gradient
- CO₂ Total (en kg)
- CO₂ Mensuel (en kg)
- Niveau d'impact (🟢 🟡 🔴)
- Conseil avec calcul d'arbres

---

### 4. 🌞 Tester la météo

```
Admin: http://localhost:8000/admin/maintenances
Front: http://localhost:8000/maintenances
```

**Ce que vous devez voir:**
- Widget jaune/orange en haut de page
- 🌅 Lever du soleil (heure)
- ☀️ Heures optimales
- 🌡️ Température actuelle
- 🌇 Coucher du soleil (heure)
- ⏱️ Durée du jour

---

### 5. 📱 Tester le QR Code

1. Aller sur un équipement
2. Cliquer sur le bouton **"QR Code"** ou **"Générer QR Code"**
3. Une page s'ouvre avec le QR code
4. Tester les boutons:
   - **Télécharger** → Télécharge le QR en PNG
   - **Imprimer** → Ouvre la fenêtre d'impression

**Scanner le QR code avec un téléphone:**
- Le QR contient toutes les infos de l'équipement

---

### 6. 🎤 Tester l'assistant vocal

```
Admin: http://localhost:8000/admin/voice-chat
Front: http://localhost:8000/voice-chat
```

**Ce que vous devez voir:**
- Page de l'assistant vocal
- Interface de chat

---

### 7. 📡 Tester le bot Telegram

#### Configuration
1. Ouvrir `.env`
2. Ajouter vos tokens:
```env
TELEGRAM_BOT_TOKEN=123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11
TELEGRAM_CHAT_ID=123456789
```

#### Lancer le bot
```bash
php bin/console app:telegram:poll
```

**Ce que vous devez voir:**
```
Bot Telegram en écoute...
Appuyez sur Ctrl+C pour arrêter
```

#### Tester
1. Ouvrir Telegram
2. Chercher votre bot
3. Envoyer un message
4. Le bot doit répondre

---

## 🎯 Checklist Rapide

### Backend
- [ ] QR Code installé (`composer show endroid/qr-code`)
- [ ] Telegram Bot installé (`composer show irazasyed/telegram-bot-sdk`)
- [ ] Routes alertes (`php bin/console debug:router | grep alertes`)
- [ ] Services créés (CarboneService, MeteoService, QRCodeService, TelegramBotService, AlertesService)

### Interface Admin
- [ ] Cloche d'alertes visible en haut à droite
- [ ] Badge rouge avec nombre d'alertes
- [ ] Dropdown alertes fonctionne
- [ ] Menu "Outils IA" avec liens Assistant Vocal et Empreinte Carbone
- [ ] Widget CO2 sur page équipement
- [ ] Widget météo sur page maintenances
- [ ] Bouton QR Code sur page équipement

### Interface Front
- [ ] Menu "Outils IA" dans dropdown
- [ ] Widget CO2 sur page équipement
- [ ] Widget météo sur page maintenances
- [ ] Bouton "Générer QR Code" sur page équipement

### Fonctionnalités
- [ ] Dashboard CO2 affiche les données
- [ ] Graphiques CO2 s'affichent
- [ ] Widget météo affiche les heures
- [ ] QR Code se génère
- [ ] QR Code se télécharge
- [ ] Alertes se chargent
- [ ] Clic sur alerte redirige
- [ ] Bot Telegram répond

---

## 🐛 Dépannage

### Problème: Cloche d'alertes ne s'affiche pas
**Solution:**
1. Vider le cache: `php bin/console cache:clear`
2. Recharger la page (Ctrl+F5)
3. Vérifier la console navigateur (F12)

### Problème: Erreur "Class Builder not found"
**Solution:**
```bash
composer require endroid/qr-code
php bin/console cache:clear
```

### Problème: Widget CO2 ne s'affiche pas
**Solution:**
1. Vérifier que l'équipement a une date d'achat
2. Vérifier que le service CarboneService existe
3. Vérifier la console navigateur (F12)

### Problème: Widget météo ne s'affiche pas
**Solution:**
1. Vérifier la connexion internet (APIs externes)
2. Vérifier que le service MeteoService existe
3. Attendre quelques secondes (appel API)

### Problème: Bot Telegram ne répond pas
**Solution:**
1. Vérifier le token dans `.env`
2. Vérifier que le bot est démarré (`php bin/console app:telegram:poll`)
3. Vérifier la connexion internet

### Problème: Alertes ne se chargent pas
**Solution:**
1. Ouvrir la console navigateur (F12)
2. Vérifier l'erreur
3. Vérifier que la route `/admin/alertes/notifications` existe
4. Vider le cache: `php bin/console cache:clear`

---

## 📞 Support

### Logs
```bash
# Voir les logs Symfony
tail -f var/log/dev.log

# Vider le cache
php bin/console cache:clear
```

### Console navigateur
- Appuyer sur **F12**
- Onglet **Console** pour voir les erreurs JavaScript
- Onglet **Network** pour voir les requêtes AJAX

---

## ✅ Tout fonctionne?

Si toutes les cases sont cochées, **félicitations!** 🎉

Votre projet FarmVision est maintenant complet avec:
- ✅ Système d'alertes en temps réel
- ✅ Empreinte carbone
- ✅ Météo et lever/coucher du soleil
- ✅ QR Code
- ✅ Assistant vocal
- ✅ Bot Telegram

**Le projet est prêt pour la production! 🚀**
