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

$parish_cond = "";
$params = ["%$q%", "%$q%", "%$q%"];

// Parish isolation: non-admin clergy only search parishioners within their parish
if (!is_admin() && !empty($_SESSION['parish_id'])) {
    $parish_cond = " AND p.current_parish_id = ? ";
    $params[] = $_SESSION['parish_id'];
}

$sql = "SELECT p.person_id, p.first_name, p.last_name, p.dob, pa.parish_name,
               (SELECT COUNT(*) FROM baptisms b WHERE b.person_id = p.person_id) AS has_baptism
        FROM parishioners p
        LEFT JOIN parishes pa ON p.current_parish_id = pa.parish_id
        WHERE (p.first_name LIKE ? OR p.last_name LIKE ? OR (CONCAT(p.first_name, ' ', p.last_name)) LIKE ?)
        $parish_cond
        ORDER BY p.last_name, p.first_name
        LIMIT 10";

$results = db_fetchAll($sql, $params);

echo json_encode($results);
?>
