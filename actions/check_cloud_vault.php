<?php
/**
 * Cloud Vault Diagnostics
 */
require_once '../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== CLOUD VAULT STATUS REPORT ===\n\n";

$is_vercel = getenv('VERCEL') ? 'YES' : 'NO';
echo "Vercel Environment: $is_vercel\n";

$gist_id = getenv('RMS_CLOUD_GIST_ID') ?: '';
$token = getenv('RMS_CLOUD_TOKEN') ?: '';

if (!empty($gist_id)) {
    echo "RMS_CLOUD_GIST_ID: " . substr($gist_id, 0, 4) . "..." . substr($gist_id, -4) . " (Length: " . strlen($gist_id) . ")\n";
} else {
    echo "RMS_CLOUD_GIST_ID: NOT SET (Missing)\n";
}

if (!empty($token)) {
    echo "RMS_CLOUD_TOKEN: " . substr($token, 0, 4) . "..." . substr($token, -4) . " (Length: " . strlen($token) . ")\n";
} else {
    echo "RMS_CLOUD_TOKEN: NOT SET (Missing)\n";
}

if (!empty($gist_id) && !empty($token)) {
    echo "\nTesting connection to GitHub Cloud Vault...\n";
    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/vnd.github+json',
        'User-Agent: HwangeRMS'
    ];
    $res = rms_cloud_http('GET', 'https://api.github.com/gists/' . $gist_id, $headers, null, 8);
    echo "GitHub HTTP Status: " . $res['status'] . "\n";
    if ($res['status'] === 200) {
        $data = @json_decode($res['body'], true);
        if (isset($data['files']['hwange_rms_db.gz.b64'])) {
            echo "Cloud Database File: PRESENT in Vault\n";
            echo "Cloud Vault Description: " . ($data['description'] ?? 'None') . "\n";
            echo "Last Updated: " . ($data['updated_at'] ?? 'Unknown') . "\n";
            echo "\n>>> SUCCESS: Cloud Vault is 100% CONNECTED & OPERATIONAL! <<<\n";
        } else {
            echo "Warning: Gist exists but hwange_rms_db.gz.b64 not found.\n";
        }
    } elseif ($res['status'] === 401) {
        echo "Error: 401 Unauthorized - The GitHub Token is invalid or expired.\n";
    } elseif ($res['status'] === 404) {
        echo "Error: 404 Not Found - The Gist ID does not exist or token has no access.\n";
    } else {
        echo "Error: Unexpected response code: " . $res['status'] . "\n";
    }
} else {
    echo "\n>>> ACTION NEEDED: Please add the missing environment variable(s) in Vercel Settings. <<<\n";
}

$tmp_sqlite = sys_get_temp_dir() . '/hwange_database.sqlite';
if (file_exists($tmp_sqlite)) {
    echo "\nLocal Active SQLite File: " . $tmp_sqlite . " (" . filesize($tmp_sqlite) . " bytes)\n";
}
