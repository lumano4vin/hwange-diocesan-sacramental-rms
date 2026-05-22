<?php
/**
 * Meeting Minutes List
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$pdo = getDB();

$stmt = $pdo->query("
    SELECT m.*, pa.name as parish_name, u.username 
    FROM meeting_minutes m 
    JOIN parishes pa ON m.parish_id = pa.id 
    LEFT JOIN users u ON m.created_by = u.id 
    ORDER BY m.meeting_date DESC
");
$records = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Minutes - Hwange SRMS</title>
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
                    <li class="nav-item"><a href="minutes_list.php" class="nav-link active"><i class="fa-solid fa-file-lines"></i> Minutes</a></li>
                    <li class="nav-item"><a href="assets_list.php" class="nav-link"><i class="fa-solid fa-boxes-stacked"></i> Assets</a></li>
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link" style="color: #f56565;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header>
                <div class="page-title">
                    <h1>Meeting Minutes & Documents</h1>
                    <p style="color: var(--text-muted);">View and manage parish and diocesan records.</p>
                </div>
                <a href="minutes_add.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Minutes</a>
            </header>

            <div class="form-card">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid var(--border-color);">
                            <th style="padding: 1rem 0.5rem; font-size: 0.875rem; color: var(--text-muted);">Title</th>
                            <th style="padding: 1rem 0.5rem; font-size: 0.875rem; color: var(--text-muted);">Parish</th>
                            <th style="padding: 1rem 0.5rem; font-size: 0.875rem; color: var(--text-muted);">Date</th>
                            <th style="padding: 1rem 0.5rem; font-size: 0.875rem; color: var(--text-muted);">Author</th>
                            <th style="padding: 1rem 0.5rem; font-size: 0.875rem; color: var(--text-muted);">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="5" style="padding: 2rem; text-align: center; color: var(--text-muted);">No minutes found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $row): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 1rem 0.5rem; font-weight: 500;"><?= e($row['title']) ?></td>
                                    <td style="padding: 1rem 0.5rem;"><?= e($row['parish_name']) ?></td>
                                    <td style="padding: 1rem 0.5rem;"><?= formatDate($row['meeting_date']) ?></td>
                                    <td style="padding: 1rem 0.5rem; font-size: 0.875rem;"><?= e($row['username'] ?: 'Admin') ?></td>
                                    <td style="padding: 1rem 0.5rem;">
                                        <a href="minutes_view.php?id=<?= $row['id'] ?>" style="color: var(--primary-color);">View</a>
                                    </td>
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
