<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Financial Assessment & Levy Oversight
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/diocesan_financial_categories.php';

require_admin();

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$year_payment = db_year_sql('payment_date');
$year_baptism = db_year_sql('date_of_baptism');

// Aggregate 8 Annual Special Diocesan Collections across the entire Diocese
$special_definitions = get_annual_diocesan_special_collections();
$diocesan_specials = [];
$total_special_all_parishes = 0.0;

foreach ($special_definitions as $s_key => $s_meta) {
    $diocesan_specials[$s_key] = [
        'meta' => $s_meta,
        'total' => 0.0,
        'parish_count' => 0
    ];
}

$all_year_submissions = db_fetchAll("
    SELECT parish_id, financial_details 
    FROM parish_financial_submissions 
    WHERE fiscal_year = ?
", [$year]);

foreach ($all_year_submissions as $sub_item) {
    if (empty($sub_item['financial_details'])) continue;
    $det = json_decode($sub_item['financial_details'], true);
    if (!$det) continue;
    $sc = $det['special_collections'] ?? [];
    $inc = $det['income'] ?? [];
    $exp = $det['expenditure'] ?? [];

    foreach ($special_definitions as $s_key => $s_meta) {
        $amount = (float)($sc[$s_key] ?? 0.0);
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
            $diocesan_specials[$s_key]['total'] += $amount;
            $diocesan_specials[$s_key]['parish_count']++;
            $total_special_all_parishes += $amount;
        }
    }
}

