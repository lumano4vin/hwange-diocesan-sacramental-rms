<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Canonical Mass Intentions & Stipends Register (Canons 945-958)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user_role = strtolower($_SESSION['role'] ?? '');
$is_admin = ($user_role === 'admin' || $user_role === 'chancellor');

$parish_id = (int)($_SESSION['parish_id'] ?? 0);
if ($is_admin) {
    if (!empty($_GET['parish_id'])) {
        $parish_id = (int)$_GET['parish_id'];
    } elseif ($parish_id <= 0) {
        $first_parish = db_fetch("SELECT parish_id FROM parishes ORDER BY parish_name ASC LIMIT 1");
        $parish_id = (int)($first_parish['parish_id'] ?? 1);
    }
}

if ($parish_id <= 0) {
    die("You must be assigned to a parish to view the Mass Intentions Register.");
}

$parish = db_fetch("SELECT * FROM parishes WHERE parish_id = ?", [$parish_id]);
$all_parishes = $is_admin ? db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC") : [];
$parish_priests = db_fetchAll("SELECT user_id, full_name FROM users WHERE role IN ('priest', 'deacon', 'admin', 'chancellor') ORDER BY full_name ASC");

$filter_status = trim($_GET['status'] ?? 'all');
$filter_type = trim($_GET['type'] ?? 'all');
$search_q = trim($_GET['q'] ?? '');

$where = "WHERE m.parish_id = ?";
$params = [$parish_id];

if ($filter_status !== 'all' && !empty($filter_status)) {
    $where .= " AND m.status = ?";
    $params[] = $filter_status;
}

if ($filter_type !== 'all' && !empty($filter_type)) {
    $where .= " AND m.intention_type = ?";
    $params[] = $filter_type;
}

