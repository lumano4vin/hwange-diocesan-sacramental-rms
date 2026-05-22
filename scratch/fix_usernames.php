<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$fixes = [
    ['old' => 'Francis Xavier', 'new' => 'stanislaus_lumano'],
    ['old' => 'Holy Family', 'new' => 'asani_nyendwa'],
    ['old' => 'Sacred Heart', 'new' => 'vusumuzi_ndlovu']
];

$pass = "Hwange2026!";
$hash = password_hash($pass, PASSWORD_DEFAULT);

try {
    $pdo = getDB();
    foreach ($fixes as $fix) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, password_hash = ?, must_change_password = 1 WHERE username = ?");
        $stmt->execute([$fix['new'], $hash, $fix['old']]);
        if ($stmt->rowCount() > 0) {
            echo "SUCCESS: Updated '{$fix['old']}' to username '{$fix['new']}' and reset password.\n";
        } else {
            echo "WARNING: '{$fix['old']}' not found or already updated.\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
