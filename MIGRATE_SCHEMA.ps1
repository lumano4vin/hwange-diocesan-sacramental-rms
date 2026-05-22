# Hwange Diocese RMS - Schema Normalization Tool
# This script fixes HTTP 500 errors by standardizing database columns.

Write-Host "--- Hwange Diocese RMS Schema Fixer ---" -ForegroundColor Cyan
Write-Host "Running database check..." -ForegroundColor Gray

$localPhp = Join-Path $PSScriptRoot "php\php.exe"
$logicFile = Join-Path $PSScriptRoot "migrate_logic.php"

if (-not (Test-Path $localPhp)) {
    Write-Host "[ERROR] PHP Engine not found in project folder." -ForegroundColor Red
    Pause
    exit
}

# Run the migration
& $localPhp $logicFile

Write-Host ""
Write-Host "Done!" -ForegroundColor Green
Pause