if (!empty($search_q)) {
    $where .= " AND (m.donor_name LIKE ? OR m.intention_text LIKE ? OR m.donor_phone LIKE ?)";
    $like = "%$search_q%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$intentions = db_fetchAll("
    SELECT m.*, u.full_name as celebrant_name, r.full_name as recorded_by_name
    FROM mass_intentions m
    LEFT JOIN users u ON m.assigned_priest_id = u.user_id
    LEFT JOIN users r ON m.recorded_by = r.user_id
    $where
    ORDER BY 
        CASE WHEN m.status = 'pending' THEN 1 WHEN m.status = 'scheduled' THEN 2 ELSE 3 END,
        m.requested_date ASC, m.intention_id DESC
", $params);

// Stats
$pending_count = (int)(db_fetch("SELECT COUNT(*) as c FROM mass_intentions WHERE parish_id = ? AND status IN ('pending', 'scheduled')", [$parish_id])['c'] ?? 0);
$celebrated_count = (int)(db_fetch("SELECT COUNT(*) as c FROM mass_intentions WHERE parish_id = ? AND status = 'celebrated'", [$parish_id])['c'] ?? 0);
$total_stipends = (float)(db_fetch("SELECT SUM(stipend_amount) as s FROM mass_intentions WHERE parish_id = ?", [$parish_id])['s'] ?? 0);

$header_title = h($parish['parish_name']) . " — Mass Intentions";
$header_subtitle = "Canonical Register of Intentions & Stipends (Canons 945–958)";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($parish['parish_name']); ?> - Mass Intentions - Hwange RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=1.1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .stats-grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .card-stat {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            border: 1px solid rgba(255,255,255,0.06);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card-stat .label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            font-weight: 700;
        }
        .card-stat .val {
            font-size: 1.65rem;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            margin: 6px 0 2px 0;
        }
        .table-card {
            background: var(--card-bg);
            border-radius: 18px;
            padding: 1.5rem;
            border: 1px solid rgba(255,255,255,0.06);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .intention-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.85rem;
        }
        .intention-table th {
            text-align: left;
            padding: 12px 14px;
            color: var(--text-muted);
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .intention-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            vertical-align: middle;
        }
        .intention-table tr:hover td {
            background: rgba(255,255,255,0.02);
        }
        .badge-type {
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-block;
        }
        .type-repose { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); }
        .type-thanksgiving { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .type-healing { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .type-special { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
        
        .badge-status-pending { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
        .badge-status-celebrated { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-status-transferred { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(5px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            padding: 2rem;
            max-height: 90vh;
            overflow-y: auto;
        }
        @media print {
            .no-print, .sidebar, .header-actions, .filter-panel, .btn { display: none !important; }
            body, .main-content { background: white !important; color: black !important; padding: 0 !important; }
            .card-stat, .table-card { border: 1px solid #ccc !important; box-shadow: none !important; background: white !important; color: black !important; }
            .intention-table th, .intention-table td { color: black !important; border-bottom: 1px solid #ddd !important; }
        }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="container-fluid" style="padding: 1.5rem 0;">
                
                <!-- Top Actions Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 style="font-family: 'Outfit'; font-weight: 800; margin: 0; font-size: 1.5rem; display: flex; align-items: center; gap: 8px;">
                            <ion-icon name="flame-outline" style="color: #fbbf24;"></ion-icon>
                            <span>Canonical Mass Intentions & Stipends</span>
                        </h2>
                        <p style="color: var(--text-muted); font-size: 0.8rem; margin: 3px 0 0 0;">
                            Official register of Mass intentions and stipends prescribed by Canons 945–958.
                        </p>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px;" class="no-print">
                        <?php if ($is_admin && !empty($all_parishes)): ?>
                            <form method="GET" style="display: flex; align-items: center; gap: 6px;">
                                <select name="parish_id" class="input-field" onchange="this.form.submit()" style="padding: 6px 12px; font-size: 0.82rem; font-weight: 700;">
                                    <?php foreach ($all_parishes as $p_item): ?>
                                        <option value="<?php echo $p_item['parish_id']; ?>" <?php echo $p_item['parish_id'] == $parish_id ? 'selected' : ''; ?>>
                                            <?php echo h($p_item['parish_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        <?php endif; ?>

                        <button onclick="window.print()" class="btn btn-secondary" style="font-size: 0.82rem; padding: 0.6rem 1rem;">
                            <ion-icon name="print-outline"></ion-icon> Print Sacristy Schedule
                        </button>

                        <button onclick="openIntentionModal()" class="btn btn-primary" style="font-weight: 700; font-size: 0.85rem; padding: 0.6rem 1.25rem;">
                            <ion-icon name="add-circle-outline"></ion-icon> + Book Intention
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="stats-grid-4">
                    <div class="card-stat">
                        <div class="label">Pending Intentions</div>
                        <div class="val" style="color: #fbbf24;"><?php echo $pending_count; ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Awaiting celebration (Canon 953)</div>
                    </div>
                    <div class="card-stat">
                        <div class="label">Celebrated Masses</div>
                        <div class="val" style="color: #10b981;"><?php echo $celebrated_count; ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Fulfilled intentions on record</div>
                    </div>
                    <div class="card-stat">
                        <div class="label">Total Stipends Received</div>
                        <div class="val" style="color: #38bdf8;">$<?php echo number_format($total_stipends, 2); ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Sacramental offerings for altar</div>
                    </div>
                    <div class="card-stat">
                        <div class="label">Parish Ministers</div>
                        <div class="val" style="color: #a78bfa;"><?php echo count($parish_priests); ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Priests available for celebration</div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 10px;" class="no-print">
                    <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <?php if ($is_admin): ?>
                            <input type="hidden" name="parish_id" value="<?php echo $parish_id; ?>">
                        <?php endif; ?>

                        <input type="text" name="q" value="<?php echo h($search_q); ?>" class="input-field" placeholder="Search donor or intention..." style="padding: 7px 12px; font-size: 0.82rem; min-width: 200px;">

                        <select name="status" class="input-field" style="padding: 7px 10px; font-size: 0.82rem;">
                            <option value="all">All Statuses</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending Celebration</option>
                            <option value="scheduled" <?php echo $filter_status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="celebrated" <?php echo $filter_status === 'celebrated' ? 'selected' : ''; ?>>Celebrated (Fulfilled)</option>
                            <option value="transferred" <?php echo $filter_status === 'transferred' ? 'selected' : ''; ?>>Transferred (Binated)</option>
                        </select>

                        <select name="type" class="input-field" style="padding: 7px 10px; font-size: 0.82rem;">
                            <option value="all">All Intention Types</option>
                            <option value="repose" <?php echo $filter_type === 'repose' ? 'selected' : ''; ?>>Repose of the Soul (RIP)</option>
                            <option value="thanksgiving" <?php echo $filter_type === 'thanksgiving' ? 'selected' : ''; ?>>Thanksgiving</option>
                            <option value="healing_petition" <?php echo $filter_type === 'healing_petition' ? 'selected' : ''; ?>>Healing / Petition</option>
                            <option value="special_intention" <?php echo $filter_type === 'special_intention' ? 'selected' : ''; ?>>Special Intention</option>
                        </select>

                        <button type="submit" class="btn btn-secondary" style="padding: 7px 12px; font-size: 0.82rem;">Filter</button>
                        <?php if ($filter_status !== 'all' || $filter_type !== 'all' || !empty($search_q)): ?>
                            <a href="mass_intentions.php<?php echo $is_admin ? '?parish_id=' . $parish_id : ''; ?>" class="btn btn-secondary" style="padding: 7px 10px; font-size: 0.78rem; color: var(--text-muted);">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Table Card -->
                <div class="table-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 0.75rem;">
                        <div>
                            <h3 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1.15rem;">Intentions Journal</h3>
                            <p style="font-size: 0.72rem; color: var(--text-muted); margin: 2px 0 0 0;">
                                Showing <?php echo count($intentions); ?> records for <?php echo h($parish['parish_name']); ?>.
                            </p>
                        </div>
                    </div>

                    <?php if (empty($intentions)): ?>
                        <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                            <ion-icon name="flame-outline" style="font-size: 3rem; opacity: 0.3; margin-bottom: 8px;"></ion-icon>
                            <h4 style="color: white; margin: 0 0 6px 0;">No Mass intentions booked</h4>
                            <p style="font-size: 0.8rem; max-width: 450px; margin: 0 auto 1.5rem auto;">
                                Log parishioner Mass requests with stipends and assign them to priests in accordance with Canon 958.
                            </p>
                            <button onclick="openIntentionModal()" class="btn btn-primary" style="font-weight: 700;">+ Book First Intention</button>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="intention-table">
                                <thead>
                                    <tr>
                                        <th>Requested Date</th>
                                        <th>Donor</th>
                                        <th>Type</th>
                                        <th>Intention / Intention Wording</th>
                                        <th>Stipend</th>
                                        <th>Celebrant</th>
                                        <th>Status</th>
                                        <th class="no-print">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($intentions as $m): ?>
                                        <?php 
                                            $t_class = 'type-special';
                                            if ($m['intention_type'] === 'repose') $t_class = 'type-repose';
                                            elseif ($m['intention_type'] === 'thanksgiving') $t_class = 'type-thanksgiving';
                                            elseif ($m['intention_type'] === 'healing_petition') $t_class = 'type-healing';

                                            $is_pending = ($m['status'] === 'pending' || $m['status'] === 'scheduled');
                                        ?>
                                        <tr>
                                            <td style="font-weight: 700; white-space: nowrap; color: #fff;">
                                                <?php echo !empty($m['requested_date']) ? date('d M Y', strtotime($m['requested_date'])) : '<span style="color: var(--text-muted);">Open Date</span>'; ?>
                                            </td>
                                            <td>
                                                <div style="font-weight: 700; color: #e2e8f0;"><?php echo h($m['donor_name']); ?></div>
                                                <?php if (!empty($m['donor_phone'])): ?>
                                                    <div style="font-size: 0.7rem; color: var(--text-muted);"><?php echo h($m['donor_phone']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge-type <?php echo $t_class; ?>">
                                                    <?php echo ucwords(str_replace('_', ' ', $m['intention_type'])); ?>
                                                </span>
                                            </td>
                                            <td style="max-width: 320px; font-size: 0.8rem; color: #fff;">
                                                <?php echo h($m['intention_text']); ?>
                                            </td>
                                            <td style="font-weight: 800; color: #10b981; white-space: nowrap;">
                                                <?php echo h($m['currency']); ?> <?php echo number_format((float)$m['stipend_amount'], 2); ?>
                                            </td>
                                            <td style="font-size: 0.78rem;">
                                                <?php echo h($m['celebrant_name'] ?: 'Unassigned'); ?>
                                                <?php if (!empty($m['celebrated_date'])): ?>
                                                    <div style="font-size: 0.68rem; color: #10b981;">
                                                        on <?php echo date('d M Y', strtotime($m['celebrated_date'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge-type badge-status-<?php echo h($m['status']); ?>">
                                                    <?php echo ucfirst($m['status']); ?>
                                                </span>
                                            </td>
                                            <td class="no-print" style="white-space: nowrap;">
                                                <div style="display: inline-flex; gap: 6px;">
                                                    <?php if ($is_pending): ?>
                                                        <form action="../actions/update_mass_intention_status.php" method="POST" style="display: inline;">
                                                            <input type="hidden" name="intention_id" value="<?php echo $m['intention_id']; ?>">
                                                            <input type="hidden" name="status" value="celebrated">
                                                            <input type="hidden" name="celebrated_date" value="<?php echo date('Y-m-d'); ?>">
                                                            <input type="hidden" name="return_to" value="dashboard/mass_intentions.php?parish_id=<?php echo $parish_id; ?>">
                                                            <button type="submit" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.7rem; color: #10b981;" title="Mark Celebrated Today">
                                                                <ion-icon name="checkmark-done-outline"></ion-icon> Celebrated
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <button onclick='editIntention(<?php echo json_encode($m); ?>)' class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.7rem;" title="Edit Intention">
                                                        <ion-icon name="create-outline"></ion-icon>
                                                    </button>
                                                </div>
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

    <!-- Modal: Book / Edit Intention -->
    <div id="intentionModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.75rem;">
                <h3 id="intModalTitle" style="font-family: 'Outfit'; color: #fbbf24; margin: 0; font-size: 1.25rem;">Book Mass Intention</h3>
                <span onclick="closeIntentionModal()" style="font-size: 1.5rem; color: var(--text-muted); cursor: pointer;">&times;</span>
            </div>

            <form action="../actions/save_mass_intention.php" method="POST">
                <input type="hidden" name="intention_id" id="int_id" value="0">
                <input type="hidden" name="parish_id" value="<?php echo $parish_id; ?>">
                <input type="hidden" name="return_to" value="dashboard/mass_intentions.php?parish_id=<?php echo $parish_id; ?>">

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Donor / Requester Name *</label>
                        <input type="text" name="donor_name" id="int_donor" class="input-field" required placeholder="e.g. Mrs. Maria Sibanda">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Donor Phone</label>
                        <input type="text" name="donor_phone" id="int_phone" class="input-field" placeholder="077... / 071...">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Intention Wording / Prayer Request *</label>
                    <textarea name="intention_text" id="int_text" class="input-field" rows="3" required placeholder="e.g. For the repose of the soul of the late Bernard Moyo (RIP); or Thanksgiving for the blessing of a new child..."></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Intention Type *</label>
                        <select name="intention_type" id="int_type" class="input-field" required>
                            <option value="repose">Repose of the Soul (RIP / Deceased)</option>
                            <option value="thanksgiving">Thanksgiving / Birthday / Blessing</option>
                            <option value="healing_petition">Healing / Sick / Recovery</option>
                            <option value="special_intention">Special Intention / Exams / Family</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Requested Celebration Date</label>
                        <input type="date" name="requested_date" id="int_date" class="input-field">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Stipend Amount</label>
                        <input type="number" step="0.01" name="stipend_amount" id="int_amount" class="input-field" placeholder="5.00">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Currency</label>
                        <select name="currency" id="int_currency" class="input-field">
                            <option value="USD">USD ($)</option>
                            <option value="ZiG">ZiG</option>
                            <option value="ZAR">ZAR (R)</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Assigned Priest (Celebrant)</label>
                        <select name="assigned_priest_id" id="int_priest" class="input-field">
                            <option value="">Unassigned (Any Available Priest)</option>
                            <?php foreach ($parish_priests as $pr): ?>
                                <option value="<?php echo $pr['user_id']; ?>"><?php echo h($pr['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Status *</label>
                        <select name="status" id="int_status" class="input-field" required>
                            <option value="pending">Pending</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="celebrated">Celebrated (Fulfilled)</option>
                            <option value="transferred">Transferred (Binated)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Notes</label>
                    <input type="text" name="notes" id="int_notes" class="input-field" placeholder="e.g. Mass at Sacred Heart Outstation; 1st anniversary...">
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeIntentionModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 800; padding: 0.7rem 1.5rem;">Save Intention</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openIntentionModal() {
            document.getElementById('int_id').value = '0';
            document.getElementById('intModalTitle').textContent = 'Book Mass Intention';
            document.getElementById('int_donor').value = '';
            document.getElementById('int_phone').value = '';
            document.getElementById('int_text').value = '';
            document.getElementById('int_type').value = 'repose';
            document.getElementById('int_amount').value = '5.00';
            document.getElementById('int_currency').value = 'USD';
            document.getElementById('int_date').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('int_priest').value = '';
            document.getElementById('int_status').value = 'pending';
            document.getElementById('int_notes').value = '';

            document.getElementById('intentionModal').classList.add('active');
        }

        function closeIntentionModal() {
            document.getElementById('intentionModal').classList.remove('active');
        }

        function editIntention(row) {
            document.getElementById('int_id').value = row.intention_id;
            document.getElementById('intModalTitle').textContent = 'Edit Mass Intention #' + row.intention_id;
            document.getElementById('int_donor').value = row.donor_name;
            document.getElementById('int_phone').value = row.donor_phone || '';
            document.getElementById('int_text').value = row.intention_text;
            document.getElementById('int_type').value = row.intention_type || 'repose';
            document.getElementById('int_amount').value = row.stipend_amount;
            document.getElementById('int_currency').value = row.currency || 'USD';
            document.getElementById('int_date').value = row.requested_date || '';
            document.getElementById('int_priest').value = row.assigned_priest_id || '';
            document.getElementById('int_status').value = row.status || 'pending';
            document.getElementById('int_notes').value = row.notes || '';

            document.getElementById('intentionModal').classList.add('active');
        }
    </script>
</body>
</html>
