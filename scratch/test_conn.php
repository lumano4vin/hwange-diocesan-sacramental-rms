<?php
$env_file = dirname(__DIR__) . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $value = trim($value, '"\'');
            putenv("$name=$value");
        }
    }
}

$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');

echo "Testing connection with PDO::MYSQL_ATTR_SSL_CA = true...\n";
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = true;
    }
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Success!\n";
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n\n";
}

echo "Testing connection with PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT = false (and no SSL_CA set)...\n";
try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Success!\n";
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n\n";
}

echo "Testing connection with PDO::MYSQL_ATTR_SSL_CA = '' and PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT = false...\n";
try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = '';
    }
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Success!\n";
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n\n";
}

echo "Testing connection with ONLY PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT = false...\n";
try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Success!\n";
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n\n";
}
