<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parish Financial Submission & Remittance Form
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$parish_id = $_SESSION['parish_id'] ?? null;
if (!$parish_id) {
    // If admin or chancellor without a parish, redirect to admin finance overview
    if (is_admin()) {
        redirect("admin/finance_overview.php");
        exit;
    }
    die("You must be assigned to a parish to submit financial reports.");
}

$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total_collections = isset($_POST['total_collections']) ? (float)$_POST['total_collections'] : 0.0;
    $tithe = isset($_POST['tithe']) ? (float)$_POST['tithe'] : 0.0;
    $sacramental_offerings = isset($_POST['sacramental_offerings']) ? (float)$_POST['sacramental_offerings'] : 0.0;
    $other_income = isset($_POST['other_income']) ? (float)$_POST['other_income'] : 0.0;
    $fiscal_year = isset($_POST['fiscal_year']) ? (int)$_POST['fiscal_year'] : (int)date('Y');
    $fiscal_month = isset($_POST['fiscal_month']) ? (int)$_POST['fiscal_month'] : (int)date('m');
    $currency = trim($_POST['currency'] ?? 'USD');
    
    // Canonical Diocesan Rule: 10% Levy is strictly applied to Sunday Collections and Tithe ONLY.
    // Sacramental offerings and other parish income are retained 100% by the Parish/Mission.
    $assessable_income = $total_collections + $tithe;
    $calculated_levy = $assessable_income * 0.10;

    // Check if a submission already exists for this parish, year, and month
    $existing = db_fetch("SELECT submission_id FROM parish_financial_submissions WHERE parish_id = ? AND fiscal_year = ? AND fiscal_month = ?", [
        $parish_id, $fiscal_year, $fiscal_month
    ]);

    if ($existing) {
        $sql = "UPDATE parish_financial_submissions 
                SET total_collections = ?, tithe = ?, sacramental_offerings = ?, other_income = ?, calculated_levy = ?, currency = ?, status = 'Submitted', submitted_by = ?, created_at = CURRENT_TIMESTAMP
                WHERE submission_id = ?";
        db_query($sql, [$total_collections, $tithe, $sacramental_offerings, $other_income, $calculated_levy, $currency, $_SESSION['user_id'], $existing['submission_id']]);
        
        log_audit($_SESSION['user_id'] ?? 0, 'UPDATE', 'parish_financial_submissions', $existing['submission_id'], "Updated remittance submission for month $fiscal_month/$fiscal_year (Levy: $currency " . number_format($calculated_levy, 2) . " on Collections: $currency " . number_format($total_collections, 2) . " + Tithe: $currency " . number_format($tithe, 2) . ")");
        set_flash("Financial report for " . date('F', mktime(0,0,0,$fiscal_month,1)) . " $fiscal_year updated successfully.", "success");
    } else {
        $sql = "INSERT INTO parish_financial_submissions (parish_id, fiscal_year, fiscal_month, total_collections, tithe, sacramental_offerings, other_income, calculated_levy, currency, status, submitted_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', ?)";
        db_query($sql, [$parish_id, $fiscal_year, $fiscal_month, $total_collections, $tithe, $sacramental_offerings, $other_income, $calculated_levy, $currency, $_SESSION['user_id']]);
        $sub_id = db_last_insert_id();

        log_audit($_SESSION['user_id'] ?? 0, 'CREATE', 'parish_financial_submissions', $sub_id, "Submitted remittance for month $fiscal_month/$fiscal_year (Levy: $currency " . number_format($calculated_levy, 2) . " on Collections: $currency " . number_format($total_collections, 2) . " + Tithe: $currency " . number_format($tithe, 2) . ")");
        set_flash("Financial report for " . date('F', mktime(0,0,0,$fiscal_month,1)) . " $fiscal_year submitted successfully.", "success");
    }

    redirect("dashboard/finance_submission.php?year=$fiscal_year");
    exit;
}

