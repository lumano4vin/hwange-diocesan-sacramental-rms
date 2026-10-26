<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Custom Financial Category Action
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['format']) && $_POST['format'] === 'json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name'] ?? '');
    $category_type = strtolower(trim($_POST['category_type'] ?? ''));
    $is_assessable = !empty($_POST['is_assessable']) ? 1 : 0;
    $return_to = trim($_POST['return_to'] ?? '');

    // Determine Parish ID
    $parish_id = null;
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chancellor') {
        $parish_id = !empty($_POST['parish_id']) ? (int)$_POST['parish_id'] : null; // Admin can create global (null) or parish-specific
    } else {
        $parish_id = (int)($_SESSION['parish_id'] ?? 0);
    }

    if (empty($category_name)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Category name is required.']);
            exit;
        }
        set_flash("Category name is required.", "error");
        redirect($return_to ?: "dashboard/finance_submission.php");
        exit;
    }

    if (!in_array($category_type, ['income', 'expenditure'])) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid category type.']);
            exit;
        }
        set_flash("Category type must be Income or Expenditure.", "error");
        redirect($return_to ?: "dashboard/finance_submission.php");
        exit;
    }

    // Check duplicate
    $existing = db_fetch("
        SELECT category_id FROM parish_financial_custom_categories 
        WHERE category_name = ? AND category_type = ? AND (parish_id = ? OR (parish_id IS NULL AND ? IS NULL)) AND is_active = 1
    ", [$category_name, $category_type, $parish_id, $parish_id]);

    if ($existing) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'This category already exists.']);
            exit;
        }
        set_flash("Category '{$category_name}' already exists.", "warning");
        redirect($return_to ?: "dashboard/finance_submission.php");
        exit;
    }

    try {
        db_query("
            INSERT INTO parish_financial_custom_categories (parish_id, category_type, category_name, is_assessable, is_active, created_by)
            VALUES (?, ?, ?, ?, 1, ?)
        ", [$parish_id, $category_type, $category_name, $is_assessable, $_SESSION['user_id']]);

        $new_id = db_last_insert_id();

        log_audit($_SESSION['user_id'], 'CREATE', 'parish_financial_custom_categories', $new_id, 
            "Created custom financial category '$category_name' ($category_type)" . ($parish_id ? " for parish #$parish_id" : " (Global)"));

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'category' => [
                    'id' => $new_id,
                    'name' => $category_name,
                    'type' => $category_type,
                    'is_assessable' => (bool)$is_assessable,
                    'code' => ($category_type === 'income' ? 'custom_inc_' : 'custom_exp_') . $new_id
                ]
            ]);
            exit;
        }

        set_flash("Custom category '{$category_name}' added successfully.", "success");
        redirect($return_to ?: "dashboard/finance_submission.php");
        exit;

    } catch (Exception $e) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        set_flash("Failed to save category: " . $e->getMessage(), "error");
        redirect($return_to ?: "dashboard/finance_submission.php");
        exit;
    }
}

redirect("dashboard/finance_submission.php");
exit;
