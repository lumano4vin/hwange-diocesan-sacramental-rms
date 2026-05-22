<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * User Management List - Modernized
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page - Only Admins can manage users
require_admin();

// Header metadata
$header_title = "User Management";
$header_subtitle = "Manage Diocesan staff, Clergy, and Parish Secretary accounts.";

// Handle Deactivation
if (isset($_GET['deactivate'])) {
    $id = $_GET['deactivate'];
    if ($id != $_SESSION['user_id']) {
        db_query("UPDATE users SET status = 'Inactive' WHERE user_id = ?", [$id]);
        set_flash("User account has been deactivated.", "warning");
    }
    header("Location: users_list.php"); exit;
}

// Handle Permanent Deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $target = db_fetch("SELECT role, full_name FROM users WHERE user_id = ?", [$id]);
    if ($target && $id != $_SESSION['user_id'] && $target['role'] !== 'admin') {
        db_query("DELETE FROM users WHERE user_id = ?", [$id]);
        set_flash("Account for <b>" . h($target['full_name']) . "</b> has been permanently deleted.", "warning");
    } else {
        set_flash("This account cannot be deleted.", "error");
    }
    header("Location: users_list.php"); exit;
}

// Fetch all users with parish names
$users = db_fetchAll("
    SELECT u.*, pa.parish_name 
    FROM users u 
    LEFT JOIN parishes pa ON u.parish_id = pa.parish_id 
    ORDER BY u.role, u.username
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=1.1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Header -->
            <?php include '../includes/header.php'; ?>
            
            <div class="content-body" style="padding: 2rem 0;">
                
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 2rem;">
                    <div class="stats-brief" style="display: flex; align-items: center; gap: 2rem;">
                        <div class="stat-mini">
                            <span style="font-size: 1.5rem; font-weight: 900; color: var(--accent);"><?php echo count($users); ?></span>
                            <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-left: 8px;">Total Users</span>
                        </div>
                        <!-- Search Engine -->
                        <div class="search-wrapper" style="position: relative; width: 350px;">
                            <ion-icon name="search-outline" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1.2rem;"></ion-icon>
                            <input type="text" id="userSearch" placeholder="Search by name, username, or email..." 
                                style="width: 100%; padding: 12px 15px 12px 45px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-family: 'Inter'; font-size: 0.9rem; outline: none; transition: all 0.3s ease;">
                        </div>
                    </div>
                    <a href="../dashboard/add_user.php" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px; font-weight: 800; white-space: nowrap;">
                        <ion-icon name="person-add-outline"></ion-icon> Create New Account
                    </a>
                </div>

                <div class="card bg-card no-padding overflow-hidden" style="border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                    <div class="table-responsive">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.02); text-align: left;">
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">User Identity</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Security Role</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Assignment</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Last Active</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Status</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.03); transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                        <td style="padding: 1.25rem 2rem;">
                                            <div style="font-weight: 700; color: white;"><?php echo h($user['full_name']); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--accent);"><?php echo h($user['username']); ?> • <?php echo h($user['email']); ?></div>
                                        </td>
                                        <td style="padding: 1.25rem 2rem;">
                                            <span style="font-size: 0.8rem; font-weight: 600; color: white; background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 6px; text-transform: capitalize;">
                                                <?php echo h($user['role']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1.25rem 2rem; font-size: 0.9rem; color: var(--text-muted);">
                                            <?php echo $user['parish_name'] ? h($user['parish_name']) : 'Diocesan Curia'; ?>
                                        </td>
                                        <td style="padding: 1.25rem 2rem; font-size: 0.85rem; color: var(--text-muted);">
                                            <?php echo $user['last_login'] ? date('d M, H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                        </td>
                                        <td style="padding: 1.25rem 2rem;">
                                            <?php if (($user['status'] ?? 'Active') === 'Active'): ?>
                                                <span style="color: #10b981; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; display: flex; align-items: center; gap: 4px;">
                                                    <span style="width: 6px; height: 6px; background: #10b981; border-radius: 50%;"></span> Active
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #ef4444; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Inactive</span>
                                            <?php endif; ?>
                                         <td style="padding: 1.25rem 2rem;">
                                            <div style="display: flex; gap: 0.75rem; align-items: center;">
                                                <a href="users_edit.php?id=<?php echo $user['user_id']; ?>" title="Edit User" style="color: var(--accent); font-size: 1.25rem;"><ion-icon name="create-outline"></ion-icon></a>
                                                <?php if ($user['user_id'] != $_SESSION['user_id'] && $user['role'] !== 'admin'): ?>
                                                    <a href="?deactivate=<?php echo $user['user_id']; ?>" onclick="return confirm('Deactivate this account?')" title="Deactivate" style="color: #f59e0b; font-size: 1.25rem;"><ion-icon name="ban-outline"></ion-icon></a>
                                                    <a href="?delete=<?php echo $user['user_id']; ?>" onclick="return confirm('PERMANENTLY delete the account for &quot;<?php echo h($user['full_name']); ?>&quot;? This cannot be undone.')" title="Delete Permanently" style="color: #ef4444; font-size: 1.25rem;"><ion-icon name="trash-outline"></ion-icon></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php include '../includes/privacy_footer.php'; ?>
        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <script>
        document.getElementById('userSearch').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Add focus effect to search input
        const searchInput = document.getElementById('userSearch');
        if (searchInput) {
            searchInput.addEventListener('focus', () => {
                searchInput.style.borderColor = 'var(--accent)';
                searchInput.style.background = 'rgba(255,255,255,0.08)';
                searchInput.style.boxShadow = '0 0 15px rgba(56, 189, 248, 0.1)';
            });
            searchInput.addEventListener('blur', () => {
                searchInput.style.borderColor = 'rgba(255,255,255,0.1)';
                searchInput.style.background = 'rgba(255,255,255,0.05)';
                searchInput.style.boxShadow = 'none';
            });
        }
    </script>
</body>
</html>
