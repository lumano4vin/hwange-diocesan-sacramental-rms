<?php
require_once 'includes/db.php';
try {
    $res = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $res->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
