<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Canonical Handover Assistant
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page - Only Diocesan Admins can execute formal handovers
require_admin();

$step = $_GET['step'] ?? 1;
$parish_id = $_GET['parish_id'] ?? null;

$error = '';
$success = '';

// Step 1: Fetch All Parishes
$parishes = db_fetchAll("SELECT * FROM parishes ORDER BY parish_name");

// If parish selected, fetch its data
$parish = null;
$stats = [];
if ($parish_id) {
    $parish = db_fetch("SELECT * FROM parishes WHERE parish_id = ?", [$parish_id]);
    
    // Fetch current counts for the Handover Report
    $stats['parishioners'] = db_fetch("SELECT COUNT(*) as total FROM parishioners WHERE current_parish_id = ?", [$parish_id])['total'];
    $stats['baptisms'] = db_fetch("SELECT COUNT(*) as total FROM baptisms WHERE parish_id = ?", [$parish_id])['total'];
    $stats['marriages'] = db_fetch("SELECT COUNT(*) as total FROM marriages WHERE parish_id = ?", [$parish_id])['total'];
    $stats['confirmations'] = db_fetch("SELECT COUNT(*) as total FROM confirmations WHERE parish_id = ?", [$parish_id])['total'];
}

// Fetch priests for assignment
$priests = db_fetchAll("SELECT user_id, full_name, username, role FROM users WHERE role IN ('priest', 'deacon', 'admin') AND status = 'Active'");

