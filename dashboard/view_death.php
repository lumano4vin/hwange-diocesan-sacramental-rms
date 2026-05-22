<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * View Death Record & Canonical Verification
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? 0;

// 1. Fetch Core Death Data
$sql = "SELECT d.*, p.first_name, p.last_name, p.dob, pr.parish_name, pr.location
        FROM deaths d
        JOIN parishioners p ON d.person_id = p.person_id
        JOIN parishes pr ON d.parish_id = pr.parish_id
        WHERE d.death_id = ?";

$record = db_fetch($sql, [$id]);

if (!$record) {
    header("Location: deaths.php");
    exit;
}

// 2. Permission Check (Status Aware)
$can_print  = has_record_permission($record, 'print');
$can_edit   = has_record_permission($record, 'edit');
$can_verify = has_record_permission($record, 'verify');
$is_verified = ($record['status'] === 'Verified');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Death Record Detail - <?php echo h($record['first_name'] . ' ' . $record['last_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <style>
        .header-actions a.btn-secondary {
            background: #334155 !important;
            color: #ffffff !important;
            border: 2px solid #38bdf8 !important;
            font-weight: 800 !important;
        }
        
        /* Status Banners */
        .status-banner {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            animation: slideDown 0.4s ease-out;
        }
        @keyframes slideDown { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .status-draft { background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; color: #fbbf24; }
        .status-verified { background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #34d399; }
        
        .status-info { display: flex; align-items: center; gap: 0.75rem; font-weight: 600; }
        .status-info ion-icon { font-size: 1.5rem; }
        
        .btn-verify {
            background: #10b981 !important;
            color: white !important;
            border: none !important;
            padding: 0.6rem 1.2rem;
            border-radius: 0.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-verify:hover { background: #059669 !important; transform: scale(1.05); }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .info-item label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; }
        .info-item span { color: white; font-weight: 500; font-size: 1.1rem; }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <header class="content-header">
                <div class="welcome-text">
                    <h2>Death Record Detail</h2>
                    <p>Canonical record of burial for <strong><?php echo h($record['first_name'] . ' ' . $record['last_name']); ?></strong>.</p>
                </div>
                <div class="header-actions">
                    <?php if ($can_print && $is_verified): ?>
                    <a href="death_certificate.php?id=<?php echo $id; ?>" class="btn btn-primary" target="_blank">
                        <ion-icon name="print-outline"></ion-icon>
                        Print Extract
                    </a>
                    <?php endif; ?>

                    <a href="deaths.php" class="btn btn-secondary">
                        <ion-icon name="arrow-back-outline"></ion-icon>
                        Return to List
                    </a>
                </div>
            </header>

            <!-- Verification Status Banner -->
            <?php if (!$is_verified): ?>
            <div class="status-banner status-draft">
                <div class="status-info">
                    <ion-icon name="shield-outline"></ion-icon>
                    <div>
                        <span>Status: CANONICAL DRAFT</span>
                        <p style="font-size: 0.8rem; font-weight: 400; opacity: 0.8;">Entry awaits verification by authorized clergy.</p>
                    </div>
                </div>
                <?php if ($can_verify): ?>
                <a href="../actions/verify_record.php?type=death&id=<?php echo $id; ?>" class="btn-verify">
                    <ion-icon name="checkmark-circle-outline"></ion-icon>
                    Confirm & Verify Entry
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="status-banner status-verified">
                <div class="status-info">
                    <ion-icon name="checkmark-seal" style="color: #10b981;"></ion-icon>
                    <div>
                        <span>Status: OFFICIALLY VERIFIED</span>
                        <p style="font-size: 0.8rem; font-weight: 400; opacity: 0.8;">Canonically sealed in the Register.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card bg-card">
                <div class="card-header">
                    <h3><ion-icon name="body-outline"></ion-icon> Register of the Departed</h3>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Date of Death</label>
                            <span><?php echo date('d M Y', strtotime($record['date_of_death'])); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Date of Burial</label>
                            <span><?php echo date('d M Y', strtotime($record['date_of_burial'])); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Place of Burial</label>
                            <span><?php echo h($record['place_of_burial']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Parish / Mission</label>
                            <span><?php echo h($record['parish_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Minister</label>
                            <span><?php echo h($record['minister']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Cause of Death (Optional)</label>
                            <span><?php echo h($record['cause_of_death'] ?: 'Not Stated'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
