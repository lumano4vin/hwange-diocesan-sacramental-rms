<?php
// Load environment variables from .env
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

$host = getenv('DB_HOST') ?: "mysql-3614de40-lumano4vin-1607.f.aivencloud.com";
$port = getenv('DB_PORT') ?: "11162";
$db = getenv('DB_NAME') ?: "defaultdb";
$user = getenv('DB_USER') ?: "avnadmin";
$pass = getenv('DB_PASSWORD') ?: "";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 10,
];

// Enable SSL
$ssl_verify_key = defined('Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') ? Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT : (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') ? PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT : 1014);
$ssl_ca_key = defined('Pdo\Mysql::ATTR_SSL_CA') ? Pdo\Mysql::ATTR_SSL_CA : (defined('PDO::MYSQL_ATTR_SSL_CA') ? PDO::MYSQL_ATTR_SSL_CA : 1012);
$options[$ssl_verify_key] = false;
$options[$ssl_ca_key] = '';

try {
    echo "Connecting to Aiven MySQL Cloud Database...\n";
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "SUCCESS! Connected successfully to the cloud database.\n";
    $stmt = $pdo->query("SELECT 1");
    print_r($stmt->fetch());
} catch (\PDOException $e) {
    echo "FAILURE! Could not connect.\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "Error Message: " . $e->getMessage() . "\n";
}
?>
