# ✅ Liste de Vérification - Fonctionnalités UI

## Comment vérifier que tout fonctionne

### 1. 🌱 Empreinte Carbone

#### Dashboard CO2
- [ ] Aller sur `/admin/carbone` (Admin)
- [ ] Aller sur `/carbone` (Front)
- [ ] Vérifier que les statistiques s'affichent:
  - CO₂ Total
  - CO₂ Mensuel
  - Arbres nécessaires
  - Nombre d'équipements
- [ ] Vérifier que le graphique s'affiche
- [ ] Vérifier que la liste des équipements s'affiche

#### Widget CO2 sur Équipement
- [ ] Aller sur `/admin/equipements/{id}` (Admin)
- [ ] Aller sur `/equipements/{id}` (Front)
- [ ] Vérifier que le widget CO2 s'affiche avec:
  - CO₂ Total (en kg)
  - CO₂ Mensuel (en kg)
  - Niveau d'impact (🟢 Faible / 🟡 Moyen / 🔴 Élevé)
  - Conseil avec calcul d'arbres

---

### 2. 🌞 Météo & Lever/Coucher du Soleil

#### Widget Météo sur Maintenance
- [ ] Aller sur `/admin/maintenances` (Admin)
- [ ] Aller sur `/maintenances` (Front)
- [ ] Vérifier que le widget météo s'affiche en haut avec:
  - 🌅 Lever du soleil (heure)
  - ☀️ Heures optimales (plage horaire)
  - 🌡️ Température (en °C)
  - 🌇 Coucher du soleil (heure)
  - ⏱️ Durée du jour (heures)
- [ ] Vérifier le design avec gradient jaune/orange
- [ ] Vérifier le conseil de planification

---

### 3. 📱 QR Code

#### Page QR Code
- [ ] Aller sur un équipement
- [ ] Cliquer sur le bouton "QR Code" (Admin) ou "Générer QR Code" (Front)
- [ ] Vérifier que la page `/admin/equipements/{id}/qr` ou `/equipements/{id}/qr` s'affiche
- [ ] Vérifier que le QR code s'affiche
- [ ] Vérifier que les informations de l'équipement s'affichent
- [ ] Tester le bouton "Télécharger"
- [ ] Tester le bouton "Imprimer"

#### Bouton QR Code sur Équipement
- [ ] Aller sur `/admin/equipements/{id}` (Admin)
- [ ] Vérifier que le bouton "QR Code" est visible
- [ ] Aller sur `/equipements/{id}` (Front)
- [ ] Vérifier que le bouton "Générer QR Code" est visible dans une carte dédiée

---

### 4. 🎤 Assistant Vocal

#### Navigation
- [ ] Menu Admin → Section "Outils IA" → Vérifier le lien "Assistant Vocal"
- [ ] Menu Front → Dropdown "Outils IA" → Vérifier le lien "Assistant Vocal"

#### Page Assistant Vocal
- [ ] Aller sur `/admin/voice-chat` (Admin)
- [ ] Aller sur `/voice-chat` (Front)
- [ ] Vérifier que la page s'affiche correctement

---

### 5. 📡 Bot Telegram

#### Configuration
- [ ] Vérifier que `.env` contient:
  ```
  TELEGRAM_BOT_TOKEN=your_bot_token_here
  TELEGRAM_CHAT_ID=your_chat_id_here
  ```

#### Commandes CLI
- [ ] Tester: `php bin/console app:telegram:get-chat-id`
- [ ] Tester: `php bin/console app:telegram:poll` (doit fonctionner)
- [ ] Tester: `php bin/console app:telegram:send-alerts`
- [ ] Tester: `php bin/console app:telegram:set-webhook`

---

### 6. 🧭 Navigation

#### Menu Admin
- [ ] Vérifier la section "🤖 Outils IA" dans le menu latéral
- [ ] Vérifier le lien "Assistant Vocal"
- [ ] Vérifier le lien "Empreinte Carbone"

#### Menu Front
- [ ] Vérifier le dropdown "🤖 Outils IA" dans la barre de navigation
- [ ] Vérifier le lien "🎤 Assistant Vocal"
- [ ] Vérifier le lien "🌱 Empreinte Carbone"
- [ ] Vérifier que le dropdown s'ouvre au survol

---

## 🎨 Vérifications Visuelles

### Widget CO2
- [ ] Gradient vert (#d1fae5 → #a7f3d0)
- [ ] Bordure verte 2px (#10b981)
- [ ] 3 colonnes bien alignées
- [ ] Icônes 🟢🟡🔴 selon le niveau
- [ ] Texte lisible et bien formaté

### Widget Météo
- [ ] Gradient jaune (#fef3c7 → #fde68a)
- [ ] Bordure orange 2px (#f59e0b)
- [ ] 5 colonnes bien alignées
- [ ] Icônes 🌅☀️🌡️🌇⏱️ visibles
- [ ] Conseil en bas avec fond semi-transparent

### Bouton QR Code
- [ ] Style cohérent avec le design
- [ ] Icône QR code visible
- [ ] Hover effect fonctionnel

---

## 🔧 Tests Fonctionnels

### Test CO2
1. Créer un équipement
2. Ajouter des maintenances
3. Vérifier que le CO2 est calculé
4. Vérifier que le niveau change selon le CO2

### Test Météo
1. Aller sur la page maintenances
2. Vérifier que les heures sont affichées
3. Vérifier que la température est affichée

### Test QR Code
1. Générer un QR code
2. Scanner avec un téléphone
3. Vérifier que le lien fonctionne

### Test Telegram
1. Configurer le bot
2. Lancer `php bin/console app:telegram:poll`
3. Envoyer un message au bot
4. Vérifier la réponse

---

## ✅ Résultat Attendu

Toutes les cases doivent être cochées ✅

Si une fonctionnalité ne s'affiche pas:
1. Vérifier que le service existe
2. Vérifier que le contrôleur passe les données
3. Vérifier que le template utilise les bonnes variables
4. Vérifier la console du navigateur pour les erreurs JavaScript

---

## 📞 Support

Si vous rencontrez des problèmes:
1. Vérifier les logs Symfony: `var/log/dev.log`
2. Vérifier la console du navigateur (F12)
3. Vérifier que composer install a été exécuté
4. Vérifier que les variables d'environnement sont configurées

---

**Bonne vérification! 🚀**
