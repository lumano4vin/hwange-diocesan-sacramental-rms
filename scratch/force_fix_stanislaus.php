<?php
require_once 'includes/db.php';
$pdo = getDB();
$pass = "Hwange2026!";
$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'stanislaus_lumano'");
$stmt->execute([$hash]);
echo "Updated hash for stanislaus_lumano to: " . $hash . "\n";
echo "Verifying immediately: " . (password_verify($pass, $hash) ? "OK" : "FAIL") . "\n";
