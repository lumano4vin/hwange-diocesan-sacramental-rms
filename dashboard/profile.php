<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * User Profile Management
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Fetch full user details
$user_id = $_SESSION['user_id'];
$user = db_fetch("
    SELECT u.*, p.parish_name 
    FROM users u 
    LEFT JOIN parishes p ON u.parish_id = p.parish_id 
    WHERE u.user_id = ?", [$user_id]);

if (!$user) {
    die("User session invalid. Please log in again.");
}

$header_title = "My User Profile";
$header_subtitle = "Review your canonical assignment and security credentials.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.4">
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
            
            <?php include '../includes/header.php'; ?>

            <section class="profile-section" style="max-width: 900px; margin: 0 auto; animation: fadeIn 0.4s ease-out;">
                
                <div class="profile-grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
                    
                    <!-- Profile Card -->
                    <div class="card bg-card" style="text-align: center; display: flex; flex-direction: column; align-items: center; padding: 3rem 2rem;">
                        <div style="width: 100px; height: 100px; background: var(--accent); color: var(--bg-dark); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; margin-bottom: 1.5rem; border: 5px solid rgba(255,255,255,0.1);">
                            <?php 
                                $initials = strtoupper(substr($user['full_name'], 0, 1) . substr(strrchr($user['full_name'], " "), 1, 1));
                                if (strlen($initials) < 2) $initials = strtoupper(substr($user['full_name'], 0, 2));
                                echo $initials; 
                            ?>
                        </div>
                        <h2 style="margin-bottom: 5px; color: white;"><?php echo h($user['full_name']); ?></h2>
                        <span class="status-badge verified" style="margin-bottom: 2rem;"><?php echo ucfirst(h($user['role'])); ?></span>
                        
                        <div style="width: 100%; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 2rem;">
                            <p style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Canonical Mission</p>
                            <h4 style="color: var(--accent);"><?php echo h($user['parish_name'] ?? 'Diocesan Chancery'); ?></h4>
                        </div>
                    </div>

                    <!-- Details Area -->
                    <div class="details-stack" style="display: flex; flex-direction: column; gap: 1.5rem;">
                        
                        <div class="card bg-card">
                            <div class="card-header" style="margin-bottom: 1.5rem;">
                                <h3><ion-icon name="person-outline" style="color: var(--accent); vertical-align: middle; margin-right: 8px;"></ion-icon> Account Details</h3>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="detail-item">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Username</label>
                                    <p style="font-weight: 700; color: white;"><?php echo h($user['username']); ?></p>
                                </div>
                                <div class="detail-item">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Member Since</label>
                                    <p style="font-weight: 700; color: white;"><?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                                </div>
                                <div class="detail-item">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Email Address</label>
                                    <p style="font-weight: 700; color: white;"><?php echo h($user['email'] ?? 'Not Provided'); ?></p>
                                </div>
                                <div class="detail-item">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">System ID</label>
                                    <p style="font-weight: 700; color: white;">#<?php echo str_pad($user['user_id'], 4, '0', STR_PAD_LEFT); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-card">
                            <div class="card-header" style="margin-bottom: 1.5rem;">
                                <h3><ion-icon name="shield-checkmark-outline" style="color: var(--accent); vertical-align: middle; margin-right: 8px;"></ion-icon> Privacy & Security</h3>
                            </div>
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <a href="2fa_setup.php" class="btn btn-primary" style="flex: 1; min-width: 200px;">
                                    <ion-icon name="shield-lock-outline"></ion-icon>
                                    Manage 2FA Settings
                                </a>
                                <a href="users.php" class="btn btn-secondary" style="flex: 1; min-width: 200px;">
                                    <ion-icon name="key-outline"></ion-icon>
                                    Update Credentials
                                </a>
                            </div>
                        </div>

                    </div>

                </div>

            </section>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .profile-grid { margin-top: 1rem; }
    </style>
</body>
</html>
