$phpPath = ""
if (Test-Path "php\php.exe") {
    $phpPath = "php\php.exe"
} else {
    $commonPaths = @(
        "C:\xampp\php\php.exe",
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

if ($phpPath) {
    Write-Host "Found PHP at $phpPath, executing migration..." -ForegroundColor Green
    & $phpPath migrate_to_mysql.php
} else {
    Write-Host "PHP Engine not found in local or common paths!" -ForegroundColor Red
}
