<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Delete Parish Outstation / Mass Centre
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $outstation_id = isset($_POST['outstation_id']) ? (int)$_POST['outstation_id'] : 0;
    $return_to = trim($_POST['return_to'] ?? '');

    if ($outstation_id <= 0) {
        set_flash("Invalid outstation specified.", "error");
        redirect($return_to ?: "dashboard/outstations.php");
        exit;
    }

    $outstation = db_fetch("SELECT * FROM parish_outstations WHERE outstation_id = ?", [$outstation_id]);
    if (!$outstation) {
        set_flash("Outstation record not found.", "error");
        redirect($return_to ?: "dashboard/outstations.php");
        exit;
    }

    $user_parish_id = (int)($_SESSION['parish_id'] ?? 0);
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chancellor' && (int)$outstation['parish_id'] !== $user_parish_id) {
        set_flash("Unauthorized to delete outstations for this parish.", "error");
        redirect($return_to ?: "dashboard/outstations.php");
        exit;
    }

    try {
        db_query("DELETE FROM parish_outstations WHERE outstation_id = ?", [$outstation_id]);

        log_audit($_SESSION['user_id'], 'DELETE', 'parish_outstations', $outstation_id, 
            "Deleted outstation: {$outstation['outstation_name']}");

        set_flash("Outstation record deleted successfully.", "success");
    } catch (Exception $e) {
        set_flash("Failed to delete outstation: " . $e->getMessage(), "error");
    }

    redirect($return_to ?: "dashboard/outstations.php");
    exit;
}

redirect("dashboard/outstations.php");
exit;
