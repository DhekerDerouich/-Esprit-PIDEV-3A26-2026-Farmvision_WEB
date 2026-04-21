# 🔍 Vérification Rapide des Fonctionnalités

## ✅ Checklist de vérification

### 1. Navigation (2 minutes)

**Admin:**
- [ ] Connectez-vous en tant qu'admin
- [ ] Vérifiez la sidebar gauche
- [ ] Cherchez la section "🤖 Outils IA"
- [ ] Vous devez voir:
  - 🎤 Assistant Vocal
  - 🌱 Empreinte Carbone

**Front:**
- [ ] Allez sur la page d'accueil front
- [ ] Vérifiez le menu de navigation
- [ ] Cherchez "🤖 Outils IA" (menu déroulant)
- [ ] Vous devez voir:
  - 🎤 Assistant Vocal
  - 🌱 Empreinte Carbone

---

### 2. Équipements avec CO₂ (3 minutes)

**Liste des équipements:**
- [ ] Allez sur `/admin/equipement`
- [ ] Vous devez voir une **carte verte** avec:
  - "🌱 Empreinte Carbone du Parc"
  - Total CO₂ en kg
  - Moyenne CO₂
  - Bouton "Voir détails"

**Détails d'un équipement:**
- [ ] Cliquez sur un équipement
- [ ] Vous devez voir:
  - Bouton "QR Code" (bleu)
  - Section "🌱 Empreinte Carbone" (fond vert)
  - CO₂ Total, Équivalent Arbres, Impact

---

### 3. Maintenances avec Météo (2 minutes)

**Liste des maintenances:**
- [ ] Allez sur `/admin/maintenance`
- [ ] Vous devez voir une **carte jaune** avec:
  - "🌞 Planification - Heures d'ensoleillement"
  - 🌅 Lever du soleil
  - 🌡️ Température
  - 🌇 Coucher du soleil
  - ⏱️ Durée du jour

---

### 4. Dashboard Carbone (2 minutes)

**Admin:**
- [ ] Cliquez sur "🌱 Empreinte Carbone" dans le menu
- [ ] OU allez sur `/admin/carbone`
- [ ] Vous devez voir:
  - 4 cartes statistiques colorées
  - Explication du calcul
  - Tableau détaillé par équipement
  - Recommandations

**Front:**
- [ ] Allez sur `/carbone`
- [ ] Même contenu avec design front

---

### 5. QR Code (2 minutes)

**Génération:**
- [ ] Depuis un équipement, cliquez sur "QR Code"
- [ ] OU allez sur `/admin/equipement/{id}/qr`
- [ ] Vous devez voir:
  - Le QR code généré (image)
  - Informations de l'équipement
  - Boutons "Imprimer" et "Télécharger"

**Test:**
- [ ] Cliquez sur "Télécharger"
- [ ] Le fichier PNG doit se télécharger
- [ ] Scannez le QR code avec votre téléphone
- [ ] Il doit ouvrir la page de l'équipement

---

### 6. Assistant Vocal (1 minute)

**Admin:**
- [ ] Cliquez sur "🎤 Assistant Vocal"
- [ ] OU allez sur `/admin/voice-chat`
- [ ] Vous devez voir:
  - Interface de chat vocal
  - Bouton microphone
  - Zone de messages

**Front:**
- [ ] Allez sur `/voice-chat`
- [ ] Même interface

---

## 🚨 Si quelque chose ne s'affiche pas

### Problème: Menu "Outils IA" absent

**Solution:**
```bash
# Videz le cache Symfony
cd main
php bin/console cache:clear
```

### Problème: Données CO₂ ou météo vides

**Vérification:**
1. Les équipements ont-ils une date d'achat et un âge ?
2. Les services sont-ils bien injectés dans les contrôleurs ?

**Test rapide:**
```bash
# Vérifiez que les services existent
cd main
php bin/console debug:container MeteoService
php bin/console debug:container CarboneService
```

### Problème: QR Code ne se génère pas

**Vérification:**
```bash
# Vérifiez que la bibliothèque est installée
cd main
composer show endroid/qr-code
```

Si absent:
```bash
composer require endroid/qr-code
```

### Problème: Erreur 404 sur les routes

**Solution:**
```bash
# Videz le cache des routes
cd main
php bin/console cache:clear
php bin/console router:match /admin/carbone
```

---

## 📊 Résultat attendu

Si tout fonctionne correctement, vous devez avoir:

✅ **6 nouveaux liens** dans les menus (3 admin + 3 front)
✅ **Données CO₂** affichées sur les pages équipements
✅ **Données météo** affichées sur les pages maintenances
✅ **2 dashboards carbone** fonctionnels (admin + front)
✅ **QR codes** générables et téléchargeables
✅ **2 interfaces** d'assistant vocal (admin + front)

---

## 🎯 Test complet en 5 minutes

1. **Connexion admin** → Vérifiez le menu sidebar
2. **`/admin/equipement`** → Vérifiez la carte CO₂ verte
3. **Cliquez sur un équipement** → Vérifiez CO₂ + bouton QR Code
4. **`/admin/maintenance`** → Vérifiez la carte météo jaune
5. **`/admin/carbone`** → Vérifiez le dashboard complet
6. **`/admin/voice-chat`** → Vérifiez l'interface vocale
7. **Cliquez sur "QR Code"** → Vérifiez la génération

**Si ces 7 étapes fonctionnent, TOUT est OK! ✅**

---

## 💡 Astuce

Pour voir toutes les routes disponibles:
```bash
cd main
php bin/console debug:router | grep -E "(carbone|voice|qr)"
```

Vous devriez voir:
- `admin_carbone_index`
- `admin_voice_chat`
- `admin_equipement_qr`
- `front_carbone_index`
- `front_voice_chat`
- `front_equipement_qr`

---

Date: 21 avril 2026
Temps de vérification: ~15 minutes
