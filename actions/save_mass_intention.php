<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Canonical Mass Intention & Stipend (Canons 945-958)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intention_id = isset($_POST['intention_id']) ? (int)$_POST['intention_id'] : 0;
    $donor_name = trim($_POST['donor_name'] ?? '');
    $donor_phone = trim($_POST['donor_phone'] ?? '');
    $intention_text = trim($_POST['intention_text'] ?? '');
    $intention_type = trim($_POST['intention_type'] ?? 'repose');
    $stipend_amount = isset($_POST['stipend_amount']) ? (float)$_POST['stipend_amount'] : 0.0;
    $currency = trim($_POST['currency'] ?? 'USD');
    $payment_method = trim($_POST['payment_method'] ?? 'Cash');
    $requested_date = !empty($_POST['requested_date']) ? trim($_POST['requested_date']) : null;
    $celebrated_date = !empty($_POST['celebrated_date']) ? trim($_POST['celebrated_date']) : null;
    $assigned_priest_id = !empty($_POST['assigned_priest_id']) ? (int)$_POST['assigned_priest_id'] : null;
    $status = trim($_POST['status'] ?? 'pending');
    $notes = trim($_POST['notes'] ?? '');
    $return_to = trim($_POST['return_to'] ?? '');

    // Determine Parish ID
    $parish_id = (int)($_SESSION['parish_id'] ?? 0);
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chancellor') {
        if (!empty($_POST['parish_id'])) {
            $parish_id = (int)$_POST['parish_id'];
        }
    }

    if ($parish_id <= 0) {
        set_flash("Invalid parish assignment.", "error");
        redirect($return_to ?: "dashboard/mass_intentions.php");
        exit;
    }

    if (empty($donor_name) || empty($intention_text)) {
        set_flash("Donor name and Intention wording are required.", "error");
        redirect($return_to ?: "dashboard/mass_intentions.php");
        exit;
    }

    if ($status === 'celebrated' && empty($celebrated_date)) {
        $celebrated_date = date('Y-m-d');
    }

    try {
        if ($intention_id > 0) {
            // Update Existing Intention
            $existing = db_fetch("SELECT * FROM mass_intentions WHERE intention_id = ?", [$intention_id]);
            if (!$existing) {
                set_flash("Mass intention not found.", "error");
                redirect($return_to ?: "dashboard/mass_intentions.php");
                exit;
            }

            if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chancellor' && (int)$existing['parish_id'] !== $parish_id) {
                set_flash("Unauthorized to edit intentions for this parish.", "error");
                redirect($return_to ?: "dashboard/mass_intentions.php");
                exit;
            }

            $sql = "UPDATE mass_intentions SET 
                        donor_name = ?, donor_phone = ?, intention_text = ?, intention_type = ?,
                        stipend_amount = ?, currency = ?, payment_method = ?, requested_date = ?,
                        celebrated_date = ?, assigned_priest_id = ?, status = ?, notes = ?
                    WHERE intention_id = ?";
            db_query($sql, [
                $donor_name, $donor_phone, $intention_text, $intention_type,
                $stipend_amount, $currency, $payment_method, $requested_date,
                $celebrated_date, $assigned_priest_id, $status, $notes, $intention_id
            ]);

            log_audit($_SESSION['user_id'], 'UPDATE', 'mass_intentions', $intention_id, 
                "Updated Mass intention for $donor_name: $intention_text (Status: $status)");

            set_flash("Mass intention updated successfully.", "success");
        } else {
            // Insert New Intention
            $sql = "INSERT INTO mass_intentions 
                    (parish_id, donor_name, donor_phone, intention_text, intention_type, stipend_amount, currency, payment_method, requested_date, celebrated_date, assigned_priest_id, status, notes, recorded_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            db_query($sql, [
                $parish_id, $donor_name, $donor_phone, $intention_text, $intention_type,
                $stipend_amount, $currency, $payment_method, $requested_date,
                $celebrated_date, $assigned_priest_id, $status, $notes, $_SESSION['user_id']
            ]);

            $new_id = db_last_insert_id();

            log_audit($_SESSION['user_id'], 'CREATE', 'mass_intentions', $new_id, 
                "Booked Mass intention from $donor_name: $intention_text (Stipend: $currency " . number_format($stipend_amount, 2) . ")");

            set_flash("Mass intention booked successfully in Parish Register.", "success");
        }

        redirect($return_to ?: "dashboard/mass_intentions.php");
        exit;

    } catch (Exception $e) {
        set_flash("Database error: " . $e->getMessage(), "error");
        redirect($return_to ?: "dashboard/mass_intentions.php");
        exit;
    }
}

redirect("dashboard/mass_intentions.php");
exit;
