<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Daily Ledger Transaction Entry
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/diocesan_financial_categories.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entry_id = isset($_POST['entry_id']) ? (int)$_POST['entry_id'] : 0;
    $entry_date = trim($_POST['entry_date'] ?? date('Y-m-d'));
    $entry_type = strtolower(trim($_POST['entry_type'] ?? 'income'));
    $category_code = trim($_POST['category_code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
    $currency = trim($_POST['currency'] ?? 'USD');
    $payment_method = trim($_POST['payment_method'] ?? 'Cash');
    $account_type = trim($_POST['account_type'] ?? 'cash_at_hand');
    $reference_number = trim($_POST['reference_number'] ?? '');
    $outstation_id = !empty($_POST['outstation_id']) ? (int)$_POST['outstation_id'] : null;
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
        redirect("dashboard/daily_ledger.php");
        exit;
    }

    if ($amount <= 0) {
        set_flash("Transaction amount must be greater than zero.", "error");
        redirect($return_to ?: "dashboard/daily_ledger.php");
        exit;
    }

    if (empty($description)) {
        set_flash("Please provide a description or narration for this transaction.", "error");
        redirect($return_to ?: "dashboard/daily_ledger.php");
        exit;
    }

    if (empty($entry_date)) {
        $entry_date = date('Y-m-d');
    }

    // Resolve human-readable category name
    $category_name = "General";
    $all_cats = get_all_daily_ledger_categories($parish_id);
    $found = false;

    foreach (['income', 'expenditure', 'special'] as $grp) {
        foreach ($all_cats[$grp] as $c) {
            if ($c['code'] === $category_code) {
                $category_name = $c['raw_name'] ?? $c['name'];
                $found = true;
                break 2;
            }
        }
    }

    if (!$found) {
        $category_name = ucwords(str_replace('_', ' ', $category_code));
    }

    // Optional Receipt / Voucher File Upload
    $receipt_path = null;
    if (!empty($_FILES['receipt_file']['name']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        $orig_name = $_FILES['receipt_file']['name'];
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_exts)) {
            $max_size = 12 * 1024 * 1024; // 12MB
            if ($_FILES['receipt_file']['size'] <= $max_size) {
                $upload_dir = dirname(__DIR__) . '/uploads/receipts';
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0777, true);
                }
                $safe_name = 'rcpt_' . $parish_id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                $dest = $upload_dir . '/' . $safe_name;
                if (move_uploaded_file($_FILES['receipt_file']['tmp_name'], $dest)) {
                    $receipt_path = 'uploads/receipts/' . $safe_name;
                }
            }
        }
    }

    try {
        if ($entry_id > 0) {
            // Update Existing Entry
            // Verify permission
            $existing = db_fetch("SELECT * FROM parish_daily_ledger WHERE entry_id = ?", [$entry_id]);
            if (!$existing) {
                set_flash("Ledger entry not found.", "error");
                redirect($return_to ?: "dashboard/daily_ledger.php");
                exit;
            }

            if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chancellor' && (int)$existing['parish_id'] !== $parish_id) {
                set_flash("Unauthorized to edit entries for this parish.", "error");
                redirect($return_to ?: "dashboard/daily_ledger.php");
                exit;
            }

            $final_receipt = $receipt_path !== null ? $receipt_path : ($existing['receipt_path'] ?? null);

            $sql = "UPDATE parish_daily_ledger SET 
                        entry_date = ?, entry_type = ?, category_code = ?, category_name = ?,
                        description = ?, amount = ?, currency = ?, payment_method = ?,
                        account_type = ?, reference_number = ?, receipt_path = ?, outstation_id = ?
                    WHERE entry_id = ?";
            db_query($sql, [
                $entry_date, $entry_type, $category_code, $category_name,
                $description, $amount, $currency, $payment_method,
                $account_type, $reference_number, $final_receipt, $outstation_id, $entry_id
            ]);

            log_audit($_SESSION['user_id'], 'UPDATE', 'parish_daily_ledger', $entry_id, 
                "Updated daily ledger entry: $entry_type of $currency " . number_format($amount, 2) . " [$category_name] - $description");

            set_flash("Ledger entry updated successfully.", "success");
        } else {
            // Insert New Entry
            $sql = "INSERT INTO parish_daily_ledger 
                    (parish_id, entry_date, entry_type, category_code, category_name, description, amount, currency, payment_method, account_type, reference_number, receipt_path, outstation_id, recorded_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            db_query($sql, [
                $parish_id, $entry_date, $entry_type, $category_code, $category_name,
                $description, $amount, $currency, $payment_method,
                $account_type, $reference_number, $receipt_path, $outstation_id, $_SESSION['user_id']
            ]);

            $new_entry_id = db_last_insert_id();

            log_audit($_SESSION['user_id'], 'CREATE', 'parish_daily_ledger', $new_entry_id, 
                "Recorded daily ledger entry: $entry_type of $currency " . number_format($amount, 2) . " [$category_name] - $description");

            set_flash("Ledger transaction recorded successfully.", "success");
        }

        $y = date('Y', strtotime($entry_date));
        $m = (int)date('m', strtotime($entry_date));
        redirect($return_to ?: "dashboard/daily_ledger.php?year=$y&month=$m");
        exit;

    } catch (Exception $e) {
        set_flash("Database error: " . $e->getMessage(), "error");
        redirect($return_to ?: "dashboard/daily_ledger.php");
        exit;
    }
}

redirect("dashboard/daily_ledger.php");
exit;
