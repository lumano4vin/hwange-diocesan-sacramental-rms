<?php
/**
 * Hwange Diocese Records Management System
 * Save Sacramental Notation (Canon 535)
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baptism_id = (int)$_POST['baptism_id'];
    $sacrament_type = trim($_POST['sacrament_type']);
    $event_date = trim($_POST['event_date']);
    $parish_name = trim($_POST['parish_name']);
    $details = trim($_POST['details']);

    if (!$baptism_id || !$sacrament_type || !$event_date || !$parish_name) {
        $_SESSION['flash_error'] = "All required fields must be filled.";
        header("Location: ../dashboard/view_baptism.php?id=$baptism_id");
        exit;
    }

    // Verify permission on this record
    $record = db_fetch("SELECT parish_id FROM baptisms WHERE baptism_id = ?", [$baptism_id]);
    if (!$record || !has_record_permission($record, 'edit')) {
        $_SESSION['flash_error'] = "You do not have permission to add notations to this record.";
        header("Location: ../dashboard/view_baptism.php?id=$baptism_id");
        exit;
    }

    try {
        db_query(
            "INSERT INTO sacraments_notations (baptism_id, sacrament_type, event_date, parish_name, details) VALUES (?, ?, ?, ?, ?)",
            [$baptism_id, $sacrament_type, $event_date, $parish_name, $details]
        );

        // Log the action
        db_query(
            "INSERT INTO audit_logs (user_id, action_type, table_name, record_id, details) VALUES (?, ?, ?, ?, ?)",
            [$_SESSION['user_id'], 'CREATE', 'sacraments_notations', $pdo->lastInsertId(), "Added $sacrament_type notation for Baptism ID $baptism_id"]
        );

        $_SESSION['flash_success'] = "Canonical notation added successfully.";
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
    }

    header("Location: ../dashboard/view_baptism.php?id=$baptism_id");
    exit;
}
