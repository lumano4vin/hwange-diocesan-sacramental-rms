<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Clergy Self-Service Password Recovery
 * Gives every priest the autonomous right to reset their password without admin restriction.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$error = '';
$success = '';
$reset_done = false;
$target_user_name = '';

// Helper to resiliently locate a user by ID, username, email, full name, or canonical alias
function resolve_cleric_account($pdo, $user_id, $identifier) {
    if (!empty($user_id) && is_numeric($user_id)) {
        $stmt = $pdo->prepare("SELECT user_id, full_name, username, email, role FROM users WHERE user_id = ?");
        $stmt->execute([(int)$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $raw = trim($identifier ?? '');
    if (empty($raw)) return null;

    $clean = strtolower($raw);
    $clean_no_domain = preg_replace('/@hwangediocese\.org$/i', '', $clean);
    $with_underscore = str_replace(['.', ' ', '-'], '_', $clean_no_domain);
    $with_dot = str_replace(['_', ' ', '-'], '.', $clean_no_domain);

    $aliases = [
        'nicolas_mnkamba' => 'nicolas_nkamba',
        'nicolas.mnkamba' => 'nicolas_nkamba',
        'mnkamba' => 'nicolas_nkamba',
        'nkamba' => 'nicolas_nkamba',
        'fr. nicolas nkamba' => 'nicolas_nkamba',
        'fr nicolas nkamba' => 'nicolas_nkamba',
        'tshongokwe' => 'albert_mudenda',
        'albert_mudenda' => 'Tshongokwe',
        'albert.mudenda' => 'Tshongokwe',
        'linus_dube' => 'linos_dube',
        'linus.dube' => 'linos_dube',
        'timothy_peacock' => 'tim_peacock',
        'timothy.peacock' => 'tim_peacock',
    ];

    $candidates = array_unique(array_filter([
        $raw,
        $clean,
        $clean_no_domain,
        $with_underscore,
        $with_dot,
        $aliases[$clean] ?? null,
        $aliases[$clean_no_domain] ?? null,
        $aliases[$with_underscore] ?? null,
        $aliases[$with_dot] ?? null,
    ]));

    $placeholders = implode(',', array_fill(0, count($candidates), '?'));
    $sql = "SELECT user_id, full_name, username, email, role FROM users WHERE 
        LOWER(username) IN ($placeholders) 
        OR LOWER(email) IN ($placeholders) 
        OR LOWER(full_name) IN ($placeholders)
        OR LOWER(REPLACE(username, '.', '_')) IN ($placeholders) 
        OR LOWER(REPLACE(username, '_', '.')) IN ($placeholders) 
        LIMIT 1";

    $params = array_map('strtolower', array_merge($candidates, $candidates, $candidates, $candidates, $candidates));
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $loose_stmt = $pdo->prepare("SELECT user_id, full_name, username, email, role FROM users WHERE LOWER(full_name) LIKE ? LIMIT 1");
        $loose_stmt->execute(['%' . $clean . '%']);
        $user = $loose_stmt->fetch(PDO::FETCH_ASSOC);
    }

    return $user;
}

// Fetch list of all active clergy & parishes for dropdown selection
$clergy_list = [];
try {
    $clergy_stmt = $pdo->query("
        SELECT u.user_id, u.username, u.full_name, u.email, u.role, p.parish_name, p.location
        FROM users u
        LEFT JOIN parishes p ON u.parish_id = p.parish_id
        WHERE u.role IN ('priest', 'mission_admin', 'admin', 'deacon')
        ORDER BY u.full_name ASC
    ");
    $clergy_list = $clergy_stmt ? $clergy_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    $clergy_list = [];
}

// Handle Self-Service Reset Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cleric_id = $_POST['cleric_id'] ?? '';
    $identifier = trim($_POST['username'] ?? '');
    $new_pass = $_POST['password'] ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';

    $user = resolve_cleric_account($pdo, $cleric_id, $identifier);

    if (!$user) {
        $error = "Account not found. Please select your name from the Clergy list or verify your username/email.";
    } elseif (empty($new_pass) || empty($conf_pass)) {
        $error = "Please enter and confirm your new password.";
    } elseif (strlen($new_pass) < 8) {
        $error = "Password must be at least 8 characters in length for security.";
    } elseif ($new_pass !== $conf_pass) {
        $error = "New passwords do not match. Please retype them carefully.";
    } else {
        try {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("
                UPDATE users 
                SET password_hash = ?, 
                    must_change_password = 0, 
                    reset_token = NULL, 
                    reset_token_expiry = NULL 
                WHERE user_id = ?
            ");
            $update_stmt->execute([$new_hash, $user['user_id']]);

            // Flag cloud sync for serverless environments
            $GLOBALS['rms_db_dirty'] = true;

            $target_user_name = $user['full_name'] ?: $user['username'];
            $success = "Password successfully updated for " . htmlspecialchars($target_user_name) . "!";
            $reset_done = true;
        } catch (Exception $e) {
            $error = "Database update error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clergy Password Self-Service - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        :root {
            --primary: #800020;
            --primary-hover: #5c0017;
            --accent: #fbbf24;
            --bg-dark: #0f172a;
            --bg-card: rgba(30, 41, 59, 0.95);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
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
            background-image: radial-gradient(rgba(128, 0, 32, 0.25) 1px, transparent 1px), radial-gradient(rgba(251, 191, 36, 0.15) 1px, #0b1120 1px);
            background-size: 40px 40px;
            background-position: 0 0, 20px 20px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .recovery-card {
            width: 100%;
            max-width: 480px;
            padding: 2.5rem;
            background: var(--bg-card);
            border-radius: 1.5rem;
            border: 1px solid rgba(251, 191, 36, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(15px);
        }

        .header-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(128, 0, 32, 0.4), rgba(251, 191, 36, 0.3));
            border: 1px solid rgba(251, 191, 36, 0.4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .header-section h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.35rem;
        }

        .header-section p {
            color: var(--text-muted);
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            line-height: 1.4;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--error);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid var(--success);
            color: #6ee7b7;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
            color: #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.875rem 1rem;
            background: #090e17;
            border: 1px solid #334155;
            border-radius: 0.75rem;
            color: white;
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.15);
        }

        .password-field {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .password-toggle-btn:hover {
            color: var(--accent);
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #800020, #a01030);
            color: white;
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 1.5rem;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #950025, #b81538);
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(128, 0, 32, 0.4);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.25rem 0;
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #334155;
        }
        .divider:not(:empty)::before { margin-right: 0.75em; }
        .divider:not(:empty)::after { margin-left: 0.75em; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: var(--accent);
        }
    </style>
</head>
<body>

    <div class="recovery-card">
        <div class="header-section">
            <div class="header-icon">
                <ion-icon name="key-outline"></ion-icon>
            </div>
            <h1>Clergy Password Self-Service</h1>
            <p>Priest Autonomous Recovery &bull; Catholic Diocese of Hwange</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>Attention:</strong> <?php echo h($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($reset_done): ?>
            <div class="alert alert-success" style="text-align: center;">
                <ion-icon name="checkmark-circle-outline" style="font-size: 2.2rem; color: #10b981; display: block; margin: 0 auto 0.5rem;"></ion-icon>
                <h3 style="color: white; margin-bottom: 0.5rem;">Password Updated!</h3>
                <p style="color: #cbd5e1; font-size: 0.9rem; margin-bottom: 1.25rem;">
                    Your new password has been successfully saved for <strong><?php echo h($target_user_name); ?></strong>. You can now access your mission dashboard directly.
                </p>
                <a href="index.php" class="btn-submit" style="text-decoration: none; display: inline-flex; width: auto; padding: 0.75rem 2rem;">
                    <ion-icon name="log-in-outline"></ion-icon>
                    Sign In to the Mission
                </a>
            </div>
        <?php else: ?>
            <form method="POST" id="reset-form">
                
                <div class="form-group">
                    <label for="cleric_id">Select Cleric / Mission (Quick-Select)</label>
                    <select id="cleric_id" name="cleric_id" onchange="onClericSelect(this)">
                        <option value="">-- Choose your Clergy account from the list --</option>
                        <?php foreach ($clergy_list as $c): 
                            $parish_label = $c['parish_name'] ? " ({$c['parish_name']})" : "";
                            $clean_name = str_ireplace(['Mnkmba', 'Mnkamba'], 'Nkamba', $c['full_name']);
                        ?>
                            <option value="<?php echo $c['user_id']; ?>" data-username="<?php echo h($c['username']); ?>">
                                <?php echo h($clean_name . $parish_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="divider">OR TYPE CREDENTIALS</div>

                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" placeholder="e.g. nicolas_nkamba or your email" autocapitalize="none" autocorrect="off" spellcheck="false">
                </div>

                <div class="divider">SET NEW PASSWORD</div>

                <div class="form-group">
                    <label for="password">New Password (Min. 8 Characters)</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" required minlength="8" placeholder="••••••••">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)">
                            <ion-icon name="eye-outline"></ion-icon>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="••••••••">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('confirm_password', this)">
                            <ion-icon name="eye-outline"></ion-icon>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <ion-icon name="shield-checkmark-outline"></ion-icon>
                    Change & Save Password
                </button>
            </form>
        <?php endif; ?>

        <div style="text-align: center;">
            <a href="index.php" class="back-link">
                <ion-icon name="arrow-back-outline"></ion-icon>
                Return to Mission Sign-In
            </a>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const icon = btn.querySelector('ion-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('name', 'eye-off-outline');
            } else {
                input.type = 'password';
                icon.setAttribute('name', 'eye-outline');
            }
        }

        function onClericSelect(select) {
            const selected = select.options[select.selectedIndex];
            const username = selected.getAttribute('data-username');
            const usernameInput = document.getElementById('username');
            if (username) {
                usernameInput.value = username;
            }
        }
    </script>
</body>
</html>
