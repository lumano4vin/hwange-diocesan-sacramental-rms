<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Change Password - Self Service
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Secure the endpoint
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Basic Validation
    if ($new_password !== $confirm_password) {
        header("Location: ../dashboard/settings.php?error=Passwords do not match");
        exit;
    }

    if (strlen($new_password) < 8) {
        header("Location: ../dashboard/settings.php?error=Password must be at least 8 characters");
        exit;
    }

    try {
        // 2. Verify current password
        $user = db_fetch("SELECT password_hash FROM users WHERE user_id = ?", [$user_id]);
        
        if (!password_verify($current_password, $user['password_hash'])) {
            header("Location: ../dashboard/settings.php?error=Incorrect current password");
            exit;
        }

        // 3. Update to new password
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        db_query("UPDATE users SET password_hash = ? WHERE user_id = ?", [$new_hash, $user_id]);

        header("Location: ../dashboard/settings.php?success=Password updated successfully");
        exit;

    } catch (Exception $e) {
        die("Security error: " . $e->getMessage());
    }
} else {
    header("Location: ../dashboard/settings.php");
    exit;
}
