<?php
/**
 * Mandatory Password Change
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['new_password'] ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';
    
    if (strlen($new_pass) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_pass !== $conf_pass) {
        $error = "Passwords do not match.";
    } else {
        $pdo = getDB();
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $user_id = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?");
        $stmt->execute([$hash, $user_id]);
        
        $_SESSION['must_change_password'] = 0;
        logAction("Password Changed Successfully");
        
        $success = "Password updated! Redirecting to dashboard...";
        header("Refresh: 2; URL=../dashboard/index.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Hwange SRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body style="background: var(--bg-main); display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;">
    <div class="form-card" style="max-width: 400px; width: 100%; border-top: 5px solid var(--accent-color);">
        <h2 style="margin-top: 0; color: var(--primary-color);">Set Secure Password</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 2rem;">As a security measure, you must set a new password before continuing.</p>
        
        <?php if ($error): ?>
            <div style="background: var(--danger); color: white; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.875rem;">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background: var(--success); color: white; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.875rem;">
                <?= h($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="At least 8 characters">
                    <span class="toggle-password" onclick="togglePasswordVisibility('new_password', this)">
                        <ion-icon name="eye-outline"></ion-icon>
                    </span>
                </div>
            </div>
            <div class="input-group">
                <label>Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)">
                        <ion-icon name="eye-outline"></ion-icon>
                    </span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Update Password</button>
        </form>
    </div>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
