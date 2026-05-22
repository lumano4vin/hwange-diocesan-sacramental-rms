<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Authentication Handler
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        redirect('../index.php?error=empty_fields');
    }

    // Fetch user
    $user = db_fetch("SELECT * FROM users WHERE username = ?", [$username]);

    if ($user && $user['status'] === 'inactive') {
        redirect('../index.php?error=account_disabled');
    }

    if ($user && password_verify($password, $user['password_hash'])) {
        // Check if 2FA is enabled for this user
        if ($user['two_factor_enabled']) {
            // Success: High security mode - Pause login
            $_SESSION['2fa_pending_user_id'] = $user['user_id'];
            $_SESSION['2fa_pending_username'] = $user['username'];
            redirect('2fa_verify.php');
        }

        // Standard Login Success: Initialize session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['parish_id'] = $user['parish_id'];
        $_SESSION['login_success_popup'] = true;

        // Update last login
        db_query("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?", [$user['user_id']]);

        if (strtolower($user['role']) === 'observer') {
            redirect('../dashboard/observer.php');
        } else {
            redirect('../dashboard/index.php');
        }
    } else {
        // Failure
        redirect('../index.php?error=invalid_credentials');
    }
} else {
    redirect('../index.php');
}
?>
