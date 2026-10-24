<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Financial Payment Action
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parish_id = isset($_POST['parish_id']) ? (int)$_POST['parish_id'] : 0;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
    $payment_date = trim($_POST['payment_date'] ?? date('Y-m-d'));
    $payment_method = trim($_POST['payment_method'] ?? 'Bank Transfer');
    $reference_number = trim($_POST['reference_number'] ?? '');
    $currency = trim($_POST['currency'] ?? 'USD');
    $notes = trim($_POST['notes'] ?? '');
    $return_to = trim($_POST['return_to'] ?? '');
    $fiscal_year = isset($_POST['fiscal_year']) ? (int)$_POST['fiscal_year'] : (int)date('Y', strtotime($payment_date));

    if ($parish_id <= 0) {
        set_flash("Invalid parish selected.", "error");
        redirect("admin/finance_overview.php");
        exit;
    }

    if ($amount <= 0) {
        set_flash("Payment amount must be greater than zero.", "error");
        if ($return_to === 'details') {
            redirect("admin/parish_finance_details.php?id=$parish_id&year=$fiscal_year");
        } else {
            redirect("admin/finance_overview.php?year=$fiscal_year");
        }
        exit;
    }

    if (empty($payment_date)) {
        $payment_date = date('Y-m-d');
    }

    try {
        $sql = "INSERT INTO financial_payments (parish_id, amount, payment_date, payment_method, reference_number, currency, notes, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        db_query($sql, [
            $parish_id,
            $amount,
            $payment_date,
            $payment_method,
            $reference_number,
            $currency,
            $notes,
            $_SESSION['user_id'] ?? null
        ]);

        $payment_id = db_last_insert_id();

        // Audit Logging
        $parish = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$parish_id]);
        $parish_name = $parish ? $parish['parish_name'] : "Parish #$parish_id";
        
        log_audit(
            $_SESSION['user_id'] ?? 0,
            'CREATE',
            'financial_payments',
            $payment_id,
            "Recorded levy payment of $currency " . number_format($amount, 2) . " for $parish_name (Ref: $reference_number)"
        );

        set_flash("Payment of $currency " . number_format($amount, 2) . " for $parish_name recorded successfully.", "success");

    } catch (Exception $e) {
        set_flash("Database error recording payment: " . $e->getMessage(), "error");
    }

    if ($return_to === 'details') {
        redirect("admin/parish_finance_details.php?id=$parish_id&year=$fiscal_year");
    } else {
        redirect("admin/finance_overview.php?year=$fiscal_year");
    }
    exit;
}

redirect("admin/finance_overview.php");
