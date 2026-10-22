<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * User Manual PDF Streamer
 */

$candidate_paths = [
    __DIR__ . '/Hwange_Diocesan_Sacramental_RMS_Manual.pdf',
    __DIR__ . '/docs/Hwange_Diocesan_Sacramental_RMS_Manual.pdf',
    dirname(__DIR__) . '/Hwange_Diocesan_Sacramental_RMS_Manual.pdf',
    dirname(__DIR__) . '/docs/Hwange_Diocesan_Sacramental_RMS_Manual.pdf',
];

$pdf_file = null;
foreach ($candidate_paths as $p) {
    if (file_exists($p) && is_file($p) && filesize($p) > 0) {
        $pdf_file = $p;
        break;
    }
}

if ($pdf_file) {
    $size = filesize($pdf_file);
    header('Content-Type: application/pdf');
    header('Content-Length: ' . $size);
    header('Content-Disposition: inline; filename="Hwange_Diocesan_Sacramental_RMS_Manual.pdf"');
    header('Cache-Control: public, max-age=86400');
    header('Accept-Ranges: bytes');
    readfile($pdf_file);
    exit();
}

// Fallback to HTML version if PDF binary was stripped in serverless bundling
$html_paths = [
    __DIR__ . '/docs/Hwange_Diocesan_Sacramental_RMS_Manual.html',
    dirname(__DIR__) . '/docs/Hwange_Diocesan_Sacramental_RMS_Manual.html',
];

foreach ($html_paths as $hp) {
    if (file_exists($hp)) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile($hp);
        exit();
    }
}

http_response_code(404);
echo "Manual file not found.";
exit();
