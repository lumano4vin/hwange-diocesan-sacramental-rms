<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Update Staff Account - Administrative
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Secure the endpoint (Admin Only)
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = !empty(trim($_POST['email'] ?? '')) ? trim($_POST['email']) : null;
    $role = $_POST['role'];
    $parish_id = !empty($_POST['parish_id']) ? $_POST['parish_id'] : null;
    $status = $_POST['status'] ?? 'active';
    $new_password = $_POST['new_password'];

    try {
        // 1. Basic Update (Always update these fields)
        $sql = "UPDATE users SET full_name = ?, username = ?, email = ?, role = ?, parish_id = ?, status = ? WHERE user_id = ?";
        $params = [$full_name, $username, $email, $role, $parish_id, $status, $user_id];
        db_query($sql, $params);

        // 2. Optional Password Update (If a new password is provided)
        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            db_query("UPDATE users SET password_hash = ? WHERE user_id = ?", [$password_hash, $user_id]);
        }

        // Redirect back with success
        header("Location: ../admin/users_list.php?msg=Staff+updated+successfully");
        exit;

    } catch (Exception $e) {
        die("Error updating staff member: " . $e->getMessage());
    }
} else {
    header("Location: ../admin/users_list.php");
    exit;
}
