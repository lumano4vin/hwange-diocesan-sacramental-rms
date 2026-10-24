<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parish Financial Audit & Remittance Ledger
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/diocesan_financial_categories.php';

require_admin();

$parish_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if ($parish_id <= 0) {
    set_flash("Invalid parish selected.", "error");
    redirect("admin/finance_overview.php?year=$year");
    exit;
}

// Fetch Parish Details
$parish = db_fetch("
    SELECT p.*, u.full_name as priest_name
    FROM parishes p
    LEFT JOIN users u ON p.priest_in_charge_id = u.user_id
    WHERE p.parish_id = ?
", [$parish_id]);

if (!$parish) {
    set_flash("Parish not found.", "error");
    redirect("admin/finance_overview.php?year=$year");
    exit;
}

$year_payment = db_year_sql('payment_date');
$year_bap = db_year_sql('date_of_baptism');
$year_marr = db_year_sql('date_of_marriage');
$year_conf = db_year_sql('date_of_confirmation');

// 1. Fetch Monthly Submissions for this year
$submissions_raw = db_fetchAll("
    SELECT s.*, u.full_name as submitter_name, v.full_name as verifier_name
    FROM parish_financial_submissions s
    LEFT JOIN users u ON s.submitted_by = u.user_id
    LEFT JOIN users v ON s.verified_by = v.user_id
    WHERE s.parish_id = ? AND s.fiscal_year = ?
    ORDER BY s.fiscal_month ASC
", [$parish_id, $year]);

// Index submissions by month (1-12)
$monthly_submissions = [];
foreach ($submissions_raw as $sub) {
    $monthly_submissions[(int)$sub['fiscal_month']] = $sub;
}

// 1b. Aggregate Annual Special Diocesan Collections (8 Appeals)
$special_definitions = get_annual_diocesan_special_collections();
$special_annual_summary = [];
$total_special_remitted = 0.0;

foreach ($special_definitions as $s_key => $s_meta) {
    $special_annual_summary[$s_key] = [
        'meta' => $s_meta,
        'total' => 0.0,
        'months' => [],
    ];
}

foreach ($submissions_raw as $sub) {
    $m_num = (int)$sub['fiscal_month'];
    $m_name = date('M', mktime(0, 0, 0, $m_num, 1));
    $det = !empty($sub['financial_details']) ? json_decode($sub['financial_details'], true) : [];
    $sc = $det['special_collections'] ?? [];
    $inc = $det['income'] ?? [];
    $exp = $det['expenditure'] ?? [];

    foreach ($special_definitions as $s_key => $s_meta) {
        $amount = (float)($sc[$s_key] ?? 0.0);

        // Fallback to 23-line items if not explicitly stored in special_collections map
        if ($amount == 0.0) {
            if ($s_key === 'holy_land' && !empty($inc[17])) {
                $amount = (float)$inc[17];
            } elseif ($s_key === 'lenten_collection' && !empty($inc[20])) {
                $amount = (float)$inc[20];
            } elseif ($s_key === 'peters_pence' && !empty($inc[22])) {
                $amount = (float)$inc[22];
            } elseif ($s_key === 'missionary_childhood' && !empty($exp[19])) {
                $amount = (float)$exp[19];
            } elseif ($s_key === 'mission_sunday' && !empty($exp[21])) {
                $amount = (float)$exp[21];
            }
        }

        if ($amount > 0) {
            $special_annual_summary[$s_key]['total'] += $amount;
            $special_annual_summary[$s_key]['months'][] = $m_name . " ($" . number_format($amount, 2) . ")";
            $total_special_remitted += $amount;
        }
    }
}

// 2. Fetch Payments Ledger for this year
$payments = db_fetchAll("
    SELECT p.*, u.full_name as recorded_by_name
    FROM financial_payments p
    LEFT JOIN users u ON p.recorded_by = u.user_id
    WHERE p.parish_id = ? AND {$year_payment} = ?
    ORDER BY p.payment_date DESC, p.payment_id DESC
", [$parish_id, $year]);

// 3. Aggregate Financial Stats
$total_collections = 0;
$total_tithe = 0;
$total_offerings = 0;
$total_other = 0;
$target_levy = 0;

foreach ($submissions_raw as $sub) {
    $total_collections += (float)$sub['total_collections'];
    $total_tithe += (float)($sub['tithe'] ?? 0);
    $total_offerings += (float)$sub['sacramental_offerings'];
    $total_other += (float)$sub['other_income'];
    $target_levy += (float)$sub['calculated_levy'];
}

$total_assessable = $total_collections + $total_tithe;
$total_parish_retained = ($total_assessable - $target_levy) + $total_offerings + $total_other;
$paid_levy = (float)array_sum(array_column($payments, 'amount'));
$balance_due = $target_levy - $paid_levy;
$collection_rate = $target_levy > 0 ? min(100, round(($paid_levy / $target_levy) * 100)) : 0;

// 4. Sacramental Cross-Audit (counts for this parish in this year)
$bap_count = (int)(db_fetch("SELECT COUNT(*) as c FROM baptisms WHERE parish_id = ? AND {$year_bap} = ?", [$parish_id, $year])['c'] ?? 0);
$marr_count = (int)(db_fetch("SELECT COUNT(*) as c FROM marriages WHERE parish_id = ? AND {$year_marr} = ?", [$parish_id, $year])['c'] ?? 0);
$conf_count = (int)(db_fetch("SELECT COUNT(*) as c FROM confirmations WHERE parish_id = ? AND {$year_conf} = ?", [$parish_id, $year])['c'] ?? 0);

$header_title = h($parish['parish_name']) . " — Financial Audit";
$header_subtitle = "Detailed assessment, monthly remittances, and payment ledger for $year.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($parish['parish_name']); ?> - Financial Audit - Hwange RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .audit-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 1.75rem;
            border: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 2rem;
        }
        .stats-grid-5 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .stat-card-gold {
            background: rgba(251, 191, 36, 0.05);
            border: 1px solid rgba(251, 191, 36, 0.2);
            border-radius: 16px;
            padding: 1.25rem;
        }
        .stat-card-green {
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 16px;
            padding: 1.25rem;
        }
        .stat-card-blue {
            background: rgba(56, 189, 248, 0.05);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 16px;
            padding: 1.25rem;
        }
        .stat-card-red {
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 16px;
            padding: 1.25rem;
        }
        .audit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            margin-top: 1rem;
        }
        .audit-table th {
            text-align: left;
            padding: 10px 12px;
            background: rgba(255,255,255,0.03);
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.65rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .audit-table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            color: #e2e8f0;
        }
        .audit-table tr:hover {
            background: rgba(255,255,255,0.02);
        }
        .badge-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.65rem;
            font-weight: 700;
        }
        .badge-submitted { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .badge-verified { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-reconciled { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
        .badge-none { background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px dashed rgba(148, 163, 184, 0.3); }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.75);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #1e293b;
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 20px;
            width: 90%;
            max-width: 520px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0;">

                <!-- Navigation Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <a href="finance_overview.php?year=<?php echo $year; ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.8rem; margin-bottom: 0.75rem;">
                            <ion-icon name="arrow-back-outline"></ion-icon>
                            <span>Back to Financial Stewardship</span>
                        </a>
                        <h2 style="font-family: 'Outfit'; color: white; margin: 0; display: flex; align-items: center; gap: 12px;">
                            <span><?php echo h($parish['parish_name']); ?></span>
                            <span style="font-size: 0.75rem; background: rgba(255,255,255,0.06); padding: 4px 12px; border-radius: 20px; color: var(--accent); font-weight: 700;">
                                <?php echo h($parish['deanery'] ?? 'Deanery'); ?>
                            </span>
                        </h2>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                            Priest in Charge: <strong><?php echo h($parish['priest_name'] ?: 'Not Assigned'); ?></strong>
                        </p>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: center;">
                        <!-- Fiscal Year Dropdown -->
                        <form method="GET" style="display: flex; align-items: center; gap: 8px;">
                            <input type="hidden" name="id" value="<?php echo $parish_id; ?>">
                            <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">YEAR:</label>
                            <select name="year" onchange="this.form.submit()" class="input-field" style="padding: 6px 12px; font-size: 0.85rem; width: auto;">
                                <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </form>

                        <!-- Open Record Payment Modal -->
                        <button onclick="openPaymentModal()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; padding: 0.65rem 1.25rem;">
                            <ion-icon name="add-circle-outline" style="font-size: 1.1rem;"></ion-icon>
                            <span>Record Payment</span>
                        </button>
                    </div>
                </div>

                <!-- Financial Metrics Cards -->
                <div class="stats-grid-5">
                    <div class="stat-card stat-card-gold">
                        <span class="stat-label">Assessable Base (Coll + Tithe)</span>
                        <span class="stat-value" style="color: var(--accent); font-size: 1.35rem;">$<?php echo number_format($total_assessable, 2); ?></span>
                        <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 4px;">
                            Sunday Coll: $<?php echo number_format($total_collections, 2); ?> | Tithe/Unsupo/Mutulo: $<?php echo number_format($total_tithe, 2); ?>
                        </div>
                    </div>

                    <div class="stat-card stat-card-blue">
                        <span class="stat-label">Diocesan Levy Target (10%)</span>
                        <span class="stat-value" style="color: #38bdf8; font-size: 1.35rem;">$<?php echo number_format($target_levy, 2); ?></span>
                        <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 4px;">
                            10% of (Sunday Coll. + Tithe/Unsupo/Mutulo) only
                        </div>
                    </div>

                    <div class="stat-card stat-card-green">
                        <span class="stat-label">Actual Remitted</span>
                        <span class="stat-value" style="color: #10b981; font-size: 1.35rem;">$<?php echo number_format($paid_levy, 2); ?></span>
                        <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 4px;">
                            <?php echo count($payments); ?> payments recorded
                        </div>
                    </div>

                    <div class="stat-card <?php echo $balance_due <= 0 ? 'stat-card-green' : 'stat-card-red'; ?>">
                        <span class="stat-label"><?php echo $balance_due <= 0 ? 'Surplus / Cleared' : 'Outstanding Balance'; ?></span>
                        <span class="stat-value" style="color: <?php echo $balance_due <= 0 ? '#10b981' : '#ef4444'; ?>; font-size: 1.35rem;">
                            $<?php echo number_format(abs($balance_due), 2); ?>
                        </span>
                        <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 4px;">
                            Compliance: <?php echo $collection_rate; ?>%
                        </div>
                    </div>

                    <div class="stat-card stat-card-gold">
                        <span class="stat-label">Parish Retained Income</span>
                        <span class="stat-value" style="color: #fff; font-size: 1.35rem;">$<?php echo number_format($total_parish_retained, 2); ?></span>
                        <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 4px;">
                            Stole: $<?php echo number_format($total_offerings, 2); ?> | Other: $<?php echo number_format($total_other, 2); ?>
                        </div>
                    </div>
                </div>

                <!-- Monthly Assessment Grid -->
                <div class="audit-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 1rem; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <h3 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1.15rem;">Monthly Remittance Schedule (FY <?php echo $year; ?>)</h3>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 2px 0 0 0;">
                                Note: 10% Diocesan Levy applies exclusively to <strong>Sunday Collections</strong> & <strong>Tithe/Unsupo/Mutulo</strong>. Parish/Mission collections, stole fees & other local incomes are 100% retained by the parish.
                            </p>
                        </div>
                        <span style="font-size: 0.75rem; color: var(--accent); font-weight: 700;"><?php echo count($submissions_raw); ?> of 12 Months Submitted</span>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Sunday Collection</th>
                                    <th>Tithe/Unsupo/Mutulo</th>
                                    <th>Assessable Base</th>
                                    <th>10% Levy</th>
                                    <th>Stole Fees (Retained)</th>
                                    <th>Other (Retained)</th>
                                    <th>Status</th>
                                    <th>Submitted By</th>
                                    <th>Verified By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <?php 
                                        $month_name = date('F', mktime(0, 0, 0, $m, 1));
                                        $sub = $monthly_submissions[$m] ?? null;
                                    ?>
                                    <tr>
                                        <td style="font-weight: 700; color: #fff;"><?php echo $month_name; ?></td>
                                        <?php if ($sub): ?>
                                            <?php 
                                                $coll = (float)$sub['total_collections'];
                                                $tith = (float)($sub['tithe'] ?? 0);
                                                $base = $coll + $tith;
                                                $levy = (float)$sub['calculated_levy'];
                                                $stole = (float)$sub['sacramental_offerings'];
                                                $oth = (float)$sub['other_income'];
                                            ?>
                                            <td>$<?php echo number_format($coll, 2); ?></td>
                                            <td>$<?php echo number_format($tith, 2); ?></td>
                                            <td style="font-weight: 700; color: var(--accent);">$<?php echo number_format($base, 2); ?></td>
                                            <td style="font-weight: 800; color: #10b981;">$<?php echo number_format($levy, 2); ?></td>
                                            <td style="color: #38bdf8; font-size: 0.75rem;">$<?php echo number_format($stole, 2); ?></td>
                                            <td style="color: #38bdf8; font-size: 0.75rem;">$<?php echo number_format($oth, 2); ?></td>
                                            <td>
                                                <?php 
                                                    $st = $sub['status'] ?? 'Submitted';
                                                    $badge_class = 'badge-submitted';
                                                    if ($st === 'Verified') $badge_class = 'badge-verified';
                                                    if ($st === 'Reconciled') $badge_class = 'badge-reconciled';
                                                ?>
                                                <span class="badge-status <?php echo $badge_class; ?>"><?php echo h($st); ?></span>
                                            </td>
                                            <td style="font-size: 0.7rem; color: var(--text-muted);">
                                                <?php echo h($sub['submitter_name'] ?? 'Parish User'); ?><br>
                                                <span style="font-size: 0.65rem; opacity: 0.7;"><?php echo date('d M Y', strtotime($sub['created_at'])); ?></span>
                                            </td>
                                            <td style="font-size: 0.7rem; color: var(--text-muted);">
                                                <?php if ($sub['verifier_name']): ?>
                                                    <span style="color: #10b981; font-weight: 600;"><?php echo h($sub['verifier_name']); ?></span><br>
                                                    <span style="font-size: 0.65rem; opacity: 0.7;"><?php echo date('d M Y', strtotime($sub['verified_at'])); ?></span>
                                                <?php else: ?>
                                                    <span style="opacity: 0.4;">Unverified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                    <form action="../actions/verify_financial_submission.php" method="POST" style="display: inline-flex; align-items: center;">
                                                        <input type="hidden" name="submission_id" value="<?php echo $sub['submission_id']; ?>">
                                                        <input type="hidden" name="parish_id" value="<?php echo $parish_id; ?>">
                                                        <input type="hidden" name="year" value="<?php echo $year; ?>">
                                                        <select name="status" onchange="this.form.submit()" class="input-field" style="padding: 4px 6px; font-size: 0.7rem; border-radius: 6px; width: auto;">
                                                            <option value="Submitted" <?php echo $sub['status'] == 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                                                            <option value="Verified" <?php echo $sub['status'] == 'Verified' ? 'selected' : ''; ?>>Verified</option>
                                                            <option value="Reconciled" <?php echo $sub['status'] == 'Reconciled' ? 'selected' : ''; ?>>Reconciled</option>
                                                            <option value="Disputed" <?php echo $sub['status'] == 'Disputed' ? 'selected' : ''; ?>>Disputed</option>
                                                        </select>
                                                    </form>
                                                    <a href="../actions/export_financial_docx.php?submission_id=<?php echo $sub['submission_id']; ?>" 
                                                       class="btn btn-secondary" style="font-size: 0.65rem; padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;" title="Download Word (.docx)">
                                                        <ion-icon name="document-text-outline"></ion-icon>
                                                        <span>.docx</span>
                                                    </a>
                                                </div>
                                            </td>
                                        <?php else: ?>
                                            <td colspan="6" style="color: var(--text-muted); font-style: italic;">No submission filed for this month</td>
                                            <td><span class="badge-status badge-none">Pending</span></td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td>—</td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Annual Special Diocesan Collections (8 Appeals) -->
                <div class="audit-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 1rem; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <h3 style="font-family: 'Outfit'; color: #38bdf8; margin: 0; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
                                <ion-icon name="globe-outline"></ion-icon>
                                <span>Annual Special Diocesan Collections (8 Appeals for FY <?php echo $year; ?>)</span>
                            </h3>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 2px 0 0 0;">
                                These 8 Pontifical and Diocesan collections occur <strong>once a year</strong>. 100% of receipts are remitted to Chancery / Holy See (0% diocesan assessment). Parish/mission collections are separate and kept locally.
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Total Remitted: </span>
                            <span style="font-size: 1.1rem; font-weight: 800; color: #38bdf8;">$<?php echo number_format($total_special_remitted, 2); ?></span>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th>Appeal / Collection</th>
                                    <th>Canonical Season</th>
                                    <th>Canonical Beneficiary</th>
                                    <th>FY <?php echo $year; ?> Remitted</th>
                                    <th>Remittance Month(s)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($special_annual_summary as $s_key => $s_data): ?>
                                    <?php 
                                        $meta = $s_data['meta'];
                                        $tot = $s_data['total'];
                                        $has_remitted = $tot > 0;
                                    ?>
                                    <tr>
                                        <td style="font-weight: 700; color: #fff;">
                                            <?php echo h($meta['title']); ?>
                                        </td>
                                        <td style="color: #38bdf8; font-size: 0.75rem;">
                                            <?php echo h($meta['season']); ?>
                                        </td>
                                        <td style="font-size: 0.75rem; color: var(--text-muted); max-width: 250px;">
                                            <?php echo h($meta['canonical_target']); ?>
                                        </td>
                                        <td style="font-weight: 800; font-size: 0.95rem; color: <?php echo $has_remitted ? '#10b981' : '#94a3b8'; ?>;">
                                            $<?php echo number_format($tot, 2); ?>
                                        </td>
                                        <td style="font-size: 0.75rem; color: #e2e8f0;">
                                            <?php if (!empty($s_data['months'])): ?>
                                                <?php echo implode(', ', $s_data['months']); ?>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-style: italic;">Not yet filed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($has_remitted): ?>
                                                <span class="badge-status badge-verified" style="display: inline-flex; align-items: center; gap: 4px;">
                                                    <ion-icon name="checkmark-circle"></ion-icon>
                                                    <span>Remitted</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-status badge-none" style="display: inline-flex; align-items: center; gap: 4px;">
                                                    <ion-icon name="time-outline"></ion-icon>
                                                    <span>Awaiting Season</span>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payments Ledger Table -->
                <div class="audit-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 1rem;">
                        <div>
                            <h3 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1.15rem;">Chancery Payment & Remittance Ledger</h3>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 2px 0 0 0;">Actual receipts and bank transfers credited toward the diocesan assessment.</p>
                        </div>
                        <button onclick="openPaymentModal()" class="btn btn-secondary" style="font-size: 0.75rem; padding: 6px 12px;">+ Record Payment</button>
                    </div>

                    <?php if (empty($payments)): ?>
                        <div style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted);">
                            <ion-icon name="receipt-outline" style="font-size: 2.5rem; opacity: 0.3; margin-bottom: 8px;"></ion-icon>
                            <p style="font-size: 0.85rem; margin: 0;">No payments have been recorded for this parish in <?php echo $year; ?>.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="audit-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Receipt / Ref Number</th>
                                        <th>Payment Method</th>
                                        <th>Amount ($)</th>
                                        <th>Currency</th>
                                        <th>Notes</th>
                                        <th>Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $pay): ?>
                                        <tr>
                                            <td style="font-weight: 700; color: #fff;"><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></td>
                                            <td style="font-family: monospace; color: var(--accent);"><?php echo h($pay['reference_number'] ?: 'N/A'); ?></td>
                                            <td>
                                                <span style="background: rgba(255,255,255,0.05); padding: 3px 8px; border-radius: 6px; font-size: 0.75rem;">
                                                    <?php echo h($pay['payment_method'] ?? 'Bank Transfer'); ?>
                                                </span>
                                            </td>
                                            <td style="font-weight: 800; color: #10b981; font-size: 0.95rem;">
                                                $<?php echo number_format((float)$pay['amount'], 2); ?>
                                            </td>
                                            <td><?php echo h($pay['currency'] ?? 'USD'); ?></td>
                                            <td style="font-size: 0.75rem; color: var(--text-muted); max-width: 250px;"><?php echo h($pay['notes'] ?: '—'); ?></td>
                                            <td style="font-size: 0.75rem; color: var(--text-muted);">
                                                <?php echo h($pay['recorded_by_name'] ?? 'Chancery'); ?><br>
                                                <span style="font-size: 0.65rem; opacity: 0.6;"><?php echo date('d M Y H:i', strtotime($pay['created_at'])); ?></span>
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

    <!-- Record Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.75rem;">
                <h3 style="font-family: 'Outfit'; color: var(--accent); margin: 0; font-size: 1.25rem;">Record Chancery Payment</h3>
                <span onclick="closePaymentModal()" style="font-size: 1.5rem; color: var(--text-muted); cursor: pointer;">&times;</span>
            </div>

            <form action="../actions/save_financial_payment.php" method="POST">
                <input type="hidden" name="parish_id" value="<?php echo $parish_id; ?>">
                <input type="hidden" name="fiscal_year" value="<?php echo $year; ?>">
                <input type="hidden" name="return_to" value="details">

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Parish</label>
                    <input type="text" class="input-field" value="<?php echo h($parish['parish_name']); ?>" disabled style="opacity: 0.7;">
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Payment Amount *</label>
                        <input type="number" step="0.01" name="amount" class="input-field" required placeholder="0.00" autofocus>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Currency</label>
                        <select name="currency" class="input-field">
                            <option value="USD">USD ($)</option>
                            <option value="ZiG">ZiG</option>
                            <option value="ZAR">ZAR (R)</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Payment Date *</label>
                        <input type="date" name="payment_date" class="input-field" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Payment Method</label>
                        <select name="payment_method" class="input-field">
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="EcoCash / Mobile">EcoCash / Mobile</option>
                            <option value="InnBucks">InnBucks</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Reference / Receipt Number</label>
                    <input type="text" name="reference_number" class="input-field" placeholder="e.g. STANBIC-TR-84920">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Notes / Remarks</label>
                    <textarea name="notes" class="input-field" rows="2" placeholder="e.g. Q1 Diocesan assessment remittance"></textarea>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="closePaymentModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 800; padding: 0.75rem 1.5rem;">Save Payment</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <script>
        function openPaymentModal() {
            document.getElementById('paymentModal').classList.add('active');
        }
        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
        }
        window.onclick = function(event) {
            const modal = document.getElementById('paymentModal');
            if (event.target === modal) {
                closePaymentModal();
            }
        };
    </script>
</body>
</html>
