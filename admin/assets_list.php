<?php
/**
 * Parish Asset Inventory List
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$pdo = getDB();

$stmt = $pdo->query("
    SELECT a.*, pa.name as parish_name 
    FROM parish_assets a 
    JOIN parishes pa ON a.parish_id = pa.id 
    ORDER BY a.item_name ASC
");
$records = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Inventory - Hwange SRMS</title>
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
                    <li class="nav-item"><a href="minutes_list.php" class="nav-link"><i class="fa-solid fa-file-lines"></i> Minutes</a></li>
                    <li class="nav-item"><a href="assets_list.php" class="nav-link active"><i class="fa-solid fa-boxes-stacked"></i> Assets</a></li>
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link" style="color: #f56565;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header>
                <div class="page-title">
                    <h1>Parish Asset Inventory</h1>
                    <p style="color: var(--text-muted);">Tracking and management of physical property across the diocese.</p>
                </div>
                <a href="assets_add.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Asset</a>
            </header>

            <div class="form-card">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid var(--border-color);">
                            <th style="padding: 1rem 0.5rem; font-size: 0.875rem; color: var(--text-muted);">Item Name</th>
                            <th style="padding: 1rem 0.5rem; font-size: 0.875rem; color: var(--text-muted);">Category</th>
                            <th style="padding: 1rem 0.5rem; font-size: 0.875rem; color: var(--text-muted);">Parish</th>
                            <th style="padding: 1rem 0.5rem; font-size: 0.875rem; color: var(--text-muted);">Qty</th>
                            <th style="padding: 1rem 0.5rem; font-size: 0.875rem; color: var(--text-muted);">Status</th>
                            <th style="padding: 1rem 0.5rem; font-size: 0.875rem; color: var(--text-muted);">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-muted);">No assets found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $row): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 1rem 0.5rem; font-weight: 500;">
                                        <?= e($row['item_name']) ?>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($row['category']) ?></div>
                                    </td>
                                    <td style="padding: 1rem 0.5rem;"><?= e($row['category']) ?></td>
                                    <td style="padding: 1rem 0.5rem;"><?= e($row['parish_name']) ?></td>
                                    <td style="padding: 1rem 0.5rem;"><?= e($row['quantity']) ?></td>
                                    <td style="padding: 1rem 0.5rem;">
                                        <?php 
                                            $color = 'var(--success)';
                                            if ($row['current_status'] == 'Broken') $color = 'var(--danger)';
                                            if ($row['current_status'] == 'Poor' || $row['current_status'] == 'Fair') $color = '#ed8936';
                                        ?>
                                        <span style="color: <?= $color ?>; font-weight: 600; font-size: 0.875rem;"><?= e($row['current_status']) ?></span>
                                    </td>
                                    <td style="padding: 1rem 0.5rem;"><?= $row['estimated_value'] ? '$' . number_format($row['estimated_value'], 2) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
