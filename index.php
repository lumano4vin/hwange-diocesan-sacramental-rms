<?php
/**
 * Hwange Diocesan Sacramental Records Management System
 * SELF-CONTAINED EMERGENCY LOGIN
 */

// 1. Force Error Visibility
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include unified DB and Functions

// Include unified DB and Functions
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

// 4. Login Logic
$error_msg = '';
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_msg = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['parish_id'] = $user['parish_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['must_change_password'] = $user['must_change_password'];
                $_SESSION['login_success_popup'] = true;
                
                $upd = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
                $upd->execute([$user['user_id']]);
                
                logAction("User Logged In");
                
                session_write_close();
                if ($user['must_change_password']) {
                    header("Location: profile/password_change.php");
                } else {
                    header("Location: dashboard/index.php");
                }
                exit;
            } else {
                $error_msg = 'Invalid email/username or password. Please try again.';
            }
        } catch (Exception $e) {
            $error_msg = 'System Error: ' . $e->getMessage();
        }
    }
} else if (isset($_GET['error']) || isset($_GET['msg'])) {
    $code = $_GET['error'] ?? $_GET['msg'];
    switch($code) {
        case 'unauthorized': $error_msg = 'Please sign in to access the system.'; break;
        case 'session_expired': $error_msg = 'Your session has expired. Please sign in again.'; break;
        case 'logged_out': 
            $name_str = isset($_GET['name']) ? h(urldecode($_GET['name'])) : 'Minister';
            $success_msg = "Goodbye, <strong>$name_str</strong>. Thank you for your service today. We look forward to your return to continue safeguarding the canonical mission!"; 
            break;
        default: $error_msg = 'An error occurred. Please try again.'; break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hwange Diocesan Sacramental Database System - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        :root {
            --primary: #fbbf24;
            --primary-hover: #f59e0b;
            --bg-dark: #020617;
            --bg-card: rgba(15, 23, 42, 0.7);
            --text-main: #ffffff;
            --text-muted: #94a3b8;
            --accent: #fbbf24;
            --error: #ef4444;
            --success: #10b981;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(251, 191, 36, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(217, 119, 6, 0.1) 0%, transparent 50%),
                url('assets/img/background.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated Mesh Background */
        .mesh-bg {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            background: linear-gradient(45deg, #020617, #0f172a);
            overflow: hidden;
        }
        .mesh-sphere {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            animation: meshMove 20s infinite alternate ease-in-out;
        }
        .sphere-1 { width: 500px; height: 500px; background: radial-gradient(circle, rgba(251, 191, 36, 0.2) 0%, transparent 70%); top: -100px; left: -100px; animation-duration: 25s; }
        .sphere-2 { width: 600px; height: 600px; background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%); bottom: -150px; right: -150px; animation-duration: 30s; animation-delay: -5s; }
        .sphere-3 { width: 400px; height: 400px; background: radial-gradient(circle, rgba(56, 189, 248, 0.1) 0%, transparent 70%); top: 40%; left: 60%; animation-duration: 20s; animation-delay: -2s; }

        @keyframes meshMove {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(100px, 50px) scale(1.2); }
        }

        /* Floating Elements */
        .floating-element {
            position: absolute;
            pointer-events: none;
            z-index: 0;
            opacity: 0.15;
            animation: float 25s infinite ease-in-out;
            color: var(--accent);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0) rotate(0deg); }
            33% { transform: translateY(-100px) translateX(60px) rotate(120deg); }
            66% { transform: translateY(50px) translateX(-80px) rotate(240deg); }
        }

        .login-premium-card {
            width: 100%;
            max-width: 580px;
            padding: 3.5rem;
            background: var(--bg-card);
            border-radius: 3rem;
            border: 1px solid rgba(251, 191, 36, 0.3);
            box-shadow: 
                0 50px 100px -20px rgba(0, 0, 0, 0.8), 
                0 0 50px rgba(251, 191, 36, 0.1);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            position: relative;
            z-index: 10;
            animation: containerReveal 1s cubic-bezier(0.23, 1, 0.32, 1);
        }
        @keyframes containerReveal {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .logo-section { text-align: center; margin-bottom: 2.5rem; }
        .login-logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin: 0 auto 1.5rem auto;
            border-radius: 50%;
            background: white;
            padding: 8px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.5), 0 0 30px rgba(251, 191, 36, 0.3);
            border: 4px solid var(--accent);
            animation: logoFloat 6s infinite ease-in-out;
        }
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .logo-section h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            font-weight: 900;
            color: #ffffff;
            margin-bottom: 0.25rem;
            letter-spacing: -0.5px;
            background: linear-gradient(to bottom, #fff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .logo-section .brand-sub {
            color: var(--accent);
            font-weight: 800;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 4px;
            display: block;
            margin-bottom: 1.25rem;
        }

        /* Typewriter Cursor */
        #typed-subtitle::after {
            content: '|';
            animation: blink 0.7s infinite;
        }
        @keyframes blink { 50% { opacity: 0; } }

        /* Sacred Dust Particles */
        .sacred-dust {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        .dust {
            position: absolute;
            background: var(--accent);
            border-radius: 50%;
            opacity: 0.3;
            filter: blur(1px);
            animation: dustFloat var(--d) infinite linear;
        }
        @keyframes dustFloat {
            0% { transform: translateY(110vh) translateX(0); opacity: 0; }
            50% { opacity: 0.4; }
            100% { transform: translateY(-10vh) translateX(50px); opacity: 0; }
        }

        /* Creed Card with Soul */
        .creed-card {
            margin-top: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.08) 0%, rgba(15, 23, 42, 0.6) 100%);
            border: 1px solid rgba(251, 191, 36, 0.2);
            border-radius: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4), inset 0 0 20px rgba(251, 191, 36, 0.05);
            animation: creedGlow 4s infinite alternate ease-in-out;
            text-align: center;
        }
        @keyframes creedGlow {
            0% { border-color: rgba(251, 191, 36, 0.2); box-shadow: 0 15px 35px rgba(0,0,0,0.4); }
            100% { border-color: rgba(251, 191, 36, 0.6); box-shadow: 0 15px 50px rgba(251, 191, 36, 0.15); }
        }
        .creed-text {
            font-family: 'Outfit', sans-serif;
            font-style: italic;
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 1.25rem;
            position: relative;
            z-index: 2;
            
            /* Divine Shimmer Effect */
            background: linear-gradient(90deg, #fff, #fbbf24, #fff);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: divineShimmer 3s linear infinite;
        }
        @keyframes divineShimmer {
            to { background-position: 200% center; }
        }
        .creed-author {
            font-weight: 900;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 3px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .creed-author::before, .creed-author::after {
            content: '';
            height: 1px;
            width: 30px;
            background: var(--accent);
            opacity: 0.3;
        }
        .soul-icon {
            font-size: 2.5rem;
            color: var(--accent);
            opacity: 0.15;
            position: absolute;
            top: -10px;
            right: -10px;
            animation: soulFloat 6s infinite ease-in-out;
        }
        @keyframes soulFloat {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-10px, 15px) rotate(15deg); }
        }

        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 0.6rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group input {
            width: 100%;
            padding: 1.1rem 1.25rem;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(251, 191, 36, 0.2);
            border-radius: 1rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-group input:focus { outline: none; border-color: var(--accent); background: rgba(0, 0, 0, 0.6); box-shadow: 0 0 20px rgba(251, 191, 36, 0.2); }

        .btn-login {
            width: 100%;
            padding: 1.25rem;
            background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%);
            color: #000;
            border: none;
            border-radius: 1rem;
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            margin-top: 1rem;
            box-shadow: 0 10px 30px rgba(217, 119, 6, 0.3);
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .btn-login:hover { transform: translateY(-3px); box-shadow: 0 15px 40px rgba(217, 119, 6, 0.4); filter: brightness(1.1); }

        .divider { margin: 1.5rem 0; display: flex; align-items: center; gap: 15px; }
        .divider div { flex: 1; height: 1px; background: rgba(255,255,255,0.1); }
        .divider span { font-size: 0.7rem; color: var(--text-muted); font-weight: 700; }

        .btn-sso {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(251, 191, 36, 0.15);
            border-radius: 1rem;
            color: white;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: 0.3s;
        }
        .btn-sso:hover { background: rgba(251, 191, 36, 0.1); border-color: var(--accent); }

        .portal-utilities {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-top: 2.5rem;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 1.5rem;
        }
        .util-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            transition: 0.3s;
            padding: 0.75rem 0.25rem;
            border-radius: 0.75rem;
            background: rgba(255,255,255,0.02);
            border: none;
            cursor: pointer;
        }
        .util-btn ion-icon { font-size: 1.25rem; color: var(--accent); }
        .util-btn:hover { color: white; background: rgba(251, 191, 36, 0.1); transform: translateY(-3px); }

        .footer-note { margin-top: 2.5rem; text-align: center; font-size: 0.75rem; color: var(--text-muted); line-height: 1.5; }
        
        /* Modal System */
        .info-modal {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(2, 6, 23, 0.85);
            backdrop-filter: blur(15px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .info-modal.active { display: flex; opacity: 1; }
        .modal-card {
            background: var(--bg-card);
            border: 1px solid rgba(251, 191, 36, 0.4);
            border-radius: 2rem;
            padding: 2.5rem;
            max-width: 450px;
            width: 90%;
            text-align: center;
            transform: translateY(20px);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .info-modal.active .modal-card { transform: translateY(0); }
    </style>
</head>
<body>
    <!-- Sacred Dust Layer -->
    <div class="sacred-dust" id="dust-container"></div>

    <div class="mesh-bg">
        <div class="mesh-sphere sphere-1"></div>
        <div class="mesh-sphere sphere-2"></div>
        <div class="mesh-sphere sphere-3"></div>
    </div>

    <!-- Decorative Floating Icons -->
    <div class="floating-element" style="top: 10%; left: 5%; font-size: 30px;"><ion-icon name="star"></ion-icon></div>
    <div class="floating-element" style="top: 20%; right: 10%; animation-delay: -2s; font-size: 40px;"><ion-icon name="ribbon"></ion-icon></div>
    <div class="floating-element" style="bottom: 15%; left: 12%; animation-delay: -5s; font-size: 35px;"><ion-icon name="shield-checkmark"></ion-icon></div>
    <div class="floating-element" style="bottom: 25%; right: 8%; animation-delay: -8s; font-size: 45px;"><ion-icon name="heart"></ion-icon></div>
    <div class="floating-element" style="top: 45%; left: 18%; animation-delay: -12s; font-size: 25px;"><ion-icon name="book"></ion-icon></div>

    <div class="login-premium-card">
        <div class="logo-section">
            <img src="assets/img/diocesan_logo.png" alt="<?php echo get_diocese_branding(); ?> Logo" class="login-logo">
            <h1 id="dynamic-greeting">Welcome back, Minister of Sacraments</h1>
            <span class="brand-sub">Sacramental Archive Portal</span>
            <p id="typed-subtitle"></p>
        </div>

        <form action="" method="POST">
            <?php if ($error_msg): ?>
                <div class="error-message" style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--error); color: white; padding: 1rem; border-radius: 1rem; margin-bottom: 1.5rem; text-align: center; font-size: 0.85rem;">
                    <?php echo h($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_msg): ?>
                <div class="success-message" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.4); color: white; padding: 1.25rem; border-radius: 1.25rem; margin-bottom: 1.5rem; text-align: left; font-size: 0.85rem; display: flex; align-items: flex-start; gap: 0.75rem; box-shadow: 0 10px 30px -5px rgba(16, 185, 129, 0.2);">
                    <div style="background: var(--success); color: var(--bg-dark); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                        <ion-icon name="checkmark-done-outline" style="font-size: 1.1rem;"></ion-icon>
                    </div>
                    <div>
                        <strong style="display: block; color: var(--success); margin-bottom: 2px; font-family: 'Outfit', sans-serif;">Session Safely Closed</strong>
                        <?php echo $success_msg; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" placeholder="Enter your credentials" required 
                       value="<?php echo isset($_POST['username']) ? h($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                    <label style="margin-bottom: 0;">Password</label>
                    <a href="forgot_password.php" style="font-size: 0.75rem; color: var(--accent); text-decoration: none; font-weight: 700;">Forgot?</a>
                </div>
                <div class="password-input-group" style="position: relative;">
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('password', this)" 
                            style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--accent); cursor: pointer; font-size: 1.4rem;">
                        <ion-icon name="eye-outline"></ion-icon>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">Sign In to the Mission</button>

            <div class="divider">
                <div></div>
                <span>OR</span>
                <div></div>
            </div>

            <button type="button" class="btn-sso">
                <ion-icon name="key-outline" style="color: var(--accent);"></ion-icon>
                Log in with Single Sign-On
            </button>
        </form>

        <div class="portal-utilities">
            <button onclick="showInfo('support')" class="util-btn">
                <ion-icon name="help-buoy-outline"></ion-icon>
                <span>Support</span>
            </button>
            <button onclick="showInfo('about')" class="util-btn">
                <ion-icon name="information-circle-outline"></ion-icon>
                <span>About</span>
            </button>
            <button onclick="showInfo('guide')" class="util-btn">
                <ion-icon name="journal-outline"></ion-icon>
                <span>Guide</span>
            </button>
        </div>

        <!-- Living Digital Mission Creed -->
        <div class="creed-card">
            <ion-icon name="heart" class="soul-icon"></ion-icon>
            <p class="creed-text">
                "A Database of Souls, A Network of Grace. <br>
                Preserving the sacred heartbeat of Hwange through digital devotion."
            </p>
            <div class="creed-author">
                Digital Mission Creed
            </div>
        </div>

        <div class="footer-note">
            <p>&copy; 2026 Hwange Diocesan Sacramental Database System. <br> All rights reserved.</p>
        </div>
    </div>

    <!-- Info Modal -->
    <div id="infoModal" class="info-modal" onclick="closeInfo(event)">
        <div class="modal-card" onclick="event.stopPropagation()">
            <div id="modalIcon" style="font-size: 3rem; color: var(--accent); margin-bottom: 1rem;"><ion-icon name="information-circle"></ion-icon></div>
            <h2 id="modalTitle" style="font-family: 'Outfit'; color: white; margin-bottom: 0.75rem;">Information</h2>
            <div id="modalBody" style="color: var(--text-muted); line-height: 1.6; margin-bottom: 1.5rem;"></div>
            <button onclick="closeInfo()" style="padding: 0.75rem 2rem; background: var(--accent); border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer;">Dismiss</button>
        </div>
    </div>

    <script>
        // 1. Dynamic Greeting
        const hour = new Date().getHours();
        const greetingElement = document.getElementById('dynamic-greeting');
        let timeGreet = "Good Morning";
        if (hour >= 12 && hour < 17) timeGreet = "Good Afternoon";
        else if (hour >= 17) timeGreet = "Good Evening";
        
        greetingElement.innerText = `${timeGreet}, Welcome back, Minister of Sacraments`;

        // 2. Premium Typewriter Animation
        const subtitleText = "Safeguarding our Canonical Legacy, one record at a time...";
        let subtitleIdx = 0;
        
        function typeWriter() {
            const subtitleElement = document.getElementById('typed-subtitle');
            if (subtitleElement && subtitleIdx < subtitleText.length) {
                subtitleElement.innerHTML += subtitleText.charAt(subtitleIdx);
                subtitleIdx++;
                setTimeout(typeWriter, 40);
            }
        }
        
        window.addEventListener('load', typeWriter);

        // 3. Info Modal Logic
        const infoData = {
            about: { 
                title: "About the System", 
                icon: "information-circle-outline", 
                body: "The <strong>Hwange Diocesan Sacramental Database System</strong> is a secure digital vault for all canonical records. It ensures that the sacramental history of every parishioner is preserved with modern encryption and digital stewardship." 
            },
            guide: { 
                title: "How to Sign In", 
                icon: "journal-outline", 
                body: "1. Enter your official <strong>Username</strong> (usually your surname or parish ID).<br>2. Type your <strong>Password</strong> in the protected field.<br>3. Click <strong>'Sign In to the Mission'</strong>.<br><br><em>Tip: Use the eye icon to verify your password if you are having trouble signing in.</em>" 
            },
            support: { 
                title: "Support", 
                icon: "help-buoy-outline", 
                body: "If you have forgotten your password or are locked out, please contact the <strong>Diocesan IT Department</strong> or your Parish Administrator for a reset." 
            },
            sso: { 
                title: "About Single Sign-On", 
                icon: "key-outline", 
                body: "<strong>Single Sign-On (SSO)</strong> means using <em>one</em> set of credentials to access <em>all</em> diocesan apps. <br><br>We are currently integrating with the Diocesan Microsoft portal. Once active, you will be able to log in using your official <code>@hwangediocese.org</code> email address." 
            }
        };

        function showInfo(type) {
            const data = infoData[type];
            document.getElementById('modalTitle').innerText = data.title;
            document.getElementById('modalIcon').innerHTML = `<ion-icon name="${data.icon}"></ion-icon>`;
            document.getElementById('modalBody').innerHTML = data.body;
            document.getElementById('infoModal').classList.add('active');
        }

        function closeInfo() { document.getElementById('infoModal').classList.remove('active'); }

        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('ion-icon');
            input.type = (input.type === 'password') ? 'text' : 'password';
            icon.name = (input.type === 'password') ? 'eye-outline' : 'eye-off-outline';
        }

        document.querySelector('.btn-sso').addEventListener('click', () => showInfo('sso'));

        // 4. Keyboard Shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeInfo();
        });
    </script>
</body>
</html>
