<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Diagnostics: Confirmation Save Test
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

echo "<h2>Confirmation Save Diagnostics</h2>";
echo "<pre>";

// 1. Check existing confirmations
$confirms = db_fetchAll("SELECT c.confirmation_id, c.person_id, p.first_name, p.last_name FROM confirmations c LEFT JOIN parishioners p ON c.person_id = p.person_id");
echo "=== Existing Confirmations (" . count($confirms) . ") ===\n";
foreach ($confirms as $c) {
    echo "  ID #{$c['confirmation_id']} → Person #{$c['person_id']} → Name: {$c['first_name']} {$c['last_name']}\n";
}

echo "\n=== Confirmations Table Columns ===\n";
$cols = db_fetchAll("PRAGMA table_info(confirmations)");
foreach ($cols as $col) {
    echo "  {$col['name']} ({$col['type']})\n";
}

echo "\n=== audit_logs Table Columns ===\n";
$acols = db_fetchAll("PRAGMA table_info(audit_logs)");
foreach ($acols as $col) {
    echo "  {$col['name']} ({$col['type']})\n";
}

echo "\n=== Recent Parishioners ===\n";
$persons = db_fetchAll("SELECT person_id, first_name, last_name, dob FROM parishioners ORDER BY person_id DESC LIMIT 10");
foreach ($persons as $p) {
    echo "  ID #{$p['person_id']} → {$p['first_name']} {$p['last_name']} — DOB: {$p['dob']}\n";
}

echo "</pre>";
?>