// Fetch submissions for this parish in selected year
$my_submissions = db_fetchAll("
    SELECT * FROM parish_financial_submissions 
    WHERE parish_id = ? AND fiscal_year = ? 
    ORDER BY fiscal_month ASC
", [$parish_id, $selected_year]);

$sub_by_month = [];
foreach ($my_submissions as $s) {
    $sub_by_month[(int)$s['fiscal_month']] = $s;
}

$header_title = "Financial Remittance";
$header_subtitle = "Submit monthly financial assessment to the Diocesan Chancery.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Remittance - Hwange Diocese</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=1.1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;900&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .finance-layout {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 2rem;
            align-items: flex-start;
        }
        @media (max-width: 992px) {
            .finance-layout { grid-template-columns: 1fr; }
        }
        .form-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.8px;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .form-section-box {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .audit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }
        .audit-table th {
            text-align: left;
            padding: 10px;
            background: rgba(255,255,255,0.03);
            color: var(--text-muted);
            font-size: 0.65rem;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .audit-table td {
            padding: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            color: #e2e8f0;
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
        .badge-pending { background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px dashed rgba(148, 163, 184, 0.3); }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0;">
                
                <div class="finance-layout">
                    <!-- Submission Form -->
                    <div class="card bg-card" style="padding: 2.25rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <h2 style="font-family: 'Outfit'; color: var(--accent); margin: 0; font-size: 1.35rem;">Monthly Remittance Entry</h2>
                                <p style="font-size: 0.75rem; color: var(--text-muted); margin: 4px 0 0 0;">
                                    Diocesan Rule: 10% Levy applies exclusively to <strong>Sunday Collections</strong> & <strong>Tithe</strong>.
                                </p>
                            </div>
                            <span style="font-size: 0.7rem; background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 4px 10px; border-radius: 12px; font-weight: 700;">
                                Canonical Formula
                            </span>
                        </div>
                        
                        <form method="POST" class="premium-form">
                            <!-- Period & Currency Selection -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                                <div class="form-group">
                                    <label class="form-label" style="font-size: 0.75rem;">Fiscal Year</label>
                                    <select name="fiscal_year" class="input-field">
                                        <?php for($y = date('Y') + 1; $y >= 2022; $y--): ?>
                                            <option value="<?php echo $y; ?>" <?php echo $y == $selected_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" style="font-size: 0.75rem;">Fiscal Month</label>
                                    <select name="fiscal_month" class="input-field">
                                        <?php for($m=1; $m<=12; $m++): ?>
                                            <option value="<?php echo $m; ?>" <?php echo $m == $selected_month ? 'selected' : ''; ?>>
                                                <?php echo date('F', mktime(0,0,0,$m,1)); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
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

                            <!-- Section 1: Assessable Income (Subject to 10% Diocesan Assessment) -->
                            <div class="form-section-box" style="border-color: rgba(251, 191, 36, 0.25); background: rgba(251, 191, 36, 0.03);">
                                <div class="form-section-title" style="color: var(--accent);">
                                    <ion-icon name="calculator-outline"></ion-icon>
                                    <span>I. Assessable Income (Subject to 10% Diocesan Levy)</span>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label class="form-label" style="font-size: 0.75rem;">Total Sunday Collections *</label>
                                        <input type="number" step="0.01" name="total_collections" id="input-collections" class="input-field calc-assessable" required placeholder="0.00">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" style="font-size: 0.75rem;">Parish Tithe *</label>
                                        <input type="number" step="0.01" name="tithe" id="input-tithe" class="input-field calc-assessable" required placeholder="0.00">
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 8px; border-top: 1px dashed rgba(251, 191, 36, 0.15);">
                                    <span style="font-size: 0.7rem; color: var(--text-muted);">Assessable Base (Collections + Tithe):</span>
                                    <span id="assessable-base-display" style="font-size: 0.85rem; font-weight: 700; color: var(--accent);">$0.00</span>
                                </div>
                            </div>

                            <!-- Section 2: Parish / Mission Retained Income (100% retained by Parish) -->
                            <div class="form-section-box" style="border-color: rgba(56, 189, 248, 0.25); background: rgba(56, 189, 248, 0.02);">
                                <div class="form-section-title" style="color: #38bdf8;">
                                    <ion-icon name="shield-checkmark-outline"></ion-icon>
                                    <span>II. Parish / Mission Retained Income (0% Levy — 100% Retained)</span>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label class="form-label" style="font-size: 0.75rem;">Sacramental / Stole Fees</label>
                                        <input type="number" step="0.01" name="sacramental_offerings" id="input-stole" class="input-field calc-retained" placeholder="0.00">
                                        <span style="font-size: 0.65rem; color: var(--text-muted);">Mass stipends, baptism/marriage fees</span>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" style="font-size: 0.75rem;">Other Parish Income</label>
                                        <input type="number" step="0.01" name="other_income" id="input-other" class="input-field calc-retained" placeholder="0.00">
                                        <span style="font-size: 0.65rem; color: var(--text-muted);">Fundraising, donations, rentals, etc.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Calculation Display Summary -->
                            <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); padding: 1.25rem; border-radius: 1rem; margin-bottom: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <div>
                                        <span style="font-size: 0.75rem; color: #10b981; font-weight: 800; text-transform: uppercase;">10% Diocesan Assessment Levy</span>
                                        <div style="font-size: 0.65rem; color: var(--text-muted);">(Calculated as: 10% of Sunday Collections & Tithe only)</div>
                                    </div>
                                    <span id="levy-display" style="font-size: 1.5rem; font-weight: 900; color: #10b981;">$0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; padding-top: 8px; border-top: 1px solid rgba(16, 185, 129, 0.15);">
                                    <span style="color: var(--text-muted);">Net Retained by Parish/Mission:</span>
                                    <span id="parish-retained-display" style="font-weight: 700; color: #fff;">$0.00</span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-weight: 800;">Submit Financial Assessment</button>
                        </form>
                    </div>

                    <!-- Parish Submission History Card -->
                    <div class="card bg-card" style="padding: 2rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                            <h3 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1.2rem;">Filing History (FY <?php echo $selected_year; ?>)</h3>
                            <span style="font-size: 0.75rem; color: var(--accent); font-weight: 700;">
                                <?php echo count($my_submissions); ?> / 12 Filed
                            </span>
                        </div>

                        <div style="overflow-x: auto;">
                            <table class="audit-table">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Collections</th>
                                        <th>Tithe</th>
                                        <th>10% Levy</th>
                                        <th>Retained</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <?php 
                                            $sub = $sub_by_month[$m] ?? null;
                                            $m_name = date('M', mktime(0,0,0,$m,1));
                                        ?>
                                        <tr>
                                            <td style="font-weight: 700; color: #fff;"><?php echo $m_name; ?></td>
                                            <?php if ($sub): ?>
                                                <?php 
                                                    $coll = (float)$sub['total_collections'];
                                                    $tith = (float)($sub['tithe'] ?? 0);
                                                    $stole = (float)$sub['sacramental_offerings'];
                                                    $oth = (float)$sub['other_income'];
                                                    $levy = (float)$sub['calculated_levy'];
                                                    $retained = ($coll + $tith - $levy) + $stole + $oth;
                                                    $curr = $sub['currency'] ?? 'USD';
                                                    $st = $sub['status'] ?? 'Submitted';
                                                    $b_class = 'badge-submitted';
                                                    if ($st === 'Verified') $b_class = 'badge-verified';
                                                    if ($st === 'Reconciled') $b_class = 'badge-reconciled';
                                                ?>
                                                <td>$<?php echo number_format($coll, 2); ?></td>
                                                <td>$<?php echo number_format($tith, 2); ?></td>
                                                <td style="font-weight: 700; color: #10b981;">$<?php echo number_format($levy, 2); ?></td>
                                                <td style="color: #38bdf8; font-size: 0.75rem;">$<?php echo number_format($retained, 2); ?></td>
                                                <td><span class="badge-status <?php echo $b_class; ?>"><?php echo h($st); ?></span></td>
                                            <?php else: ?>
                                                <td colspan="4" style="color: var(--text-muted); font-size: 0.75rem; font-style: italic;">Not Submitted</td>
                                                <td><span class="badge-status badge-pending">Pending</span></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
    <script src="../assets/js/main.js?v=1.6.2"></script>
    <?php include '../includes/privacy_footer.php'; ?>

    <script>
        function recalculate() {
            const coll = parseFloat(document.getElementById('input-collections').value || 0);
            const tithe = parseFloat(document.getElementById('input-tithe').value || 0);
            const stole = parseFloat(document.getElementById('input-stole').value || 0);
            const other = parseFloat(document.getElementById('input-other').value || 0);

            // Canonical Diocesan Calculation: 10% on Sunday Collections and Tithe ONLY
            const assessable = coll + tithe;
            const levy = assessable * 0.10;
            const parishRetained = (assessable - levy) + stole + other;

            document.getElementById('assessable-base-display').innerText = '$' + assessable.toFixed(2);
            document.getElementById('levy-display').innerText = '$' + levy.toFixed(2);
            document.getElementById('parish-retained-display').innerText = '$' + parishRetained.toFixed(2);
        }

        document.querySelectorAll('.calc-assessable, .calc-retained').forEach(input => {
            input.addEventListener('input', recalculate);
        });
    </script>
</body>
</html>
