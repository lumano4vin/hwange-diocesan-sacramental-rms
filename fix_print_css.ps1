
$files = Get-ChildItem -Path "sacraments\*_certificate.php"
foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    
    $replacement = "outline-offset: -10px !important;`n                padding: 5mm 15mm 2mm 15mm !important;`n            }`n            @page {`n                size: A4 portrait;`n                margin: 0;`n            }`n            body.theme-heirloom .heirloom-only {`n                margin-top: 2px !important;`n                padding-top: 2px !important;`n            }`n            body.theme-heirloom .certificate-title {`n                margin: 2px 0 !important;`n                font-size: 30px !important;`n            }`n            body.theme-heirloom .sacrament-declaration {`n                margin: 2px 0 !important;`n                font-size: 20px !important;`n            }"
    
    if ($content -match "outline-offset: -10px !important;\r?\n\s*}") {
        $newContent = $content -replace "outline-offset: -10px !important;\r?\n\s*}", $replacement
        Set-Content -Path $file.FullName -Value $newContent -NoNewline
        Write-Host "Updated $($file.Name)"
    } else {
        Write-Host "Could not find target block or already updated in $($file.Name)"
    }
}

