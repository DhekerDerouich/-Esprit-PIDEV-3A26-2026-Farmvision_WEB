# ⚡ Guide Rapide - Activer GD en 3 étapes

## 🎯 Objectif
Activer l'extension GD de PHP pour générer les QR Codes

---

## 📝 Méthode 1: Script Automatique (30 secondes)

### Étape 1: Exécuter le script
```powershell
.\activer-gd-simple.ps1
```

### Étape 2: Redémarrer Apache
1. Ouvrir **XAMPP Control Panel**
2. Cliquer **"Stop"** sur Apache
3. Cliquer **"Start"** sur Apache

### Étape 3: Vérifier
```powershell
php -m | Select-String -Pattern "gd"
```

**✅ Si vous voyez "gd", c'est bon!**

---

## 🔧 Méthode 2: Manuel (2 minutes)

### Étape 1: Ouvrir php.ini
1. Aller à: `C:\xampp\php\`
2. Ouvrir `php.ini` avec Notepad

### Étape 2: Chercher et modifier
Appuyer sur **Ctrl+F** et chercher:
```
;extension=gd
```

Changer en (enlever le point-virgule):
```
extension=gd
```

### Étape 3: Sauvegarder et redémarrer
1. **Ctrl+S** pour sauvegarder
2. Fermer Notepad
3. Redémarrer Apache dans XAMPP

---

## ✅ Test Final

Aller sur:
```
http://localhost:8000/admin/equipements/1
```

Cliquer sur **"QR Code"**

**✅ Le QR Code doit s'afficher!**

---

## 🐛 Problème?

### GD toujours pas activé?

**Vérifier le bon fichier:**
```powershell
php --ini
```

**Essayer avec php_gd2.dll:**
Dans `php.ini`, chercher et décommenter:
```ini
;extension=php_gd2.dll
```

Changer en:
```ini
extension=php_gd2.dll
```

---

## 📞 Besoin d'aide?

Lire le guide complet: `ACTIVER_GD_EXTENSION.md`

---

**C'est tout! 🎉**
