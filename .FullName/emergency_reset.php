<?php
require_once 'includes/db.php';
$pdo = getDB();

$pass = "Hwange2026!";
$hash = password_hash($pass, PASSWORD_DEFAULT);

// Verify before updating
if (!password_verify($pass, $hash)) {
    die("FATAL: Hashing failed locally. This environment is unstable.\n");
}

$stmt = $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 1 WHERE username != 'vincent_lumano'");
$stmt->execute([$hash]);
$count = $stmt->rowCount();

echo "SUCCESS: Re-hashed and updated $count users.\n";
echo "New verified hash used: $hash\n";
echo "Testing verification again for peace of mind: " . (password_verify($pass, $hash) ? "OK" : "FAIL") . "\n";
