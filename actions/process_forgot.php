<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Process Forgot Password Request
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    if (empty($username)) {
        redirect('../forgot_password.php?status=empty');
    }

    // Check if user exists
    $user = db_fetch("SELECT user_id FROM users WHERE username = ?", [$username]);

    if ($user) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save token to database
        db_query(
            "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?",
            [$token, $expiry, $user['user_id']]
        );

        // In a real app, send email here. 
        // For this local/genuine system demo, we redirect back with the token in the URL 
        // so the user can "see" the link that would have been sent.
        redirect("../forgot_password.php?status=token_generated&token=$token");
    } else {
        // User not found
        redirect("../forgot_password.php?status=not_found");
    }
} else {
    redirect("../dashboard/index.php");
}
?>
