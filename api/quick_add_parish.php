<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Quick Add Parish / Mission API Endpoint (Modal Support)
 */

header('Content-Type: application/json; charset=UTF-8');
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_login();

// Restrict to admins and chancellors
$role = strtolower($_SESSION['role'] ?? '');
if (!in_array($role, ['admin', 'chancellor'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin or Chancellor role required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$parish_name = trim($_POST['parish_name'] ?? '');
$location = trim($_POST['location'] ?? '');
$deanery = trim($_POST['deanery'] ?? 'Hwange');

if (empty($parish_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parish or Mission name is required.']);
    exit();
}

if (empty($location)) {
    $location = $parish_name;
}

try {
    // Check if parish with same name exists
    $existing = db_fetch("SELECT parish_id, parish_name FROM parishes WHERE LOWER(TRIM(parish_name)) = LOWER(?)", [$parish_name]);
    if ($existing) {
        echo json_encode([
            'success' => true,
            'parish_id' => $existing['parish_id'],
            'parish_name' => $existing['parish_name'],
            'message' => 'Parish already exists and has been selected.'
        ]);
        exit();
    }

    db_query("INSERT INTO parishes (parish_name, location, deanery) VALUES (?, ?, ?)", [$parish_name, $location, $deanery]);
    $new_id = (int)$pdo->lastInsertId();

    if (!$new_id) {
        $p = db_fetch("SELECT parish_id FROM parishes WHERE parish_name = ? ORDER BY parish_id DESC LIMIT 1", [$parish_name]);
        $new_id = $p ? (int)$p['parish_id'] : 0;
    }

    // Log in audit trail
    if (function_exists('log_audit')) {
        log_audit($_SESSION['user_id'] ?? 0, 'CREATE', 'parishes', $new_id, "Registered new parish/mission: $parish_name via Quick-Add");
    }

    echo json_encode([
        'success' => true,
        'parish_id' => $new_id,
        'parish_name' => $parish_name,
        'deanery' => $deanery,
        'message' => 'Parish successfully registered.'
    ]);
    exit();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}
