<?php
/**
 * Hwange Diocese RMS - Password Recovery Utility
 * This script manually overrides the password for 'Francis Xavier'
 */

try {
    $db_path = __DIR__ . '/../database.sqlite';
    if (!file_exists($db_path)) {
        die("\n[ERROR] database.sqlite not found! Make sure this script is inside the 'includes' folder.\n");
    }

    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $username = "Francis Xavier";
    $new_password = "PopeFrancis";
    $hash = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    $stmt->execute([$hash, $username]);

    if ($stmt->rowCount() > 0) {
        echo "\n[SUCCESS] Password for '$username' has been reset to: $new_password\n";
        echo "You can now log in to the system.\n";
    } else {
        echo "\n[WARNING] User '$username' was not found in the database.\n";
        echo "Please check if the username is correct in the Staff list.\n";
    }

} catch (Exception $e) {
    echo "\n[SYSTEM ERROR] " . $e->getMessage() . "\n";
}
