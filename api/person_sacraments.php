<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * API: Get Person Sacramental Summary
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$person_id = $_GET['person_id'] ?? null;

if (!$person_id) {
    echo json_encode(['error' => 'Missing Person ID']);
    exit;
}

$summary = [
    'baptism' => db_fetch("SELECT date_of_baptism, parish_id FROM baptisms WHERE person_id = ?", [$person_id]),
    'confirmation' => db_fetch("SELECT date_of_confirmation, parish_id FROM confirmations WHERE person_id = ?", [$person_id]),
    'communion' => db_fetch("SELECT date_of_communion FROM first_holy_communions WHERE person_id = ?", [$person_id])
];

// Get parish names for the summary
if ($summary['baptism']) {
    $parish = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$summary['baptism']['parish_id']]);
    $summary['baptism']['parish_name'] = $parish['parish_name'] ?? 'Unknown';
}
if ($summary['confirmation']) {
    $parish = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$summary['confirmation']['parish_id']]);
    $summary['confirmation']['parish_name'] = $parish['parish_name'] ?? 'Unknown';
}

echo json_encode($summary);
