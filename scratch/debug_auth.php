<?php
require_once 'includes/db.php';
$pdo = getDB();
$u = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$u->execute(['stanislaus_lumano']);
$user = $u->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "USER NOT FOUND\n";
} else {
    echo "Username: [" . $user['username'] . "] (Length: " . strlen($user['username']) . ")\n";
    $pass = "Hwange2026!";
    if (password_verify($pass, $user['password_hash'])) {
        echo "Password VERIFIED successfully in PHP.\n";
    } else {
        echo "Password VERIFICATION FAILED in PHP.\n";
        // Let's re-verify with the hash I think it should be
        $new_hash = password_hash($pass, PASSWORD_DEFAULT);
        echo "Current Hash: " . $user['password_hash'] . "\n";
        echo "New Calculated Hash (example): " . $new_hash . "\n";
    }
}
