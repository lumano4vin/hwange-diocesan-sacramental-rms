<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Session Termination
 */

require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$name = get_user_greeting_name($_SESSION['full_name'] ?? 'Minister');
$encoded_name = urlencode($name);

session_unset();
session_destroy();

redirect('../index.php?msg=logged_out&name=' . $encoded_name);
?>
