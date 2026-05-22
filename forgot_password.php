<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Hwange Diocese RMS</title>
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
            --success: #10b981;
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
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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

        .back-link {
            display: block;
            margin-top: 1.5rem;
            text-align: center;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.875rem;
        }

        .back-link:hover { color: var(--accent); }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: none;
        }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid var(--error); color: var(--error); }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="logo-section">
            <h1>Reset Password</h1>
            <p>Enter your username to receive a reset token.</p>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'not_found'): ?>
                <div class="alert alert-error" style="display:block;">Username not found. Please try again.</div>
            <?php elseif ($_GET['status'] == 'token_generated'): ?>
                <div class="alert alert-success" style="display:block;"> A reset link has been generated. <br><br>
                <strong>For local testing:</strong><br>
                <a href="reset_password.php?token=<?php echo htmlspecialchars($_GET['token']); ?>" style="color:var(--accent); text-decoration:none;">Click here to reset your password</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form action="actions/process_forgot.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <button type="submit" class="btn-login">Generate Reset Link</button>
        </form>

        <a href="index.php" class="back-link">Back to Login</a>
    </div>

</body>
</html>
