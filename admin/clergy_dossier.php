<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Clergy Dossier - Canonical Personnel Profile
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_admin();

$user_id = $_GET['id'] ?? null;
if (!$user_id) redirect("clergy_list.php");

// Fetch User and Profile
$cleric = db_fetch("
    SELECT u.*, p.parish_name, cp.*
    FROM users u
    LEFT JOIN parishes p ON u.parish_id = p.parish_id
    LEFT JOIN clergy_profiles cp ON u.user_id = cp.user_id
    WHERE u.user_id = ?
", [$user_id]);

if (!$cleric) die("Cleric not found.");

// CANONICAL PRIVACY: Log access to sensitive dossier
log_sensitive_access('clergy_profiles', $user_id, "Clergy Dossier: " . $cleric['full_name']);

// Fetch Assignment History
$assignments = db_fetchAll("
    SELECT a.*, p.parish_name 
    FROM parish_assignments a
    JOIN parishes p ON a.parish_id = p.parish_id
    WHERE a.user_id = ?
    ORDER BY a.start_date DESC
", [$user_id]);

$header_title = "Clergy Dossier";
$header_subtitle = "Confidential canonical record for " . h($cleric['full_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clergy Dossier - <?php echo h($cleric['full_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=1.1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .dossier-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; margin-top: 2rem; }
        .sidebar-card { background: var(--card-bg); border-radius: 24px; padding: 2rem; border: 1px solid rgba(255,255,255,0.05); height: max-content; }
        .main-card { background: var(--card-bg); border-radius: 24px; padding: 2.5rem; border: 1px solid rgba(255,255,255,0.05); }
        .section-title { font-family: 'Outfit'; font-size: 1.1rem; color: var(--accent); margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.75rem; display: flex; align-items: center; gap: 10px; }
        .data-row { display: flex; justify-content: space-between; margin-bottom: 1rem; border-bottom: 1px dashed rgba(255,255,255,0.03); padding-bottom: 0.5rem; }
        .data-label { color: var(--text-muted); font-size: 0.85rem; }
        .data-value { font-weight: 700; color: white; font-size: 0.9rem; }
        .timeline-item { position: relative; padding-left: 2rem; border-left: 2px solid var(--accent); padding-bottom: 2rem; }
        .timeline-item::before { content: ''; position: absolute; left: -7px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--accent); }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0;">
                
                <div class="action-bar" style="margin-bottom: 2rem; display: flex; justify-content: space-between;">
                    <a href="clergy_list.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px;">
                        <ion-icon name="arrow-back-outline"></ion-icon> Back to Registry
                    </a>
                    <button onclick="window.print()" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px;">
                        <ion-icon name="print-outline"></ion-icon> Export Personnel File
                    </button>
                </div>

                <div class="dossier-grid">
                    <!-- Left Sidebar: Photo & Status -->
                    <div class="sidebar-card" style="text-align: center;">
                        <div style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, var(--accent) 0%, #0369a1 100%); margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #000; font-weight: 900; border: 4px solid rgba(255,255,255,0.1);">
                            <?php echo strtoupper(substr($cleric['full_name'], 0, 1)); ?>
                        </div>
                        <h2 style="font-family: 'Outfit'; color: white; margin-bottom: 0.5rem;"><?php echo h(($cleric['title'] ?: 'Rev. ') . $cleric['full_name']); ?></h2>
                        <div class="status-badge <?php echo strtolower($cleric['faculties_status']) === 'active' ? 'verified' : 'draft'; ?>" style="display: inline-block; margin-bottom: 2rem;">
                            Faculties: <?php echo h($cleric['faculties_status'] ?: 'Inactive'); ?>
                        </div>

                        <div style="text-align: left;">
                            <div class="section-title"><ion-icon name="briefcase-outline"></ion-icon> Canonical Status</div>
                            <div class="data-row">
                                <span class="data-label">Incardination</span>
                                <span class="data-value"><?php echo h($cleric['incardination_status'] ?: 'Unknown'); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Role</span>
                                <span class="data-value"><?php echo h($cleric['role']); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Ordination Date</span>
                                <span class="data-value"><?php echo $cleric['date_of_ordination'] ? date('d M Y', strtotime($cleric['date_of_ordination'])) : 'Pending'; ?></span>
                            </div>
                        </div>

                        <div style="text-align: left; margin-top: 2rem;">
                            <div class="section-title"><ion-icon name="shield-checkmark-outline"></ion-icon> Special Faculties</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;">
                                <?php echo nl2br(h($cleric['special_faculties'] ?: 'No special faculties recorded.')); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content: History & Details -->
                    <div class="main-card">
                        <div class="section-title"><ion-icon name="medal-outline"></ion-icon> Ordination Details</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
                            <div>
                                <div class="data-label">Ordaining Bishop</div>
                                <div class="data-value" style="font-size: 1.1rem; margin-top: 5px;"><?php echo h($cleric['ordaining_bishop'] ?: 'Not Recorded'); ?></div>
                            </div>
                            <div>
                                <div class="data-label">Place of Ordination</div>
                                <div class="data-value" style="font-size: 1.1rem; margin-top: 5px;"><?php echo h($cleric['place_of_ordination'] ?: 'Not Recorded'); ?></div>
                            </div>
                        </div>

                        <div class="section-title"><ion-icon name="time-outline"></ion-icon> Assignment History</div>
                        <div class="assignment-timeline" style="margin-top: 2rem;">
                            <?php if (empty($assignments)): ?>
                                <p style="color: var(--text-muted); font-size: 0.9rem;">No assignment history recorded in the archives.</p>
                            <?php else: ?>
                                <?php foreach ($assignments as $a): ?>
                                    <div class="timeline-item">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                            <div>
                                                <div style="font-weight: 700; color: white; font-size: 1.1rem;"><?php echo h($a['parish_name']); ?></div>
                                                <div style="color: var(--accent); font-size: 0.85rem; font-weight: 600;"><?php echo h($a['role']); ?></div>
                                            </div>
                                            <div style="text-align: right;">
                                                <div style="font-size: 0.8rem; color: white; font-weight: 700;">
                                                    <?php echo date('M Y', strtotime($a['start_date'])); ?> 
                                                    — 
                                                    <?php echo $a['end_date'] ? date('M Y', strtotime($a['end_date'])) : '<span style="color: #10b981;">Present</span>'; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($a['notes']): ?>
                                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 10px; background: rgba(0,0,0,0.2); padding: 0.75rem; border-radius: 10px;">
                                                <?php echo h($a['notes']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="section-title" style="margin-top: 3rem;"><ion-icon name="medkit-outline"></ion-icon> Private Medical & Emergency</div>
                        <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.1); padding: 1.5rem; border-radius: 1rem;">
                            <div class="data-label" style="color: #fca5a5;">Health Observations</div>
                            <div style="color: white; font-size: 0.9rem; margin-top: 5px; margin-bottom: 1.5rem;">
                                <?php echo nl2br(h($cleric['health_notes'] ?: 'No health notes on file.')); ?>
                            </div>
                            
                            <div class="data-label" style="color: #fca5a5;">Emergency Contact</div>
                            <div style="color: white; font-size: 0.9rem; margin-top: 5px;">
                                <?php echo nl2br(h($cleric['emergency_contact'] ?: 'None provided.')); ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
    <script src="../assets/js/main.js?v=1.6.2"></script>
    <?php include '../includes/privacy_footer.php'; ?>
</body>
</html>
