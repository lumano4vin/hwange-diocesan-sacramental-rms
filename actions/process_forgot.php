<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Process Forgot Password Request - Clergy Self-Service
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cleric_id = $_POST['cleric_id'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // If submitted with new password, validate
    if (!empty($password)) {
        if (empty($password) || empty($confirm_password)) {
            redirect('../forgot_password.php?status=empty_password');
        }
        if ($password !== $confirm_password) {
            redirect('../forgot_password.php?status=mismatch');
        }
        if (strlen($password) < 8) {
            redirect('../forgot_password.php?status=short_password');
        }
    }

    $user = null;
    if (!empty($cleric_id) && is_numeric($cleric_id)) {
        $user = db_fetch("SELECT user_id, full_name, username FROM users WHERE user_id = ?", [(int)$cleric_id]);
    }

    if (!$user && !empty($username)) {
        $clean_input = strtolower($username);
        $clean_no_domain = preg_replace('/@hwangediocese\.org$/i', '', $clean_input);
        $with_underscore = str_replace(['.', ' ', '-'], '_', $clean_no_domain);
        $with_dot = str_replace(['_', ' ', '-'], '.', $clean_no_domain);

        $aliases = [
            'nicolas_mnkamba' => 'nicolas_nkamba',
            'nicolas.mnkamba' => 'nicolas_nkamba',
            'mnkamba' => 'nicolas_nkamba',
            'nkamba' => 'nicolas_nkamba',
            'fr. nicolas nkamba' => 'nicolas_nkamba',
            'fr nicolas nkamba' => 'nicolas_nkamba',
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
        $sql = "SELECT user_id, full_name, username FROM users WHERE 
            LOWER(username) IN ($placeholders) 
            OR LOWER(email) IN ($placeholders) 
            OR LOWER(full_name) IN ($placeholders)
            OR LOWER(REPLACE(username, '.', '_')) IN ($placeholders) 
            OR LOWER(REPLACE(username, '_', '.')) IN ($placeholders) 
            LIMIT 1";

        $params = array_map('strtolower', array_merge($candidates, $candidates, $candidates, $candidates, $candidates));
        $user = db_fetch($sql, $params);
    }

    if ($user) {
        if (!empty($password)) {
            // Direct reset without admin restriction
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            db_query("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL, must_change_password = 0 WHERE user_id = ?", [$new_hash, $user['user_id']]);
            $GLOBALS['rms_db_dirty'] = true;
            redirect("../index.php?status=password_reset_success");
        } else {
            // Direct link to set new password immediately
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+2 hours'));
            db_query("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?", [$token, $expiry, $user['user_id']]);
            redirect("../reset_password.php?token=$token");
        }
    } else {
        redirect("../forgot_password.php?status=not_found");
    }
} else {
    redirect("../forgot_password.php");
}
