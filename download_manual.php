<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * User Manual PDF Streamer
 */

// 1. Check if the physical PDF file exists on disk
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

// 2. Resilient Fallback: Stream directly from embedded binary PHP data
$embedded_file = __DIR__ . '/includes/manual_pdf_data.php';
if (!file_exists($embedded_file)) {
    $embedded_file = dirname(__DIR__) . '/includes/manual_pdf_data.php';
}

if (file_exists($embedded_file)) {
    require_once $embedded_file;
    if (function_exists('get_embedded_manual_pdf_bytes')) {
        $bytes = get_embedded_manual_pdf_bytes();
        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($bytes));
        header('Content-Disposition: inline; filename="Hwange_Diocesan_Sacramental_RMS_Manual.pdf"');
        header('Cache-Control: public, max-age=86400');
        header('Accept-Ranges: bytes');
        echo $bytes;
        exit();
    }
}

// 3. Fallback to HTML version
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
