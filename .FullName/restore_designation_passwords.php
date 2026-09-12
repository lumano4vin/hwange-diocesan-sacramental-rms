<?php
/**
 * Restore Designation-Specific Passwords
 * Reverts the unified passwords to role-specific ones as requested.
 */

require_once 'includes/db.php';

$designations = [
    'admin' => 'admin123',
    'priest_hwange' => 'priest123',
    'deacon_hwange' => 'deacon123',
    'secretary_hwange' => 'sec123'
];

echo "--- Restoring Designation-Specific Credentials ---\n";

foreach ($designations as $username => $password) {
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if user exists
        $user = db_fetch("SELECT user_id FROM users WHERE username = ?", [$username]);
        
        if ($user) {
            db_query("UPDATE users SET password_hash = ? WHERE username = ?", [$hash, $username]);
            echo "SUCCESS: Updated $username to password: $password\n";
        } else {
            // Re-create with correct role if missing
            $role = ($username === 'admin') ? 'admin' : ($username === 'secretary_hwange' ? 'secretary' : ($username === 'deacon_hwange' ? 'deacon' : 'priest'));
            $name = ($username === 'admin') ? 'Diocesan Administrator' : ($username === 'priest_hwange' ? 'Rev. Fr. Thulani Sibanda' : ($username === 'deacon_hwange' ? 'Rev. Dn. Moses Ncube' : 'Mrs. Maria Moyo'));
            
            db_query("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)", [$username, $hash, $name, $role]);
            echo "SUCCESS: Restored missing account $username with password: $password\n";
        }
    } catch (Exception $e) {
        echo "ERROR for $username: " . $e->getMessage() . "\n";
    }
}

echo "--- Restore Complete. Passwords now correspond to designations. ---\n";
?>
