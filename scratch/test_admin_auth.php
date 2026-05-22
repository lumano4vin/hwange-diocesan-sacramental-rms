<?php
require_once 'includes/db.php';
$pdo = getDB();
$u = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
$u->execute();
$user = $u->fetch(PDO::FETCH_ASSOC);

$pass = "Hwange2026!";
echo "Testing admin login with Hwange2026!\n";
if ($user && password_verify($pass, $user['password_hash'])) {
    echo "ADMIN VERIFIED\n";
} else {
    echo "ADMIN FAILED\n";
    echo "Current Admin Hash: " . ($user ? $user['password_hash'] : "NULL") . "\n";
}
