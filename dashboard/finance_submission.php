<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parish Monthly Financial Statement (Official 23-Line Double-Entry Cashbook)
 * Aligned with the Diocesan Chancery Administration Standard
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/diocesan_financial_categories.php';

require_login();

$parish_id = $_SESSION['parish_id'] ?? null;
if (!$parish_id) {
    if (is_admin()) {
        redirect("admin/finance_overview.php");
        exit;
    }
    die("You must be assigned to a parish to submit financial reports.");
}

$parish = db_fetch("SELECT * FROM parishes WHERE parish_id = ?", [$parish_id]);
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

$income_cats = get_diocesan_income_categories();
$expenditure_cats = get_diocesan_expenditure_categories();
$annual_spec_cats = get_annual_diocesan_special_collections();
$custom_cats = get_parish_custom_categories($parish_id);
$custom_income_cats = array_filter($custom_cats, function($c) { return $c['category_type'] === 'income'; });
$custom_expenditure_cats = array_filter($custom_cats, function($c) { return $c['category_type'] === 'expenditure'; });

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fiscal_year = isset($_POST['fiscal_year']) ? (int)$_POST['fiscal_year'] : $selected_year;
    $fiscal_month = isset($_POST['fiscal_month']) ? (int)$_POST['fiscal_month'] : $selected_month;
    $currency = trim($_POST['currency'] ?? 'USD');

    $inc_post = $_POST['inc'] ?? [];
    $exp_post = $_POST['exp'] ?? [];
    $spec_post = $_POST['spec'] ?? [];
    $custom_inc_post = $_POST['custom_inc'] ?? [];
    $custom_exp_post = $_POST['custom_exp'] ?? [];

    $income_values = [];
    $expenditure_values = [];
    $special_collections = [];
    $custom_income_values = [];
    $custom_expenditure_values = [];
    $custom_assessable_sum = 0.0;

    for ($i = 1; $i <= 23; $i++) {
        $income_values[$i] = isset($inc_post[$i]) ? max(0, (float)$inc_post[$i]) : 0.0;
        $expenditure_values[$i] = isset($exp_post[$i]) ? max(0, (float)$exp_post[$i]) : 0.0;
    }

    // Capture the 8 Annual Special Diocesan Collections (occur once a year)
    foreach ($annual_spec_cats as $s_key => $s_meta) {
        $special_collections[$s_key] = isset($spec_post[$s_key]) ? max(0, (float)$spec_post[$s_key]) : 0.0;
    }

    // Capture Custom Categories
    foreach ($custom_income_cats as $cc) {
        $cid = (int)$cc['category_id'];
        $val = isset($custom_inc_post[$cid]) ? max(0, (float)$custom_inc_post[$cid]) : 0.0;
        $custom_income_values[$cid] = $val;
        if (!empty($cc['is_assessable'])) {
            $custom_assessable_sum += $val;
        }
    }
    foreach ($custom_expenditure_cats as $cc) {
        $cid = (int)$cc['category_id'];
        $val = isset($custom_exp_post[$cid]) ? max(0, (float)$custom_exp_post[$cid]) : 0.0;
        $custom_expenditure_values[$cid] = $val;
    }

    // Sync specific special collections with corresponding cashbook rows if provided
    if (!empty($special_collections['holy_land'])) {
        $income_values[17] = $special_collections['holy_land'];
        $expenditure_values[18] = $special_collections['holy_land'];
    }
    if (!empty($special_collections['lenten_collection'])) {
        $income_values[20] = $special_collections['lenten_collection'];
    }
    if (!empty($special_collections['peters_pence'])) {
        $income_values[22] = $special_collections['peters_pence'];
    }
    if (!empty($special_collections['missionary_childhood'])) {
        $expenditure_values[19] = $special_collections['missionary_childhood'];
    }

    // Canonical Diocesan Rule: 10% Levy is strictly applied to Line 3 (Tithe/Unsupo/Mutulo) & Line 4 (Sunday Collection) ONLY
    $tithe_val = $income_values[3];
    $sunday_collection_val = $income_values[4];
    $calculated_levy = ($tithe_val + $sunday_collection_val + $custom_assessable_sum) * 0.10;
    $expenditure_values[4] = $calculated_levy; // Override expenditure line 4 with exact 10%

    $total_income = array_sum($income_values) + array_sum($custom_income_values);
    $total_expenditure = array_sum($expenditure_values) + array_sum($custom_expenditure_values);
    $net_operating = $total_income - $total_expenditure;

    $opening_balance = isset($_POST['opening_balance']) ? (float)$_POST['opening_balance'] : 0.0;
    $closing_balance = $net_operating + $opening_balance;

    $cash_at_hand = isset($_POST['cash_at_hand']) ? (float)$_POST['cash_at_hand'] : $closing_balance;
    $cash_in_bank = isset($_POST['cash_in_bank']) ? (float)$_POST['cash_in_bank'] : 0.0;
    $savings = isset($_POST['savings']) ? (float)$_POST['savings'] : 0.0;
    $investments = isset($_POST['investments']) ? (float)$_POST['investments'] : 0.0;

    // First-class columns for fast Chancery analytics
    $sacramental_offerings = $income_values[14] ?? 0.0; // Repository / Sacramental goods
    $other_income = $total_income - ($tithe_val + $sunday_collection_val);

    $financial_details_json = json_encode([
        'income' => $income_values,
        'expenditure' => $expenditure_values,
        'special_collections' => $special_collections,
        'custom_income' => $custom_income_values,
        'custom_expenditure' => $custom_expenditure_values,
        'opening_balance' => $opening_balance,
        'closing_balance' => $closing_balance,
        'cash_at_hand' => $cash_at_hand,
        'cash_in_bank' => $cash_in_bank,
        'savings' => $savings,
        'investments' => $investments,
        'notes' => trim($_POST['report_notes'] ?? '')
    ]);

    // Check if report already exists for this month
    $existing = db_fetch("SELECT submission_id FROM parish_financial_submissions WHERE parish_id = ? AND fiscal_year = ? AND fiscal_month = ?", [
        $parish_id, $fiscal_year, $fiscal_month
    ]);

    if ($existing) {
        $sql = "UPDATE parish_financial_submissions 
                SET total_collections = ?, tithe = ?, sacramental_offerings = ?, other_income = ?, calculated_levy = ?, 
                    total_income = ?, total_expenditure = ?, opening_balance = ?, closing_balance = ?,
                    cash_at_hand = ?, cash_in_bank = ?, savings = ?, investments = ?,
                    financial_details = ?, currency = ?, status = 'Submitted', submitted_by = ?, created_at = CURRENT_TIMESTAMP
                WHERE submission_id = ?";
        db_query($sql, [
            $sunday_collection_val, $tithe_val, $sacramental_offerings, $other_income, $calculated_levy,
            $total_income, $total_expenditure, $opening_balance, $closing_balance,
            $cash_at_hand, $cash_in_bank, $savings, $investments,
            $financial_details_json, $currency, $_SESSION['user_id'], $existing['submission_id']
        ]);

        log_audit($_SESSION['user_id'], 'UPDATE', 'parish_financial_submissions', $existing['submission_id'], 
            "Updated 23-line return for $fiscal_month/$fiscal_year (Tithe/Unsupo/Mutulo: $currency " . number_format($tithe_val, 2) . ", Sunday Coll: $currency " . number_format($sunday_collection_val, 2) . ", 10% Levy: $currency " . number_format($calculated_levy, 2) . ")");

        set_flash("Diocesan financial statement for " . date('F Y', mktime(0,0,0,$fiscal_month,1,$fiscal_year)) . " updated successfully.", "success");
    } else {
        $sql = "INSERT INTO parish_financial_submissions 
                (parish_id, fiscal_year, fiscal_month, total_collections, tithe, sacramental_offerings, other_income, calculated_levy,
                 total_income, total_expenditure, opening_balance, closing_balance, cash_at_hand, cash_in_bank, savings, investments,
                 financial_details, currency, status, submitted_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', ?)";
        db_query($sql, [
            $sunday_collection_val, $tithe_val, $sacramental_offerings, $other_income, $calculated_levy,
            $total_income, $total_expenditure, $opening_balance, $closing_balance,
            $cash_at_hand, $cash_in_bank, $savings, $investments,
            $financial_details_json, $currency, $_SESSION['user_id']
        ]);
        $target_sub_id = db_last_insert_id();

        log_audit($_SESSION['user_id'], 'CREATE', 'parish_financial_submissions', $target_sub_id, 
            "Submitted 23-line return for $fiscal_month/$fiscal_year (Tithe/Unsupo/Mutulo: $currency " . number_format($tithe_val, 2) . ", Sunday Coll: $currency " . number_format($sunday_collection_val, 2) . ", 10% Levy: $currency " . number_format($calculated_levy, 2) . ")");

        set_flash("Diocesan financial statement for " . date('F Y', mktime(0,0,0,$fiscal_month,1,$fiscal_year)) . " submitted successfully.", "success");
    }

    redirect("dashboard/finance_submission.php?year=$fiscal_year&month=$fiscal_month");
    exit;
}

