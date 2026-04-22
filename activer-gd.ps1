# Script pour activer GD
Write-Host "Activation de l'extension GD..." -ForegroundColor Cyan

$phpIni = "C:\xampp\php\php.ini"

# Lire le contenu
$content = Get-Content $phpIni -Raw

# Remplacer
$newContent = $content -replace ";extension=gd", "extension=gd"

# Sauvegarder
Set-Content -Path $phpIni -Value $newContent -NoNewline

Write-Host "Extension GD activee!" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANT: Redemarrez Apache maintenant!" -ForegroundColor Yellow
Write-Host ""
Write-Host "Etapes:" -ForegroundColor Cyan
Write-Host "1. Ouvrez XAMPP Control Panel" -ForegroundColor White
Write-Host "2. Cliquez Stop sur Apache" -ForegroundColor White
Write-Host "3. Cliquez Start sur Apache" -ForegroundColor White
