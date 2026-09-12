<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Clergy Assignment History View
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_role('admin');

$user_id = $_GET['id'] ?? 0;
$cleric = db_fetch("SELECT full_name, role FROM users WHERE user_id = ?", [$user_id]);

if (!$cleric) {
    die("Cleric not found.");
}

$history = db_fetchAll("
    SELECT pa.*, p.parish_name 
    FROM parish_assignments pa 
    JOIN parishes p ON pa.parish_id = p.parish_id 
    WHERE pa.user_id = ? 
    ORDER BY pa.start_date DESC
", [$user_id]);

$header_title = "Canonical History: " . h($cleric['full_name']);
$header_subtitle = "Historical mission assignments and leadership roles.";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clergy History - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-body" style="padding: 2rem 0;">
                <div class="card bg-card">
                    <h3 style="color: white; font-family: 'Outfit'; margin-bottom: 2rem;">Service Timeline</h3>
                    
                    <?php if (empty($history)): ?>
                        <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
                            <ion-icon name="calendar-clear-outline" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></ion-icon>
                            <p>No historical assignments found for this cleric.</p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                            <?php foreach ($history as $h): ?>
                                <div style="display: flex; gap: 1.5rem; padding: 1.5rem; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px solid rgba(255,255,255,0.05);">
                                    <div style="text-align: right; min-width: 120px;">
                                        <div style="color: white; font-weight: 700;"><?php echo date('M Y', strtotime($h['start_date'])); ?></div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted);">EFFECTIVE DATE</div>
                                    </div>
                                    <div style="flex: 1;">
                                        <h4 style="color: var(--accent); margin-bottom: 4px;"><?php echo h($h['role']); ?></h4>
                                        <p style="color: white; font-weight: 600;"><?php echo h($h['parish_name']); ?></p>
                                    </div>
                                    <div>
                                        <span class="status-pill <?php echo strtolower($h['status']); ?>"><?php echo $h['status']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 2rem;">
                        <a href="../admin/clergy_management.php" class="btn btn-secondary">
                            <ion-icon name="arrow-back-outline"></ion-icon> Back to Management
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
