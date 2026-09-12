<?php
/**
 * Unify Mission Passwords
 * Sets all default staff accounts to 'priest123' as requested.
 */

require_once 'includes/db.php';

$password = 'priest123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$accounts = [
    'admin' => 'Diocesan Administrator',
    'priest_hwange' => 'Rev. Fr. Thulani Sibanda',
    'deacon_hwange' => 'Rev. Dn. Moses Ncube',
    'secretary_hwange' => 'Mrs. Maria Moyo'
];

echo "--- Harmonizing Mission Credentials ---\n";

foreach ($accounts as $username => $name) {
    try {
        // Check if user exists
        $user = db_fetch("SELECT user_id FROM users WHERE username = ?", [$username]);
        
        if ($user) {
            db_query("UPDATE users SET password_hash = ? WHERE username = ?", [$hash, $username]);
            echo "SUCCESS: Updated password for $username ($name)\n";
        } else {
            // Create if missing
            $role = ($username === 'admin') ? 'admin' : ($username === 'secretary_hwange' ? 'secretary' : ($username === 'deacon_hwange' ? 'deacon' : 'priest'));
            db_query("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)", [$username, $hash, $name, $role]);
            echo "SUCCESS: Created missing account $username ($name)\n";
        }
    } catch (Exception $e) {
        echo "ERROR for $username: " . $e->getMessage() . "\n";
    }
}

echo "--- Done. All default accounts now use: $password ---\n";
?>
