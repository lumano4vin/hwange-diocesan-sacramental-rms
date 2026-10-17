<?php
/**
 * Hwange Diocesan RMS - Cloud Vault Diagnostics & Setup Assistant
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$is_vercel = getenv('VERCEL') ? true : false;
$gist_id = getenv('RMS_CLOUD_GIST_ID') ?: '';
$token = getenv('RMS_CLOUD_TOKEN') ?: '';

$message = null;
$message_type = 'info';

// Handle Action: Manual Sync Now
if (($_POST['action'] ?? '') === 'sync_now' || ($_GET['action'] ?? '') === 'sync_now') {
    if (!empty($gist_id) && !empty($token)) {
        $GLOBALS['rms_is_serverless'] = true;
        $synced = rms_sync_db_to_cloud(true);
        if ($synced) {
            $message = "Database successfully compressed and uploaded to Cloud Vault!";
            $message_type = "success";
        } else {
            $message = "Manual sync failed. Please check token permissions or network.";
            $message_type = "danger";
        }
    } else {
        $message = "Cannot sync: RMS_CLOUD_GIST_ID and RMS_CLOUD_TOKEN must be configured first.";
        $message_type = "danger";
    }
}

// Handle Action: 1-Click Vault Initialization via Token
if (($_POST['action'] ?? '') === 'create_vault' && !empty($_POST['init_token'])) {
    $init_token = trim($_POST['init_token']);
    $sqlite_to_upload = $GLOBALS['rms_sqlite_path'] ?? (dirname(__DIR__) . "/database.sqlite");
    if (!file_exists($sqlite_to_upload)) {
        $sqlite_to_upload = dirname(__DIR__) . "/data/fallback.sqlite";
    }
    
    $raw_bytes = file_exists($sqlite_to_upload) ? file_get_contents($sqlite_to_upload) : '';
    if ($raw_bytes && substr($raw_bytes, 0, 15) === 'SQLite format 3') {
        $b64_db = base64_encode(gzencode($raw_bytes, 6));
        $gist_payload = json_encode([
            'description' => 'Hwange Diocesan RMS Cloud Database Vault (Created ' . date('Y-m-d H:i:s') . ')',
            'public' => false,
            'files' => [
                'hwange_rms_db.gz.b64' => [
                    'content' => $b64_db
                ]
            ]
        ]);
        
        $auth_hdr = (strpos($init_token, 'ghp_') === 0 || strpos($init_token, 'github_pat_') === 0) ? 'Authorization: Bearer ' . $init_token : 'Authorization: token ' . $init_token;
        $create_headers = [
            $auth_hdr,
            'Accept: application/vnd.github+json',
            'Content-Type: application/json',
            'User-Agent: HwangeRMS'
        ];
        
        $create_res = rms_cloud_http('POST', 'https://api.github.com/gists', $create_headers, $gist_payload, 15);
        if ($create_res['status'] === 201) {
            $created_data = @json_decode($create_res['body'], true);
            $new_gist_id = $created_data['id'] ?? '';
            $created_vault_info = [
                'gist_id' => $new_gist_id,
                'token' => $init_token
            ];
            $message = "Success! New Cloud Vault Gist created on GitHub with ID: " . htmlspecialchars($new_gist_id);
            $message_type = "success";
        } else {
            $message = "Failed to create Gist on GitHub (HTTP " . $create_res['status'] . "). Please ensure your GitHub token has the 'gist' permission.";
            $message_type = "danger";
        }
    } else {
        $message = "Error: Local SQLite file could not be read or is invalid.";
        $message_type = "danger";
    }
}

// Check Cloud Connection
$cloud_status = 'NOT_CONFIGURED';
$github_code = 0;
$vault_updated_at = null;
$vault_desc = null;

if (!empty($gist_id) && !empty($token)) {
    $auth_hdr = (strpos($token, 'ghp_') === 0 || strpos($token, 'github_pat_') === 0) ? 'Authorization: Bearer ' . $token : 'Authorization: token ' . $token;
    $test_headers = [
        $auth_hdr,
        'Accept: application/vnd.github+json',
        'User-Agent: HwangeRMS'
    ];
    $res = rms_cloud_http('GET', 'https://api.github.com/gists/' . $gist_id, $test_headers, null, 8);
    $github_code = $res['status'];
    
    if ($github_code === 200) {
        $data = @json_decode($res['body'], true);
        if (isset($data['files']['hwange_rms_db.gz.b64'])) {
            $cloud_status = 'OPERATIONAL';
            $vault_updated_at = $data['updated_at'] ?? 'Unknown';
            $vault_desc = $data['description'] ?? 'Diocesan Cloud Vault';
        } else {
            $cloud_status = 'FILE_MISSING';
        }
    } elseif ($github_code === 401) {
        $cloud_status = 'UNAUTHORIZED';
    } elseif ($github_code === 404) {
        $cloud_status = 'NOT_FOUND';
    } else {
        $cloud_status = 'ERROR';
    }
}

// Plain text format output if requested
if (($_GET['format'] ?? '') === 'text' || ($_GET['format'] ?? '') === 'raw') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== CLOUD VAULT STATUS REPORT ===\n";
    echo "Environment: " . ($is_vercel ? "Vercel Serverless" : "Local/Standard") . "\n";
    echo "Cloud Status: $cloud_status\n";
    echo "RMS_CLOUD_GIST_ID: " . (!empty($gist_id) ? substr($gist_id, 0, 4) . "..." . substr($gist_id, -4) : "NOT SET") . "\n";
    echo "RMS_CLOUD_TOKEN: " . (!empty($token) ? substr($token, 0, 4) . "..." . substr($token, -4) : "NOT SET") . "\n";
    echo "GitHub HTTP Code: $github_code\n";
    if ($vault_updated_at) echo "Last Synced: $vault_updated_at\n";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Vault Diagnostic - Hwange Diocesan RMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <style>
        .diag-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(12px);
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-operational { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.4); }
        .status-missing { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); }
        .status-warning { background: rgba(234, 179, 8, 0.2); color: #facc15; border: 1px solid rgba(234, 179, 8, 0.4); }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 0.95rem;
        }
        .code-box {
            font-family: 'JetBrains Mono', monospace;
            background: rgba(0, 0, 0, 0.5);
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #38bdf8;
        }
        .btn-action {
            display: inline-block;
            background: linear-gradient(135deg, #38bdf8 0%, #2563eb 100%);
            color: white;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-action:hover { opacity: 0.9; }
        .step-box {
            background: rgba(255, 255, 255, 0.03);
            border-left: 3px solid #38bdf8;
            padding: 1.25rem;
            margin: 1rem 0;
            border-radius: 0 10px 10px 0;
        }
    </style>
</head>
<body class="dashboard-body" style="background: #0b0f19; color: #f8fafc; font-family: 'Inter', sans-serif; padding: 2rem 1rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
            <div>
                <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 10px;">
                    🛡️ Cloud Vault Status
                </h1>
                <p style="color: #94a3b8; font-size: 0.9rem; margin: 0;">Cross-deployment persistence system for Hwange Diocesan RMS</p>
            </div>
            <div>
                <a href="../dashboard/index.php" style="color: #38bdf8; text-decoration: none; font-size: 0.9rem; font-weight: 600;">&larr; Return to Dashboard</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 600; <?php echo ($message_type === 'success') ? 'background: rgba(34, 197, 94, 0.2); border: 1px solid rgba(34, 197, 94, 0.4); color: #4ade80;' : 'background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171;'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Overall Status Card -->
        <div class="diag-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; margin: 0;">Persistence Health</h2>
                <div>
                    <?php if ($cloud_status === 'OPERATIONAL'): ?>
                        <span class="status-badge status-operational">● 100% Connected & Active</span>
                    <?php elseif ($cloud_status === 'NOT_CONFIGURED'): ?>
                        <span class="status-badge status-missing">● Action Needed: Missing Keys</span>
                    <?php else: ?>
                        <span class="status-badge status-warning">● Attention Needed (<?php echo htmlspecialchars($cloud_status); ?>)</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-row">
                <span style="color: #94a3b8;">Runtime Platform</span>
                <span><strong><?php echo $is_vercel ? "Vercel Serverless (Stateless)" : "Local / Dedicated Server"; ?></strong></span>
            </div>

            <div class="info-row">
                <span style="color: #94a3b8;">RMS_CLOUD_GIST_ID</span>
                <span>
                    <?php if (!empty($gist_id)): ?>
                        <span class="code-box"><?php echo substr($gist_id, 0, 4) . "..." . substr($gist_id, -4); ?></span>
                    <?php else: ?>
                        <span style="color: #ef4444; font-weight: 700;">NOT SET</span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="info-row">
                <span style="color: #94a3b8;">RMS_CLOUD_TOKEN</span>
                <span>
                    <?php if (!empty($token)): ?>
                        <span class="code-box"><?php echo substr($token, 0, 4) . "..." . substr($token, -4); ?></span>
                    <?php else: ?>
                        <span style="color: #ef4444; font-weight: 700;">NOT SET</span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="info-row">
                <span style="color: #94a3b8;">GitHub API Response</span>
                <span>
                    <?php if ($github_code === 200): ?>
                        <span style="color: #4ade80; font-weight: 700;">200 OK</span>
                    <?php elseif ($github_code === 401): ?>
                        <span style="color: #f87171; font-weight: 700;">401 Unauthorized (Token expired/invalid)</span>
                    <?php elseif ($github_code === 404): ?>
                        <span style="color: #f87171; font-weight: 700;">404 Not Found (Invalid Gist ID or no permission)</span>
                    <?php else: ?>
                        <span style="color: #94a3b8;">HTTP <?php echo $github_code; ?></span>
                    <?php endif; ?>
                </span>
            </div>

            <?php if ($vault_updated_at): ?>
            <div class="info-row">
                <span style="color: #94a3b8;">Last Synced to Cloud</span>
                <span><strong><?php echo htmlspecialchars($vault_updated_at); ?></strong></span>
            </div>
            <?php endif; ?>

            <div class="info-row" style="border-bottom: none; padding-top: 1.5rem;">
                <span style="color: #94a3b8;">Diagnostics Action</span>
                <div>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="sync_now">
                        <button type="submit" class="btn-action" <?php if (empty($gist_id) || empty($token)) echo 'disabled style="opacity: 0.5; cursor: not-allowed;"'; ?>>
                            🔄 Backup Local DB to Cloud Now
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php if (isset($created_vault_info)): ?>
            <!-- Generated Vault Info to copy into Vercel -->
            <div class="diag-card" style="border: 2px solid #22c55e;">
                <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #4ade80; margin-bottom: 1rem;">
                    🎉 Step 2: Copy These to Vercel Settings
                </h2>
                <p style="color: #94a3b8; font-size: 0.95rem;">
                    Your Cloud Vault Gist was created! Now paste these two environment variables into your Vercel Project Settings:
                </p>

                <div style="background: rgba(0,0,0,0.6); padding: 1.25rem; border-radius: 12px; margin: 1rem 0;">
                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 4px;">Variable 1 Name</div>
                        <input type="text" readonly value="RMS_CLOUD_GIST_ID" style="width: 100%; padding: 8px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #38bdf8; font-family: monospace;">
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin: 8px 0 4px 0;">Variable 1 Value</div>
                        <input type="text" readonly value="<?php echo htmlspecialchars($created_vault_info['gist_id']); ?>" style="width: 100%; padding: 8px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #4ade80; font-family: monospace;">
                    </div>

                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 4px;">Variable 2 Name</div>
                        <input type="text" readonly value="RMS_CLOUD_TOKEN" style="width: 100%; padding: 8px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #38bdf8; font-family: monospace;">
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin: 8px 0 4px 0;">Variable 2 Value</div>
                        <input type="text" readonly value="<?php echo htmlspecialchars($created_vault_info['token']); ?>" style="width: 100%; padding: 8px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #4ade80; font-family: monospace;">
                    </div>
                </div>

                <p style="font-size: 0.9rem; color: #cbd5e1;">
                    After pasting both in Vercel: Click <strong>Save</strong> and trigger a <strong>Redeploy</strong>. Your system will then preserve 100% of records across all future redeployments!
                </p>
            </div>
        <?php endif; ?>

        <!-- Setup Instructions Card -->
        <?php if ($cloud_status !== 'OPERATIONAL'): ?>
        <div class="diag-card">
            <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; margin-bottom: 1rem;">
                🚀 How to Solve "Changes Not Saved After Redeployment"
            </h2>
            <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.6;">
                Because Vercel serverless functions have a temporary (ephemeral) file system, SQLite files in <code>/tmp</code> are reset whenever Vercel redeploys. To make your database survive redeployments, connect your <strong>Cloud Vault</strong>:
            </p>

            <!-- Option A: 1-Click Vault Creator -->
            <div style="background: rgba(37, 99, 235, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 12px; padding: 1.5rem; margin: 1.5rem 0;">
                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; color: #60a5fa; margin-top: 0; margin-bottom: 0.5rem;">
                    ⚡ Fast 1-Click Vault Setup
                </h3>
                <p style="font-size: 0.9rem; color: #cbd5e1; margin-bottom: 1rem;">
                    1. Generate a GitHub Token at <a href="https://github.com/settings/tokens" target="_blank" style="color: #38bdf8; text-decoration: underline;">GitHub Tokens</a> (check the <code>gist</code> permission).<br>
                    2. Paste the token below and click <strong>Create Cloud Vault Gist</strong>:
                </p>
                <form method="POST" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <input type="hidden" name="action" value="create_vault">
                    <input type="password" name="init_token" placeholder="Paste GitHub Token (ghp_...)" required style="flex: 1; min-width: 280px; padding: 10px 14px; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: white; font-family: monospace;">
                    <button type="submit" class="btn-action">Create Cloud Vault Gist</button>
                </form>
            </div>

            <div class="step-box">
                <h4 style="margin: 0 0 8px 0; color: #38bdf8;">Manual Setup in 2 Minutes:</h4>
                <ol style="color: #94a3b8; font-size: 0.9rem; padding-left: 20px; line-height: 1.8; margin: 0;">
                    <li>Open <a href="https://gist.github.com" target="_blank" style="color: #38bdf8;">gist.github.com</a>, create a secret gist named <code>hwange_rms_db.gz.b64</code> with any placeholder text.</li>
                    <li>Copy the Gist ID from the URL (the hexadecimal string at the end of the URL).</li>
                    <li>Open your <a href="https://vercel.com" target="_blank" style="color: #38bdf8;">Vercel Project Dashboard</a> &rarr; <strong>Settings</strong> &rarr; <strong>Environment Variables</strong>.</li>
                    <li>Add <code>RMS_CLOUD_GIST_ID</code> = your Gist ID.</li>
                    <li>Add <code>RMS_CLOUD_TOKEN</code> = your GitHub Personal Access Token (with <code>gist</code> scope).</li>
                    <li>Redeploy your project on Vercel.</li>
                </ol>
            </div>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
