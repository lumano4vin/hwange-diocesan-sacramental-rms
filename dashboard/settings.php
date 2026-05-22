<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * System Settings & Information
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$user_full_name = $_SESSION['full_name'];
$user_role = ucfirst($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <?php 
                $header_title = "System Settings";
                $header_subtitle = "Current configuration and user profile for the Hwange Diocese RMS.";
                include '../includes/header.php'; 
            ?>


            <div class="dashboard-grid single-column">
                
                <div class="card bg-card">
                    <div class="card-header">
                        <h3><ion-icon name="person-circle-outline"></ion-icon> User Profile & Account</h3>
                    </div>
                    <div class="card-body">
                        <form action="../actions/update_profile.php" method="POST" class="settings-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" value="<?php echo h($user_full_name); ?>" disabled style="opacity: 0.6; cursor: not-allowed;">
                                    <span style="font-size: 0.7rem; color: var(--text-muted);">Name changes must be verified by the Chancery.</span>
                                </div>
                                <div class="form-group">
                                    <label>Access Role</label>
                                    <input type="text" value="<?php echo h($user_role); ?>" disabled style="opacity: 0.6;">
                                </div>
                            </div>
                            
                            <div class="form-row" style="margin-top: 1rem;">
                                <div class="form-group">
                                    <label>Username (Used for Sign-In)</label>
                                    <input type="text" name="username" value="<?php echo h($_SESSION['username']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <?php 
                                        $user_data = db_fetch("SELECT email FROM users WHERE user_id = ?", [$_SESSION['user_id']]);
                                    ?>
                                    <input type="email" name="email" value="<?php echo h($user_data['email'] ?? ''); ?>" placeholder="name@hwangediocese.org">
                                </div>
                            </div>

                            <div class="security-verification" style="margin-top: 1.5rem; padding: 1.5rem; background: rgba(56, 189, 248, 0.05); border-radius: 1rem; border: 1px dashed var(--accent);">
                                <label style="display: block; margin-bottom: 0.75rem; color: var(--accent); font-weight: 700;">Security Verification Required</label>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">To protect the canonical archives, you must verify your current password to authorize these changes.</p>
                                <div class="password-wrapper">
                                    <input type="password" id="verify_current_password" name="current_password" required placeholder="Enter current password to save changes">
                                    <span class="toggle-password" onclick="togglePasswordVisibility('verify_current_password', this)">
                                        <ion-icon name="eye-outline"></ion-icon>
                                    </span>
                                </div>
                            </div>

                            <div class="form-actions" style="margin-top: 1.5rem;">
                                <button type="submit" class="btn btn-secondary" style="width: auto;">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Update Account Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card bg-card" style="margin-top: 1.5rem;">
                    <div class="card-header">
                        <h3><ion-icon name="lock-closed-outline"></ion-icon> Security & Password</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted" style="margin-bottom: 1.5rem;">Update your login credentials below. You will be required to verify your current password.</p>
                        
                        <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-error" style="margin-bottom: 1rem;"><?php echo h($_GET['error']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success" style="margin-bottom: 1rem;"><?php echo h($_GET['success']); ?></div>
                        <?php endif; ?>

                        <form action="../actions/change_password.php" method="POST" class="settings-form">
                            <div class="form-group">
                                <label>Current Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="current_password" name="current_password" required placeholder="Verify current password">
                                    <span class="toggle-password" onclick="togglePasswordVisibility('current_password', this)">
                                        <ion-icon name="eye-outline"></ion-icon>
                                    </span>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>New Password</label>
                                    <div class="password-wrapper">
                                        <input type="password" id="new_password" name="new_password" required placeholder="Min. 8 characters" minlength="8">
                                        <span class="toggle-password" onclick="togglePasswordVisibility('new_password', this)">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <div class="password-wrapper">
                                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repeat new password">
                                        <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions" style="margin-top: 1rem;">
                                <button type="submit" class="btn btn-primary">
                                    <ion-icon name="shield-checkmark-outline"></ion-icon>
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card bg-card" style="margin-top: 1.5rem;">
                    <div class="card-header">
                        <h3><ion-icon name="server-outline"></ion-icon> System Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-list">
                            <div class="info-item">
                                <label>PHP Version</label>
                                <span><?php echo phpversion(); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Database Engine</label>
                                <span>SQLite (Portable Mode)</span>
                            </div>
                            <div class="info-item">
                                <label>Root Path</label>
                                <span style="font-size: 0.8rem;"><?php echo __DIR__; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Developer & Architecture Attribution -->
                <div class="card bg-card" style="margin-top: 1.5rem; background: linear-gradient(135deg, rgba(30, 41, 59, 0.5) 0%, rgba(15, 23, 42, 0.8) 100%); border-top: 4px solid var(--accent);">
                    <div class="card-header">
                        <h3 style="color: var(--accent);"><ion-icon name="code-working-outline"></ion-icon> Software Architecture</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1.5rem;">
                            <div class="lumsystems-diamond" style="width: 60px; height: 60px; position: relative; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                 <!-- CSS Diamond: Large Edition -->
                                 <div style="position: absolute; width: 100%; height: 100%; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%); clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%); box-shadow: 0 0 20px rgba(251, 191, 36, 0.3);"></div>
                                 <div style="position: absolute; width: 80%; height: 80%; background: rgba(255,255,255,0.1); clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%); backdrop-filter: blur(2px);"></div>
                            </div>
                            <div>
                                <h4 style="color: white; font-family: 'Outfit'; margin-bottom: 2px;">Developed & Orchestrated by LumSystems</h4>
                                <p style="font-size: 0.6rem; color: var(--accent); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Honoring Legacy, Illuminating Excellence, Engineering the Future</p>
                                <p style="font-size: 0.8rem; color: var(--text-muted);">Digital Custodians of Canonical History</p>
                            </div>
                        </div>
                        <div class="info-list">
                            <div class="info-item">
                                <label>Build Version</label>
                                <span>v<?php echo defined('APP_VERSION') ? APP_VERSION : '1.6.0'; ?> (Stable)</span>
                            </div>
                            <div class="info-item">
                                <label>Authorized User</label>
                                <span style="color: white; font-weight: 700;">Catholic Diocese of Hwange</span>
                            </div>
                            <div class="info-item">
                                <label>Copyright</label>
                                <span style="color: var(--accent); font-weight: 700;">&copy; 2026 LumSystems. Authorized.</span>
                            </div>
                            <div class="info-item" style="border: none;">
                                <label>Architectural Support</label>
                                <span style="font-size: 0.8rem;">support@lumsystems.net</span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (is_admin()): ?>
                <div class="card bg-card" style="margin-top: 1.5rem; border: 1px solid rgba(56, 189, 248, 0.2);">
                    <div class="card-header">
                        <h3 style="color: #38bdf8;"><ion-icon name="construct-outline"></ion-icon> Maintenance & Archival Integrity</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted" style="margin-bottom: 1.5rem;">Critical tools for diocesan archive management and disaster recovery.</p>
                        
                        <div class="maintenance-actions" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <a href="../actions/backup.php" class="btn" download style="background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid #38bdf8;">
                                <ion-icon name="cloud-download-outline"></ion-icon>
                                Download Database Backup
                            </a>
                            
                            <a href="audit_logs.php" class="btn" style="background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid #475569;">
                                <ion-icon name="list-outline"></ion-icon>
                                View Archival Audit Trail
                            </a>

                            <a href="import_records.php" class="btn" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981;">
                                <ion-icon name="cloud-upload-outline"></ion-icon>
                                Bulk Legacy Import (CSV)
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php include '../includes/privacy_footer.php'; ?>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <style>
        .single-column { max-width: 800px; }
        .info-list { display: flex; flex-direction: column; gap: 1.5rem; padding: 1rem 0; }
        .info-item { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 0.75rem; }
        .info-item label { color: var(--text-muted); font-size: 0.9rem; font-weight: 600; }
        .info-item span { color: white; font-weight: 500; }
        .card-header h3 { display: flex; align-items: center; gap: 0.75rem; color: var(--accent); }
    </style>
</body>
</html>
