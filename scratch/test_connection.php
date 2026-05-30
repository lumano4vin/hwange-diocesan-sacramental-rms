<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$env_file = __DIR__ . '/../.env';
if (!file_exists($env_file)) {
    die(".env file not found at $env_file\n");
}

$lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') !== false) {
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim(trim($value), '"\'');
    }
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$db = $env['DB_NAME'] ?? 'defaultdb';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';

echo "Connecting to mysql:host=$host;port=$port;dbname=$db as $user...\n";

try {
    // Disable SSL verification for testing
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ];
    
    // SSL keys
    $ssl_verify_key = defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') ? PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT : 1014;
    $ssl_ca_key = defined('PDO::MYSQL_ATTR_SSL_CA') ? PDO::MYSQL_ATTR_SSL_CA : 1012;
    $options[$ssl_verify_key] = false;
    $options[$ssl_ca_key] = '';

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass, $options);
    echo "Connected successfully!\n";
} catch (PDOException $e) {
    echo "Connection failed! Error code: " . $e->getCode() . "\n";
    echo "Error message: " . $e->getMessage() . "\n";
}
