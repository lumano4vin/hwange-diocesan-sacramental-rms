<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Two-Factor Authentication Setup
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$user_id = $_SESSION['user_id'];
$user = db_fetch("SELECT * FROM users WHERE user_id = ?", [$user_id]);

$message = '';
$message_type = '';

// Handle Activation/Deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enable_2fa'])) {
        $code = $_POST['verify_code'] ?? '';
        $secret = $_POST['temp_secret'] ?? '';
        
        if (TOTPHelper::verifyCode($secret, $code)) {
            db_query("UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1 WHERE user_id = ?", [$secret, $user_id]);
            $message = "Two-Factor Authentication has been successfully enabled!";
            $message_type = "success";
            $user['two_factor_enabled'] = 1;
        } else {
            $message = "Invalid verification code. Setup failed.";
            $message_type = "error";
        }
    } elseif (isset($_POST['disable_2fa'])) {
        db_query("UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE user_id = ?", [$user_id]);
        $message = "Two-Factor Authentication has been disabled.";
        $message_type = "success";
        $user['two_factor_enabled'] = 0;
    }
}

// Generate a temporary secret if not enabled
$temp_secret = $user['two_factor_enabled'] ? $user['two_factor_secret'] : TOTPHelper::generateSecret();
$qr_url = TOTPHelper::getQRUrl($user['username'], $temp_secret);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Settings - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.3">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode_js@1.0.0/qrcode.min.js"></script>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Header (Unified) -->
            <?php 
                $header_title = "Security Settings";
                $header_subtitle = "Manage your account security and authentication methods.";
                include '../includes/header.php'; 
            ?>

            <div class="card bg-card" style="max-width: 800px; margin-top: 2rem;">
                <div class="card-header">
                    <h3><ion-icon name="shield-lock-outline" style="color: var(--accent); vertical-align: middle; margin-right: 10px;"></ion-icon> Two-Factor Authentication (2FA)</h3>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; background: <?php echo $message_type === 'success' ? 'rgba(34, 197, 94, 0.1)' : 'rgba(239, 68, 68, 0.1)'; ?>; color: <?php echo $message_type === 'success' ? '#4ade80' : '#f87171'; ?>;">
                            <?php echo h($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($user['two_factor_enabled']): ?>
                        <div style="text-align: center; padding: 20px;">
                            <div style="font-size: 4rem; color: #4ade80; margin-bottom: 10px;"><ion-icon name="checkmark-circle"></ion-icon></div>
                            <h4 style="color: white; margin-bottom: 10px;">2FA is Active</h4>
                            <p style="color: var(--text-muted); margin-bottom: 30px;">Your account is protected by an additional security layer.</p>
                            
                            <form method="POST" onsubmit="return confirm('Are you sure you want to disable 2FA? This will reduce your account security.');">
                                <button type="submit" name="disable_2fa" class="btn btn-secondary" style="background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.2);">
                                    Disable 2FA Protection
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="setup-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start;">
                            <div class="setup-instructions">
                                <h4 style="color: white; margin-bottom: 15px;">How to enable 2FA:</h4>
                                <ol style="color: var(--text-muted); line-height: 1.8; padding-left: 20px;">
                                    <li>Download an authenticator app (Google Authenticator, Authy, or Microsoft Authenticator).</li>
                                    <li>Scan the QR code on the right using the app.</li>
                                    <li>If you cannot scan, manually enter the setup key below.</li>
                                    <li>Enter the 6-digit code generated by the app to confirm.</li>
                                </ol>
                                
                                <div style="margin-top: 25px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                                    <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 5px;">Setup Key</span>
                                    <code style="font-size: 1.2rem; color: var(--accent); letter-spacing: 2px;"><?php echo $temp_secret; ?></code>
                                </div>
                            </div>
                            
                            <div class="setup-actions" style="text-align: center;">
                                <div id="qrcode" style="background: white; padding: 15px; border-radius: 12px; display: inline-block; margin-bottom: 20px;"></div>
                                <script>
                                    new QRCode(document.getElementById("qrcode"), {
                                        text: "<?php echo $qr_url; ?>",
                                        width: 180,
                                        height: 180,
                                        colorDark : "#0f172a",
                                        colorLight : "#ffffff",
                                        correctLevel : QRCode.CorrectLevel.H
                                    });
                                </script>

                                <form method="POST">
                                    <input type="hidden" name="temp_secret" value="<?php echo $temp_secret; ?>">
                                    <div class="form-group">
                                        <label>Enter 6-Digit Verification Code</label>
                                        <input type="text" name="verify_code" class="input-field" placeholder="000000" maxlength="6" required style="text-align: center; font-size: 1.5rem; letter-spacing: 5px;">
                                    </div>
                                    <button type="submit" name="enable_2fa" class="btn btn-primary" style="width: 100%;">Activate 2FA Now</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <?php if (isset($_SESSION['flash'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showToast("<?php echo $_SESSION['flash']['message']; ?>", "<?php echo $_SESSION['flash']['type']; ?>");
        });
    </script>
    <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
</body>
</html>
