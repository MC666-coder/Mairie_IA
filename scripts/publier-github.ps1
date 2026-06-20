# Publie le projet sur GitHub (à lancer après avoir créé le dépôt vide sur github.com)
param(
    [string]$Username = "marvine999",
    [string]$RepoName = "portail-mairie-ia"
)

Set-Location "$PSScriptRoot\.."

Write-Host "`n=== Vérification sécurité ===" -ForegroundColor Cyan

$secrets = git ls-files | Select-String -Pattern "\.env$|\.log$"
if ($secrets) {
    Write-Host "ERREUR : fichiers sensibles détectés dans Git :" -ForegroundColor Red
    $secrets | ForEach-Object { Write-Host "  - $_" -ForegroundColor Red }
    exit 1
}
Write-Host "OK — .env et .log exclus du dépôt" -ForegroundColor Green

if (-not (git rev-parse HEAD 2>$null)) {
    Write-Host "ERREUR : aucun commit. Lancez d'abord git commit." -ForegroundColor Red
    exit 1
}

$remote = "https://github.com/$Username/$RepoName.git"
Write-Host "`n=== Configuration remote ===" -ForegroundColor Cyan
Write-Host "Remote : $remote"

git remote remove origin 2>$null
git remote add origin $remote
git branch -M main

Write-Host "`n=== Push vers GitHub ===" -ForegroundColor Cyan
Write-Host "Si GitHub demande une connexion, utilise ton compte $Username`n"
git push -u origin main

if ($LASTEXITCODE -eq 0) {
    Write-Host "`nProjet publié : https://github.com/$Username/$RepoName" -ForegroundColor Green
} else {
    Write-Host "`nLe push a échoué. Vérifie que le dépôt existe sur GitHub (sans README)." -ForegroundColor Yellow
    Write-Host "Créer le dépôt : https://github.com/new?name=$RepoName&visibility=public"
}