// Load current selected report data
$current_sub = db_fetch("
    SELECT * FROM parish_financial_submissions 
    WHERE parish_id = ? AND fiscal_year = ? AND fiscal_month = ?
", [$parish_id, $selected_year, $selected_month]);

$current_details = !empty($current_sub['financial_details']) ? json_decode($current_sub['financial_details'], true) : [];
$saved_special = $current_details['special_collections'] ?? [];
$saved_custom_inc = $current_details['custom_income'] ?? [];
$saved_custom_exp = $current_details['custom_expenditure'] ?? [];

// Auto-lookup previous month's closing balance to pre-fill opening balance if new
$default_opening = 0.0;
if (!$current_sub) {
    $prev_month = ($selected_month == 1) ? 12 : $selected_month - 1;
    $prev_year = ($selected_month == 1) ? $selected_year - 1 : $selected_year;
    $prev_sub = db_fetch("SELECT closing_balance FROM parish_financial_submissions WHERE parish_id = ? AND fiscal_year = ? AND fiscal_month = ?", [
        $parish_id, $prev_year, $prev_month
    ]);
    if ($prev_sub) {
        $default_opening = (float)$prev_sub['closing_balance'];
    }
} else {
    $default_opening = (float)$current_sub['opening_balance'];
}

// Fetch all filed submissions for history table
$my_submissions = db_fetchAll("
    SELECT * FROM parish_financial_submissions 
    WHERE parish_id = ? AND fiscal_year = ? 
    ORDER BY fiscal_month ASC
", [$parish_id, $selected_year]);

$sub_by_month = [];
foreach ($my_submissions as $s) {
    $sub_by_month[(int)$s['fiscal_month']] = $s;
}

$header_title = "Monthly Financial Statement";
$header_subtitle = "Official Hwange Diocesan 23-Line Double-Entry Cashbook for " . h($parish['parish_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Statement - <?php echo h($parish['parish_name']); ?> - Hwange RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=1.1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;900&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .cashbook-container {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.06);
            margin-bottom: 2rem;
        }
        .diocesan-notice-banner {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(251, 191, 36, 0.2);
            border-left: 4px solid var(--accent);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.75rem;
        }
        .notice-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-top: 10px;
        }
        .notice-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 8px;
            padding: 10px 14px;
        }
        .ledger-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        @media (max-width: 1024px) {
            .ledger-columns { grid-template-columns: 1fr; }
        }
        .ledger-panel {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 1.25rem;
        }
        .ledger-panel-income { border-top: 4px solid var(--accent); }
        .ledger-panel-expenditure { border-top: 4px solid #ef4444; }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .ledger-row {
            display: grid;
            grid-template-columns: 35px 1fr 125px;
            gap: 8px;
            align-items: center;
            padding: 4px 0;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            font-size: 0.8rem;
        }
        .ledger-row:hover { background: rgba(255,255,255,0.02); }
        .ledger-row.highlight-levy {
            background: rgba(251, 191, 36, 0.08);
            border-left: 3px solid var(--accent);
            padding-left: 6px;
        }
        .ledger-row.highlight-auto {
            background: rgba(16, 185, 129, 0.08);
            border-left: 3px solid #10b981;
            padding-left: 6px;
        }
        .num-col { color: var(--text-muted); font-weight: 700; font-size: 0.75rem; text-align: center; }
        .label-col { color: #e2e8f0; }
        .input-num {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 5px 8px;
            border-radius: 6px;
            text-align: right;
            font-size: 0.8rem;
            font-weight: 600;
            width: 100%;
        }
        .input-num:focus { border-color: var(--accent); outline: none; }
        .input-readonly {
            background: rgba(16, 185, 129, 0.12);
            border-color: rgba(16, 185, 129, 0.3);
            color: #10b981;
            font-weight: 800;
        }
        .reconcile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        @media (max-width: 900px) {
            .reconcile-grid { grid-template-columns: 1fr; }
        }
        .reconcile-box {
            background: rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 1.25rem;
        }
        .reconcile-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 0.8rem;
        }
        .reconcile-total {
            font-weight: 800;
            font-size: 0.95rem;
            padding-top: 10px;
            margin-top: 6px;
            border-top: 2px solid rgba(255,255,255,0.1);
        }
        .badge-assess {
            font-size: 0.65rem;
            background: rgba(251, 191, 36, 0.2);
            color: var(--accent);
            border: 1px solid rgba(251, 191, 36, 0.4);
            padding: 1px 6px;
            border-radius: 4px;
            margin-left: 6px;
            font-weight: 700;
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
        .special-collection-card {
            background: rgba(56, 189, 248, 0.03);
            border: 1px solid rgba(56, 189, 248, 0.15);
            border-radius: 14px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        .special-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 12px;
            margin-top: 1rem;
        }
        .special-item {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 1.5rem 0;">

                <!-- Period Selection & Quick Actions Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1.4rem;">
                            <?php echo h($parish['parish_name']); ?>
                        </h2>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 3px 0 0 0;">
                            Official Diocesan Monthly Financial Return & Double-Entry Cashbook
                        </p>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <!-- Month / Year Selector -->
                        <form method="GET" style="display: flex; align-items: center; gap: 8px;">
                            <select name="month" onchange="this.form.submit()" class="input-field" style="padding: 6px 10px; font-size: 0.85rem; width: auto;">
                                <?php for($m=1; $m<=12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m == $selected_month ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0,0,0,$m,1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select name="year" onchange="this.form.submit()" class="input-field" style="padding: 6px 10px; font-size: 0.85rem; width: auto;">
                                <?php for($y = date('Y') + 1; $y >= 2022; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $selected_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </form>

                        <!-- Action Buttons -->
                        <a href="daily_ledger.php?year=<?php echo $selected_year; ?>&month=<?php echo $selected_month; ?>" 
                           class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; padding: 6px 12px;">
                            <ion-icon name="journal-outline" style="color: #38bdf8;"></ion-icon>
                            <span>Daily Cash Ledger</span>
                        </a>

                        <button type="button" id="btn-auto-sync" onclick="autoSyncFromDailyLedger()" 
                                class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; padding: 6px 12px; border-color: rgba(56, 189, 248, 0.4); color: #38bdf8;" 
                                title="Auto-populate this monthly return from your daily transactions">
                            <ion-icon name="flash-outline"></ion-icon>
                            <span>Auto-Sync from Daily Ledger</span>
                        </button>

                        <button type="button" onclick="openCategoryModal('income')" 
                                class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; padding: 6px 12px;">
                            <ion-icon name="pricetag-outline"></ion-icon>
                            <span>+ New Category</span>
                        </button>

                        <!-- Download Word Document Button (if saved) -->
                        <?php if ($current_sub): ?>
                            <a href="../actions/export_financial_docx.php?submission_id=<?php echo $current_sub['submission_id']; ?>" 
                               class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; padding: 6px 14px; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                                <ion-icon name="document-text-outline" style="font-size: 1.1rem;"></ion-icon>
                                <span>Download Word (.docx)</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Diocesan Financial Governance Notice -->
                <div class="diocesan-notice-banner">
                    <div style="display: flex; align-items: center; gap: 8px; color: var(--accent); font-weight: 800; font-size: 0.85rem;">
                        <ion-icon name="shield-checkmark" style="font-size: 1.2rem;"></ion-icon>
                        <span>DIOCESAN CANONICAL COLLECTION & ASSESSMENT GUIDELINES</span>
                    </div>
                    <div class="notice-grid">
                        <div class="notice-card" style="border-left: 3px solid var(--accent);">
                            <strong style="color: var(--accent); font-size: 0.75rem;">1. Diocesan Operating Quota (10% Levy)</strong>
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin: 3px 0 0 0;">
                                The 10% diocesan assessment is calculated <strong>exclusively</strong> on <strong>Sunday Collection</strong> and <strong>Tithe/Unsupo/Mutulo</strong>.
                            </p>
                        </div>
                        <div class="notice-card" style="border-left: 3px solid #10b981;">
                            <strong style="color: #10b981; font-size: 0.75rem;">2. Parish / Mission Incomes (100% Local Retained)</strong>
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin: 3px 0 0 0;">
                                Local parish collections, projects, stolen fees, farm/garden, and donations belong 100% to the parish/mission and are <strong>NOT</strong> part of diocesan collections.
                            </p>
                        </div>
                        <div class="notice-card" style="border-left: 3px solid #38bdf8;">
                            <strong style="color: #38bdf8; font-size: 0.75rem;">3. Annual Special Diocesan Collections (100% Remitted)</strong>
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin: 3px 0 0 0;">
                                The 8 annual Pontifical & Diocesan appeals occur <strong>once a year</strong> during their designated seasons. 100% is remitted to the Chancery (0% levy).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Main Cashbook Form -->
                <div class="cashbook-container">
                    <form method="POST" id="cashbook-form">
                        <input type="hidden" name="fiscal_year" value="<?php echo $selected_year; ?>">
                        <input type="hidden" name="fiscal_month" value="<?php echo $selected_month; ?>">

                        <!-- Header Info Banner -->
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); padding: 1rem 1.25rem; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(251, 191, 36, 0.1); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.5rem;">
                                    <ion-icon name="cash-outline"></ion-icon>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #fff; font-size: 0.9rem;">
                                        Reporting Period: <?php echo strtoupper(date('F Y', mktime(0,0,0,$selected_month,1,$selected_year))); ?>
                                    </div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                                        Assessable items: <strong>#3 Tithe/Unsupo/Mutulo</strong> + <strong>#4 Sunday Collection</strong>.
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">CURRENCY:</label>
                                <select name="currency" class="input-field" style="padding: 4px 8px; font-size: 0.8rem; width: auto;">
                                    <option value="USD" <?php echo ($current_sub['currency'] ?? 'USD') == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                    <option value="ZiG" <?php echo ($current_sub['currency'] ?? '') == 'ZiG' ? 'selected' : ''; ?>>ZiG</option>
                                    <option value="ZAR" <?php echo ($current_sub['currency'] ?? '') == 'ZAR' ? 'selected' : ''; ?>>ZAR (R)</option>
                                </select>
                                <?php if ($current_sub): ?>
                                    <span class="badge-status badge-submitted" style="font-size: 0.75rem;">Status: <?php echo h($current_sub['status']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 23-Line Double-Entry Grid -->
                        <div class="ledger-columns">
                            
                            <!-- LEFT: INCOME (1 to 23) -->
                            <div class="ledger-panel ledger-panel-income">
                                <div class="panel-header">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <ion-icon name="arrow-down-circle-outline" style="color: var(--accent); font-size: 1.2rem;"></ion-icon>
                                        <h3 style="font-family: 'Outfit'; color: var(--accent); margin: 0; font-size: 1.05rem;">INCOME</h3>
                                    </div>
                                    <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">AMOUNTS IN USD</span>
                                </div>

                                <?php foreach ($income_cats as $num => $cat): ?>
                                    <?php 
                                        $val = 0.0;
                                        if (isset($current_details['income'][$num])) {
                                            $val = (float)$current_details['income'][$num];
                                        } elseif ($current_sub) {
                                            if ($num == 3) $val = (float)$current_sub['tithe'];
                                            if ($num == 4) $val = (float)$current_sub['total_collections'];
                                        }
                                        $is_ass = $cat['is_assessable'];
                                        $row_class = $is_ass ? 'ledger-row highlight-levy' : 'ledger-row';
                                    ?>
                                    <div class="<?php echo $row_class; ?>">
                                        <span class="num-col"><?php echo $num; ?></span>
                                        <span class="label-col">
                                            <?php echo h($cat['name']); ?>
                                            <?php if ($is_ass): ?>
                                                <span class="badge-assess">10% Levy</span>
                                            <?php endif; ?>
                                        </span>
                                        <input type="number" step="0.01" name="inc[<?php echo $num; ?>]" 
                                               id="inc-<?php echo $num; ?>" 
                                               class="input-num calc-income <?php echo $is_ass ? 'calc-assessable' : ''; ?>" 
                                               value="<?php echo number_format($val, 2, '.', ''); ?>">
                                    </div>
                                <?php endforeach; ?>

                                <!-- Custom / Additional Parish Incomes -->
                                <div id="custom-income-container" style="margin-top: 10px; border-top: 1px dashed rgba(251, 191, 36, 0.2); padding-top: 8px;">
                                    <?php if (!empty($custom_income_cats)): ?>
                                        <div style="font-size: 0.65rem; color: #38bdf8; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                            Additional / Parish-Specific Incomes
                                        </div>
                                        <?php foreach ($custom_income_cats as $cc): ?>
                                            <?php 
                                                $cid = (int)$cc['category_id'];
                                                $val = (float)($saved_custom_inc[$cid] ?? 0.0);
                                                $is_ass = (bool)$cc['is_assessable'];
                                            ?>
                                            <div class="ledger-row" style="background: rgba(56, 189, 248, 0.04);">
                                                <span class="num-col" style="color: #38bdf8; font-weight: 800;">+</span>
                                                <span class="label-col">
                                                    <?php echo h($cc['category_name']); ?>
                                                    <?php if ($is_ass): ?>
                                                        <span class="badge-assess">10% Levy</span>
                                                    <?php else: ?>
                                                        <span style="font-size: 0.58rem; color: #10b981; font-weight: 600;">(100% Local)</span>
                                                    <?php endif; ?>
                                                </span>
                                                <input type="number" step="0.01" name="custom_inc[<?php echo $cid; ?>]" 
                                                       id="custom-inc-<?php echo $cid; ?>" 
                                                       class="input-num calc-income calc-custom-inc <?php echo $is_ass ? 'calc-assessable' : ''; ?>" 
                                                       value="<?php echo $val > 0 ? number_format($val, 2, '.', '') : ''; ?>"
                                                       placeholder="0.00">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <div style="text-align: right; margin-top: 6px;">
                                        <a href="javascript:void(0)" onclick="openCategoryModal('income')" style="font-size: 0.68rem; color: #38bdf8; text-decoration: underline; font-weight: 600;">
                                            + Add New Income Category
                                        </a>
                                    </div>
                                </div>

                                <!-- Income Total -->
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 12px; border-top: 2px solid rgba(251, 191, 36, 0.3);">
                                    <strong style="color: var(--accent); font-size: 0.95rem;">TOTAL INCOME</strong>
                                    <strong id="display-total-income" style="color: #fff; font-size: 1.15rem;">$0.00</strong>
                                </div>
                            </div>

                            <!-- RIGHT: EXPENDITURE (1 to 23) -->
                            <div class="ledger-panel ledger-panel-expenditure">
                                <div class="panel-header">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <ion-icon name="arrow-up-circle-outline" style="color: #ef4444; font-size: 1.2rem;"></ion-icon>
                                        <h3 style="font-family: 'Outfit'; color: #f87171; margin: 0; font-size: 1.05rem;">EXPENDITURE</h3>
                                    </div>
                                    <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">AMOUNT/USD</span>
                                </div>

                                <?php foreach ($expenditure_cats as $num => $cat): ?>
                                    <?php 
                                        $val = 0.0;
                                        if (isset($current_details['expenditure'][$num])) {
                                            $val = (float)$current_details['expenditure'][$num];
                                        } elseif ($current_sub) {
                                            if ($num == 4) $val = (float)$current_sub['calculated_levy'];
                                        }
                                        $is_auto = $cat['is_auto_levy'];
                                        $row_class = $is_auto ? 'ledger-row highlight-auto' : 'ledger-row';
                                    ?>
                                    <div class="<?php echo $row_class; ?>">
                                        <span class="num-col"><?php echo $num; ?></span>
                                        <span class="label-col">
                                            <?php echo h($cat['name']); ?>
                                            <?php if ($is_auto): ?>
                                                <span style="font-size: 0.6rem; color: #10b981; font-weight: 700; margin-left: 4px;">(Auto 10%)</span>
                                            <?php endif; ?>
                                        </span>
                                        <input type="number" step="0.01" name="exp[<?php echo $num; ?>]" 
                                               id="exp-<?php echo $num; ?>" 
                                               class="input-num calc-expenditure <?php echo $is_auto ? 'input-readonly' : ''; ?>" 
                                               <?php echo $is_auto ? 'readonly' : ''; ?>
                                               value="<?php echo number_format($val, 2, '.', ''); ?>">
                                    </div>
                                <?php endforeach; ?>

                                <!-- Custom / Additional Parish Expenditures -->
                                <div id="custom-expenditure-container" style="margin-top: 10px; border-top: 1px dashed rgba(239, 68, 68, 0.2); padding-top: 8px;">
                                    <?php if (!empty($custom_expenditure_cats)): ?>
                                        <div style="font-size: 0.65rem; color: #f87171; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
                                            Additional / Parish-Specific Expenditures
                                        </div>
                                        <?php foreach ($custom_expenditure_cats as $cc): ?>
                                            <?php 
                                                $cid = (int)$cc['category_id'];
                                                $val = (float)($saved_custom_exp[$cid] ?? 0.0);
                                            ?>
                                            <div class="ledger-row" style="background: rgba(239, 68, 68, 0.04);">
                                                <span class="num-col" style="color: #f87171; font-weight: 800;">-</span>
                                                <span class="label-col">
                                                    <?php echo h($cc['category_name']); ?>
                                                </span>
                                                <input type="number" step="0.01" name="custom_exp[<?php echo $cid; ?>]" 
                                                       id="custom-exp-<?php echo $cid; ?>" 
                                                       class="input-num calc-expenditure calc-custom-exp" 
                                                       value="<?php echo $val > 0 ? number_format($val, 2, '.', '') : ''; ?>"
                                                       placeholder="0.00">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <div style="text-align: right; margin-top: 6px;">
                                        <a href="javascript:void(0)" onclick="openCategoryModal('expenditure')" style="font-size: 0.68rem; color: #f87171; text-decoration: underline; font-weight: 600;">
                                            + Add New Expenditure Category
                                        </a>
                                    </div>
                                </div>

                                <!-- Expenditure Total -->
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 12px; border-top: 2px solid rgba(239, 68, 68, 0.3);">
                                    <strong style="color: #f87171; font-size: 0.95rem;">TOTAL EXPENDITURE</strong>
                                    <strong id="display-total-expenditure" style="color: #fff; font-size: 1.15rem;">$0.00</strong>
                                </div>
                            </div>

                        </div>

                        <!-- Annual Special Diocesan Collections (8 Appeals) -->
                        <div class="special-collection-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                <div>
                                    <h4 style="font-family: 'Outfit'; color: #38bdf8; margin: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 6px;">
                                        <ion-icon name="globe-outline"></ion-icon>
                                        <span>Annual Special Diocesan Collections (Occur Once a Year)</span>
                                    </h4>
                                    <p style="font-size: 0.7rem; color: var(--text-muted); margin: 3px 0 0 0;">
                                        100% of these collections are remitted to the Chancery / Holy See. They are separate from regular parish collections and are <strong>not</strong> subject to the 10% levy.
                                    </p>
                                </div>
                                <span style="font-size: 0.65rem; background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); padding: 3px 8px; border-radius: 6px; font-weight: 700;">
                                    8 Pontifical & Diocesan Appeals
                                </span>
                            </div>

                            <div class="special-grid">
                                <?php foreach ($annual_spec_cats as $s_key => $s_meta): ?>
                                    <?php 
                                        $s_val = (float)($saved_special[$s_key] ?? 0.0);
                                    ?>
                                    <div class="special-item">
                                        <div>
                                            <div style="font-weight: 700; color: #fff; font-size: 0.78rem;">
                                                <?php echo h($s_meta['title']); ?>
                                            </div>
                                            <div style="font-size: 0.65rem; color: #38bdf8; margin-top: 2px;">
                                                Season: <?php echo h($s_meta['season']); ?>
                                            </div>
                                        </div>
                                        <div style="margin-top: 8px;">
                                            <input type="number" step="0.01" name="spec[<?php echo $s_key; ?>]" 
                                                   id="spec-<?php echo $s_key; ?>" 
                                                   class="input-num calc-special" 
                                                   placeholder="0.00" 
                                                   value="<?php echo $s_val > 0 ? number_format($s_val, 2, '.', '') : ''; ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Bottom Reconciliation Matrix -->
                        <div class="reconcile-grid">
                            
                            <!-- Operating Statement -->
                            <div class="reconcile-box">
                                <h4 style="font-family: 'Outfit'; color: var(--accent); margin: 0 0 10px 0; font-size: 0.95rem; display: flex; align-items: center; gap: 6px;">
                                    <ion-icon name="calculator-outline"></ion-icon>
                                    <span>Operating Statement</span>
                                </h4>

                                <div class="reconcile-row">
                                    <span style="color: var(--text-muted);">Total Income:</span>
                                    <span id="rec-total-income" style="font-weight: 700; color: #fff;">$0.00</span>
                                </div>
                                <div class="reconcile-row">
                                    <span style="color: var(--text-muted);">Less Total Expenditure:</span>
                                    <span id="rec-total-expenditure" style="font-weight: 700; color: #f87171;">$0.00</span>
                                </div>
                                <div class="reconcile-row">
                                    <span style="color: var(--text-muted);">Total Income Less Expenditure:</span>
                                    <span id="rec-operating-surplus" style="font-weight: 700; color: #38bdf8;">$0.00</span>
                                </div>
                                <div class="reconcile-row">
                                    <span style="color: var(--text-muted);">Add Opening Balance:</span>
                                    <div style="width: 120px;">
                                        <input type="number" step="0.01" name="opening_balance" id="input-opening-bal" 
                                               class="input-num" value="<?php echo number_format($default_opening, 2, '.', ''); ?>">
                                    </div>
                                </div>
                                <div class="reconcile-row reconcile-total">
                                    <span style="color: var(--accent);">CLOSING BALANCE:</span>
                                    <span id="rec-closing-balance" style="color: var(--accent); font-size: 1.2rem;">$0.00</span>
                                </div>
                            </div>

                            <!-- Cash & Bank Position -->
                            <div class="reconcile-box">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <h4 style="font-family: 'Outfit'; color: #10b981; margin: 0; font-size: 0.95rem; display: flex; align-items: center; gap: 6px;">
                                        <ion-icon name="wallet-outline"></ion-icon>
                                        <span>Cash & Bank Position</span>
                                    </h4>
                                    <span id="balance-check-badge" style="font-size: 0.65rem; padding: 2px 8px; border-radius: 6px; font-weight: 700; background: rgba(16, 185, 129, 0.15); color: #10b981;">
                                        Balanced
                                    </span>
                                </div>

                                <div class="reconcile-row">
                                    <span style="color: var(--text-muted);">Cash at Hand:</span>
                                    <div style="width: 120px;">
                                        <input type="number" step="0.01" name="cash_at_hand" id="pos-cash-hand" 
                                               class="input-num calc-position" 
                                               value="<?php echo number_format($current_details['cash_at_hand'] ?? $current_sub['cash_at_hand'] ?? 0, 2, '.', ''); ?>">
                                    </div>
                                </div>
                                <div class="reconcile-row">
                                    <span style="color: var(--text-muted);">Cash in Bank:</span>
                                    <div style="width: 120px;">
                                        <input type="number" step="0.01" name="cash_in_bank" id="pos-cash-bank" 
                                               class="input-num calc-position" 
                                               value="<?php echo number_format($current_details['cash_in_bank'] ?? $current_sub['cash_in_bank'] ?? 0, 2, '.', ''); ?>">
                                    </div>
                                </div>
                                <div class="reconcile-row">
                                    <span style="color: var(--text-muted);">Savings:</span>
                                    <div style="width: 120px;">
                                        <input type="number" step="0.01" name="savings" id="pos-savings" 
                                               class="input-num calc-position" 
                                               value="<?php echo number_format($current_details['savings'] ?? $current_sub['savings'] ?? 0, 2, '.', ''); ?>">
                                    </div>
                                </div>
                                <div class="reconcile-row">
                                    <span style="color: var(--text-muted);">Investments:</span>
                                    <div style="width: 120px;">
                                        <input type="number" step="0.01" name="investments" id="pos-investments" 
                                               class="input-num calc-position" 
                                               value="<?php echo number_format($current_details['investments'] ?? $current_sub['investments'] ?? 0, 2, '.', ''); ?>">
                                    </div>
                                </div>
                                <div class="reconcile-row reconcile-total">
                                    <span style="color: #10b981;">TOTAL CLOSING ASSETS:</span>
                                    <span id="pos-total-closing" style="color: #10b981; font-size: 1.2rem;">$0.00</span>
                                </div>
                            </div>

                        </div>

                        <!-- Action Buttons -->
                        <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                Parish Priest: <strong><?php echo h($parish['priest_name'] ?: 'Parish Priest'); ?></strong>
                            </div>

                            <div style="display: flex; gap: 12px;">
                                <?php if ($current_sub): ?>
                                    <a href="../actions/export_financial_docx.php?submission_id=<?php echo $current_sub['submission_id']; ?>" 
                                       class="btn btn-secondary" style="font-weight: 700; padding: 0.85rem 1.25rem;">
                                        <ion-icon name="download-outline"></ion-icon>
                                        <span>Export Word (.docx)</span>
                                    </a>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary" style="font-weight: 800; padding: 0.85rem 2rem;">
                                    <ion-icon name="cloud-upload-outline"></ion-icon>
                                    <span><?php echo $current_sub ? 'Update Financial Return' : 'Commit Monthly Return'; ?></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Annual Filing History Table -->
                <div class="cashbook-container">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                        <h3 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1.15rem;">
                            Annual Filing History (FY <?php echo $selected_year; ?>)
                        </h3>
                        <span style="font-size: 0.75rem; color: var(--accent); font-weight: 700;">
                            <?php echo count($my_submissions); ?> / 12 Months Recorded
                        </span>
                    </div>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.03); color: var(--text-muted); text-transform: uppercase; font-size: 0.65rem; border-bottom: 1px solid rgba(255,255,255,0.08);">
                                    <th style="padding: 10px; text-align: left;">Month</th>
                                    <th style="padding: 10px; text-align: right;">Total Income</th>
                                    <th style="padding: 10px; text-align: right;">Total Expenditure</th>
                                    <th style="padding: 10px; text-align: right;">10% Levy</th>
                                    <th style="padding: 10px; text-align: right;">Closing Balance</th>
                                    <th style="padding: 10px; text-align: center;">Status</th>
                                    <th style="padding: 10px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <?php 
                                        $sub = $sub_by_month[$m] ?? null;
                                        $m_name = date('F', mktime(0,0,0,$m,1));
                                    ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                        <td style="padding: 10px; font-weight: 700; color: #fff;">
                                            <a href="finance_submission.php?year=<?php echo $selected_year; ?>&month=<?php echo $m; ?>" style="color: #fff; text-decoration: none;">
                                                <?php echo $m_name; ?>
                                            </a>
                                        </td>
                                        <?php if ($sub): ?>
                                            <td style="padding: 10px; text-align: right; color: var(--accent); font-weight: 600;">
                                                $<?php echo number_format((float)$sub['total_income'], 2); ?>
                                            </td>
                                            <td style="padding: 10px; text-align: right; color: #f87171;">
                                                $<?php echo number_format((float)$sub['total_expenditure'], 2); ?>
                                            </td>
                                            <td style="padding: 10px; text-align: right; color: #10b981; font-weight: 700;">
                                                $<?php echo number_format((float)$sub['calculated_levy'], 2); ?>
                                            </td>
                                            <td style="padding: 10px; text-align: right; font-weight: 700; color: #fff;">
                                                $<?php echo number_format((float)$sub['closing_balance'], 2); ?>
                                            </td>
                                            <td style="padding: 10px; text-align: center;">
                                                <span class="badge-status badge-submitted"><?php echo h($sub['status']); ?></span>
                                            </td>
                                            <td style="padding: 10px; text-align: center;">
                                                <div style="display: inline-flex; gap: 6px;">
                                                    <a href="finance_submission.php?year=<?php echo $selected_year; ?>&month=<?php echo $m; ?>" 
                                                       class="btn btn-secondary" style="font-size: 0.65rem; padding: 4px 8px;">Edit</a>
                                                    <a href="../actions/export_financial_docx.php?submission_id=<?php echo $sub['submission_id']; ?>" 
                                                       class="btn btn-primary" style="font-size: 0.65rem; padding: 4px 8px; background: #2563eb;">.docx</a>
                                                </div>
                                            </td>
                                        <?php else: ?>
                                            <td colspan="4" style="padding: 10px; color: var(--text-muted); font-style: italic; text-align: center;">
                                                Not Submitted
                                            </td>
                                            <td style="padding: 10px; text-align: center;">
                                                <span class="badge-status badge-pending">Pending</span>
                                            </td>
                                            <td style="padding: 10px; text-align: center;">
                                                <a href="finance_submission.php?year=<?php echo $selected_year; ?>&month=<?php echo $m; ?>" 
                                                   class="btn btn-secondary" style="font-size: 0.65rem; padding: 4px 8px;">Fill</a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Modal: Add New Custom Category -->
    <div id="categoryModal" class="modal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 1rem;">
        <div style="background: #1e293b; border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 20px; width: 100%; max-width: 480px; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.75rem;">
                <h3 style="font-family: 'Outfit'; color: #38bdf8; margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 6px;">
                    <ion-icon name="pricetag-outline"></ion-icon>
                    <span>Create New Financial Category</span>
                </h3>
                <span onclick="closeCategoryModal()" style="font-size: 1.5rem; color: var(--text-muted); cursor: pointer;">&times;</span>
            </div>

            <form id="new-category-form" onsubmit="submitNewCategory(event)">
                <input type="hidden" name="parish_id" value="<?php echo $parish_id; ?>">
                <input type="hidden" name="format" value="json">

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Category Type *</label>
                    <select name="category_type" id="new_cat_type" class="input-field" required>
                        <option value="income">Income (Parish Inflow)</option>
                        <option value="expenditure">Expenditure (Parish Outflow)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Category Name *</label>
                    <input type="text" name="category_name" id="new_cat_name" class="input-field" required placeholder="e.g. Centenary Chapel Fund, Borehole Repairs, Pig Farm..." autofocus>
                    <small style="color: var(--text-muted); font-size: 0.68rem; margin-top: 3px; display: block;">
                        This category will be permanently available for your parish daily ledger and monthly returns.
                    </small>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem; background: rgba(255,255,255,0.03); padding: 10px 12px; border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.75rem; color: #fff; cursor: pointer;">
                        <input type="checkbox" name="is_assessable" value="1">
                        <span>Subject to 10% Diocesan Levy? (Leave unchecked for standard parish funds)</span>
                    </label>
                    <small style="color: var(--text-muted); font-size: 0.65rem; display: block; margin-left: 24px; margin-top: 2px;">
                        Standard diocesan rule: 10% levy only applies to Sunday Collection & Tithe.
                    </small>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeCategoryModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" id="btn-save-cat" class="btn btn-primary" style="font-weight: 800; padding: 0.65rem 1.25rem;">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <?php include '../includes/privacy_footer.php'; ?>

    <script>
        function recalculateCashbook() {
            // 1. Calculate Total Income (Standard + Custom)
            let totalIncome = 0;
            document.querySelectorAll('.calc-income').forEach(inp => {
                totalIncome += parseFloat(inp.value || 0);
            });

            // 2. Compute 10% Levy on Line 3 (Tithe/Unsupo/Mutulo) & Line 4 (Sunday Collection) + any Custom Assessable
            const tithe = parseFloat(document.getElementById('inc-3').value || 0);
            const sundayColl = parseFloat(document.getElementById('inc-4').value || 0);
            let assessableBase = tithe + sundayColl;

            document.querySelectorAll('.calc-custom-inc.calc-assessable').forEach(inp => {
                assessableBase += parseFloat(inp.value || 0);
            });

            const diocesanLevy = assessableBase * 0.10;

            const levyField = document.getElementById('exp-4');
            if (levyField) {
                levyField.value = diocesanLevy.toFixed(2);
            }

            // 3. Calculate Total Expenditure (Standard + Custom)
            let totalExpenditure = 0;
            document.querySelectorAll('.calc-expenditure').forEach(inp => {
                totalExpenditure += parseFloat(inp.value || 0);
            });

            // 4. Calculate Operating Statement
            const operatingSurplus = totalIncome - totalExpenditure;
            const openingBal = parseFloat(document.getElementById('input-opening-bal').value || 0);
            const closingBal = operatingSurplus + openingBal;

            // Update DOM displays
            document.getElementById('display-total-income').innerText = '$' + totalIncome.toFixed(2);
            document.getElementById('display-total-expenditure').innerText = '$' + totalExpenditure.toFixed(2);
            document.getElementById('rec-total-income').innerText = '$' + totalIncome.toFixed(2);
            document.getElementById('rec-total-expenditure').innerText = '$' + totalExpenditure.toFixed(2);

            const opDisplay = document.getElementById('rec-operating-surplus');
            opDisplay.innerText = (operatingSurplus < 0 ? '-$' : '$') + Math.abs(operatingSurplus).toFixed(2);
            opDisplay.style.color = (operatingSurplus >= 0) ? '#10b981' : '#f87171';

            const closeDisplay = document.getElementById('rec-closing-balance');
            closeDisplay.innerText = (closingBal < 0 ? '-$' : '$') + Math.abs(closingBal).toFixed(2);
            closeDisplay.style.color = (closingBal >= 0) ? 'var(--accent)' : '#ef4444';

            // 5. Calculate Total Closing Assets
            const cashHand = parseFloat(document.getElementById('pos-cash-hand').value || 0);
            const cashBank = parseFloat(document.getElementById('pos-cash-bank').value || 0);
            const savings = parseFloat(document.getElementById('pos-savings').value || 0);
            const investments = parseFloat(document.getElementById('pos-investments').value || 0);
            const totalAssets = cashHand + cashBank + savings + investments;

            const assetsDisplay = document.getElementById('pos-total-closing');
            assetsDisplay.innerText = (totalAssets < 0 ? '-$' : '$') + Math.abs(totalAssets).toFixed(2);

            // Validation Check
            const badge = document.getElementById('balance-check-badge');
            if (Math.abs(closingBal - totalAssets) < 0.05) {
                badge.innerText = 'Balanced';
                badge.style.background = 'rgba(16, 185, 129, 0.15)';
                badge.style.color = '#10b981';
            } else {
                badge.innerText = 'Variance: $' + Math.abs(closingBal - totalAssets).toFixed(2);
                badge.style.background = 'rgba(239, 68, 68, 0.15)';
                badge.style.color = '#ef4444';
            }
        }

        // Auto Sync from Daily Ledger
        async function autoSyncFromDailyLedger(isAuto = false) {
            const btn = document.getElementById('btn-auto-sync');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<ion-icon name="sync-outline" style="animation: spin 1s infinite linear;"></ion-icon> <span>Syncing...</span>';
            }

            try {
                const resp = await fetch(`../actions/get_daily_ledger_summary.php?year=<?php echo $selected_year; ?>&month=<?php echo $selected_month; ?>&parish_id=<?php echo $parish_id; ?>`);
                const json = await resp.json();

                if (!json.success) {
                    if (!isAuto) alert('Could not sync: ' + (json.error || 'Unknown error'));
                    return;
                }

                const data = json.data;
                if (data.entry_count === 0) {
                    if (!isAuto) {
                        alert('No daily ledger transactions found for <?php echo date('F Y', mktime(0,0,0,$selected_month,1,$selected_year)); ?>.\n\nYou can use the "Daily Cash Ledger" button to log your day-to-day collections and payments.');
                    }
                    return;
                }

                // 1. Populate Standard Income
                for (let i = 1; i <= 23; i++) {
                    const field = document.getElementById('inc-' + i);
                    if (field) {
                        field.value = (data.income[i] || 0) > 0 ? parseFloat(data.income[i]).toFixed(2) : '0.00';
                    }
                }

                // 2. Populate Standard Expenditure
                for (let i = 1; i <= 23; i++) {
                    const field = document.getElementById('exp-' + i);
                    if (field && i !== 4) {
                        field.value = (data.expenditure[i] || 0) > 0 ? parseFloat(data.expenditure[i]).toFixed(2) : '0.00';
                    }
                }

                // 3. Populate Special Collections
                if (data.special_collections) {
                    for (const [key, amt] of Object.entries(data.special_collections)) {
                        const field = document.getElementById('spec-' + key);
                        if (field) {
                            field.value = parseFloat(amt || 0) > 0 ? parseFloat(amt).toFixed(2) : '';
                        }
                    }
                }

                // 4. Populate Custom Categories
                if (data.custom_income) {
                    for (const [cid, amt] of Object.entries(data.custom_income)) {
                        const field = document.getElementById('custom-inc-' + cid);
                        if (field) {
                            field.value = parseFloat(amt || 0) > 0 ? parseFloat(amt).toFixed(2) : '';
                        }
                    }
                }
                if (data.custom_expenditure) {
                    for (const [cid, amt] of Object.entries(data.custom_expenditure)) {
                        const field = document.getElementById('custom-exp-' + cid);
                        if (field) {
                            field.value = parseFloat(amt || 0) > 0 ? parseFloat(amt).toFixed(2) : '';
                        }
                    }
                }

                recalculateCashbook();

                if (!isAuto) {
                    alert(`✅ Successfully imported and aggregated ${data.entry_count} daily ledger transactions!\n\n` +
                          `• Total Income: $${data.total_income.toFixed(2)}\n` +
                          `• Total Expenditure: $${data.total_expenditure.toFixed(2)}\n` +
                          `• Assessable Base (Tithe + Sunday Coll): $${data.assessable_base.toFixed(2)}\n` +
                          `• 10% Diocesan Levy: $${data.calculated_levy.toFixed(2)}`);
                }

            } catch (err) {
                if (!isAuto) alert('Connection error while fetching daily ledger data.');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<ion-icon name="flash-outline"></ion-icon> <span>Auto-Sync from Daily Ledger</span>';
                }
            }
        }

        // Modal Controls
        function openCategoryModal(type = 'income') {
            document.getElementById('new_cat_name').value = '';
            document.getElementById('new_cat_type').value = type;
            const modal = document.getElementById('categoryModal');
            modal.style.display = 'flex';
            setTimeout(() => document.getElementById('new_cat_name').focus(), 100);
        }

        function closeCategoryModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        async function submitNewCategory(e) {
            e.preventDefault();
            const form = document.getElementById('new-category-form');
            const btn = document.getElementById('btn-save-cat');
            btn.disabled = true;
            btn.textContent = 'Saving...';

            const formData = new FormData(form);

            try {
                const resp = await fetch('../actions/save_custom_category.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const res = await resp.json();

                if (res.success) {
                    closeCategoryModal();
                    // Reload to render new custom category field cleanly
                    window.location.reload();
                } else {
                    alert('Error: ' + (res.error || 'Failed to save category.'));
                }
            } catch (err) {
                alert('Connection error. Please try again.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Save Category';
            }
        }

        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target === modal) {
                closeCategoryModal();
            }
        };

        // Attach event listeners
        document.querySelectorAll('.calc-income, .calc-expenditure, #input-opening-bal, .calc-position').forEach(inp => {
            inp.addEventListener('input', recalculateCashbook);
        });

        // Run calculation on load
        window.addEventListener('DOMContentLoaded', () => {
            recalculateCashbook();

            // Check if auto_sync parameter is present in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auto_sync') === '1') {
                autoSyncFromDailyLedger(true);
            }
        });
    </script>
</body>
</html>
