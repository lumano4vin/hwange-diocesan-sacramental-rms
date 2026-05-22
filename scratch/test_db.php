<?php
require_once dirname(__DIR__) . '/includes/db.php';
try {
    $pdo = getDB();
    echo "Connected successfully to " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . " database!\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "Number of users in table: " . $count . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
