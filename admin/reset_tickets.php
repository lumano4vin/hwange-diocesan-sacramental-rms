<?php
/**
 * Admin - Password Reset Tickets Management
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();
if (!isDiocesanAdmin()) {
    die("Access Denied.");
}

$pdo = getDB();
$success_msg = '';
$temp_pass = '';

// Handle Resolve Ticket
if (isset($_POST['action']) && $_POST['action'] === 'resolve') {
    $ticket_id = $_POST['ticket_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    
    if ($ticket_id && $user_id) {
        $temp_pass = generateTempPassword();
        $hash = password_hash($temp_pass, PASSWORD_DEFAULT);
        
        // Update user: set temp password and flag for change
        $u_stmt = $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?");
        $u_stmt->execute([$hash, $user_id]);
        
        // Resolve ticket
        $t_stmt = $pdo->prepare("UPDATE password_reset_tickets SET status = 'Resolved', resolved_at = NOW() WHERE id = ?");
        $t_stmt->execute([$ticket_id]);
        
        $success_msg = "Ticket resolved! New temporary password generated.";
    }
}

// Fetch Pending Tickets
$tickets = $pdo->query("
    SELECT t.*, u.full_name, u.email, u.username, u.role
    FROM password_reset_tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.status = 'Pending'
    ORDER BY t.request_time ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Tickets - Hwange SRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="logo-container"><h2>HWANGE SRMS</h2></div>
            <nav>
                <ul class="nav-links">
                    <li class="nav-item"><a href="../dashboard/index.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="users_list.php" class="nav-link"><i class="fa-solid fa-user-shield"></i> Users</a></li>
                    <li class="nav-item"><a href="reset_tickets.php" class="nav-link active"><i class="fa-solid fa-ticket"></i> Reset Tickets</a></li>
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link" style="color: #f56565;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header>
                <h1>Password Reset Tickets</h1>
                <p style="color: var(--text-muted);">Diocesan queue for forgotten password requests.</p>
            </header>

            <?php if ($success_msg): ?>
                <div style="background: var(--success); color: white; padding: 2rem; border-radius: var(--radius-md); margin-bottom: 2rem; text-align: center;">
                    <i class="fa-solid fa-key" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <h2 style="margin: 0;"><?= $success_msg ?></h2>
                    <p style="margin-top: 1rem; font-size: 1.25rem;">
                        New Temp Password: <strong style="background: rgba(0,0,0,0.2); padding: 5px 10px; border-radius: 4px; font-family: monospace;"><?= $temp_pass ?></strong>
                    </p>
                    <p style="font-size: 0.875rem; opacity: 0.9;">Communicate this code to the user. They will be forced to change it on their next login.</p>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <?php if (empty($tickets)): ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 2rem;">No pending reset tickets found.</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid var(--border-color);">
                                <th style="padding: 1rem 0.5rem; color: var(--text-muted);">Requester</th>
                                <th style="padding: 1rem 0.5rem; color: var(--text-muted);">Role</th>
                                <th style="padding: 1rem 0.5rem; color: var(--text-muted);">Time Requested</th>
                                <th style="padding: 1rem 0.5rem; color: var(--text-muted);">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $t): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 1rem 0.5rem;">
                                        <div style="font-weight: 600;"><?= e($t['full_name']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($t['email']) ?></div>
                                    </td>
                                    <td style="padding: 1rem 0.5rem;"><?= e($t['role']) ?></td>
                                    <td style="padding: 1rem 0.5rem;"><?= formatDate($t['request_time']) ?></td>
                                    <td style="padding: 1rem 0.5rem;">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="resolve">
                                            <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $t['user_id'] ?>">
                                            <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">Generate & Resolve</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
