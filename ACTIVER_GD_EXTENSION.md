# 🔧 Activer l'extension GD pour les QR Codes

## Problème
```
Unable to generate image: please check if the GD extension is enabled and configured correctly
```

Cette erreur signifie que l'extension GD de PHP n'est pas activée. Elle est nécessaire pour générer les images QR Code.

---

## ✅ Solution Automatique (Recommandée)

### Étape 1: Exécuter le script PowerShell

```powershell
# Ouvrir PowerShell en tant qu'administrateur
# Clic droit sur PowerShell → "Exécuter en tant qu'administrateur"

# Aller dans le dossier du projet
cd C:\Users\HP\Desktop\fv\main

# Exécuter le script
.\enable-gd.ps1
```

### Étape 2: Redémarrer Apache

1. Ouvrir le **panneau de contrôle XAMPP**
2. Cliquer sur **"Stop"** pour Apache
3. Attendre 2 secondes
4. Cliquer sur **"Start"** pour Apache

### Étape 3: Vérifier

```powershell
php -m | Select-String -Pattern "gd"
```

**Résultat attendu:**
```
gd
```

---

## 🔧 Solution Manuelle

### Étape 1: Ouvrir php.ini

1. Ouvrir l'explorateur de fichiers
2. Aller à: `C:\xampp\php\`
3. Ouvrir le fichier `php.ini` avec **Notepad++** ou **VSCode**

### Étape 2: Chercher l'extension GD

Appuyer sur **Ctrl+F** et chercher:
```
;extension=gd
```

OU

```
;extension=php_gd2.dll
```

### Étape 3: Décommenter la ligne

**Avant:**
```ini
;extension=gd
```

**Après:**
```ini
extension=gd
```

OU

**Avant:**
```ini
;extension=php_gd2.dll
```

**Après:**
```ini
extension=php_gd2.dll
```

### Étape 4: Sauvegarder

- Appuyer sur **Ctrl+S** pour sauvegarder
- Fermer le fichier

### Étape 5: Redémarrer Apache

1. Ouvrir le **panneau de contrôle XAMPP**
2. Cliquer sur **"Stop"** pour Apache
3. Attendre 2 secondes
4. Cliquer sur **"Start"** pour Apache

### Étape 6: Vérifier

```powershell
php -m | Select-String -Pattern "gd"
```

**Résultat attendu:**
```
gd
```

---

## 🎯 Tester les QR Codes

### Test 1: Vérifier l'extension
```powershell
php -m | Select-String -Pattern "gd"
```

### Test 2: Générer un QR Code
1. Aller sur: `http://localhost:8000/admin/equipements/1`
2. Cliquer sur le bouton **"QR Code"**
3. Le QR Code doit s'afficher ✅

### Test 3: Télécharger le QR Code
1. Sur la page QR Code
2. Cliquer sur **"Télécharger"**
3. Le fichier PNG doit se télécharger ✅

---

## 🐛 Dépannage

### Problème: "extension=gd" n'existe pas dans php.ini

**Solution:**
1. Ouvrir `php.ini`
2. Chercher la section `[PHP]`
3. Ajouter cette ligne:
```ini
extension=gd
```
4. Sauvegarder
5. Redémarrer Apache

### Problème: GD toujours pas activé après redémarrage

**Solution 1: Vérifier le bon php.ini**
```powershell
php --ini
```
Assurez-vous de modifier le bon fichier.

**Solution 2: Vérifier les DLL**
1. Aller à: `C:\xampp\php\ext\`
2. Vérifier que `php_gd2.dll` existe
3. Si absent, réinstaller XAMPP

**Solution 3: Utiliser php_gd2.dll**
Dans `php.ini`, essayer:
```ini
extension=php_gd2.dll
```

### Problème: Apache ne redémarre pas

**Solution:**
1. Fermer complètement XAMPP
2. Ouvrir le Gestionnaire des tâches (Ctrl+Shift+Esc)
3. Chercher "httpd.exe" et terminer le processus
4. Relancer XAMPP
5. Démarrer Apache

---

## 📋 Checklist

- [ ] Fichier php.ini trouvé (`C:\xampp\php\php.ini`)
- [ ] Ligne `;extension=gd` décommentée → `extension=gd`
- [ ] Fichier php.ini sauvegardé
- [ ] Apache redémarré
- [ ] Extension GD visible dans `php -m`
- [ ] QR Code se génère sur la page équipement
- [ ] QR Code se télécharge

---

## ✅ Vérification Finale

```powershell
# Vérifier GD
php -m | Select-String -Pattern "gd"

# Vérifier toutes les extensions graphiques
php -m | Select-String -Pattern "gd|imagick"

# Voir la version de GD
php -r "echo gd_info()['GD Version'];"
```

**Résultat attendu:**
```
gd
GD Version => bundled (2.1.0 compatible)
```

---

## 🎉 Succès!

Si vous voyez `gd` dans la liste des extensions, **félicitations!** 🎉

Les QR Codes fonctionnent maintenant:
- ✅ Génération de QR Code
- ✅ Téléchargement de QR Code
- ✅ Impression de QR Code

---

## 📞 Support

Si le problème persiste:

1. **Vérifier la version de PHP:**
```powershell
php -v
```

2. **Vérifier les extensions disponibles:**
```powershell
php -m
```

3. **Vérifier phpinfo:**
```powershell
php -r "phpinfo();" | Select-String -Pattern "gd"
```

4. **Logs Apache:**
- Ouvrir: `C:\xampp\apache\logs\error.log`
- Chercher les erreurs liées à GD

---

**Bonne chance! 🚀**
