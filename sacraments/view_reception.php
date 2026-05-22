<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * View Profession of Faith / Reception Record
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? null;
if (!$id) redirect('reception_list.php');

// Fetch record details
$record = db_fetch("
    SELECT r.*, p.first_name, p.last_name, p.other_names, p.gender, p.dob, p.place_of_birth, pa.parish_name, u.full_name as registrar_name
    FROM receptions r 
    JOIN parishioners p ON r.person_id = p.person_id 
    JOIN parishes pa ON r.parish_id = pa.parish_id 
    LEFT JOIN audit_logs a ON (a.record_id = r.reception_id AND a.table_name = 'receptions' AND a.action_type = 'CREATE')
    LEFT JOIN users u ON a.user_id = u.user_id
    WHERE r.reception_id = ?
", [$id]);

if (!$record) redirect('reception_list.php');

// Handle Verification
if (isset($_POST['verify']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chancellor' || $_SESSION['role'] === 'priest')) {
    db_query("UPDATE receptions SET status = 'Verified' WHERE reception_id = ?", [$id]);
    log_audit($_SESSION['user_id'], 'UPDATE', 'receptions', $id, "Verified Profession of Faith record");
    set_flash("Record has been verified and officially added to the canonical archives.");
    header("Location: view_reception.php?id=$id");
    exit;
}

$header_title = "Canonical Record: " . h($record['first_name'] . ' ' . $record['last_name']);
$header_subtitle = "Profession of Faith & Reception into Full Communion";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reception - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <?php include '../includes/header.php'; ?>
            
            <div class="content-body" style="padding: 2rem 0; max-width: 1000px; margin: 0 auto;">
                
                <div class="action-bar" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <a href="reception_list.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px;">
                        <ion-icon name="arrow-back-outline"></ion-icon> Back to Registry
                    </a>
                    <div style="display: flex; gap: 10px;">
                        <a href="reception_certificate.php?id=<?php echo $id; ?>" class="btn btn-primary" style="background: var(--gold); color: #000; border: none; font-weight: 800;">
                            <ion-icon name="print-outline"></ion-icon> Print Certificate
                        </a>
                        <a href="reception_edit.php?id=<?php echo $id; ?>" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px;">
                            <ion-icon name="create-outline"></ion-icon> Edit Entry
                        </a>
                    </div>
                </div>

                <div class="record-container" style="display: grid; grid-template-columns: 1fr 300px; gap: 2rem; align-items: start;">
                    
                    <div class="card bg-card" style="padding: 3rem; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 1.5rem;">
                            <div>
                                <span class="status-badge <?php echo strtolower($record['status']); ?>" style="margin-bottom: 8px;"><?php echo h($record['status']); ?></span>
                                <h2 style="font-family: 'Outfit'; font-size: 2.5rem; color: white;"><?php echo h($record['first_name'] . ' ' . $record['last_name']); ?></h2>
                                <p style="color: var(--text-muted);">GUID: <?php echo h($record['guid']); ?></p>
                            </div>
                            <div style="text-align: right;">
                                <p style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Reception Date</p>
                                <h3 style="font-family: 'Outfit'; color: var(--accent); font-size: 1.5rem;"><?php echo date('d M Y', strtotime($record['date_of_reception'])); ?></h3>
                            </div>
                        </div>

                        <div class="record-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
                            
                            <div class="detail-section">
                                <h4 style="color: #8b5cf6; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 2px; margin-bottom: 1.5rem;">Biographical Context</h4>
                                <div class="detail-item" style="margin-bottom: 1rem;">
                                    <label style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Previous Denomination</label>
                                    <p style="color: white; font-weight: 600;"><?php echo h($record['previous_denomination'] ?: 'Not Specified'); ?></p>
                                </div>
                                <div class="detail-item" style="margin-bottom: 1rem;">
                                    <label style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Date of Birth</label>
                                    <p style="color: white; font-weight: 600;">
                                        <?php echo date('d M Y', strtotime($record['dob'])); ?>
                                        <span style="color: var(--accent); margin-left: 8px;">(Age at Reception: <?php echo get_age($record['dob'], $record['date_of_reception']); ?>)</span>
                                    </p>
                                </div>
                                <div class="detail-item">
                                    <label style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Parish of Reception</label>
                                    <p style="color: white; font-weight: 600;"><?php echo h($record['parish_name']); ?></p>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h4 style="color: #8b5cf6; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 2px; margin-bottom: 1.5rem;">Canonical Details</h4>
                                <div class="detail-item" style="margin-bottom: 1rem;">
                                    <label style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Minister of Sacrament</label>
                                    <p style="color: white; font-weight: 600;"><?php echo h($record['minister']); ?></p>
                                </div>
                                <div class="detail-item" style="margin-bottom: 1rem;">
                                    <label style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Sponsors</label>
                                    <p style="color: white; font-weight: 600;"><?php echo h($record['godparents_sponsors']); ?></p>
                                </div>
                                <div class="detail-item">
                                    <label style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Registry Reference</label>
                                    <p style="color: white; font-weight: 600;">Book <?php echo h($record['register_book_number']); ?>, Page <?php echo h($record['page_number']); ?>, Entry <?php echo h($record['entry_number']); ?></p>
                                </div>
                            </div>

                        </div>

                        <?php if ($record['status'] === 'Draft' && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chancellor' || $_SESSION['role'] === 'priest')): ?>
                        <div style="margin-top: 4rem; padding: 2rem; background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); border-radius: 1.5rem; text-align: center;">
                            <h4 style="color: white; margin-bottom: 1rem;">Verification Pending</h4>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">This canonical record is currently in 'Draft' status. Please verify the details against the physical register before finalizing.</p>
                            <form method="POST">
                                <button type="submit" name="verify" class="btn btn-primary" style="background: var(--success); border: none; color: #000; font-weight: 800; padding: 1rem 3rem; border-radius: 12px;">Verify Canonical Entry</button>
                            </form>
                        </div>
                        <?php endif; ?>

                    </div>

                    <div class="sidebar-info" style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div class="card bg-card" style="padding: 1.5rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05);">
                            <h4 style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem;">System Metadata</h4>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div>
                                    <label style="font-size: 0.65rem; color: var(--text-muted);">Registered By</label>
                                    <p style="color: white; font-size: 0.85rem;"><?php echo h($record['registrar_name'] ?: 'System Admin'); ?></p>
                                </div>
                                <div>
                                    <label style="font-size: 0.65rem; color: var(--text-muted);">Created At</label>
                                    <p style="color: white; font-size: 0.85rem;"><?php echo date('M j, Y H:i', strtotime($record['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card bg-card" style="padding: 1.5rem; border-radius: 1.5rem; border: 1px dashed var(--accent); opacity: 0.8;">
                            <p style="font-size: 0.8rem; color: var(--accent); font-style: italic; text-align: center;">"This record serves as primary canonical proof of reception into full communion."</p>
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
