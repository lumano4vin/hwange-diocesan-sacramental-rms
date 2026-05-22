<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Mass Verification Hash Generator
 * 
 * This utility generates unique cryptographic fingerprints for all 
 * existing sacramental records to enable secure QR verification.
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the script - Admin only
require_login();
if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized Access.");
}

$salt = "HWANGE_DIOCESE_SECRET_SALT_2024";
$tables = [
    'baptisms' => 'baptism_id',
    'marriages' => 'marriage_id',
    'confirmations' => 'confirmation_id',
    'deaths' => 'death_id',
    'ordinations_professions' => 'ordination_id'
];

$stats = [];

foreach ($tables as $table => $id_col) {
    $records = db_fetchAll("SELECT * FROM $table WHERE verification_hash IS NULL");
    $count = 0;
    
    foreach ($records as $row) {
        // Generate a unique 64-character hash
        $data = $table . $row[$id_col] . ($row['parish_id'] ?? '0') . ($row['created_at'] ?? '2024') . $salt;
        $hash = hash('sha256', $data);
        
        db_query("UPDATE $table SET verification_hash = ? WHERE $id_col = ?", [$hash, $row[$id_col]]);
        $count++;
    }
    
    $stats[$table] = $count;
}

echo "<h1>Verification Hash Generation Complete</h1>";
echo "<ul>";
foreach ($stats as $table => $count) {
    echo "<li><strong>" . ucfirst($table) . ":</strong> $count records updated.</li>";
}
echo "</ul>";
echo "<p><a href='../dashboard/index.php'>Return to Dashboard</a></p>";
?>
