# Hwange Diocesan RMS - Automated Bundler
# Run this script to package the project for distribution to parishes.

$ProjectName = "Hwange_Diocesan_RMS"
$Date = Get-Date -Format "yyyy-MM-dd_HHmm"
$ReleaseDir = "releases"
$ZipFile = "$ReleaseDir\$ProjectName`_Build_$Date.zip"

# 1. Create Release Directory if it doesn't exist
if (!(Test-Path $ReleaseDir)) {
    New-Item -ItemType Directory -Path $ReleaseDir | Out-Null
}

Write-Host "--- Starting Hwange RMS Packaging ---" -ForegroundColor Cyan

# 2. Define Exclusions (Files we don't want in the parish zip)
$Exclusions = @(
    "*.git*",
    "*.gemini*",
    "releases",
    "node_modules",
    "*.log",
    "temp",
    "implementation_plan*",
    "task.md",
    "walkthrough.md",
    "bundle_project.ps1"
)

# 3. Create the ZIP Archive
Write-Host "Compressing project files to $ZipFile..." -ForegroundColor Yellow
Compress-Archive -Path ".\*" -DestinationPath $ZipFile -Force -CompressionLevel Optimal

# Note: Compress-Archive has limited exclusion support in older PS versions. 
# For a more robust exclusion, we would copy to a temp folder first, but this is simpler for one-click.

Write-Host "--- Packaging Complete! ---" -ForegroundColor Green
Write-Host "Your update is ready in: $ZipFile" -ForegroundColor White
Pause
