<?php
/**
 * Hwange Diocese Records Management System
 * Export Vault Table Data to CSV
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_role('admin');

$table = $_GET['table'] ?? '';
$allowed_tables = ['baptisms', 'marriages', 'confirmations', 'parishioners', 'deaths', 'catechumens'];

if (!in_array($table, $allowed_tables)) {
    die("Invalid table specified.");
}

// Ensure output is clean
ob_clean();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Diocese_' . ucfirst($table) . '_Export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Base query logic
$sql = "";
if ($table === 'baptisms') {
    $sql = "SELECT b.*, p.first_name, p.last_name, p.dob, p.father_name, p.mother_name, pr.parish_name 
            FROM baptisms b 
            JOIN parishioners p ON b.person_id = p.person_id 
            JOIN parishes pr ON b.parish_id = pr.parish_id";
} elseif ($table === 'marriages') {
    $sql = "SELECT m.*, g.first_name as groom_first, g.last_name as groom_last, br.first_name as bride_first, br.last_name as bride_last, pr.parish_name 
            FROM marriages m 
            JOIN parishioners g ON m.groom_person_id = g.person_id 
            JOIN parishioners br ON m.bride_person_id = br.person_id 
            JOIN parishes pr ON m.parish_id = pr.parish_id";
} elseif ($table === 'parishioners') {
    $sql = "SELECT p.*, pr.parish_name FROM parishioners p LEFT JOIN parishes pr ON p.current_parish_id = pr.parish_id";
} else {
    $sql = "SELECT t.*, p.first_name, p.last_name, pr.parish_name 
            FROM $table t 
            JOIN parishioners p ON t.person_id = p.person_id 
            JOIN parishes pr ON t.parish_id = pr.parish_id";
}

$stmt = getDB()->prepare($sql);
$stmt->execute();

$firstRow = true;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Exclude sensitive internal data
    unset($row['verification_hash'], $row['guid']);
    
    if ($firstRow) {
        fputcsv($output, array_keys($row));
        $firstRow = false;
    }
    fputcsv($output, array_values($row));
}

fclose($output);
exit;
