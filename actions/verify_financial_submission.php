<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Verify / Update Parish Financial Submission Status
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
    $status = trim($_POST['status'] ?? 'Verified');
    $parish_id = isset($_POST['parish_id']) ? (int)$_POST['parish_id'] : 0;
    $year = isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y');

    $allowed_statuses = ['Draft', 'Submitted', 'Verified', 'Reconciled', 'Disputed'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'Verified';
    }

    if ($submission_id > 0) {
        try {
            db_query("UPDATE parish_financial_submissions 
                      SET status = ?, verified_by = ?, verified_at = CURRENT_TIMESTAMP 
                      WHERE submission_id = ?", 
                      [$status, $_SESSION['user_id'] ?? null, $submission_id]);

            log_audit(
                $_SESSION['user_id'] ?? 0,
                'UPDATE',
                'parish_financial_submissions',
                $submission_id,
                "Updated submission #$submission_id status to $status"
            );

            set_flash("Monthly submission status updated to $status.", "success");
        } catch (Exception $e) {
            set_flash("Error updating status: " . $e->getMessage(), "error");
        }
    }

    if ($parish_id > 0) {
        redirect("admin/parish_finance_details.php?id=$parish_id&year=$year");
    } else {
        redirect("admin/finance_overview.php?year=$year");
    }
    exit;
}

redirect("admin/finance_overview.php");