// Fetch Parish Financial Health
$finance_data = db_fetchAll("
    SELECT p.parish_id, p.parish_name,
           (SELECT SUM(total_collections + COALESCE(tithe, 0)) FROM parish_financial_submissions WHERE parish_id = p.parish_id AND fiscal_year = ?) as annual_assessable,
           (SELECT SUM(calculated_levy) FROM parish_financial_submissions WHERE parish_id = p.parish_id AND fiscal_year = ?) as target_levy,
           (SELECT SUM(amount) FROM financial_payments WHERE parish_id = p.parish_id AND {$year_payment} = ?) as paid_levy,
           (SELECT COUNT(*) FROM baptisms WHERE parish_id = p.parish_id AND {$year_baptism} = ?) as bap_count
    FROM parishes p
    ORDER BY p.parish_name ASC
", [$year, $year, $year, $year]);

$header_title = "Financial Stewardship";
$header_subtitle = "Oversight of Diocesan Levies and Parish Financial Assessments for $year.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Oversight - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .finance-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .parish-finance-card { 
            background: var(--card-bg); 
            border-radius: 24px; 
            padding: 1.5rem; 
            border: 1px solid rgba(255,255,255,0.05);
            transition: transform 0.3s;
        }
        .parish-finance-card:hover { transform: translateY(-5px); border-color: var(--accent); }
        .progress-bar { width: 100%; height: 8px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden; margin: 15px 0; }
        .progress-fill { height: 100%; background: var(--accent); transition: width 0.5s; }
        .metric-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .metric-label { font-size: 0.75rem; color: var(--text-muted); }
        .metric-value { font-weight: 700; font-size: 0.85rem; color: white; }

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
                
                <!-- Controls Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1.5rem;">Diocesan Assessment Ledger</h2>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Track parish quota compliance and chancery remittance receipts.</p>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <form method="GET" style="display: flex; align-items: center; gap: 8px;">
                            <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">YEAR:</label>
                            <select name="year" onchange="this.form.submit()" class="input-field" style="padding: 6px 12px; font-size: 0.85rem; width: auto;">
                                <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </form>
                        <button onclick="openPaymentModal('', '')" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; padding: 0.65rem 1.25rem;">
                            <ion-icon name="add-circle-outline" style="font-size: 1.1rem;"></ion-icon>
                            <span>Record Payment</span>
                        </button>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="stat-card">
                        <span class="stat-label">Total Diocesan Levy Target (<?php echo $year; ?>)</span>
                        <span class="stat-value" style="color: var(--accent);">$<?php 
                            $total = array_sum(array_column($finance_data, 'target_levy'));
                            echo number_format($total, 2);
                        ?></span>
                        <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 4px;">
                            10% of (Sunday Collection + Tithe/Unsupo/Mutulo)
                        </div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Levy Remitted to Date</span>
                        <span class="stat-value" style="color: #10b981;">$<?php 
                            $paid = array_sum(array_column($finance_data, 'paid_levy'));
                            echo number_format($paid, 2);
                        ?></span>
                        <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 4px;">
                            Across all parishes in <?php echo $year; ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Special Collections Remitted</span>
                        <span class="stat-value" style="color: #38bdf8;">$<?php echo number_format($total_special_all_parishes, 2); ?></span>
                        <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 4px;">
                            8 Pontifical & Diocesan Appeals (100% pass-through)
                        </div>
                    </div>
                </div>

                <!-- 8 Annual Special Diocesan Collections Summary Card -->
                <div style="background: var(--card-bg); border-radius: 20px; padding: 1.5rem; border: 1px solid rgba(56, 189, 248, 0.2); margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 8px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 1rem;">
                        <div>
                            <h3 style="font-family: 'Outfit'; color: #38bdf8; margin: 0; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
                                <ion-icon name="globe-outline"></ion-icon>
                                <span>Annual Special Diocesan Collections (8 Appeals for FY <?php echo $year; ?>)</span>
                            </h3>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 3px 0 0 0;">
                                These 8 canonical appeals occur <strong>once a year</strong> and are 100% remitted to Chancery / Holy See (0% levy). Parish/mission collections are separate and kept 100% locally.
                            </p>
                        </div>
                        <span style="font-size: 0.75rem; background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); padding: 4px 10px; border-radius: 8px; font-weight: 700;">
                            Diocesan Total: $<?php echo number_format($total_special_all_parishes, 2); ?>
                        </span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;">
                        <?php foreach ($diocesan_specials as $s_key => $s_item): ?>
                            <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 6px;">
                                    <div>
                                        <div style="font-weight: 700; color: #fff; font-size: 0.8rem;"><?php echo h($s_item['meta']['title']); ?></div>
                                        <div style="font-size: 0.65rem; color: #38bdf8; margin-top: 2px;">Season: <?php echo h($s_item['meta']['season']); ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 800; font-size: 0.95rem; color: <?php echo $s_item['total'] > 0 ? '#10b981' : '#94a3b8'; ?>;">
                                            $<?php echo number_format($s_item['total'], 2); ?>
                                        </div>
                                        <div style="font-size: 0.65rem; color: var(--text-muted);">
                                            <?php echo $s_item['parish_count']; ?> parish<?php echo $s_item['parish_count'] == 1 ? '' : 'es'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Parish Cards Grid -->
                <div class="finance-grid">
                    <?php foreach ($finance_data as $p): ?>
                        <?php 
                            $target = (float)($p['target_levy'] ?: 0);
                            $paid = (float)($p['paid_levy'] ?: 0);
                            $percent = $target > 0 ? min(100, ($paid / $target) * 100) : 0;
                            $standing_color = ($percent >= 90) ? '#10b981' : (($percent >= 50) ? '#fbbf24' : '#ef4444');
                        ?>
                        <div class="parish-finance-card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <h3 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1.15rem;"><?php echo h($p['parish_name']); ?></h3>
                                <div class="status-badge" style="background: <?php echo $standing_color; ?>22; color: <?php echo $standing_color; ?>; border: 1px solid <?php echo $standing_color; ?>44;">
                                    <?php echo $p['bap_count']; ?> Bap
                                </div>
                            </div>

                            <div class="metric-row">
                                <span class="metric-label">Levy Target (10% of Sunday Coll. + Tithe/Unsupo/Mutulo)</span>
                                <span class="metric-value">$<?php echo number_format($target, 2); ?></span>
                            </div>
                            <div class="metric-row">
                                <span class="metric-label">Actual Paid</span>
                                <span class="metric-value" style="color: <?php echo $standing_color; ?>;">$<?php echo number_format($paid, 2); ?></span>
                            </div>
                            <div class="metric-row">
                                <span class="metric-label">Balance</span>
                                <span class="metric-value" style="color: <?php echo ($target - $paid <= 0) ? '#10b981' : '#ef4444'; ?>;">
                                    $<?php echo number_format(abs($target - $paid), 2); ?> <?php echo ($target - $paid <= 0) ? '(Cleared)' : 'Due'; ?>
                                </span>
                            </div>

                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percent; ?>%; background: <?php echo $standing_color; ?>;"></div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                                <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;"><?php echo round($percent); ?>% REMITTED</span>
                                <div style="display: flex; gap: 8px;">
                                    <a href="parish_finance_details.php?id=<?php echo $p['parish_id']; ?>&year=<?php echo $year; ?>" class="btn btn-secondary" style="font-size: 0.65rem; padding: 6px 12px;">Review Audit</a>
                                    <button onclick="openPaymentModal('<?php echo $p['parish_id']; ?>', '<?php echo addslashes($p['parish_name']); ?>')" class="btn btn-primary" style="font-size: 0.65rem; padding: 6px 12px; background: <?php echo $standing_color; ?>;">Record Payment</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
                <input type="hidden" name="fiscal_year" value="<?php echo $year; ?>">
                <input type="hidden" name="return_to" value="overview">

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Select Parish *</label>
                    <select name="parish_id" id="modal_parish_id" class="input-field" required>
                        <option value="">-- Choose Parish / Mission --</option>
                        <?php foreach ($finance_data as $p): ?>
                            <option value="<?php echo $p['parish_id']; ?>"><?php echo h($p['parish_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
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
                    <textarea name="notes" class="input-field" rows="2" placeholder="e.g. Diocesan quota payment for FY <?php echo $year; ?>"></textarea>
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
        function openPaymentModal(parishId, parishName) {
            const modal = document.getElementById('paymentModal');
            const select = document.getElementById('modal_parish_id');
            if (parishId) {
                select.value = parishId;
            }
            modal.classList.add('active');
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
