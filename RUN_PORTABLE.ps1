# Hwange Diocese RMS - Portable Launch Script
# This script sets the environment, checks for PHP, and starts a local server.

Write-Host "--- Hwange Diocese RMS Portable Launcher ---" -ForegroundColor Cyan

# 0. Cleanup any previous hung instances
Write-Host "Cleaning up previous sessions..." -ForegroundColor Yellow
Stop-Process -Name php -ErrorAction SilentlyContinue

# 1. Fix Session Environment (TEMP/TMP)
$targetTemp = "$env:USERPROFILE\AppData\Local\Temp\HwangeRMS"
if (-not (Test-Path $targetTemp)) {
    New-Item -Path $targetTemp -ItemType Directory -Force | Out-Null
}
$env:TEMP = $targetTemp
$env:TMP = $targetTemp
Write-Host "[OK] Session environment variables configured." -ForegroundColor Green

# 2. Locate PHP
$currentDir = $PSScriptRoot
if (-not $currentDir) { $currentDir = Get-Location }

$localPhp = Join-Path $currentDir "php\php.exe"
Write-Host "Checking for local engine at: $localPhp" -ForegroundColor Gray

if (Test-Path $localPhp) {
    $phpPath = $localPhp
    Write-Host "[OK] Bundled portable PHP engine detected." -ForegroundColor Green
    
    # Verify execution permission
    try {
        $v = & $phpPath -v 2>$null
        if (-not $v) { throw "Execution denied" }
    } catch {
        Write-Host "[WARNING] Bundled PHP found but could not be executed. This usually happens if the folder is still inside a ZIP or if Windows is blocking the file." -ForegroundColor Yellow
        Write-Host "Suggestion: Right-click the folder, go to Properties, and click 'Unblock' if available." -ForegroundColor Cyan
    }
} else {
    Write-Host "Bundled engine not found. Searching system..." -ForegroundColor Gray
    $phpPath = Get-Command php -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source
    if (-not $phpPath) {
        # Check common locations
        $commonPaths = @(
            "C:\xampp\php\php.exe",
            "C:\xamp\php\php.exe",
            "C:\php\php.exe",
            "D:\xampp\php\php.exe"
        )
        foreach ($p in $commonPaths) {
            if (Test-Path $p) {
                $phpPath = $p
                break
            }
        }
    }
}

if (-not $phpPath) {
    Write-Host "[ERROR] PHP Engine not found!" -ForegroundColor Red
    Write-Host "Diagnostics:" -ForegroundColor Yellow
    Write-Host " - Search Location: $localPhp"
    Write-Host " - Script Root: $currentDir"
    Write-Host ""
    Write-Host "IMPORTANT: If you just downloaded this, you MUST 'Extract All' from the Zip folder first." -ForegroundColor White
    Pause
    exit
}

Write-Host "[OK] PHP found at: $phpPath" -ForegroundColor Green

# 3. Start Server
$port = 8000
$hostAddr = "127.0.0.1"
$url = "http://127.0.0.1:$port"

Write-Host "`n--- MISSION READINESS CHECK ---" -ForegroundColor Cyan
Write-Host "1. Port Check: $port" -ForegroundColor Gray
Write-Host "2. Registry Check: database.sqlite" -ForegroundColor Gray

# Open Browser with a slight delay to allow PHP to bind to the port
Start-Job -ScriptBlock {
    param($u)
    Start-Sleep -Seconds 2
    Start-Process $u
} -ArgumentList $url | Out-Null

Write-Host "`nStarting Diocesan Portal at $url ..." -ForegroundColor Green
Write-Host "THE MISSION IS LIVE. Keep this window open while working." -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop the server.`n" -ForegroundColor Gray

# Launch PHP Built-in Server
& $phpPath -S "${hostAddr}:$port" -t .
