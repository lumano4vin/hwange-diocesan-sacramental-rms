<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * User Management - Edit Account
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page - Only Admins can manage users
require_admin();

$id = $_GET['id'] ?? null;
if (!$id) {
    set_flash("User ID required.", "error");
    header("Location: users_list.php");
    exit;
}

// Fetch user details
$user = db_fetch("SELECT * FROM users WHERE user_id = ?", [$id]);
if (!$user) {
    set_flash("The user account you are trying to edit could not be found.", "error");
    header("Location: users_list.php");
    exit;
}

// Fetch Parishes for dropdown
$parishes = db_fetchAll("SELECT parish_id as id, parish_name as name FROM parishes ORDER BY parish_name");

// Handle flash messages
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - <?php echo h($user['full_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.5">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0;">
                <div style="max-width: 800px; margin: 0 auto;">

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <div>
                            <h2 style="font-family: 'Outfit'; color: white; margin: 0;">Edit Account</h2>
                            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 4px 0 0;">Updating credentials for <strong style="color: var(--accent);"><?php echo h($user['full_name']); ?></strong></p>
                        </div>
                        <a href="users_list.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px;">
                            <ion-icon name="arrow-back-outline"></ion-icon> Back to Staff List
                        </a>
                    </div>

                    <?php if ($flash): ?>
                        <div style="background: rgba(<?php echo $flash['type'] === 'success' ? '16,185,129' : '239,68,68'; ?>, 0.1); border: 1px solid <?php echo $flash['type'] === 'success' ? '#10b981' : '#ef4444'; ?>; color: <?php echo $flash['type'] === 'success' ? '#10b981' : '#ef4444'; ?>; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;">
                            <?php echo $flash['message']; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card bg-card" style="padding: 2.5rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05);">
                        <form method="POST" action="../actions/update_user.php">
                            <input type="hidden" name="user_id" value="<?php echo h($user['user_id']); ?>">

                            <!-- Row 1: Name + Email -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div>
                                    <label style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; margin-bottom: 0.4rem; display: block;">Full Name *</label>
                                    <input type="text" name="full_name" value="<?php echo h($user['full_name']); ?>" required
                                        style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; box-sizing: border-box;">
                                </div>
                                <div>
                                    <label style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; margin-bottom: 0.4rem; display: block;">Email Address</label>
                                    <input type="email" name="email" value="<?php echo h($user['email'] ?? ''); ?>"
                                        style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; box-sizing: border-box;">
                                </div>
                            </div>

                            <!-- Row 2: Username + Role -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div>
                                    <label style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; margin-bottom: 0.4rem; display: block;">Username *</label>
                                    <input type="text" name="username" value="<?php echo h($user['username']); ?>" required
                                        style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; box-sizing: border-box;">
                                </div>
                                <div>
                                    <label style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; margin-bottom: 0.4rem; display: block;">Security Role *</label>
                                    <select name="role" style="width: 100%; padding: 0.8rem; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; box-sizing: border-box;">
                                        <option value="admin"      <?php echo $user['role'] === 'admin'      ? 'selected' : ''; ?>>Diocesan Admin (Full Rights)</option>
                                        <option value="chancellor" <?php echo $user['role'] === 'chancellor' ? 'selected' : ''; ?>>Chancellor (Full Rights)</option>
                                        <option value="priest"     <?php echo $user['role'] === 'priest'     ? 'selected' : ''; ?>>Priest-in-Charge (Parish Rights)</option>
                                        <option value="deacon"     <?php echo $user['role'] === 'deacon'     ? 'selected' : ''; ?>>Deacon (Parish Entry Rights)</option>
                                        <option value="secretary"  <?php echo $user['role'] === 'secretary'  ? 'selected' : ''; ?>>Parish Secretary (Parish Entry Rights)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Row 3: Parish + Status -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div>
                                    <label style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; margin-bottom: 0.4rem; display: block;">Assigned Parish / Mission</label>
                                    <select name="parish_id" style="width: 100%; padding: 0.8rem; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; box-sizing: border-box;">
                                        <option value="">-- None / Diocesan Level --</option>
                                        <?php foreach ($parishes as $p): ?>
                                            <option value="<?php echo $p['id']; ?>" <?php echo $user['parish_id'] == $p['id'] ? 'selected' : ''; ?>>
                                                <?php echo h($p['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; margin-bottom: 0.4rem; display: block;">Account Status</label>
                                    <select name="status" style="width: 100%; padding: 0.8rem; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; box-sizing: border-box;">
                                        <option value="Active"   <?php echo ($user['status'] ?? 'Active') === 'Active'   ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo ($user['status'] ?? 'Active') === 'Inactive' ? 'selected' : ''; ?>>Inactive / Suspended</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Optional: Reset Password -->
                            <div style="margin-bottom: 1.5rem; padding: 1.25rem; background: rgba(56,189,248,0.05); border: 1px dashed rgba(56,189,248,0.3); border-radius: 10px;">
                                <label style="color: #38bdf8; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.4rem; display: block;">
                                    <ion-icon name="lock-closed-outline" style="vertical-align: middle;"></ion-icon>
                                    New Password (leave blank to keep current)
                                </label>
                                <div class="password-wrapper">
                                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password to reset, or leave blank"
                                        style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; box-sizing: border-box;">
                                    <span class="toggle-password" onclick="togglePasswordVisibility('new_password', this)">
                                        <ion-icon name="eye-outline"></ion-icon>
                                    </span>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1.5rem; text-align: right;">
                                <button type="submit" class="btn btn-primary" style="padding: 1rem 2.5rem; font-weight: 800;">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
