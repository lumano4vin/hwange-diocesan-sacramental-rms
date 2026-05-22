<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Diocesan Oversight Portal - Bishop's High-Level View
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page - Only Bishop (observer), Chancellor, or Admin can view the Oversight Portal
if (!in_array($_SESSION['role'], ['admin', 'chancellor', 'observer'])) {
    header("Location: index.php?error=" . urlencode("Access Denied: The Oversight Portal is reserved for Episcopal and Chancery leadership."));
    exit;
}

// Header metadata
$header_title = "Diocesan Oversight Portal";
$header_subtitle = "High-level canonical oversight and analytics for the Ordinary.";

// --- 1. Fetch Global Statistics ---
$totals = [
    'parishioners' => db_fetch("SELECT COUNT(*) as count FROM parishioners")['count'] ?? 0,
    'baptisms' => db_fetch("SELECT COUNT(*) as count FROM baptisms WHERE status = 'Verified'")['count'] ?? 0,
    'marriages' => db_fetch("SELECT COUNT(*) as count FROM marriages WHERE status = 'Verified'")['count'] ?? 0,
    'parishes' => db_fetch("SELECT COUNT(*) as count FROM parishes")['count'] ?? 0,
    'drafts' => 0
];

// Calculate total pending (drafts) across the diocese
$tables = ['baptisms', 'marriages', 'confirmations', 'deaths', 'ordinations_professions'];
foreach ($tables as $table) {
    $totals['drafts'] += db_fetch("SELECT COUNT(*) as count FROM $table WHERE status = 'Draft'")['count'] ?? 0;
}

// --- 2. Deanery Activity Heatmap ---
$deaneries_raw = db_fetchAll("SELECT DISTINCT deanery FROM parishes WHERE deanery IS NOT NULL ORDER BY deanery");
$heatmap_data = [];
$max_activity = 1;
foreach ($deaneries_raw as $d) {
    $d_name = $d['deanery'];
    $sql = "
        SELECT COUNT(*) as count FROM (
            SELECT b.parish_id FROM baptisms b JOIN parishes p ON b.parish_id = p.parish_id WHERE p.deanery = ?
            UNION ALL
            SELECT m.parish_id FROM marriages m JOIN parishes p ON m.parish_id = p.parish_id WHERE p.deanery = ?
            UNION ALL
            SELECT c.parish_id FROM confirmations c JOIN parishes p ON c.parish_id = p.parish_id WHERE p.deanery = ?
        ) t
    ";
    $count = db_fetch($sql, [$d_name, $d_name, $d_name])['count'] ?? 0;
    $heatmap_data[$d_name] = $count;
    if ($count > $max_activity) $max_activity = $count;
}

