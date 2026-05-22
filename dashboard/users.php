<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * User / Staff Management
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page (Admin Only)
require_role('admin');

// Handle Search
$search = $_GET['q'] ?? '';
$where = "";
$params = [];

if (!empty($search)) {
    $where = " WHERE u.full_name LIKE ? OR u.username LIKE ? OR u.role LIKE ? ";
    $params = ["%$search%", "%$search%", $search];
}

// Fetch Users with Parish context
$sql = "SELECT u.*, p.parish_name 
        FROM users u
        LEFT JOIN parishes p ON u.parish_id = p.parish_id
        $where
        ORDER BY u.role ASC, u.full_name ASC";

$users = db_fetchAll($sql, $params);

// Fetch Parish count for header alerts
$parish_count = db_fetch("SELECT COUNT(*) as count FROM parishes")['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Header (Unified) -->
            <?php 
                ob_start(); ?>
                <a href="add_user.php" class="btn btn-primary">
                    <ion-icon name="person-add-outline"></ion-icon>
                    Create Staff Account
                </a>
                <?php 
                $additional_header_actions = ob_get_clean();
                $header_title = "Staff & Access Management";
                $header_subtitle = "Manage user accounts and role-based security across the diocese.";
                include '../includes/header.php'; 
            ?>

            <?php if ($parish_count == 0): ?>
            <!-- ... Parish Alert Block (if needed) ... -->
            <?php endif; ?>

            <!-- Search Section -->
            <div class="card bg-card search-card">
                <form action="users.php" method="GET" class="search-form">
                    <div class="search-input-group">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" name="q" value="<?php echo h($search); ?>" placeholder="Search staff by name, username or role...">
                    </div>
                    <button type="submit" class="btn btn-secondary">Run Filter</button>
                </form>
            </div>

            <!-- Records Table -->
            <div class="card bg-card table-card">
                <div class="card-header">
                    <h3>Registered Diocesan Staff</h3>
                </div>
                <div class="card-body no-padding overflow-hidden">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Assigned Parish</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No staff matches found.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($users as $row): ?>
                                <tr class="<?php echo $row['status'] === 'inactive' ? 'inactive-row' : ''; ?>">
                                    <td><strong><?php echo h($row['full_name']); ?></strong></td>
                                    <td><code>@<?php echo h($row['username']); ?></code></td>
                                    <td><span class="role-badge <?php echo $row['role']; ?>"><?php echo h(ucfirst($row['role'])); ?></span></td>
                                    <td><?php echo h($row['parish_name'] ?: 'Diocesan Chancery'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $row['status'] === 'active' ? 'verified' : 'draft'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="table-actions" style="text-align: right;">
                                        <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="action-btn edit" title="Edit Permissions">
                                            <ion-icon name="key-outline"></ion-icon>
                                        </a>
                                        
                                        <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                                            <a href="../actions/toggle_user_status.php?id=<?php echo $row['user_id']; ?>" 
                                               class="action-btn <?php echo $row['status'] === 'active' ? 'delete' : 'edit'; ?>" 
                                               style="background: <?php echo $row['status'] === 'active' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; color: <?php echo $row['status'] === 'active' ? '#ef4444' : '#10b981'; ?>;"
                                               onclick="return confirm('Do you want to <?php echo $row['status'] === 'active' ? 'DEACTIVATE' : 'REACTIVATE'; ?> this account?');"
                                               title="<?php echo $row['status'] === 'active' ? 'Deactivate Account' : 'Reactivate Account'; ?>">
                                                <ion-icon name="<?php echo $row['status'] === 'active' ? 'person-remove-outline' : 'person-add-outline'; ?>"></ion-icon>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        .role-badge { padding: 0.25rem 0.75rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .role-badge.admin { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        .role-badge.priest { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
        .role-badge.deacon { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.2); }
        .role-badge.secretary { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        code { color: var(--accent); background: rgba(99, 102, 241, 0.05); padding: 0.2rem 0.4rem; border-radius: 0.3rem; }
    </style>
</body>
</html>
