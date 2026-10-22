<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Handover Hub - Priest Transition Management
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handover_id = $_POST['handover_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    
    if ($action === 'sign_off') {
        $notes = $_POST['registry_status_notes'] ?? '';
        $pdo->prepare("UPDATE parish_handovers SET status = 'Signed_Off', registry_status_notes = ?, outgoing_sign_date = CURRENT_TIMESTAMP WHERE handover_id = ? AND outgoing_priest_id = ?")
            ->execute([$notes, $handover_id, $user_id]);
    } elseif ($action === 'accept') {
        $pdo->prepare("UPDATE parish_handovers SET status = 'Completed', incoming_accept_date = CURRENT_TIMESTAMP WHERE handover_id = ? AND incoming_priest_id = ?")
            ->execute([$handover_id, $user_id]);
    }
    header("Location: handover_hub.php?success=Protocol updated.");
    exit;
}

// Fetch Handovers where the user is outgoing
$outgoing_handovers = db_fetchAll("
    SELECT h.*, p.parish_name, u.full_name as incoming_name 
    FROM parish_handovers h 
    JOIN parishes p ON h.parish_id = p.parish_id 
    LEFT JOIN users u ON h.incoming_priest_id = u.user_id 
    WHERE h.outgoing_priest_id = ? AND h.status IN ('Pending', 'Signed_Off')
", [$user_id]);

// Fetch Handovers where the user is incoming
$incoming_handovers = db_fetchAll("
    SELECT h.*, p.parish_name, u.full_name as outgoing_name 
    FROM parish_handovers h 
    JOIN parishes p ON h.parish_id = p.parish_id 
    LEFT JOIN users u ON h.outgoing_priest_id = u.user_id 
    WHERE h.incoming_priest_id = ? AND h.status IN ('Pending', 'Signed_Off')
", [$user_id]);

$user_role = $_SESSION['role'] ?? 'viewer';
$is_admin = in_array($user_role, ['admin', 'chancellor']);

// Fetch Completed Handovers (Historical Archive)
$completed_handovers = db_fetchAll("
    SELECT h.*, p.parish_name, u1.full_name as outgoing_name, u2.full_name as incoming_name 
    FROM parish_handovers h 
    JOIN parishes p ON h.parish_id = p.parish_id 
    LEFT JOIN users u1 ON h.outgoing_priest_id = u1.user_id 
    LEFT JOIN users u2 ON h.incoming_priest_id = u2.user_id 
    WHERE (h.outgoing_priest_id = ? OR h.incoming_priest_id = ? OR ? = 1) AND h.status = 'Completed'
    ORDER BY h.incoming_accept_date DESC LIMIT 15
", [$user_id, $user_id, $is_admin ? 1 : 0]);

$header_title = "Canonical Handover Hub";
$header_subtitle = "Managing mission transitions, register inventories, and accountability transfers (Can. 535 §5).";
ob_start();
if ($is_admin): ?>
    <a href="../admin/handover.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
        <ion-icon name="swap-horizontal-outline"></ion-icon>
        Initiate Canonical Handover
    </a>
<?php endif;
$additional_header_actions = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handover Hub - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .handover-card { background: rgba(30, 41, 59, 0.4); border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); padding: 1.5rem; margin-bottom: 1.5rem; }
        .handover-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; margin-bottom: 1rem; }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-signed_off { background: rgba(56, 189, 248, 0.1); color: var(--accent); }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-body" style="padding: 2rem 0;">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    
                    <!-- Outgoing Transitions -->
                    <div>
                        <h3 style="color: white; font-family: 'Outfit'; margin-bottom: 1.5rem;">Outgoing Handover (Leaving)</h3>
                        <?php if (empty($outgoing_handovers)): ?>
                            <div class="card" style="text-align: center; color: var(--text-muted);">No pending departures.</div>
                        <?php else: foreach ($outgoing_handovers as $h): ?>
                            <div class="handover-card">
                                <span class="handover-status status-<?php echo strtolower($h['status']); ?>"><?php echo str_replace('_', ' ', $h['status']); ?></span>
                                <h4 style="color: white; margin-bottom: 5px;"><?php echo h($h['parish_name']); ?></h4>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">Transferring to: <strong><?php echo h($h['incoming_name']); ?></strong></p>
                                
                                <?php if ($h['status'] === 'Pending'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="handover_id" value="<?php echo $h['handover_id']; ?>">
                                        <input type="hidden" name="action" value="sign_off">
                                        <label style="font-size: 0.7rem; color: var(--accent); font-weight: 700; display: block; margin-bottom: 5px;">REGISTRY STATUS REPORT</label>
                                        <textarea name="registry_status_notes" class="input-field" style="width: 100%; min-height: 80px; margin-bottom: 1rem;" placeholder="e.g. All baptisms recorded up to Apr 2026. Parish records locked in vault."></textarea>
                                        <button type="submit" class="btn btn-primary" style="width: 100%;">Sign Off Mission</button>
                                    </form>
                                <?php else: ?>
                                    <div style="background: rgba(16, 185, 129, 0.05); padding: 1rem; border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.1);">
                                        <div style="color: #10b981; font-weight: 700; font-size: 0.8rem; margin-bottom: 5px;">SIGN-OFF COMPLETE</div>
                                        <p style="font-size: 0.8rem; color: var(--text-muted);">Awaiting acceptance by incoming priest.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>

                    <!-- Incoming Transitions -->
                    <div>
                        <h3 style="color: white; font-family: 'Outfit'; margin-bottom: 1.5rem;">Incoming Takeover (Arriving)</h3>
                        <?php if (empty($incoming_handovers)): ?>
                            <div class="card" style="text-align: center; color: var(--text-muted);">No pending arrivals.</div>
                        <?php else: foreach ($incoming_handovers as $h): ?>
                            <div class="handover-card">
                                <span class="handover-status status-<?php echo strtolower($h['status']); ?>"><?php echo str_replace('_', ' ', $h['status']); ?></span>
                                <h4 style="color: white; margin-bottom: 5px;"><?php echo h($h['parish_name']); ?></h4>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">Handover from: <strong><?php echo h($h['outgoing_name']); ?></strong></p>
                                
                                <?php if ($h['status'] === 'Signed_Off'): ?>
                                    <div style="background: rgba(56, 189, 248, 0.05); padding: 1rem; border-radius: 12px; border: 1px solid rgba(56, 189, 248, 0.1); margin-bottom: 1rem;">
                                        <label style="font-size: 0.65rem; color: var(--accent); font-weight: 800;">OUTGOING STATUS REPORT</label>
                                        <p style="font-size: 0.85rem; color: white; margin-top: 5px;"><?php echo nl2br(h($h['registry_status_notes'])); ?></p>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="handover_id" value="<?php echo $h['handover_id']; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-primary" style="width: 100%; background: #10b981; border-color: #10b981; color: black; font-weight: 800;">Accept Mission & Records</button>
                                    </form>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 2rem; border: 2px dashed rgba(255,255,255,0.05); border-radius: 16px;">
                                        <ion-icon name="hourglass-outline" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 1rem;"></ion-icon>
                                        <p style="font-size: 0.85rem; color: var(--text-muted);">Waiting for outgoing priest to sign off the registry.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>

                </div>

                <!-- Historical Archive of Completed Transitions -->
                <div style="margin-top: 3rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="color: white; font-family: 'Outfit'; margin: 0; display: flex; align-items: center; gap: 8px;">
                            <ion-icon name="archive-outline" style="color: #10b981;"></ion-icon>
                            Archived Canonical Transitions (Can. 535 §5)
                        </h3>
                    </div>
                    <?php if (empty($completed_handovers)): ?>
                        <div class="card" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            No archived transitions found.
                        </div>
                    <?php else: ?>
                        <div class="card bg-card" style="padding: 0; overflow: hidden; border-radius: 16px;">
                            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.08);">
                                        <th style="padding: 1rem; text-align: left; font-size: 0.8rem; color: var(--text-muted);">Parish / Mission</th>
                                        <th style="padding: 1rem; text-align: left; font-size: 0.8rem; color: var(--text-muted);">Outgoing Priest</th>
                                        <th style="padding: 1rem; text-align: left; font-size: 0.8rem; color: var(--text-muted);">Incoming Priest</th>
                                        <th style="padding: 1rem; text-align: left; font-size: 0.8rem; color: var(--text-muted);">Handover Date</th>
                                        <th style="padding: 1rem; text-align: left; font-size: 0.8rem; color: var(--text-muted);">Status</th>
                                        <th style="padding: 1rem; text-align: left; font-size: 0.8rem; color: var(--text-muted);">Registry Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_handovers as $ch): ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                        <td style="padding: 1rem; font-weight: 700; color: white;"><?php echo h($ch['parish_name']); ?></td>
                                        <td style="padding: 1rem; color: #cbd5e1;"><?php echo h($ch['outgoing_name'] ?: 'Not Stated'); ?></td>
                                        <td style="padding: 1rem; color: #38bdf8; font-weight: 600;"><?php echo h($ch['incoming_name'] ?: 'Not Stated'); ?></td>
                                        <td style="padding: 1rem; color: var(--text-muted); font-size: 0.85rem;"><?php echo date('d M Y', strtotime($ch['incoming_accept_date'] ?: $ch['handover_date'])); ?></td>
                                        <td style="padding: 1rem;">
                                            <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700;">
                                                <ion-icon name="checkmark-circle-outline" style="vertical-align: middle;"></ion-icon> Completed
                                            </span>
                                        </td>
                                        <td style="padding: 1rem; color: var(--text-muted); font-size: 0.85rem; max-width: 250px;">
                                            <?php echo h($ch['registry_status_notes'] ?: 'Parochial registers & inventory transferred.'); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
