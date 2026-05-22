<?php
require_once 'includes/db.php';
$pdo = getDB();
$users = $pdo->query("SELECT username, role, full_name FROM users")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users, JSON_PRETTY_PRINT);
