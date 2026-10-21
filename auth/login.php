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

    // Resilient lookup supporting dots/underscores, case variations, email formats, and clerical aliases
    $clean_input = strtolower($username);
    $clean_no_domain = preg_replace('/@hwangediocese\.org$/i', '', $clean_input);
    $with_underscore = str_replace('.', '_', $clean_no_domain);
    $with_dot = str_replace('_', '.', $clean_no_domain);

    $aliases = [
        'nicolas_mnkamba' => 'nicolas_nkamba',
        'nicolas.mnkamba' => 'nicolas_nkamba',
        'mnkamba' => 'nicolas_nkamba',
        'nkamba' => 'nicolas_nkamba',
        'tshongokwe' => 'albert_mudenda',
        'albert_mudenda' => 'Tshongokwe',
        'albert.mudenda' => 'Tshongokwe',
        'linus_dube' => 'linos_dube',
        'linus.dube' => 'linos_dube',
        'timothy_peacock' => 'tim_peacock',
        'timothy.peacock' => 'tim_peacock',
    ];

    $candidates = array_unique(array_filter([
        $username,
        $clean_input,
        $clean_no_domain,
        $with_underscore,
        $with_dot,
        $aliases[$clean_input] ?? null,
        $aliases[$clean_no_domain] ?? null,
        $aliases[$with_underscore] ?? null,
        $aliases[$with_dot] ?? null,
    ]));

    $placeholders = implode(',', array_fill(0, count($candidates), '?'));
    $sql = "SELECT * FROM users WHERE 
        LOWER(username) IN ($placeholders) 
        OR LOWER(email) IN ($placeholders) 
        OR LOWER(REPLACE(username, '.', '_')) IN ($placeholders) 
        OR LOWER(REPLACE(username, '_', '.')) IN ($placeholders) 
        LIMIT 1";

    $params = array_map('strtolower', array_merge($candidates, $candidates, $candidates, $candidates));
    $user = db_fetch($sql, $params);

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

        // Update last login (do not trigger a full cloud DB upload just for login timestamp)
        $GLOBALS['rms_skip_cloud_sync'] = true;
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
