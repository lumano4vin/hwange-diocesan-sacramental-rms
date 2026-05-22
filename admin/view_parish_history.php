<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parish Pastoral History & Timeline
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$parish_id = $_GET['id'] ?? null;
if (!$parish_id) redirect('parishes.php');

$parish = db_fetch("SELECT * FROM parishes WHERE parish_id = ?", [$parish_id]);
if (!$parish) redirect('parishes.php');

// Handle Manual History Entry (For adding historical priests)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_history'])) {
    $name = $_POST['priest_name_manual'] ?? '';
    $start = $_POST['start_date'] ?? '';
    $end = $_POST['end_date'] ?? null;
    $role = $_POST['role'] ?? 'Parish Priest';
    $status = $end ? 'Historical' : 'Active';

    if ($name && $start) {
        db_query("INSERT INTO parish_assignments (parish_id, priest_name_manual, start_date, end_date, role, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)", 
            [$parish_id, $name, $start, $end, $role, $status, "Manual historical entry"]);
        set_flash("Historical record added to the timeline.");
    }
}

// Fetch the timeline
$history = db_fetchAll("SELECT * FROM parish_assignments WHERE parish_id = ? ORDER BY start_date DESC", [$parish_id]);

$header_title = "Pastoral History: " . h($parish['parish_name']);
$header_subtitle = "Chronological timeline of spiritual leadership and handovers.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pastoral History - <?php echo h($parish['parish_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&family=Cinzel:wght@700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-body" style="padding: 2rem 0; max-width: 1000px; margin: 0 auto;">
                
                <div class="action-bar" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <a href="parishes.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px;">
                        <ion-icon name="arrow-back-outline"></ion-icon> Back to Registry
                    </a>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <button onclick="document.getElementById('add-modal').style.display='flex'" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px;">
                        <ion-icon name="add-outline"></ion-icon> Add Historical Priest
                    </button>
                    <?php endif; ?>
                </div>

                <div class="timeline-container" style="position: relative; padding: 2rem 0;">
                    
                    <!-- Vertical Line -->
                    <div style="position: absolute; left: 50%; top: 0; bottom: 0; width: 2px; background: rgba(251, 191, 36, 0.2); transform: translateX(-50%);"></div>

                    <?php if (empty($history)): ?>
                        <div style="text-align: center; padding: 4rem; background: rgba(255,255,255,0.02); border-radius: 2rem; border: 1px dashed rgba(255,255,255,0.1);">
                            <ion-icon name="calendar-outline" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></ion-icon>
                            <p style="color: var(--text-muted);">No pastoral records found for this mission. Records began being tracked in <?php echo date('Y'); ?>.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($history as $index => $item): 
                        $is_even = $index % 2 == 0;
                    ?>
                        <div class="timeline-item" style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 3rem; position: relative;">
                            
                            <!-- Content Box -->
                            <div style="width: 45%; <?php echo $is_even ? 'text-align: right;' : 'order: 2;'; ?>">
                                <div class="card bg-card" style="padding: 1.5rem; border-radius: 1.5rem; border: 1px solid <?php echo $item['status'] == 'Active' ? 'var(--accent)' : 'rgba(255,255,255,0.05)'; ?>; position: relative;">
                                    <div style="font-size: 0.7rem; color: var(--accent); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;"><?php echo h($item['role']); ?></div>
                                    <h3 style="font-family: 'Outfit'; color: white; margin: 0 0 10px 0; font-size: 1.25rem;"><?php echo h($item['priest_name_manual']); ?></h3>
                                    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;"><?php echo $item['notes']; ?></p>
                                    
                                    <?php if ($item['status'] == 'Active'): ?>
                                        <span style="position: absolute; top: -10px; <?php echo $is_even ? 'right: 20px;' : 'left: 20px;'; ?> background: var(--accent); color: var(--navy); padding: 4px 12px; border-radius: 20px; font-size: 0.6rem; font-weight: 900; text-transform: uppercase;">Incumbent</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Date Circle -->
                            <div style="width: 10%; display: flex; justify-content: center; z-index: 10;">
                                <div style="width: 15px; height: 15px; background: <?php echo $item['status'] == 'Active' ? 'var(--accent)' : '#1e293b'; ?>; border: 3px solid var(--navy); border-radius: 50%; box-shadow: 0 0 15px <?php echo $item['status'] == 'Active' ? 'var(--accent)' : 'transparent'; ?>;"></div>
                            </div>

                            <!-- Date Label -->
                            <div style="width: 45%; <?php echo $is_even ? 'order: 2;' : 'text-align: right;'; ?>">
                                <div style="color: white; font-weight: 700;">
                                    <?php 
                                        echo date('M Y', strtotime($item['start_date'])); 
                                        if ($item['end_date']) {
                                            echo " — " . date('M Y', strtotime($item['end_date']));
                                        } else {
                                            echo " — Present";
                                        }
                                    ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo floor((($item['end_date'] ? strtotime($item['end_date']) : time()) - strtotime($item['start_date'])) / (60*60*24*30.44)); ?> months service</div>
                            </div>

                        </div>
                    <?php endforeach; ?>

                </div>

            </div>

            <!-- Add Modal -->
            <div id="add-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); z-index: 1000; align-items: center; justify-content: center; padding: 2rem;">
                <div class="card bg-card" style="max-width: 500px; width: 100%; padding: 2.5rem; border-radius: 2rem;">
                    <h2 style="color: white; font-family: 'Outfit'; margin-bottom: 2rem;">Add Historical Priest</h2>
                    <form method="POST">
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label style="display: block; color: var(--text-muted); font-size: 0.8rem; margin-bottom: 8px;">Priest Full Name</label>
                            <input type="text" name="priest_name_manual" required style="width: 100%; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label style="display: block; color: var(--text-muted); font-size: 0.8rem; margin-bottom: 8px;">Start Date</label>
                                <input type="date" name="start_date" required style="width: 100%; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                            </div>
                            <div class="form-group">
                                <label style="display: block; color: var(--text-muted); font-size: 0.8rem; margin-bottom: 8px;">End Date (If known)</label>
                                <input type="date" name="end_date" style="width: 100%; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label style="display: block; color: var(--text-muted); font-size: 0.8rem; margin-bottom: 8px;">Role</label>
                            <select name="role" style="width: 100%; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                <option value="Parish Priest">Parish Priest</option>
                                <option value="Assistant Priest">Assistant Priest</option>
                                <option value="Administrator">Administrator</option>
                                <option value="Missionary Priest">Missionary Priest</option>
                            </select>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <button type="button" onclick="document.getElementById('add-modal').style.display='none'" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                            <button type="submit" name="add_history" class="btn btn-primary" style="flex: 2;">Save to Timeline</button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
