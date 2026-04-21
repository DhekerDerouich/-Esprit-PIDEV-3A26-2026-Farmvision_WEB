# Script pour activer l'extension GD dans PHP
# Exécuter en tant qu'administrateur

$phpIniPath = "C:\xampp\php\php.ini"

Write-Host "🔍 Vérification du fichier php.ini..." -ForegroundColor Cyan
if (-not (Test-Path $phpIniPath)) {
    Write-Host "❌ Fichier php.ini non trouvé à: $phpIniPath" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Fichier php.ini trouvé" -ForegroundColor Green

# Lire le contenu
$content = Get-Content $phpIniPath -Raw

# Vérifier si GD est déjà activé
if ($content -match "^extension=gd$" -or $content -match "^extension=php_gd\.dll$" -or $content -match "^extension=php_gd2\.dll$") {
    Write-Host "✅ L'extension GD est déjà activée!" -ForegroundColor Green
    exit 0
}

# Chercher la ligne commentée
if ($content -match ";extension=gd" -or $content -match ";extension=php_gd" -or $content -match ";extension=php_gd2") {
    Write-Host "📝 Activation de l'extension GD..." -ForegroundColor Yellow
    
    # Décommenter la ligne
    $content = $content -replace ";extension=gd", "extension=gd"
    $content = $content -replace ";extension=php_gd\.dll", "extension=php_gd.dll"
    $content = $content -replace ";extension=php_gd2\.dll", "extension=php_gd2.dll"
    
    # Sauvegarder
    Set-Content -Path $phpIniPath -Value $content -NoNewline
    
    Write-Host "✅ Extension GD activée avec succès!" -ForegroundColor Green
    Write-Host "⚠️  Redémarrez Apache pour appliquer les changements" -ForegroundColor Yellow
} else {
    Write-Host "📝 Ajout de l'extension GD..." -ForegroundColor Yellow
    
    # Ajouter la ligne
    $content += "`nextension=gd`n"
    Set-Content -Path $phpIniPath -Value $content -NoNewline
    
    Write-Host "✅ Extension GD ajoutée avec succès!" -ForegroundColor Green
    Write-Host "⚠️  Redémarrez Apache pour appliquer les changements" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "📋 Instructions:" -ForegroundColor Cyan
Write-Host "1. Ouvrez le panneau de contrôle XAMPP" -ForegroundColor White
Write-Host "2. Cliquez sur 'Stop' pour Apache" -ForegroundColor White
Write-Host "3. Cliquez sur 'Start' pour Apache" -ForegroundColor White
Write-Host "4. Vérifiez avec: php -m | Select-String -Pattern 'gd'" -ForegroundColor White
