<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * User Manual & Help Center
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';

require_login();

$header_title = "User Manual & Help";
$header_subtitle = "A complete guide to using the Hwange Diocesan Sacramental Database System.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Manual - <?php echo get_diocese_branding(); ?> RMS</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.6">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">

        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">

            <?php include 'includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0; max-width: 860px; margin: 0 auto;">

                <!-- Hero Banner -->
                <div style="background: linear-gradient(135deg, rgba(56, 189, 248, 0.1) 0%, rgba(15,23,42,0.8) 100%); border: 1px solid rgba(56, 189, 248, 0.2); border-radius: 1.5rem; padding: 2.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 2rem;">
                    <div style="font-size: 4rem; color: var(--accent); flex-shrink: 0;">
                        <ion-icon name="book-outline"></ion-icon>
                    </div>
                    <div>
                        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; color: white; margin-bottom: 0.5rem;">Hwange Diocesan Sacramental Database</h1>
                        <p style="color: var(--text-muted); line-height: 1.6;">Welcome to the official help documentation. This guide covers all functional steps for accessing and using the system to manage the canonical records of the Catholic Diocese of Hwange - Zimbabwe.</p>
                    </div>
                </div>

                <!-- Section 1: Accessing the System -->
                <div class="card bg-card" style="margin-bottom: 1.5rem; border-radius: 1.25rem;">
                    <div class="card-header" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem 1.5rem 1rem;">
                        <div style="width: 40px; height: 40px; background: rgba(56, 189, 248, 0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #38bdf8; font-size: 1.25rem; flex-shrink: 0;">
                            <ion-icon name="log-in-outline"></ion-icon>
                        </div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: white;">1. Accessing the System</h2>
                    </div>
                    <div class="card-body" style="padding: 0 1.5rem 1.5rem;">
                        <p style="color: var(--text-muted); margin-bottom: 1.25rem;">Before you can sign in, ensure the system is running by double-clicking <code style="background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 4px; color: var(--accent); font-size: 0.85rem;">LAUNCH_RMS.bat</code>.</p>
                        <h4 style="color: white; margin-bottom: 0.75rem; font-size: 0.95rem;">How to Sign In</h4>
                        <ol style="color: var(--text-muted); padding-left: 1.5rem; line-height: 2;">
                            <li>Open your browser and navigate to <code style="background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 4px; color: #38bdf8; font-size: 0.85rem;">http://localhost:8000</code></li>
                            <li>On the <strong style="color: white;">Login</strong> page, enter your assigned <strong style="color: white;">Username</strong> and <strong style="color: white;">Password</strong>.</li>
                            <li>Click <strong style="color: white;">Login</strong>.</li>
                        </ol>
                        <!-- Note callout -->
                        <div style="margin-top: 1.25rem; background: rgba(56, 189, 248, 0.07); border-left: 4px solid #38bdf8; border-radius: 0 8px 8px 0; padding: 1rem 1.25rem; display: flex; gap: 1rem; align-items: flex-start;">
                            <ion-icon name="information-circle-outline" style="color: #38bdf8; font-size: 1.4rem; flex-shrink: 0; margin-top: 2px;"></ion-icon>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">If it is your first time logging in, you may be prompted to change your temporary password for security reasons.</p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Understanding SSO -->
                <div class="card bg-card" style="margin-bottom: 1.5rem; border-radius: 1.25rem;">
                    <div class="card-header" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem 1.5rem 1rem;">
                        <div style="width: 40px; height: 40px; background: rgba(251, 191, 36, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.25rem; flex-shrink: 0;">
                            <ion-icon name="key-outline"></ion-icon>
                        </div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: white;">2. Understanding SSO (Single Sign-On)</h2>
                    </div>
                    <div class="card-body" style="padding: 0 1.5rem 1.5rem;">
                        <h4 style="color: white; margin-bottom: 0.5rem; font-size: 0.95rem;">What is SSO?</h4>
                        <p style="color: var(--text-muted); margin-bottom: 1.25rem;"><strong style="color: white;">SSO (Single Sign-On)</strong> is an authentication method that allows a user to log in with a single set of credentials to any of several related, yet independent, software systems.</p>
                        <h4 style="color: white; margin-bottom: 0.75rem; font-size: 0.95rem;">Why use SSO?</h4>
                        <ul style="color: var(--text-muted); padding-left: 1.5rem; line-height: 2.2;">
                            <li><strong style="color: white;">Convenience:</strong> You don't need to remember different passwords for every diocesan system.</li>
                            <li><strong style="color: white;">Security:</strong> It reduces the risk of password fatigue and simplifies account management.</li>
                            <li><strong style="color: white;">Speed:</strong> Once signed in to one diocesan service, you are often automatically signed into others.</li>
                        </ul>
                        <h4 style="color: white; margin: 1.25rem 0 0.5rem; font-size: 0.95rem;">Does the Database System use SSO?</h4>
                        <p style="color: var(--text-muted); margin-bottom: 0.75rem;">Currently, the system uses a <strong style="color: white;">Local Authentication</strong> system. Your login is managed within the parish records database itself.</p>
                        <div style="background: rgba(251, 191, 36, 0.07); border-left: 4px solid var(--accent); border-radius: 0 8px 8px 0; padding: 1rem 1.25rem; display: flex; gap: 1rem; align-items: flex-start;">
                            <ion-icon name="time-outline" style="color: var(--accent); font-size: 1.4rem; flex-shrink: 0; margin-top: 2px;"></ion-icon>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;"><strong style="color: white;">Future Plans:</strong> We are working towards integrating the Database System with the broader Diocesan SSO portal. Once implemented, you will be able to use your official <code style="color: var(--accent);">@hwangediocese.org</code> credentials to log in here.</p>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Canonical Communication Hub -->
                <div class="card bg-card" style="margin-bottom: 1.5rem; border-radius: 1.25rem;">
                    <div class="card-header" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem 1.5rem 1rem;">
                        <div style="width: 40px; height: 40px; background: rgba(251, 191, 36, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.25rem; flex-shrink: 0;">
                            <ion-icon name="chatbubbles-outline"></ion-icon>
                        </div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: white;">3. Canonical Communication Hub</h2>
                    </div>
                    <div class="card-body" style="padding: 0 1.5rem 1.5rem;">
                        <p style="color: var(--text-muted); margin-bottom: 1.25rem;">The <strong style="color: white;">Communication Hub</strong> is the official digital channel between your Mission and the Diocesan Administration.</p>
                        <h4 style="color: white; margin-bottom: 0.75rem; font-size: 0.95rem;">How to Send a Query</h4>
                        <ol style="color: var(--text-muted); padding-left: 1.5rem; line-height: 2;">
                            <li>Select <strong style="color: white;">Communication Hub</strong> from the sidebar menu.</li>
                            <li>Choose a <strong style="color: white;">Category</strong> (e.g., Registry Correction, Dispensation Request).</li>
                            <li>Enter a clear <strong style="color: white;">Subject</strong> and your detailed message.</li>
                            <li>Click <strong style="color: white;">Send to Chancery</strong>.</li>
                        </ol>
                        <div style="margin-top: 1.25rem; background: rgba(16, 185, 129, 0.07); border-left: 4px solid #10b981; border-radius: 0 8px 8px 0; padding: 1rem 1.25rem; display: flex; gap: 1rem; align-items: flex-start;">
                            <ion-icon name="notifications-outline" style="color: #10b981; font-size: 1.4rem; flex-shrink: 0; margin-top: 2px;"></ion-icon>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;"><strong style="color: white;">Tracking Responses:</strong> When the Chancery responds, a notification badge will appear on your sidebar and the top notification bell. You can view all past correspondence in the Registry Communication Log.</p>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Troubleshooting -->
                <div class="card bg-card" style="margin-bottom: 1.5rem; border-radius: 1.25rem;">
                    <div class="card-header" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem 1.5rem 1rem;">
                        <div style="width: 40px; height: 40px; background: rgba(239, 68, 68, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.25rem; flex-shrink: 0;">
                            <ion-icon name="build-outline"></ion-icon>
                        </div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: white;">4. Troubleshooting Login Issues</h2>
                    </div>
                    <div class="card-body" style="padding: 0 1.5rem 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 10px; border: 1px solid rgba(255,255,255,0.04);">
                            <ion-icon name="lock-open-outline" style="color: var(--accent); font-size: 1.4rem; flex-shrink: 0;"></ion-icon>
                            <div>
                                <strong style="color: white; display: block; margin-bottom: 4px;">Forgotten Password</strong>
                                <span style="color: var(--text-muted); font-size: 0.9rem;">Click "Forgot password?" on the login page. You will need access to your registered email address to reset it.</span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 10px; border: 1px solid rgba(255,255,255,0.04);">
                            <ion-icon name="shield-half-outline" style="color: #ef4444; font-size: 1.4rem; flex-shrink: 0;"></ion-icon>
                            <div>
                                <strong style="color: white; display: block; margin-bottom: 4px;">Account Locked</strong>
                                <span style="color: var(--text-muted); font-size: 0.9rem;">If you enter the wrong password too many times, your account may be temporarily locked. Please contact your Parish Administrator or the Diocesan Office.</span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 10px; border: 1px solid rgba(255,255,255,0.04);">
                            <ion-icon name="alert-circle-outline" style="color: #f59e0b; font-size: 1.4rem; flex-shrink: 0;"></ion-icon>
                            <div>
                                <strong style="color: white; display: block; margin-bottom: 4px;">Invalid Credentials</strong>
                                <span style="color: var(--text-muted); font-size: 0.9rem;">Ensure your Caps Lock is off and that you are using the correct username provided during your onboarding.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Section 5: Support -->
                <div class="card bg-card" style="margin-bottom: 1.5rem; border-radius: 1.25rem;">
                    <div class="card-header" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem 1.5rem 1rem;">
                        <div style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 1.25rem; flex-shrink: 0;">
                            <ion-icon name="help-buoy-outline"></ion-icon>
                        </div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: white;">5. Support</h2>
                    </div>
                    <div class="card-body" style="padding: 0 1.5rem 1.5rem;">
                        <p style="color: var(--text-muted);">For canonical or technical assistance, use the <strong style="color: white;">Communication Hub</strong> to send a message directly to the Diocesan Information Office. For urgent technical failures, contact your local Parish System Administrator.</p>
                        <div style="display: flex; gap: 1rem; margin-top: 1.25rem; flex-wrap: wrap;">
                            <a href="communication_hub.php" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px;">
                                <ion-icon name="chatbubbles-outline"></ion-icon> Open Communication Hub
                            </a>
                            <a href="dashboard/settings.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px;">
                                <ion-icon name="settings-outline"></ion-icon> System Settings
                            </a>
                        </div>
                    </div>
                </div>

                <?php include 'includes/privacy_footer.php'; ?>

            </div>

        </main>
    </div>

    <script src="assets/js/main.js?v=1.6.2"></script>
</body>
</html>
