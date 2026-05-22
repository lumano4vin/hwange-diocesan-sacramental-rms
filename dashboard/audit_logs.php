<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Archival Audit Trail Viewer
 * 
 * Provides a secure, chronological log of all record modifications.
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page - ADMIN ONLY
require_role('admin');

$sql = "SELECT a.*, u.full_name, u.username 
        FROM audit_logs a
        JOIN users u ON a.user_id = u.user_id
        ORDER BY a.created_at DESC
        LIMIT 100";
$logs = db_fetchAll($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=1.1">
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
            
            <?php 
                $header_title = "Archival Audit Trail";
                $header_subtitle = "Chronological record of all system modifications and canonical entries.";
                include '../includes/header.php'; 
            ?>

            <div class="card bg-card">
                <div class="card-header">
                    <h3><ion-icon name="list-outline"></ion-icon> Recent System Activity</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Administrator</th>
                                    <th>Action</th>
                                    <th>Registry</th>
                                    <th>Record ID</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 2rem;">No audit logs recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td style="white-space: nowrap;"><?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?></td>
                                            <td>
                                                <div style="font-weight: 600;"><?php echo h($log['full_name']); ?></div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);">@<?php echo h($log['username']); ?></div>
                                            </td>
                                            <td>
                                                <span class="badge" style="background: <?php echo ($log['action_type'] === 'CREATE' ? 'rgba(16, 185, 129, 0.2)' : ($log['action_type'] === 'BACKUP' ? 'rgba(56, 189, 248, 0.2)' : 'rgba(148, 163, 184, 0.2)')); ?>; 
                                                                             color: <?php echo ($log['action_type'] === 'CREATE' ? '#10b981' : ($log['action_type'] === 'BACKUP' ? '#38bdf8' : '#94a3b8')); ?>;">
                                                    <?php echo h($log['action_type']); ?>
                                                </span>
                                            </td>
                                            <td style="text-transform: capitalize; font-weight: 500;"><?php echo h($log['table_name']); ?></td>
                                            <td>#<?php echo h($log['record_id']); ?></td>
                                            <td style="font-size: 0.9rem; color: #cbd5e1;"><?php echo h($log['details']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div style="margin-top: 1.5rem; text-align: right;">
                <p class="text-muted" style="font-size: 0.85rem;">Showing the last 100 archival events.</p>
            </div>

            <?php include '../includes/privacy_footer.php'; ?>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <style>
        .table-container { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th { padding: 1rem 1.5rem; border-bottom: 1px solid #334155; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }
        .data-table td { padding: 1.2rem 1.5rem; border-bottom: 1px solid #1e293b; vertical-align: middle; }
        .badge { padding: 0.35rem 0.75rem; border-radius: 50px; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.5px; }
    </style>
</body>
</html>
