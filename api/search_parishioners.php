<?php
/**
 * Hwange Diocesan RMS - Parishioner Quick Search API
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode([]);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$search = "%{$q}%";
$sql = "
    SELECT p.person_id, p.first_name, p.other_names, p.last_name, p.dob, p.gender, pa.parish_name,
           b.entry_number as baptism_entry
    FROM parishioners p
    LEFT JOIN parishes pa ON p.current_parish_id = pa.parish_id
    LEFT JOIN baptisms b ON p.person_id = b.person_id
    WHERE p.first_name LIKE ? 
       OR p.last_name LIKE ? 
       OR p.other_names LIKE ? 
       OR (p.first_name || ' ' || p.last_name) LIKE ?
       OR b.entry_number LIKE ?
    ORDER BY p.last_name ASC, p.first_name ASC
    LIMIT 15
";

$results = db_fetchAll($sql, [$search, $search, $search, $search, $search]);

$output = [];
foreach ($results as $row) {
    $output[] = [
        'person_id'   => (int)$row['person_id'],
        'first_name'  => $row['first_name'],
        'last_name'   => $row['last_name'],
        'other_names' => $row['other_names'] ?? '',
        'dob'         => $row['dob'] ? date('d M Y', strtotime($row['dob'])) : 'Unknown',
        'parish_name' => $row['parish_name'] ?? 'Diocese of Hwange',
        'gender'      => $row['gender'] ?? ''
    ];
}

echo json_encode($output);
