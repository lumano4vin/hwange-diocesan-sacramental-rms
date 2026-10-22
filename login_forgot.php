<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Legacy Forgot Password Endpoint - Redirects to Self-Service Recovery
 */
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: forgot_password.php" . $qs);
exit;
