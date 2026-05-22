<?php
/**
 * Quick script to reset the admin password hash for the Hwange Diocese RMS.
 */

$db_file = __DIR__ . '/database.sqlite';

if (!file_exists($db_file)) {
    die("Error: database.sqlite not found at $db_file\n");
}

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $username = 'admin';
    $password = 'password123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "UPDATE users SET password_hash = :hash WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['hash' => $hash, 'username' => $username]);

    if ($stmt->rowCount() > 0) {
        echo "Success: Password for '$username' has been reset to '$password'.\n";
        echo "New Hash: $hash\n";
    } else {
        // If user doesn't exist, create it
        $sql = "INSERT INTO users (username, password_hash, full_name, role) VALUES (:username, :hash, 'Diocesan Administrator', 'admin')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username, 'hash' => $hash]);
        echo "Success: Created new admin user '$username' with password '$password'.\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
