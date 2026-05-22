<?php
require_once 'includes/db.php';
$pdo = getDB();

$users = $pdo->query("SELECT username, role, full_name, password_hash FROM users")->fetchAll(PDO::FETCH_ASSOC);

$results = [];
foreach ($users as $user) {
    $detected = "UNKNOWN/CHANGED";
    if (password_verify("Mission2026!", $user['password_hash'])) {
        $detected = "Mission2026!";
    } elseif (password_verify("priest123", $user['password_hash'])) {
        $detected = "priest123";
    } elseif (password_verify("admin123", $user['password_hash'])) {
        $detected = "admin123";
    } elseif (password_verify("deacon123", $user['password_hash'])) {
        $detected = "deacon123";
    } elseif (password_verify("sec123", $user['password_hash'])) {
        $detected = "sec123";
    } elseif (password_verify("PopeFrancis", $user['password_hash'])) {
        $detected = "PopeFrancis";
    }
    
    $results[] = [
        'u' => $user['username'],
        'n' => $user['full_name'],
        'r' => $user['role'],
        'p' => $detected
    ];
}

echo json_encode($results);
