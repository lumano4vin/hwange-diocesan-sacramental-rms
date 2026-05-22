<?php
require_once 'includes/db.php';
$pdo = getDB();

$mission_hash = password_hash("Mission2026!", PASSWORD_DEFAULT);
$priest_hash = password_hash("priest123", PASSWORD_DEFAULT);

$users = $pdo->query("SELECT username, role, full_name, password_hash FROM users")->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as &$user) {
    if (password_verify("Mission2026!", $user['password_hash'])) {
        $user['detected_password'] = "Mission2026!";
    } elseif (password_verify("priest123", $user['password_hash'])) {
        $user['detected_password'] = "priest123";
    } elseif (password_verify("admin123", $user['password_hash'])) {
        $user['detected_password'] = "admin123";
    } elseif (password_verify("PopeFrancis", $user['password_hash'])) {
        $user['detected_password'] = "PopeFrancis";
    } else {
        $user['detected_password'] = "UNKNOWN/CHANGED";
    }
    unset($user['password_hash']);
}

echo json_encode($users, JSON_PRETTY_PRINT);
