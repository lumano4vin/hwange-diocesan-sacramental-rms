<?php
/**
 * Vercel Serverless Router for PHP
 */

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

// If it's a directory, look for index.php inside it
if (file_exists($file) && is_dir($file)) {
    $file = rtrim($file, '/') . '/index.php';
}

if (file_exists($file) && is_file($file)) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        // Set working directory to the target file's folder
        chdir(dirname($file));
        require $file;
        exit();
    } else {
        // For static files
        return false;
    }
} else {
    // Check if the route is for index.php at root
    if ($path === '/' || $path === '') {
        $index_file = $base_dir . '/index.php';
        if (file_exists($index_file)) {
            chdir($base_dir);
            require $index_file;
            exit();
        }
    }
    
    http_response_code(404);
    echo "404 Not Found";
    exit();
}
