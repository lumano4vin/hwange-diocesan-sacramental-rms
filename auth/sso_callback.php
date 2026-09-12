<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * SSO OAuth2 Callback Handler
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Validate State to prevent CSRF
if (empty($_GET['state']) || empty($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die("Invalid OAuth state parameter. Possible CSRF attack.");
}

$code = $_GET['code'] ?? null;
if (!$code) {
    die("Authorization code not provided.");
}

$tenant_id = getenv('OAUTH_TENANT_ID') ?: 'common';
$client_id = getenv('OAUTH_CLIENT_ID') ?: 'hwange-rms-client-id-mock';
$client_secret = getenv('OAUTH_CLIENT_SECRET') ?: 'mock-secret';
$redirect_uri = getenv('OAUTH_REDIRECT_URI') ?: 'http://localhost:8000/auth/sso_callback.php';

// 1. Exchange Code for Access Token
$token_url = "https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/token";
$post_data = http_build_query([
    'client_id' => $client_id,
    'scope' => 'openid profile email User.Read',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
    'client_secret' => $client_secret
]);

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_status !== 200 || !$response) {
    header("Location: ../index.php?error=sso_failed");
    exit;
}

$token_data = json_decode($response, true);
$access_token = $token_data['access_token'] ?? null;

if (!$access_token) {
    header("Location: ../index.php?error=sso_failed");
    exit;
}

// Fetch user's profile from Microsoft Graph API
$graph_url = "https://graph.microsoft.com/v1.0/me";
$ch = curl_init($graph_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Accept: application/json'
]);
$graph_response = curl_exec($ch);
$graph_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($graph_status !== 200 || !$graph_response) {
    header("Location: ../index.php?error=sso_failed");
    exit;
}

$graph_data = json_decode($graph_response, true);
$sso_email = $graph_data['userPrincipalName'] ?? $graph_data['mail'] ?? null;

if (!$sso_email) {
    header("Location: ../index.php?error=sso_failed");
    exit;
}

// 2. Map SSO Identity to Diocesan Users
$stmt = db_query("SELECT * FROM users WHERE email = ? OR username = ?", [$sso_email, $sso_email]);
$user = $stmt->fetch();

if ($user) {
    // 3. Establish Session (Bypass password, but respect 2FA)
    if (!empty($user['two_factor_enabled'])) {
        $_SESSION['2fa_pending_user_id'] = $user['user_id'];
        $_SESSION['2fa_pending_username'] = $user['username'];
        session_write_close();
        header("Location: 2fa_verify.php");
        exit;
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['parish_id'] = $user['parish_id'];
    $_SESSION['login_success_popup'] = true;

    db_query("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?", [$user['user_id']]);
    
    header("Location: ../dashboard/index.php");
    exit;
} else {
    // Identity not found in Diocese registry
    header("Location: ../index.php?error=unauthorized");
    exit;
}
