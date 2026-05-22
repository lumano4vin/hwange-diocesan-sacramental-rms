<?php
/**
 * Hwange Diocesan RMS - Duplicate Parishioner Checker API
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Only allow authenticated users
if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

$first_name = trim(upper($_GET['first_name'] ?? ''));
$last_name = trim(upper($_GET['last_name'] ?? ''));
$dob = trim($_GET['dob'] ?? '');

if (empty($first_name) || empty($last_name)) {
    echo json_encode(['duplicates' => []]);
    exit;
}

$pdo = getDB();

// Find potential duplicates matching first_name and last_name
$sql = "
    SELECT p.person_id, p.first_name, p.other_names, p.last_name, p.dob, p.gender, pa.parish_name, p.status 
    FROM parishioners p
    LEFT JOIN parishes pa ON p.current_parish_id = pa.parish_id
    WHERE p.first_name = ? AND p.last_name = ?
";
$params = [$first_name, $last_name];

$duplicates = db_fetchAll($sql, $params);

echo json_encode(['duplicates' => $duplicates]);
exit;
