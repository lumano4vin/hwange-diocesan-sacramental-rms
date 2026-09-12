<?php
$db_file = __DIR__ . '/database.sqlite';
try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $username = 'vincent_lumano';
    $password = 'Hwange2026!';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $full_name = 'Fr. Vincent Lumano';
    $role = 'admin';
    $parish_id = 22; // St. Augustine

    $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$username]);

    $sql = "INSERT INTO users (username, password_hash, full_name, role, parish_id) 
            VALUES (:u, :h, :f, :r, :p)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'u' => $username,
        'h' => $hash,
        'f' => $full_name,
        'r' => $role,
        'p' => $parish_id
    ]);
    
    echo "Account created successfully for $username in the active database.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
