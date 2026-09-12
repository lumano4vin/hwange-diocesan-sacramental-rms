<?php
/**
 * Hwange Diocese Records Management System
 * Save RCIA Catechumen
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parish_id = (int)$_POST['parish_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $dob = trim($_POST['dob']) ?: null;
    $sponsor_name = trim($_POST['sponsor_name']);
    $enrollment_date = trim($_POST['enrollment_date']) ?: null;
    $expected_baptism_date = trim($_POST['expected_baptism_date']) ?: null;
    $status = trim($_POST['status']);
    $notes = trim($_POST['notes']);

    if (!$parish_id || !$first_name || !$last_name) {
        $_SESSION['flash_error'] = "Parish, First Name, and Last Name are required.";
        header("Location: ../dashboard/add_catechumen.php");
        exit;
    }

    try {
        db_query(
            "INSERT INTO catechumens (parish_id, first_name, last_name, dob, sponsor_name, enrollment_date, expected_baptism_date, status, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$parish_id, $first_name, $last_name, $dob, $sponsor_name, $enrollment_date, $expected_baptism_date, $status, $notes]
        );

        db_query(
            "INSERT INTO audit_logs (user_id, action_type, table_name, record_id, details) VALUES (?, ?, ?, ?, ?)",
            [$_SESSION['user_id'], 'CREATE', 'catechumens', $pdo->lastInsertId(), "Added catechumen $first_name $last_name"]
        );

        $_SESSION['flash_success'] = "Catechumen added successfully.";
        header("Location: ../dashboard/rcia.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
        header("Location: ../dashboard/add_catechumen.php");
        exit;
    }
}
