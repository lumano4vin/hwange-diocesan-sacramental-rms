<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parish Registry Management - Modernized
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Parish & Mission Registry";
$header_subtitle = "Management of the official canonical sites of the Hwange Diocese.";

// Fetch all parishes with summary counts
$parishes = db_fetchAll("
    SELECT p.*, 
           (SELECT COUNT(*) FROM parishioners pr WHERE pr.current_parish_id = p.parish_id) as member_count,
           (SELECT COUNT(*) FROM baptisms b WHERE b.parish_id = p.parish_id) as baptism_count
    FROM parishes p 
    ORDER BY deanery, parish_name
");

// Group by Deanery for the UI
$grouped = [];
foreach ($parishes as $p) {
    $grouped[$p['deanery']][] = $p;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parish Registry - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.5">
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
                
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div class="stats-brief" style="display: flex; gap: 2rem;">
                        <div class="stat-mini">
                            <span style="font-size: 1.5rem; font-weight: 900; color: var(--accent);"><?php echo count($parishes); ?></span>
                            <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-left: 8px;">Total Sites</span>
                        </div>
                        <div class="stat-mini">
                            <span style="font-size: 1.5rem; font-weight: 900; color: white;"><?php echo count($grouped); ?></span>
                            <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-left: 8px;">Deaneries</span>
                        </div>
                    </div>
                    <?php if (is_admin()): ?>
                        <div style="display: flex; gap: 10px;">
                            <a href="handover.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px; font-weight: 800; background: rgba(251, 191, 36, 0.1); border-color: rgba(251, 191, 36, 0.3);">
                                <ion-icon name="swap-horizontal-outline"></ion-icon> Canonical Handover
                            </a>
                            <a href="add_parish.php" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px; font-weight: 800;">
                                <ion-icon name="add-circle-outline"></ion-icon> Register New Parish
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php foreach ($grouped as $deanery => $pList): ?>
                    <div class="deanery-section" style="margin-bottom: 3rem;">
                        <div class="deanery-header" style="display: flex; align-items: center; gap: 15px; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid rgba(251, 191, 36, 0.2);">
                            <ion-icon name="map-outline" style="color: var(--accent); font-size: 1.5rem;"></ion-icon>
                            <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; color: white; letter-spacing: -0.5px;">
                                <?php 
                                    $d_name = h($deanery ?: 'Unassigned');
                                    echo (stripos($d_name, 'deanery') === false) ? $d_name . " Deanery" : $d_name;
                                ?>
                            </h2>
                        </div>
                        
                        <div class="parish-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($pList as $p): 
                                $is_admin = ($_SESSION['role'] === 'admin');
                            ?>
                                <div class="parish-card-wrapper" style="position: relative;">
                                    
                                    
                                    <div class="parish-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, var(--bg-card) 60%); border-radius: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2); padding: 1.5rem; transition: all 0.3s; position: relative; overflow: hidden; display: flex; flex-direction: column; gap: 0.5rem; backdrop-filter: blur(20px);">
                                        <div style="font-weight: 800; font-size: 1.1rem; color: white; margin-bottom: 4px;"><?php echo h($p['parish_name']); ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; margin-bottom: 0.5rem;">
                                            <ion-icon name="location-outline" style="color: var(--accent);"></ion-icon>
                                            <?php echo h($p['location']); ?>
                                        </div>

                                        <!-- Live Statistics Row -->
                                        <div class="card-stats-row" style="display: flex; gap: 1rem; margin-bottom: 0.5rem;">
                                            <div style="display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: var(--text-muted);">
                                                <ion-icon name="people" style="color: var(--accent);"></ion-icon>
                                                <span><strong><?php echo number_format($p['member_count']); ?></strong> Members</span>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: var(--text-muted);">
                                                <ion-icon name="water" style="color: #38bdf8;"></ion-icon>
                                                <span><strong><?php echo number_format($p['baptism_count']); ?></strong> Baptisms</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Current Pastor & Timeline Link -->
                                        <div class="pastoral-info" style="margin: 0.5rem 0; padding: 0.75rem; background: rgba(255,255,255,0.03); border-radius: 12px;">
                                            <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Current Pastor</div>
                                            <?php 
                                                // Try to get from History Timeline first
                                                $current_assignment = db_fetch("SELECT priest_name_manual FROM parish_assignments WHERE parish_id = ? AND status = 'Active' LIMIT 1", [$p['parish_id']]);
                                                
                                                if ($current_assignment) {
                                                    $pastor_name = $current_assignment['priest_name_manual'];
                                                } else {
                                                    // Fallback to the primary parish priest link
                                                    $priest = db_fetch("SELECT full_name FROM users WHERE user_id = ?", [$p['priest_in_charge_id']]);
                                                    $pastor_name = $priest['full_name'] ?? 'Vacant / Not Assigned';
                                                }
                                            ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <span style="color: white; font-weight: 700; font-size: 0.9rem;"><?php echo h($pastor_name); ?></span>
                                                <a href="view_parish_history.php?id=<?php echo $p['parish_id']; ?>" title="View Pastoral Timeline" style="color: var(--accent); font-size: 1.2rem; display: flex;"><ion-icon name="time-outline"></ion-icon></a>
                                            </div>
                                        </div>

                                        <div class="card-footer" style="margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                                             <span style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Canonical Status</span>
                                             <?php if ($is_admin): ?>
                                                 <a href="handover.php?step=2&parish_id=<?php echo $p['parish_id']; ?>" style="font-size: 0.65rem; color: var(--accent); font-weight: 800; text-transform: uppercase; text-decoration: none; border: 1px solid rgba(251, 191, 36, 0.3); padding: 2px 6px; border-radius: 4px;">Initiate Handover</a>
                                             <?php else: ?>
                                                 <span style="font-size: 0.65rem; color: #10b981; font-weight: 800; text-transform: uppercase;">Active Registry</span>
                                             <?php endif; ?>
                                         </div>
                                        
                                        <?php if ($is_admin): ?>
                                            <div class="card-action-overlay">
                                                <a href="../dashboard/edit_parish.php?id=<?php echo $p['parish_id']; ?>" class="action-btn edit" title="Edit Parish">
                                                    <ion-icon name="create-outline"></ion-icon>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php include '../includes/privacy_footer.php'; ?>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <style>
        .parish-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: #10b981;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5), 0 0 25px rgba(16, 185, 129, 0.15);
        }
        .parish-card:hover .hover-manage-hint {
            opacity: 0.4;
            transform: rotate(45deg);
        }
        .parish-card-link {
            text-decoration: none;
            display: block;
            color: inherit;
        }
    </style>
</body>
</html>
