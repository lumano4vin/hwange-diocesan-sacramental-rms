<?php
require_once 'includes/db.php';
try {
    $res = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
    $row = $res->fetch();
    echo $row['sql'] ?? 'Table not found';
} catch (Exception $e) {
    echo $e->getMessage();
}
