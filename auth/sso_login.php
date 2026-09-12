<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * SSO Authorization Scaffolding (Microsoft Entra ID / Azure AD)
 */
session_start();

$tenant_id = getenv('OAUTH_TENANT_ID') ?: 'common'; // Or specific tenant UUID
$client_id = getenv('OAUTH_CLIENT_ID') ?: 'hwange-rms-client-id-mock';
$redirect_uri = getenv('OAUTH_REDIRECT_URI') ?: 'http://localhost:8000/auth/sso_callback.php';

// Generate a random state for CSRF protection
$_SESSION['oauth_state'] = bin2hex(random_bytes(16));

// Build the authorization URL
$auth_url = "https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/authorize";
$params = [
    'client_id' => $client_id,
    'response_type' => 'code',
    'redirect_uri' => $redirect_uri,
    'response_mode' => 'query',
    'scope' => 'openid profile email User.Read',
    'state' => $_SESSION['oauth_state']
];

$auth_redirect = $auth_url . '?' . http_build_query($params);

// Redirect the user to the Microsoft Login portal
header("Location: " . $auth_redirect);
exit;
