<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * View Baptismal Record & Full Canonical History (Notations)
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? 0;

// 1. Fetch Core Baptismal Data
$sql = "SELECT b.*, p.first_name, p.last_name, p.dob, p.father_name, p.mother_name, p.mother_maiden_name, pr.parish_name, pr.location
        FROM baptisms b
        JOIN parishioners p ON b.person_id = p.person_id
        JOIN parishes pr ON b.parish_id = pr.parish_id
        WHERE b.baptism_id = ?";

$record = db_fetch($sql, [$id]);

if (!$record) {
    header("Location: baptisms.php");
    exit;
}

// 2. Fetch All Sacramental Notations (Historical Milestones)
$notations = db_fetchAll("SELECT * FROM sacraments_notations WHERE baptism_id = ? ORDER BY event_date ASC", [$id]);

// 3. New Permission Check (Status Aware)
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
    <title>Canonical History - <?php echo h($record['first_name'] . ' ' . $record['last_name']); ?></title>
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

        <!-- Main Content -->
        <main class="main-content">
            
            <?php 
                $header_title = "Canonical History";
                $header_subtitle = "Baptismal record and subsequent sacramental notations for " . h($record['first_name'] . ' ' . $record['last_name']);
                $additional_header_actions = '
                    <div class="header-actions">
                        ' . ($can_print && $is_verified ? '
                        <a href="../sacraments/baptism_certificate.php?id=' . $id . '" class="btn btn-primary" target="_blank">
                            <ion-icon name="print-outline"></ion-icon>
                            Print Full Certificate
                        </a>
                        ' : (!$is_verified ? '
                        <button class="btn btn-secondary" disabled title="Records must be verified before printing.">
                            <ion-icon name="lock-closed-outline"></ion-icon>
                            Print Locked (Draft)
                        </button>
                        ' : '
                        <button class="btn btn-secondary" disabled title="Official extract must be requested from ' . h($record['parish_name']) . '">
                            <ion-icon name="lock-closed-outline"></ion-icon>
                            Print Restricted
                        </button>
                        ')) . '

                        <a href="baptisms.php" class="btn btn-secondary" style="background: #334155 !important; color: #ffffff !important; border: 1px solid #475569 !important; font-weight: 700 !important; text-decoration: none !important;">
                            <ion-icon name="arrow-back-outline" style="color: #ffffff !important;"></ion-icon>
                            Return to Register List
                        </a>
                    </div>
                ';
                include '../includes/header.php'; 
            ?>


            <!-- Verification Status Banner -->
            <?php if (!$is_verified): ?>
            <div class="status-banner status-draft">
                <div class="status-info">
                    <ion-icon name="shield-outline"></ion-icon>
                    <div>
                        <span>Status: CANONICAL DRAFT</span>
                        <p style="font-size: 0.8rem; font-weight: 400; opacity: 0.8;">This record has been professionally entered but awaits canonical verification by the Priest-in-Charge.</p>
                    </div>
                </div>
                <?php if ($can_verify): ?>
                <a href="../actions/verify_record.php?type=baptism&id=<?php echo $id; ?>" class="btn-verify">
                    <ion-icon name="checkmark-circle-outline"></ion-icon>
                    Confirm & Verify Entry
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="status-banner status-verified">
                <div class="status-info">
                    <ion-icon name="checkmark-seal-gradient" style="color: #10b981;"></ion-icon>
                    <div>
                        <span>Status: OFFICIALLY VERIFIED</span>
                        <p style="font-size: 0.8rem; font-weight: 400; opacity: 0.8;">Canonically sealed in the Diocesan Register.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                
                <!-- Main Baptismal Data -->
                <div class="card bg-card">
                    <div class="card-header">
                        <h3><ion-icon name="water-outline"></ion-icon> Primary Baptismal Record</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label><?php echo get_localized_label('date_of_baptism', 'en'); ?></label>
                                <span><?php echo date('d M Y', strtotime($record['date_of_baptism'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label><?php echo get_localized_label('parish', 'en'); ?></label>
                                <span><?php echo h($record['parish_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <label><?php echo get_localized_label('minister', 'en'); ?></label>
                                <span><?php echo h($record['minister']); ?></span>
                            </div>
                            <div class="info-item">
                                <label><?php echo get_localized_label('godparents', 'en'); ?></label>
                                <span><?php echo h($record['godparents']); ?></span>
                            </div>
                        </div>
                        
                        <div class="register-ref" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #334155;">
                            <p class="text-muted"><?php echo get_localized_label('register_ref', 'en'); ?>: Book <?php echo h($record['register_book_number']); ?> / Page <?php echo h($record['page_number']); ?> / Entry <?php echo h($record['entry_number']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Notations / Milestones -->
                <div class="card bg-card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3><ion-icon name="ribbon-outline"></ion-icon> Subsequent Notations (Canon 535)</h3>
                        <?php if ($can_edit): ?>
                        <button onclick="document.getElementById('notationModal').classList.add('active')" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                            <ion-icon name="add-outline"></ion-icon> Add Notation
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notations)): ?>
                        <div class="empty-state">
                            <ion-icon name="information-circle-outline"></ion-icon>
                            <p>No additional sacramental notations have been recorded for this person yet.</p>
                        </div>
                        <?php else: ?>
                        <div class="notation-timeline">
                            <?php foreach ($notations as $n): ?>
                            <div class="notation-item">
                                <div class="notation-type"><?php echo h($n['sacrament_type']); ?></div>
                                <div class="notation-date"><?php echo date('d M Y', strtotime($n['event_date'])); ?></div>
                                <div class="notation-location">At <?php echo h($n['parish_name']); ?></div>
                                <div class="notation-details"><?php echo h($n['details']); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <style>
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .info-item label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; }
        .info-item span { color: white; font-weight: 500; font-size: 1.1rem; }
        
        .notation-timeline { display: flex; flex-direction: column; gap: 1.5rem; }
        .notation-item { border-left: 4px solid var(--accent); padding-left: 1.5rem; position: relative; }
        .notation-type { font-weight: 700; color: white; font-family: 'Outfit', sans-serif; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; }
        .notation-date { font-size: 0.8rem; color: var(--accent); font-weight: 600; }
        .notation-location { font-size: 0.85rem; color: white; margin-top: 0.2rem; }
        .notation-details { font-size: 0.9rem; color: var(--text-muted); font-style: italic; margin-top: 0.5rem; }
        
        .empty-state { text-align: center; padding: 2rem; color: var(--text-muted); }
        .empty-state ion-icon { font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5; }
    </style>
    <!-- Notation Modal -->
    <div class="modal" id="notationModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Add Canonical Notation</h2>
                <button class="modal-close" onclick="document.getElementById('notationModal').classList.remove('active')"><ion-icon name="close-outline"></ion-icon></button>
            </div>
            <form action="../actions/save_notation.php" method="POST">
                <input type="hidden" name="baptism_id" value="<?php echo $id; ?>">
                
                <div class="form-group">
                    <label>Sacrament / Event Type *</label>
                    <select name="sacrament_type" class="form-control" required>
                        <option value="">Select Type...</option>
                        <option value="Confirmation">Confirmation</option>
                        <option value="Marriage">Marriage</option>
                        <option value="Holy Orders">Holy Orders</option>
                        <option value="Religious Profession">Religious Profession</option>
                        <option value="Declaration of Nullity">Declaration of Nullity</option>
                        <option value="Death">Death</option>
                        <option value="Other">Other Canonical Act</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date of Event *</label>
                    <input type="date" name="event_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Parish / Place *</label>
                    <input type="text" name="parish_name" class="form-control" placeholder="e.g. St. Ignatius Cathedral" required>
                </div>

                <div class="form-group">
                    <label>Additional Details (e.g. Protocol Number, Minister)</label>
                    <textarea name="details" class="form-control" rows="3"></textarea>
                </div>

                <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('notationModal').classList.remove('active')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Notation</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
