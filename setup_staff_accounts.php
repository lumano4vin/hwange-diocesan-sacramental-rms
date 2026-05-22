<?php
/**
 * setup_staff_accounts.php
 * Provisions sample accounts for Hwange Diocese RMS.
 */

$db_file = __DIR__ . '/database.sqlite';
if (!file_exists($db_file)) {
    die("Error: database.sqlite not found!\n");
}

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $accounts = [
        [
            'username' => 'priest_hwange',
            'password' => 'priest123',
            'full_name' => 'Rev. Fr. Thulani Sibanda',
            'role' => 'priest',
            'email' => 'tsibanda@hwangediocese.org',
            'parish_id' => 1 // St. Ignatius Cathedral
        ],
        [
            'username' => 'deacon_hwange',
            'password' => 'deacon123',
            'full_name' => 'Rev. Dn. Moses Ncube',
            'role' => 'deacon',
            'email' => 'mncube@hwangediocese.org',
            'parish_id' => 1
        ],
        [
            'username' => 'secretary_hwange',
            'password' => 'sec123',
            'full_name' => 'Mrs. Maria Moyo',
            'role' => 'secretary',
            'email' => 'mmoyo@hwangediocese.org',
            'parish_id' => 1
        ]
    ];

    foreach ($accounts as $acc) {
        $hash = password_hash($acc['password'], PASSWORD_DEFAULT);
        
        // Remove if exists
        $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$acc['username']]);

        // Insert
        $sql = "INSERT INTO users (username, password_hash, full_name, role, email, parish_id) 
                VALUES (:u, :h, :f, :r, :e, :p)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'u' => $acc['username'],
            'h' => $hash,
            'f' => $acc['full_name'],
            'r' => $acc['role'],
            'e' => $acc['email'],
            'p' => $acc['parish_id']
        ]);
        
        echo "Created account for: " . $acc['full_name'] . " (" . $acc['role'] . ")\n";
    }

    echo "--- All accounts provisioned successfully ---\n";

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}
