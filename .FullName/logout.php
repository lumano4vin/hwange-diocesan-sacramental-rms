<?php
/**
 * Logout Page
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$name = get_user_greeting_name($_SESSION['full_name'] ?? 'User');
$encoded_name = urlencode($name);

session_unset();
session_destroy();

header("Location: index.php?msg=logged_out&name=" . $encoded_name);
exit();
?>
