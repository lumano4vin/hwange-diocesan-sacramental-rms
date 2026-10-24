<?php
/**
 * Vercel Serverless Router for PHP
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Security check to prevent directory traversal
if (strpos($path, '..') !== false) {
    http_response_code(403);
    echo "403 Forbidden";
    exit();
}

$base_dir = dirname(__DIR__);
$file = $base_dir . $path;

if ($path === '/Hwange_Diocesan_Sacramental_RMS_Manual.pdf' || $path === '/manual' || $path === '/download_manual.php') {
    chdir($base_dir);
    require $base_dir . '/download_manual.php';
    exit();
}

// 1. Root route: map / or empty to index.php
if ($path === '/' || $path === '') {
    $file = $base_dir . '/index.php';
} elseif (file_exists($file) && is_dir($file)) {
    // 2. Directory route: check for index.php inside directory, or matching .php file (e.g. /sacraments -> sacraments.php)
    if (file_exists(rtrim($file, '/') . '/index.php')) {
        $file = rtrim($file, '/') . '/index.php';
    } elseif (file_exists(rtrim($file, '/') . '.php')) {
        $file = rtrim($file, '/') . '.php';
    }
} elseif (!file_exists($file) && file_exists($file . '.php')) {
    // 3. Extensionless route: /dashboard/settings -> /dashboard/settings.php
    $file = $file . '.php';
}

if (file_exists($file) && is_file($file)) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        // Set working directory to the target file's folder
        chdir(dirname($file));
        require $file;
        exit();
    } else {
        // Stream static files directly
        $mimes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'html' => 'text/html; charset=UTF-8'
        ];
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $content_type = $mimes[$ext] ?? 'application/octet-stream';
        header("Content-Type: $content_type");
        header("Content-Length: " . filesize($file));
        header("Content-Disposition: inline; filename=\"" . basename($file) . "\"");
        header("Cache-Control: public, max-age=86400");
        readfile($file);
        exit();
    }
} else {
    http_response_code(404);
    echo "404 Not Found";
    exit();
}
