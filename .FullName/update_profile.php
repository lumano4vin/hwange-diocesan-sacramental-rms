<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Update User Profile - Self Service
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Secure the endpoint
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $current_password = $_POST['current_password'];

    // 1. Basic Validation
    if (empty($new_username)) {
        header("Location: ../dashboard/settings.php?error=Username cannot be empty");
        exit;
    }

    try {
        // 2. Verify current password (Primary Security Caution)
        $user = db_fetch("SELECT username, password_hash FROM users WHERE user_id = ?", [$user_id]);
        
        if (!password_verify($current_password, $user['password_hash'])) {
            header("Location: ../dashboard/settings.php?error=Security Alert: Incorrect current password. Change unauthorized.");
            exit;
        }

        // 3. Check for Username Uniqueness (if changed)
        if ($new_username !== $user['username']) {
            $existing = db_fetch("SELECT user_id FROM users WHERE username = ? AND user_id != ?", [$new_username, $user_id]);
            if ($existing) {
                header("Location: ../dashboard/settings.php?error=Username '" . h($new_username) . "' is already taken.");
                exit;
            }
        }

        // 4. Perform Update
        db_query("UPDATE users SET username = ?, email = ? WHERE user_id = ?", [$new_username, $new_email, $user_id]);

        // 5. Update Session Data
        $_SESSION['username'] = $new_username;

        // 6. Audit Trail (Secondary Security Caution)
        log_audit($user_id, 'UPDATE_PROFILE', 'users', $user_id, "Self-service profile update. Username changed from {$user['username']} to $new_username");

        header("Location: ../dashboard/settings.php?success=Account profile updated successfully. Please use your new username for next login.");
        exit;

    } catch (Exception $e) {
        die("Security error: " . $e->getMessage());
    }
} else {
    header("Location: ../dashboard/settings.php");
    exit;
}
?>
