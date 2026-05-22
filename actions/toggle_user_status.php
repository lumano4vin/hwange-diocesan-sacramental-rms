<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Toggle User Status (Active/Inactive)
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page (Admin Only)
require_role('admin');

$user_id = $_GET['id'] ?? null;
$current_user_id = $_SESSION['user_id'];

if (!$user_id) {
    redirect('../dashboard/users.php?msg=invalid_user&type=error');
}

// Security: Prevent deactivating yourself
if ($user_id == $current_user_id) {
    redirect('../dashboard/users.php?msg=cannot_deactivate_self&type=error');
}

// Fetch current status
$user = db_fetch("SELECT full_name, status FROM users WHERE user_id = ?", [$user_id]);

if (!$user) {
    redirect('../dashboard/users.php?msg=user_not_found&type=error');
}

$new_status = ($user['status'] === 'active') ? 'inactive' : 'active';
$action_label = ($new_status === 'inactive') ? 'DEACTIVATED' : 'REACTIVATED';

// Update status
db_query("UPDATE users SET status = ? WHERE user_id = ?", [$new_status, $user_id]);

// Log the action
$log_msg = "User " . h($user['full_name']) . " was $action_label by admin " . h($_SESSION['full_name']);
db_query("INSERT INTO audit_logs (user_id, action_type, table_name, record_id, details) VALUES (?, 'UPDATE', 'users', ?, ?)", 
    [$current_user_id, $user_id, $log_msg]);

redirect("../dashboard/users.php?msg=status_updated&type=success");
