<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Two-Factor Authentication Verification
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if we have a pending 2FA login
if (!isset($_SESSION['2fa_pending_user_id'])) {
    redirect('../index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['otp_code'] ?? '');
    $user_id = $_SESSION['2fa_pending_user_id'];

    // Fetch user secret
    $user = db_fetch("SELECT * FROM users WHERE user_id = ?", [$user_id]);

    if ($user && TOTPHelper::verifyCode($user['two_factor_secret'], $code)) {
        // SUCCESS: Clear 2FA pending status and complete login
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['parish_id'] = $user['parish_id'];
        $_SESSION['login_success_popup'] = true;

        unset($_SESSION['2fa_pending_user_id']);
        unset($_SESSION['2fa_pending_username']);

        // Update last login
        db_query("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?", [$user['user_id']]);

        if (strtolower($user['role']) === 'observer') {
            redirect('../dashboard/observer.php');
        } else {
            redirect('../dashboard/index.php');
        }
    } else {
        $error = 'Invalid verification code. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.3">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <style>
        .verify-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
            padding: 20px;
        }
        .verify-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 40px;
            border-radius: 24px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .verify-icon {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 20px;
        }
        .otp-input {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 2rem;
            letter-spacing: 12px;
            text-align: center;
            padding: 15px;
            border-radius: 12px;
            width: 100%;
            margin: 20px 0;
            font-family: 'Outfit', sans-serif;
            transition: border-color 0.3s;
        }
        .otp-input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.1);
        }
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="verify-container">
        <div class="verify-card">
            <div class="verify-icon">🛡️</div>
            <h2 style="font-family: 'Outfit', sans-serif; color: white;">Identity Verification</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 8px;">
                Enter the 6-digit code from your authenticator app to secure your session.
            </p>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo h($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="text" name="otp_code" class="otp-input" placeholder="000000" 
                       maxlength="6" autocomplete="one-time-code" required autofocus>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1rem;">
                    Confirm & Sign In
                </button>
            </form>

            <div style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;">
                <a href="logout.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem;">
                    Cancel and Return to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
