<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * AJAX: Live Parishioner Lookup for Smart Autocomplete
 * 
 * Returns matching parishioners (with baptism status) based on partial name input.
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT p.person_id, p.first_name, p.last_name, p.dob,
               (SELECT COUNT(*) FROM baptisms b WHERE b.person_id = p.person_id) AS has_baptism
        FROM parishioners p
        WHERE p.first_name LIKE ? OR p.last_name LIKE ? OR (p.first_name || ' ' || p.last_name) LIKE ?
        ORDER BY p.last_name, p.first_name
        LIMIT 10";

$results = db_fetchAll($sql, ["%$q%", "%$q%", "%$q%"]);

echo json_encode($results);
?>
