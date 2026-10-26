<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Delete Daily Ledger Transaction Entry
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entry_id = isset($_POST['entry_id']) ? (int)$_POST['entry_id'] : 0;
    $return_to = trim($_POST['return_to'] ?? '');

    if ($entry_id <= 0) {
        set_flash("Invalid entry specified.", "error");
        redirect($return_to ?: "dashboard/daily_ledger.php");
        exit;
    }

    $entry = db_fetch("SELECT * FROM parish_daily_ledger WHERE entry_id = ?", [$entry_id]);
    if (!$entry) {
        set_flash("Ledger entry not found.", "error");
        redirect($return_to ?: "dashboard/daily_ledger.php");
        exit;
    }

    // Authorization check
    $user_parish_id = (int)($_SESSION['parish_id'] ?? 0);
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chancellor' && (int)$entry['parish_id'] !== $user_parish_id) {
        set_flash("Unauthorized to delete entries for this parish.", "error");
        redirect($return_to ?: "dashboard/daily_ledger.php");
        exit;
    }

    try {
        db_query("DELETE FROM parish_daily_ledger WHERE entry_id = ?", [$entry_id]);

        log_audit($_SESSION['user_id'], 'DELETE', 'parish_daily_ledger', $entry_id, 
            "Deleted daily ledger entry: {$entry['entry_type']} of {$entry['currency']} " . number_format($entry['amount'], 2) . " [{$entry['category_name']}] - {$entry['description']}");

        set_flash("Ledger entry deleted successfully.", "success");
    } catch (Exception $e) {
        set_flash("Failed to delete entry: " . $e->getMessage(), "error");
    }

    redirect($return_to ?: "dashboard/daily_ledger.php");
    exit;
}

redirect("dashboard/daily_ledger.php");
exit;
