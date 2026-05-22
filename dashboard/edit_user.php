<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Edit User / Administration
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page (Admin Only)
require_role('admin');

$id = $_GET['id'] ?? 0;
$user = db_fetch("SELECT u.*, p.parish_name FROM users u LEFT JOIN parishes p ON u.parish_id = p.parish_id WHERE u.user_id = ?", [$id]);

if (!$id || !$user) {
    header("Location: users.php");
    exit;
}

$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff - Hwange Diocese RMS</title>
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
            
            <header class="content-header">
                <div class="welcome-text">
                    <h2>Edit Staff Member</h2>
                    <p>Update access levels or reset the password for <strong><?php echo h($user['full_name']); ?></strong>.</p>
                </div>
                <div class="header-actions">
                    <a href="users.php" class="btn btn-secondary">
                        <ion-icon name="arrow-back-outline"></ion-icon>
                        Back to List
                    </a>
                </div>
            </header>

            <div class="dashboard-grid single-column">
                
                <div class="card bg-card">
                    <div class="card-header">
                        <h3><ion-icon name="person-outline"></ion-icon> Staff Information</h3>
                    </div>
                    <div class="card-body">
                        <form action="../actions/update_user.php" method="POST" class="entry-form">
                            <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="full_name" value="<?php echo h($user['full_name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Username (used for login)</label>
                                    <input type="text" name="username" value="<?php echo h($user['username']); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" value="<?php echo h($user['email']); ?>" placeholder="staff@hwangediocese.org">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>System Role</label>
                                    <select name="role" required>
                                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                        <option value="priest" <?php echo $user['role'] == 'priest' ? 'selected' : ''; ?>>Priest In Charge</option>
                                        <option value="deacon" <?php echo $user['role'] == 'deacon' ? 'selected' : ''; ?>>Deacon</option>
                                        <option value="secretary" <?php echo $user['role'] == 'secretary' ? 'selected' : ''; ?>>Parish Secretary</option>
                                    </select>
                                </div>
                                <?php
                                $parish_options = array_map(function($p) {
                                    return ['value' => (string)$p['parish_id'], 'text' => $p['parish_name']];
                                }, $parishes);
                                array_unshift($parish_options, ['value' => '', 'text' => 'Chancery / Diocesan Level']);
                                $options_json = htmlspecialchars(json_encode($parish_options), ENT_QUOTES, 'UTF-8');
                                $current_parish_name = $user['parish_name'] ?: 'Chancery / Diocesan Level';
                                ?>
                                <div class="form-group">
                                    <label>Assigned Parish / Mission</label>
                                    <div class="searchable-select-container">
                                        <input type="text" class="searchable-input" placeholder="Type to search..." 
                                               data-options='<?php echo $options_json; ?>'
                                               autocomplete="off" value="<?php echo h($current_parish_name); ?>">
                                        <input type="hidden" name="parish_id" value="<?php echo h($user['parish_id']); ?>">
                                        <div class="search-select-results"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Canonical Status</label>
                                    <select name="status" required>
                                        <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Currently Serving (Active)</option>
                                        <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>Transferred / Retired (Inactive)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <!-- Placeholder for alignment or future fields -->
                                </div>
                            </div>

                            <hr class="form-divider">

                            <div class="password-reset-section card-highlight">
                                <h3><ion-icon name="key-outline"></ion-icon> Administrative Password Reset</h3>
                                <p class="text-muted">Enter a new password below to reset this user's password. Leave blank if you don't wish to change it.</p>
                                
                                <div class="form-group" style="margin-top: 1rem;">
                                    <label>New Password / Pin</label>
                                    <div class="password-input-group">
                                        <input type="password" id="new_password" name="new_password" placeholder="Min. 8 characters" minlength="8">
                                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('new_password', this)" tabindex="-1">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions" style="margin-top: 2rem;">
                                <button type="submit" class="btn btn-primary btn-large">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Update Staff Rights
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <?php if (isset($_SESSION['flash'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showToast("<?php echo $_SESSION['flash']['message']; ?>", "<?php echo $_SESSION['flash']['type']; ?>");
        });
    </script>
    <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
    <style>
        .single-column { max-width: 900px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.8rem; background: #0f172a; border: 1px solid #334155; border-radius: 0.5rem; color: white; }
        .form-divider { border: 0; border-top: 1px solid #334155; margin: 2rem 0; }
        .card-highlight { background: rgba(99, 102, 241, 0.05); padding: 1.5rem; border-radius: 0.75rem; border-left: 4px solid var(--accent); }
        .card-highlight h3 { display: flex; align-items: center; gap: 0.75rem; color: var(--accent); font-size: 1rem; margin-bottom: 0.5rem; }
        .btn-large { width: 100%; padding: 1.25rem; font-size: 1.1rem; }
    </style>
</body>
</html>
