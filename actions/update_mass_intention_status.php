<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * 1-Click Update Mass Intention Status (Canon 948)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intention_id = isset($_POST['intention_id']) ? (int)$_POST['intention_id'] : 0;
    $status = trim($_POST['status'] ?? 'celebrated');
    $celebrated_date = !empty($_POST['celebrated_date']) ? trim($_POST['celebrated_date']) : date('Y-m-d');
    $return_to = trim($_POST['return_to'] ?? '');

    if ($intention_id <= 0) {
        set_flash("Invalid intention specified.", "error");
        redirect($return_to ?: "dashboard/mass_intentions.php");
        exit;
    }

    $intention = db_fetch("SELECT * FROM mass_intentions WHERE intention_id = ?", [$intention_id]);
    if (!$intention) {
        set_flash("Mass intention not found.", "error");
        redirect($return_to ?: "dashboard/mass_intentions.php");
        exit;
    }

    $user_parish_id = (int)($_SESSION['parish_id'] ?? 0);
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chancellor' && (int)$intention['parish_id'] !== $user_parish_id) {
        set_flash("Unauthorized for this parish.", "error");
        redirect($return_to ?: "dashboard/mass_intentions.php");
        exit;
    }

    try {
        $assigned_priest_id = $intention['assigned_priest_id'] ?: $_SESSION['user_id'];
        
        db_query("
            UPDATE mass_intentions 
            SET status = ?, celebrated_date = ?, assigned_priest_id = ?
            WHERE intention_id = ?
        ", [$status, ($status === 'celebrated' ? $celebrated_date : null), $assigned_priest_id, $intention_id]);

        log_audit($_SESSION['user_id'], 'UPDATE', 'mass_intentions', $intention_id, 
            "Marked Mass intention as $status on $celebrated_date: {$intention['intention_text']}");

        set_flash("Intention marked as " . ucfirst($status) . ".", "success");
    } catch (Exception $e) {
        set_flash("Error updating status: " . $e->getMessage(), "error");
    }

    redirect($return_to ?: "dashboard/mass_intentions.php");
    exit;
}

redirect("dashboard/mass_intentions.php");
exit;
