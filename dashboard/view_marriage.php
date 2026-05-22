<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * View Marriage Record - Premium Canonical History
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? 0;

// Fetch Marriage Details
$sql = "SELECT m.*, 
               p1.first_name as g_first, p1.last_name as g_last, p1.dob as g_dob,
               p2.first_name as b_first, p2.last_name as b_last, p2.dob as b_dob,
               pr.parish_name, pr.location
        FROM marriages m
        JOIN parishioners p1 ON m.groom_person_id = p1.person_id
        JOIN parishioners p2 ON m.bride_person_id = p2.person_id
        JOIN parishes pr ON m.parish_id = pr.parish_id
        WHERE m.marriage_id = ?";

$record = db_fetch($sql, [$id]);

if (!$record) {
    header("Location: marriages.php");
    exit;
}

// 2. New Permission Check (Status Aware)
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
    <title>Marriage Detail - <?php echo h($record['g_first'] . ' & ' . $record['b_first']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                    <h2>Marriage Canonical Detail</h2>
                    <p>Official record of Holy Matrimony for <strong><?php echo h($record['g_first'] . ' ' . $record['g_last']); ?></strong> and <strong><?php echo h($record['b_first'] . ' ' . $record['b_last']); ?></strong>.</p>
                </div>
                <div class="header-actions">
                    <?php if ($can_print && $is_verified): ?>
                    <a href="marriage_certificate.php?id=<?php echo $id; ?>" class="btn btn-primary" target="_blank">
                        <ion-icon name="print-outline"></ion-icon>
                        Print Marriage Certificate
                    </a>
                    <?php elseif (!$is_verified): ?>
                    <button class="btn btn-secondary" disabled title="Records must be verified before printing.">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        Print Locked (Draft)
                    </button>
                    <?php endif; ?>

                    <a href="marriages.php" class="btn btn-secondary" style="background: #334155 !important; color: #ffffff !important; border: 1px solid #475569 !important; font-weight: 700 !important; text-decoration: none !important;">
                        <ion-icon name="arrow-back-outline" style="color: #ffffff !important;"></ion-icon>
                        Back to List
                    </a>
                </div>
            </header>

            <!-- Verification Status Banner -->
            <?php if (!$is_verified): ?>
            <div class="status-banner status-draft">
                <div class="status-info">
                    <ion-icon name="shield-outline"></ion-icon>
                    <div>
                        <span>Status: MARRIAGE DRAFT</span>
                        <p style="font-size: 0.8rem; font-weight: 400; opacity: 0.8;">This record awaits canonical verification by the Priest-in-Charge before official extracts can be issued.</p>
                    </div>
                </div>
                <?php if ($can_verify): ?>
                <a href="../actions/verify_record.php?type=marriage&id=<?php echo $id; ?>" class="btn-verify">
                    <ion-icon name="checkmark-circle-outline"></ion-icon>
                    Confirm & Verify Marriage
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="status-banner status-verified">
                <div class="status-info">
                    <ion-icon name="checkmark-seal-gradient" style="color: #10b981;"></ion-icon>
                    <div>
                        <span>Status: OFFICIALLY SEALED</span>
                        <p style="font-size: 0.8rem; font-weight: 400; opacity: 0.8;">Verified and canonized in the Parochial Register.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                
                <!-- Groom & Bride -->
                <div class="card bg-card">
                    <div class="card-header">
                        <h3><ion-icon name="heart-outline"></ion-icon> Spousal Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid" style="grid-template-columns: 1fr 1fr;">
                            <div class="info-group">
                                <h4 style="color: var(--accent); margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">Groom</h4>
                                <div class="info-item">
                                    <label>Full Name</label>
                                    <span><?php echo h($record['g_first'] . ' ' . $record['g_last']); ?></span>
                                </div>
                            </div>
                            <div class="info-group">
                                <h4 style="color: #db2777; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">Bride</h4>
                                <div class="info-item">
                                    <label>Full Name</label>
                                    <span><?php echo h($record['b_first'] . ' ' . $record['b_last']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Canonical Details -->
                <div class="card bg-card">
                    <div class="card-header">
                        <h3><ion-icon name="ribbon-outline"></ion-icon> Canonical Celebration</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Date of Marriage</label>
                                <span><?php echo date('d M Y', strtotime($record['date_of_marriage'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Parish / Mission</label>
                                <span><?php echo h($record['parish_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Officiating Minister</label>
                                <span><?php echo h($record['officiant']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Official Witnesses</label>
                                <span><?php echo h($record['witnesses_names']); ?></span>
                            </div>
                        </div>
                        
                        <div class="register-ref" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #334155;">
                            <p class="text-muted">Register Ref: Book <?php echo h($record['register_book_number'] ?? 'N/A'); ?> / Entry <?php echo h($record['entry_number'] ?? 'N/A'); ?></p>
                            <p class="text-muted" style="margin-top: 5px; font-size: 0.8rem;">Verification Hash: <?php echo substr($record['verification_hash'], 0, 16); ?>...</p>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <style>
        .info-grid { display: grid; gap: 1.5rem; }
        .info-item { margin-bottom: 1rem; }
        .info-item label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; }
        .info-item span { color: white; font-weight: 500; font-size: 1.1rem; }
    </style>
</body>
</html>
