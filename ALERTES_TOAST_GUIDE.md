# 🔔 Guide des Alertes Toast

## Système d'alertes en notifications pop-up

---

## 🎯 Fonctionnement

### Affichage automatique
- ✅ Les alertes **URGENTES** s'affichent automatiquement au chargement de la page
- ✅ Maximum 4 alertes affichées en même temps
- ✅ Apparition décalée (500ms entre chaque)
- ✅ Position: En haut à droite de l'écran
- ✅ Auto-fermeture après 10 secondes

### Types d'alertes toast
1. **URGENT** (Rouge) - Maintenances imminentes, équipements en panne
2. **WARNING** (Orange) - Maintenances en retard, garanties expirées
3. **INFO** (Vert) - Informations générales

---

## 🎨 Design des Toasts

### Structure
```
┌─────────────────────────────────────┐
│ ⚠️  🔧 Maintenance imminente      × │
│                                     │
│ azouz - Maintenance Préventive      │
│ dans 1 jours                        │
└─────────────────────────────────────┘
```

### Couleurs
- **Rouge (#ef4444)**: Alertes urgentes
- **Orange (#f59e0b)**: Avertissements
- **Vert (#10b981)**: Informations

### Animations
- **Entrée**: Slide depuis la droite (0.3s)
- **Sortie**: Slide vers la droite (0.3s)
- **Décalage**: 500ms entre chaque toast

---

## 🔔 Cloche de notification

### Fonctionnalités
- ✅ Badge rouge avec nombre d'alertes non lues
- ✅ Clic pour ouvrir le dropdown
- ✅ Liste complète de toutes les alertes
- ✅ Marquer comme lu
- ✅ Bouton "Tout marquer lu"

---

## 💡 Comportement

### Au chargement de la page
1. Chargement des alertes via API
2. Affichage du badge si alertes non lues
3. Affichage automatique des toasts URGENTS
4. Décalage de 500ms entre chaque toast

### Interaction utilisateur
- **Clic sur toast**: Marque comme lu + Redirige vers la page
- **Clic sur X**: Ferme le toast
- **Clic sur cloche**: Ouvre le dropdown
- **Clic sur alerte dans dropdown**: Marque comme lu + Redirige

### Auto-refresh
- Rechargement automatique toutes les 30 secondes
- Mise à jour silencieuse du badge
- Pas de nouveaux toasts lors du refresh (seulement au chargement initial)

---

## 📊 Exemples d'alertes

### 1. Maintenance imminente (URGENT - Rouge)
```
⚠️ 🔧 Maintenance imminente
azouz - Maintenance Préventive dans 1 jours
```

### 2. Maintenance en retard (URGENT - Rouge)
```
⚠️ ⏰ Maintenance en retard
Tracteur Massey Ferguson - Maintenance en retard de 57 jours
```

### 3. Équipement en panne (URGENT - Rouge)
```
⚠️ 🔴 Équipement en panne
Moissonneuse John Deere est actuellement en panne
```

### 4. Garantie expirée (WARNING - Orange)
```
⚠️ 🛡️ Garantie expirée
Pulvérisateur Amazone - Garantie expirée depuis 18 mois
```

---

## 🔧 Configuration

### Modifier le nombre de toasts affichés
Dans `admin/base.html.twig`, ligne ~280:
```javascript
urgentesNonLues.slice(0, 4).forEach((alerte, index) => {
    // Changer 4 par le nombre souhaité
```

### Modifier le délai d'auto-fermeture
Dans `admin/base.html.twig`, ligne ~310:
```javascript
setTimeout(() => {
    if (toast.parentElement) {
        closeToast(toast.querySelector('.alert-toast-close'));
    }
}, 10000); // 10000 = 10 secondes
```

### Modifier le décalage entre toasts
Dans `admin/base.html.twig`, ligne ~280:
```javascript
setTimeout(() => {
    showToast(alerte);
}, index * 500); // 500 = 500ms
```

### Afficher aussi les WARNING en toast
Dans `admin/base.html.twig`, ligne ~275:
```javascript
// Avant
const urgentesNonLues = data.alertes.filter(a => a.type === 'URGENT' && !a.read);

// Après (inclure WARNING)
const urgentesNonLues = data.alertes.filter(a => 
    (a.type === 'URGENT' || a.type === 'WARNING') && !a.read
);
```

---

## 🎯 Cas d'usage

### Scénario 1: Maintenance imminente
1. Maintenance planifiée dans 1 jour
2. Au chargement de la page admin
3. Toast rouge s'affiche automatiquement
4. Utilisateur clique sur le toast
5. Redirigé vers la page de modification de la maintenance

### Scénario 2: Équipement en panne
1. Équipement passe en état "En panne"
2. Au prochain chargement de page
3. Toast rouge urgent s'affiche
4. Message: "Intervention requise immédiatement"
5. Clic redirige vers création de maintenance

### Scénario 3: Plusieurs alertes
1. 4 alertes urgentes non lues
2. Au chargement de la page
3. Les 4 toasts s'affichent avec décalage
4. Premier toast à t=0
5. Deuxième toast à t=500ms
6. Troisième toast à t=1000ms
7. Quatrième toast à t=1500ms

---

## 📱 Responsive

### Desktop
- Position: Haut droite
- Largeur: 350px minimum
- Empilés verticalement

### Mobile (< 768px)
- Position: Haut centre
- Largeur: 90% de l'écran
- Empilés verticalement

---

## ✅ Checklist

### Vérifications
- [ ] Toasts s'affichent au chargement
- [ ] Badge rouge visible si alertes
- [ ] Clic sur toast marque comme lu
- [ ] Clic sur toast redirige
- [ ] Bouton X ferme le toast
- [ ] Auto-fermeture après 10s
- [ ] Décalage entre toasts visible
- [ ] Dropdown fonctionne
- [ ] "Tout marquer lu" fonctionne

---

## 🐛 Dépannage

### Toasts ne s'affichent pas
1. Ouvrir console navigateur (F12)
2. Vérifier erreurs JavaScript
3. Vérifier que `/admin/alertes/notifications` retourne des données
4. Vérifier qu'il y a des alertes URGENTES non lues

### Badge ne s'affiche pas
1. Vérifier qu'il y a des alertes non lues
2. Vider le cache navigateur (Ctrl+F5)
3. Vérifier la console pour erreurs

### Toasts ne se ferment pas
1. Vérifier que le bouton X est cliquable
2. Vérifier la console pour erreurs JavaScript
3. Attendre 10 secondes pour auto-fermeture

---

## 🎉 Résultat

Les alertes s'affichent maintenant comme dans votre screenshot:
- ✅ Notifications toast en haut à droite
- ✅ Fond rouge pour alertes urgentes
- ✅ Icône d'avertissement ⚠️
- ✅ Titre et message clairs
- ✅ Bouton X pour fermer
- ✅ Empilées verticalement
- ✅ Auto-fermeture après 10s

**Le système d'alertes est maintenant complet! 🎉**
