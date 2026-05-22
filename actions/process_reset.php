<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Process Password Reset
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($password) || empty($confirm_password)) {
        redirect("../dashboard/index.php?error=empty_reset");
    }

    if ($password !== $confirm_password) {
        redirect("../reset_password.php?token=$token&error=match");
    }

    // Verify token and expiry one last time
    $user = db_fetch(
        "SELECT user_id FROM users WHERE reset_token = ? AND reset_token_expiry > CURRENT_TIMESTAMP", 
        [$token]
    );

    if ($user) {
        // Hash new password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Update password and clear token
        db_query(
            "UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE user_id = ?",
            [$password_hash, $user['user_id']]
        );

        // Success: Redirect to login with success message
        redirect("../dashboard/index.php?status=password_reset_success");
    } else {
        // Token invalid or expired
        redirect("../forgot_password.php?status=invalid_token");
    }
} else {
    redirect("../dashboard/index.php");
}
?>
