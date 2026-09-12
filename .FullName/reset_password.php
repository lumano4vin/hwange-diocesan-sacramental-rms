<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$token = $_GET['token'] ?? '';
$isValid = false;

if (!empty($token)) {
    // Check if token exists and is not expired
    $user = db_fetch(
        "SELECT user_id FROM users WHERE reset_token = ? AND reset_token_expiry > CURRENT_TIMESTAMP", 
        [$token]
    );
    if ($user) {
        $isValid = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a56db;
            --primary-hover: #1e429f;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #38bdf8;
            --error: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            background-image: url('assets/img/background.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
            background: var(--bg-card);
            border-radius: 1.5rem;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-section h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }

        .logo-section p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 0.75rem;
            color: white;
            font-size: 1rem;
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-login:hover { background: var(--primary-hover); }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--error);
            color: var(--error);
            padding: 1.5rem;
            border-radius: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="logo-section">
            <h1>New Password</h1>
            <p>Please choose a strong new password.</p>
        </div>

        <?php if ($isValid): ?>
            <form action="actions/process_reset.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••" required minlength="8">
                        <span class="toggle-password" onclick="togglePasswordVisibility('password', this)">
                            <ion-icon name="eye-outline"></ion-icon>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required minlength="8">
                        <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)">
                            <ion-icon name="eye-outline"></ion-icon>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn-login">Change Password</button>
            </form>
        <?php else: ?>
            <div class="alert-error">
                <p>Invalid or expired reset token.</p>
                <br>
                <a href="forgot_password.php" style="color:var(--accent); text-decoration:none;">Request a new link</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/main.js?v=1.6.2"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
