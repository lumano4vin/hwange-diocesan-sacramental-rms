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

    // Resilient user lookup
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
    $sql = "SELECT user_id FROM users WHERE 
        LOWER(username) IN ($placeholders) 
        OR LOWER(email) IN ($placeholders) 
        OR LOWER(REPLACE(username, '.', '_')) IN ($placeholders) 
        OR LOWER(REPLACE(username, '_', '.')) IN ($placeholders) 
        LIMIT 1";

    $params = array_map('strtolower', array_merge($candidates, $candidates, $candidates, $candidates));
    $user = db_fetch($sql, $params);

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
