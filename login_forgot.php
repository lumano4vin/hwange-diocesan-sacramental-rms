<?php
/**
 * Forgot Password - Digital Reset Ticket Request
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if ($email) {
        $pdo = getDB();
        // Check if user exists with this email or username
        $clean_input = strtolower(trim($email));
        $clean_no_domain = preg_replace('/@hwangediocese\.org$/i', '', $clean_input);
        $with_underscore = str_replace('.', '_', $clean_no_domain);
        $with_dot = str_replace('_', '.', $clean_no_domain);

        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE 
            LOWER(email) = ? OR LOWER(username) = ? 
            OR LOWER(email) = ? OR LOWER(username) = ? 
            OR LOWER(REPLACE(username, '.', '_')) = ? 
            OR LOWER(REPLACE(username, '_', '.')) = ? 
            LIMIT 1");
        $stmt->execute([$clean_input, $clean_input, $clean_no_domain, $clean_no_domain, $with_underscore, $with_dot]);
        $user_id = $stmt->fetchColumn();
        
        if ($user_id) {
            // Check if there's already a pending ticket
            $check = $pdo->prepare("SELECT id FROM password_reset_tickets WHERE user_id = ? AND status = 'Pending'");
            $check->execute([$user_id]);
            if ($check->fetch()) {
                $error = "A reset request is already pending for this account. Please wait for the administrator's review.";
            } else {
                // Create a digital ticket
                $ticket = $pdo->prepare("INSERT INTO password_reset_tickets (user_id, status) VALUES (?, 'Pending')");
                $ticket->execute([$user_id]);
                $success = "Your reset request has been sent to the Diocesan Administrator. Please contact the Chancery for the outcome.";
            }
        } else {
            // Security: don't reveal if email exists, but the user requested "identity linked for confirmation"
            // so we might give a generic message or be specific. Let's be generic but helpful.
            $error = "No account found with that email address.";
        }
    } else {
        $error = "Please enter your registered email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Hwange SRMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-container">
    <div class="login-card">
        <h2>Reset Password</h2>
        <p style="text-align: center; color: var(--text-muted); margin-bottom: 2rem;">Please enter your registered email to request a digital reset ticket.</p>
        
        <?php if ($error): ?>
            <div style="background: var(--danger); color: white; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.875rem;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background: var(--success); color: white; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.875rem;">
                <?= e($success) ?>
            </div>
            <p style="text-align: center; margin-top: 1rem;"><a href="login.php" style="color: var(--primary-color);">Back to Login</a></p>
        <?php else: ?>
            <form method="POST">
                <div class="input-group">
                    <label>Registered Email or Username</label>
                    <input type="text" name="email" required placeholder="username or email" autocapitalize="none" autocorrect="off" spellcheck="false">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">File Reset Request</button>
            </form>
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="login.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.875rem;">Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