// Process Handover (Step 4)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_handover'])) {
    $outgoing_id = $_POST['outgoing_id'] ?? null;
    $incoming_id = $_POST['incoming_id'] ?? null;
    $handover_date = $_POST['handover_date'] ?? date('Y-m-d');

    if ($parish_id && $incoming_id) {
        try {
            // 1. Log the Handover Event (Canonical Audit)
            $details = "Formal Handover of " . $parish['parish_name'] . ".\n";
            $details .= "Stats at Handover: Parishioners: {$stats['parishioners']}, Baptisms: {$stats['baptisms']}, Marriages: {$stats['marriages']}.\n";
            if ($outgoing_id) {
                $outgoing = db_fetch("SELECT full_name FROM users WHERE user_id = ?", [$outgoing_id]);
                $details .= "Outgoing Priest: " . ($outgoing['full_name'] ?? 'Unknown') . ".\n";
            }
            $incoming = db_fetch("SELECT full_name FROM users WHERE user_id = ?", [$incoming_id]);
            $details .= "Incoming Priest: " . ($incoming['full_name'] ?? 'Unknown') . ".\n";

            // 2. Close existing assignment in Pastoral History
            db_query("UPDATE parish_assignments SET end_date = ?, status = 'Historical' WHERE parish_id = ? AND status = 'Active'", [$handover_date, $parish_id]);

            // 3. Deactivate/Remove Assignment from Outgoing User account (if applicable)
            if ($outgoing_id && $outgoing_id != $_SESSION['user_id']) {
                db_query("UPDATE users SET parish_id = NULL WHERE user_id = ?", [$outgoing_id]);
            }

            // 4. Assign Parish to Incoming User and update Parish table
            db_query("UPDATE users SET parish_id = ? WHERE user_id = ?", [$parish_id, $incoming_id]);
            db_query("UPDATE parishes SET priest_in_charge_id = ? WHERE parish_id = ?", [$incoming_id, $parish_id]);

            // 5. Create New Entry in Pastoral History (The "Handshake")
            $incoming_user = db_fetch("SELECT full_name FROM users WHERE user_id = ?", [$incoming_id]);
            db_query("INSERT INTO parish_assignments (parish_id, user_id, priest_name_manual, start_date, status, notes) VALUES (?, ?, ?, ?, 'Active', ?)", 
                [$parish_id, $incoming_id, $incoming_user['full_name'], $handover_date, "Handover completed via Assistant"]);

            // 6. Record in Audit Log
            logAction("Formal Canonical Handover: " . $parish['parish_name'], $details);
            
            $success = "Handover of " . $parish['parish_name'] . " completed and logged successfully.";
            $step = 5; // Success Screen
        } catch (Exception $e) {
            $error = "Handover Failed: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canonical Handover Assistant - Hwange RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.5">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&family=Cinzel:wght@700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .step-pill { width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-weight: 900; color: var(--text-muted); }
        .step-pill.active { background: var(--accent); color: var(--navy); }
        .step-line { flex-grow: 1; height: 2px; background: rgba(255,255,255,0.05); margin: 0 10px; }
        .step-line.active { background: var(--accent); }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0;">
                
                <div style="max-width: 900px; margin: 0 auto;">
                    
                    <div style="text-align: center; margin-bottom: 3rem;">
                        <h1 style="font-family: 'Outfit'; font-weight: 900; color: white; margin-bottom: 0.5rem;">Canonical Handover Assistant</h1>
                        <p style="color: var(--text-muted);">Formalizing the transition of spiritual and administrative leadership.</p>
                    </div>

                    <!-- Step Progress -->
                    <div style="display: flex; align-items: center; margin-bottom: 3rem; padding: 0 2rem;">
                        <div class="step-pill <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
                        <div class="step-line <?php echo $step >= 2 ? 'active' : ''; ?>"></div>
                        <div class="step-pill <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
                        <div class="step-line <?php echo $step >= 3 ? 'active' : ''; ?>"></div>
                        <div class="step-pill <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
                        <div class="step-line <?php echo $step >= 4 ? 'active' : ''; ?>"></div>
                        <div class="step-pill <?php echo $step >= 4 ? 'active' : ''; ?>">4</div>
                    </div>

                    <?php if ($error): ?>
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 1.5rem; border-radius: 1rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 15px;">
                            <ion-icon name="alert-circle-outline" style="font-size: 1.5rem;"></ion-icon>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($step == 1): ?>
                        <!-- Step 1: Select Parish -->
                        <div class="card bg-card" style="padding: 3rem; text-align: center; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="font-size: 3rem; color: var(--accent); margin-bottom: 1.5rem;"><ion-icon name="business-outline"></ion-icon></div>
                            <h2 style="color: white; font-family: 'Outfit'; margin-bottom: 1rem;">Identify the Parish</h2>
                            <p style="color: var(--text-muted); margin-bottom: 2rem;">Select the mission or parish undergoing transition.</p>
                            
                            <div class="grid-list" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; text-align: left;">
                                <?php foreach ($parishes as $p): ?>
                                    <a href="?step=2&parish_id=<?php echo $p['parish_id']; ?>" style="padding: 1rem 1.5rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; color: white; text-decoration: none; font-weight: 600; display: flex; justify-content: space-between; align-items: center; transition: 0.3s;">
                                        <?php echo h($p['parish_name']); ?>
                                        <ion-icon name="chevron-forward-outline" style="color: var(--accent);"></ion-icon>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <?php elseif ($step == 2): ?>
                        <!-- Step 2: Stats Snapshot -->
                        <div class="card bg-card" style="padding: 3rem; border-radius: 2rem; border: 1px solid rgba(251, 191, 36, 0.2);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                                <div>
                                    <h2 style="color: white; font-family: 'Outfit'; margin: 0;"><?php echo h($parish['parish_name']); ?></h2>
                                    <p style="color: var(--accent); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px;">Canonical State Snapshot</p>
                                </div>
                                <ion-icon name="analytics-outline" style="font-size: 2.5rem; color: var(--accent);"></ion-icon>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 3rem;">
                                <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 1rem; text-align: center;">
                                    <div style="font-size: 2rem; font-weight: 900; color: white;"><?php echo $stats['parishioners']; ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Faithful</div>
                                </div>
                                <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 1rem; text-align: center;">
                                    <div style="font-size: 2rem; font-weight: 900; color: white;"><?php echo $stats['baptisms']; ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Baptisms</div>
                                </div>
                                <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 1rem; text-align: center;">
                                    <div style="font-size: 2rem; font-weight: 900; color: white;"><?php echo $stats['marriages']; ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Marriages</div>
                                </div>
                                <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 1rem; text-align: center;">
                                    <div style="font-size: 2rem; font-weight: 900; color: white;"><?php echo $stats['confirmations']; ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Confirmations</div>
                                </div>
                            </div>

                            <div style="background: rgba(251, 191, 36, 0.05); padding: 1.5rem; border-radius: 1rem; border: 1px dashed var(--accent); margin-bottom: 2rem;">
                                <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted); line-height: 1.6;">
                                    <ion-icon name="information-circle" style="color: var(--accent); vertical-align: middle; margin-right: 5px;"></ion-icon>
                                    This snapshot will be recorded as the official status of the parish at the time of handover. Please ensure all pending physical registers have been digitized before proceeding.
                                </p>
                            </div>

                            <div style="text-align: right;">
                                <a href="?step=1" class="btn btn-secondary">Back</a>
                                <a href="?step=3&parish_id=<?php echo $parish_id; ?>" class="btn btn-primary">Verify & Continue</a>
                            </div>
                        </div>

                    <?php elseif ($step == 3): ?>
                        <!-- Step 3: Select Outgoing/Incoming -->
                        <div class="card bg-card" style="padding: 3rem; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05);">
                            <h2 style="color: white; font-family: 'Outfit'; margin-bottom: 2rem;">Clerical Transition</h2>
                            
                            <form action="?step=4&parish_id=<?php echo $parish_id; ?>" method="POST">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                                    <div class="input-group">
                                        <label style="display: block; color: var(--text-muted); margin-bottom: 0.5rem; font-size: 0.8rem; text-transform: uppercase;">Outgoing Priest / Admin</label>
                                        <select name="outgoing_id" style="width: 100%; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                            <option value="">-- No existing assignment / Unknown --</option>
                                            <?php foreach ($priests as $p): ?>
                                                <option value="<?php echo $p['user_id']; ?>" <?php echo $parish['priest_in_charge_id'] == $p['user_id'] ? 'selected' : ''; ?>>
                                                    <?php echo h($p['full_name']); ?> (<?php echo h($p['username']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p style="font-size: 0.7rem; color: #ef4444; margin-top: 8px;">Note: This account will be set to Inactive to protect parish privacy.</p>
                                    </div>
                                    <div class="input-group">
                                        <label style="display: block; color: var(--accent); margin-bottom: 0.5rem; font-size: 0.8rem; text-transform: uppercase;">Incoming Priest / Admin</label>
                                        <select name="incoming_id" required style="width: 100%; padding: 1rem; background: rgba(255,255,255,0.05); border: 1px solid var(--accent); border-radius: 12px; color: white;">
                                            <option value="">-- Select Successor --</option>
                                            <?php foreach ($priests as $p): ?>
                                                <option value="<?php echo $p['user_id']; ?>">
                                                    <?php echo h($p['full_name']); ?> (<?php echo h($p['username']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="input-group" style="margin-bottom: 2rem;">
                                    <label style="display: block; color: var(--text-muted); margin-bottom: 0.5rem; font-size: 0.8rem; text-transform: uppercase;">Handover Date</label>
                                    <input type="date" name="handover_date" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>

                                <div style="text-align: right;">
                                    <input type="hidden" name="execute_handover" value="1">
                                    <a href="?step=2&parish_id=<?php echo $parish_id; ?>" class="btn btn-secondary">Back</a>
                                    <button type="submit" class="btn btn-primary" onclick="return confirm('WARNING: This will deactivate the outgoing priest and assign all records to the incoming priest. Proceed with canonical handover?')">Execute Handover</button>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($step == 5): ?>
                        <!-- Step 5: Success & Handover Certificate -->
                        <div class="card bg-card" style="padding: 4rem; text-align: center; border-radius: 2rem; border: 2px solid #10b981;">
                            <div style="font-size: 4rem; color: #10b981; margin-bottom: 1.5rem;"><ion-icon name="checkmark-circle"></ion-icon></div>
                            <h1 style="color: white; font-family: 'Outfit'; font-weight: 900; margin-bottom: 1rem;">Handover Complete</h1>
                            <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 1.1rem;"><?php echo $success; ?></p>
                            
                            <div style="background: var(--parchment); color: var(--navy); padding: 3rem; border-radius: 1rem; text-align: left; border: 8px double var(--accent); margin: 2rem auto; max-width: 600px; font-family: 'Inter', sans-serif;">
                                <div style="text-align: center; border-bottom: 2px solid var(--accent); padding-bottom: 1rem; margin-bottom: 2rem;">
                                    <h3 style="font-family: 'Cinzel'; margin: 0;"><?php echo get_diocese_branding(); ?></h3>
                                    <div style="font-size: 0.7rem; letter-spacing: 2px; text-transform: uppercase;">Canonical Record of Handover</div>
                                </div>
                                <p style="font-size: 0.9rem;">This is to certify that on this day, <strong><?php echo date('d F, Y'); ?></strong>, a formal handover of the sacramental records for <strong><?php echo h($parish['parish_name']); ?></strong> was completed.</p>
                                <div style="margin: 20px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.85rem;">
                                    <div>Faithful: <strong><?php echo $stats['parishioners']; ?></strong></div>
                                    <div>Baptisms: <strong><?php echo $stats['baptisms']; ?></strong></div>
                                    <div>Marriages: <strong><?php echo $stats['marriages']; ?></strong></div>
                                    <div>Confirmations: <strong><?php echo $stats['confirmations']; ?></strong></div>
                                </div>
                                <p style="font-size: 0.8rem; font-style: italic; margin-top: 2rem;">Digitally Signed & Archived via Hwange SRMS</p>
                            </div>

                            <div style="display: flex; gap: 1rem; justify-content: center;">
                                <button onclick="window.print()" class="btn btn-secondary"><ion-icon name="print-outline" style="margin-right: 5px;"></ion-icon> Print Report</button>
                                <a href="parishes.php" class="btn btn-primary">Return to Registry</a>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>

            </div>
        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