// --- 3. Recent Canonical Events ---
$mission_feed = db_fetchAll("
    SELECT 'Baptism' as type, p.first_name || ' ' || p.last_name as name, b.date_of_baptism as date, pr.parish_name, 'water-outline' as icon, '#38bdf8' as color
    FROM baptisms b JOIN parishioners p ON b.person_id = p.person_id JOIN parishes pr ON b.parish_id = pr.parish_id
    UNION ALL
    SELECT 'Marriage' as type, p1.first_name || ' & ' || p2.first_name as name, m.date_of_marriage as date, pr.parish_name, 'heart-outline' as icon, '#f472b6' as color
    FROM marriages m JOIN parishioners p1 ON m.groom_person_id = p1.person_id JOIN parishioners p2 ON m.bride_person_id = p2.person_id JOIN parishes pr ON m.parish_id = pr.parish_id
    ORDER BY date DESC LIMIT 8
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oversight Portal - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .oversight-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .oversight-card { 
            background: var(--card-bg); border-radius: 24px; padding: 1.5rem; 
            border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);
        }
        .oversight-val { font-size: 2rem; font-weight: 900; color: white; display: block; margin-top: 0.5rem; }
        .oversight-label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        
        .heatmap-container { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .heatmap-bar { height: 8px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden; margin-top: 8px; }
        .heatmap-fill { height: 100%; background: var(--accent); border-radius: 4px; }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0;">
                
                <!-- 1. Global Totals -->
                <div class="oversight-grid">
                    <div class="oversight-card">
                        <span class="oversight-label">Total Faithful</span>
                        <span class="oversight-val"><?php echo number_format($totals['parishioners']); ?></span>
                    </div>
                    <div class="oversight-card" style="border-left: 4px solid #38bdf8;">
                        <span class="oversight-label">Canonical Baptisms</span>
                        <span class="oversight-val"><?php echo number_format($totals['baptisms']); ?></span>
                    </div>
                    <div class="oversight-card" style="border-left: 4px solid #f472b6;">
                        <span class="oversight-label">Sacramental Marriages</span>
                        <span class="oversight-val"><?php echo number_format($totals['marriages']); ?></span>
                    </div>
                    <div class="oversight-card" style="border-left: 4px solid var(--warning);">
                        <span class="oversight-label">Pending Verifications</span>
                        <span class="oversight-val"><?php echo number_format($totals['drafts']); ?></span>
                    </div>
                </div>

                <div class="heatmap-container">
                    
                    <!-- 2. Deanery Distribution -->
                    <div class="card bg-card" style="border-radius: 24px;">
                        <h3 style="font-family: 'Outfit'; color: white; margin-bottom: 1.5rem;">Deanery Activity Heatmap</h3>
                        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                            <?php foreach ($heatmap_data as $name => $count): 
                                $percent = ($count / $max_activity) * 100;
                            ?>
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-size: 0.9rem; font-weight: 600; color: white;"><?php echo h($name); ?> Deanery</span>
                                        <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo number_format($count); ?> Events</span>
                                    </div>
                                    <div class="heatmap-bar">
                                        <div class="heatmap-fill" style="width: <?php echo $percent; ?>%; background: <?php echo $percent > 70 ? '#10b981' : ($percent > 30 ? '#38bdf8' : '#6366f1'); ?>;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 3. Diocesan Mission Feed -->
                    <div class="card bg-card" style="border-radius: 24px;">
                        <h3 style="font-family: 'Outfit'; color: white; margin-bottom: 1.5rem;">Recent Sacramental Events</h3>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($mission_feed as $item): ?>
                                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px solid rgba(255,255,255,0.03);">
                                    <div style="width: 40px; height: 40px; border-radius: 12px; background: <?php echo $item['color']; ?>20; color: <?php echo $item['color']; ?>; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                        <ion-icon name="<?php echo $item['icon']; ?>"></ion-icon>
                                    </div>
                                    <div style="flex: 1;">
                                        <span style="display: block; font-size: 0.85rem; font-weight: 700; color: white;"><?php echo h($item['name']); ?></span>
                                        <span style="font-size: 0.7rem; color: var(--text-muted);"><?php echo h($item['type']); ?> • <?php echo h($item['parish_name']); ?></span>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="font-size: 0.7rem; color: var(--text-muted);"><?php echo date('d M Y', strtotime($item['date'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="../sacraments.php" class="btn btn-secondary" style="width: 100%; margin-top: 1.5rem; font-size: 0.8rem; text-align: center;">View All Registries</a>
                    </div>

                    <!-- Mission Transitions (Handover Tracking) -->
                    <div class="card bg-card" style="margin-top: 1.5rem;">
                        <h3 style="color: white; font-family: 'Outfit'; margin-bottom: 1.5rem; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                            <ion-icon name="swap-horizontal-outline" style="color: var(--accent);"></ion-icon>
                            Mission Transitions
                        </h3>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php 
                            $transitions = db_fetchAll("
                                SELECT h.*, p.parish_name, u_out.full_name as outgoing, u_in.full_name as incoming
                                FROM parish_handovers h
                                JOIN parishes p ON h.parish_id = p.parish_id
                                LEFT JOIN users u_out ON h.outgoing_priest_id = u_out.user_id
                                LEFT JOIN users u_in ON h.incoming_priest_id = u_in.user_id
                                WHERE h.status != 'Completed'
                                ORDER BY h.created_at DESC
                                LIMIT 5
                            ");
                            
                            if (empty($transitions)): ?>
                                <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 1rem;">All missions stable.</p>
                            <?php else: foreach ($transitions as $t): ?>
                                <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                        <span style="font-weight: 700; color: white; font-size: 0.85rem;"><?php echo h($t['parish_name']); ?></span>
                                        <span class="status-pill <?php echo strtolower($t['status']); ?>" style="font-size: 0.6rem;"><?php echo $t['status']; ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 10px; font-size: 0.75rem; color: var(--text-muted);">
                                        <span><?php echo h($t['outgoing'] ?: 'Chancery'); ?></span>
                                        <ion-icon name="arrow-forward-outline"></ion-icon>
                                        <span><?php echo h($t['incoming']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                </div>

                </div>
                
                <?php include '../includes/privacy_footer.php'; ?>

            </div>
        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
