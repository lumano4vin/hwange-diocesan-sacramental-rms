<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * AJAX Endpoint: Get Aggregated Daily Ledger Data for Monthly Cashbook Sync
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/diocesan_financial_categories.php';

require_login();

header('Content-Type: application/json');

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

$parish_id = (int)($_SESSION['parish_id'] ?? 0);
if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chancellor') {
    if (!empty($_GET['parish_id'])) {
        $parish_id = (int)$_GET['parish_id'];
    }
}

if ($parish_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'No parish selected.']);
    exit;
}

try {
    $sync_data = sync_daily_ledger_to_monthly_return($parish_id, $year, $month);
    echo json_encode([
        'success' => true,
        'year' => $year,
        'month' => $month,
        'data' => $sync_data
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
