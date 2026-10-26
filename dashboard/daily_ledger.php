<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parish Daily Cash & Bank Ledger
 * Day-to-Day Financial Journal with Direct Sync to the Diocesan 23-Line Return
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/diocesan_financial_categories.php';

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
    die("You must be assigned to a parish to view the Daily Ledger.");
}

$parish = db_fetch("SELECT * FROM parishes WHERE parish_id = ?", [$parish_id]);
$all_parishes = $is_admin ? db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC") : [];

$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$filter_type = trim($_GET['type'] ?? 'all');
$filter_account = trim($_GET['account'] ?? 'all');
$search_q = trim($_GET['q'] ?? '');

$year_sql = db_year_sql('l.entry_date');
$month_sql = db_month_sql('l.entry_date');

// Build query
$where = "WHERE l.parish_id = ? AND {$year_sql} = ? AND {$month_sql} = ?";
$params = [$parish_id, $selected_year, $selected_month];

if ($filter_type === 'income' || $filter_type === 'expenditure') {
    $where .= " AND l.entry_type = ?";
    $params[] = $filter_type;
}

if ($filter_account === 'cash_at_hand' || $filter_account === 'cash_in_bank') {
    $where .= " AND l.account_type = ?";
    $params[] = $filter_account;
}

if (!empty($search_q)) {
    $where .= " AND (l.description LIKE ? OR l.reference_number LIKE ? OR l.category_name LIKE ?)";
    $like = "%$search_q%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$entries = db_fetchAll("
    SELECT l.*, u.full_name as submitter_name
    FROM parish_daily_ledger l
    LEFT JOIN users u ON l.recorded_by = u.user_id
    $where
    ORDER BY l.entry_date DESC, l.entry_id DESC
", $params);

// Calculate monthly stats for this parish & month
$month_stats = sync_daily_ledger_to_monthly_return($parish_id, $selected_year, $selected_month);

// Today's Stats
$today_str = date('Y-m-d');
$today_income = (float)(db_fetch("SELECT SUM(amount) as s FROM parish_daily_ledger WHERE parish_id = ? AND entry_date = ? AND entry_type = 'income'", [$parish_id, $today_str])['s'] ?? 0);
$today_expenditure = (float)(db_fetch("SELECT SUM(amount) as s FROM parish_daily_ledger WHERE parish_id = ? AND entry_date = ? AND entry_type = 'expenditure'", [$parish_id, $today_str])['s'] ?? 0);

// Get structured categories for dropdowns
$categories_grouped = get_all_daily_ledger_categories($parish_id);
$custom_categories = get_parish_custom_categories($parish_id);

$header_title = h($parish['parish_name']) . " — Daily Ledger";
$header_subtitle = "Day-to-day cash and bank journal for " . date('F Y', mktime(0,0,0,$selected_month,1,$selected_year)) . ".";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($parish['parish_name']); ?> - Daily Ledger - Hwange RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .ledger-container {
            max-width: 1400px;
            margin: 0 auto;
        }
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
        .card-stat .sub {
            font-size: 0.7rem;
            color: var(--text-muted);
        }
        .filter-panel {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.25rem;
            border: 1px solid rgba(255,255,255,0.06);
            margin-bottom: 1.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .table-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid rgba(255,255,255,0.06);
            overflow: hidden;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }
        .ledger-table th {
            text-align: left;
            padding: 12px 14px;
            background: rgba(255,255,255,0.03);
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.65rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .ledger-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            color: #e2e8f0;
            vertical-align: middle;
        }
        .ledger-table tr:hover td {
            background: rgba(255,255,255,0.02);
        }
        .badge-type {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-income {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .badge-expenditure {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .badge-acct {
            background: rgba(255,255,255,0.06);
            color: #94a3b8;
            font-size: 0.68rem;
            padding: 2px 7px;
            border-radius: 6px;
        }
        .sync-banner {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.12), rgba(16, 185, 129, 0.12));
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #1e293b;
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 20px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7);
        }
        .type-toggle {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 1.25rem;
        }
        .type-btn {
            padding: 10px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.03);
            color: white;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }
        .type-btn.active-income {
            background: #10b981;
            border-color: #10b981;
            color: #064e3b;
        }
        .type-btn.active-expenditure {
            background: #ef4444;
            border-color: #ef4444;
            color: #ffffff;
        }

        @media print {
            .dashboard-layout { display: block !important; }
            .sidebar, .navbar, .filter-panel, .sync-banner, .btn, .no-print { display: none !important; }
            .main-content { margin: 0 !important; padding: 0 !important; }
            body { background: white !important; color: black !important; }
            .table-card { border: none !important; background: transparent !important; padding: 0 !important; }
            .ledger-table th { background: #f1f5f9 !important; color: black !important; border: 1px solid #cbd5e1 !important; }
            .ledger-table td { color: black !important; border: 1px solid #e2e8f0 !important; }
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body ledger-container" style="padding: 2rem 0;">

                <!-- Top Action Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <a href="finance_submission.php?year=<?php echo $selected_year; ?>&month=<?php echo $selected_month; ?>" class="btn btn-secondary" style="font-size: 0.75rem; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;">
                                <ion-icon name="arrow-back-outline"></ion-icon>
                                <span>Monthly Return</span>
                            </a>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">/ Daily Cashbook</span>
                        </div>
                        <h2 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1.6rem; display: flex; align-items: center; gap: 10px;">
                            <span>Daily Cash & Bank Ledger</span>
                            <span style="font-size: 0.75rem; background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); padding: 4px 10px; border-radius: 20px; font-weight: 700;">
                                <?php echo strtoupper(date('F Y', mktime(0,0,0,$selected_month,1,$selected_year))); ?>
                            </span>
                        </h2>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <button onclick="openCategoryModal()" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 0.82rem; padding: 0.65rem 1rem;">
                            <ion-icon name="pricetag-outline"></ion-icon>
                            <span>+ New Category</span>
                        </button>
                        <button onclick="window.print()" class="btn btn-secondary no-print" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 0.82rem; padding: 0.65rem 1rem;" title="Print Ledger">
                            <ion-icon name="print-outline"></ion-icon>
                            <span>Print</span>
                        </button>
                        <button onclick="openTransactionModal()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.85rem; padding: 0.65rem 1.25rem;">
                            <ion-icon name="add-circle-outline" style="font-size: 1.15rem;"></ion-icon>
                            <span>Record Transaction</span>
                        </button>
                    </div>
                </div>

                <!-- Monthly Return Sync Notification Banner -->
                <div class="sync-banner no-print">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(56, 189, 248, 0.2); display: flex; align-items: center; justify-content: center; color: #38bdf8; font-size: 1.4rem;">
                            <ion-icon name="swap-horizontal-outline"></ion-icon>
                        </div>
                        <div>
                            <div style="font-weight: 800; color: #fff; font-size: 0.95rem;">
                                Ready to file your Diocesan Return for <?php echo date('F Y', mktime(0,0,0,$selected_month,1,$selected_year)); ?>?
                            </div>
                            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 2px;">
                                You have <strong><?php echo $month_stats['entry_count']; ?> entries</strong> recorded this month. All Sunday Collections, Tithes, and expenses can be synced directly to the 23-line return in one click.
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="finance_submission.php?year=<?php echo $selected_year; ?>&month=<?php echo $selected_month; ?>&auto_sync=1" class="btn btn-primary" style="font-size: 0.8rem; font-weight: 800; padding: 0.6rem 1.2rem; display: inline-flex; align-items: center; gap: 6px;">
                            <ion-icon name="flash-outline"></ion-icon>
                            <span>Sync to 23-Line Return</span>
                        </a>
                    </div>
                </div>

                <!-- Financial Stats Strip -->
                <div class="stats-grid-4">
                    <div class="card-stat">
                        <span class="label">Total Month Income</span>
                        <div class="val" style="color: #10b981;">$<?php echo number_format($month_stats['total_income'], 2); ?></div>
                        <span class="sub">Cash: $<?php echo number_format($month_stats['cash_at_hand_receipts'], 2); ?> | Bank: $<?php echo number_format($month_stats['cash_in_bank_receipts'], 2); ?></span>
                    </div>

                    <div class="card-stat">
                        <span class="label">Total Month Expenditure</span>
                        <div class="val" style="color: #ef4444;">$<?php echo number_format($month_stats['total_expenditure'], 2); ?></div>
                        <span class="sub">Cash: $<?php echo number_format($month_stats['cash_at_hand_payments'], 2); ?> | Bank: $<?php echo number_format($month_stats['cash_in_bank_payments'], 2); ?></span>
                    </div>

                    <div class="card-stat">
                        <span class="label">Net Operating Flow</span>
                        <div class="val" style="color: <?php echo $month_stats['net_operating'] >= 0 ? '#10b981' : '#f87171'; ?>;">
                            <?php echo $month_stats['net_operating'] < 0 ? '-$' : '$'; ?><?php echo number_format(abs($month_stats['net_operating']), 2); ?>
                        </div>
                        <span class="sub"><?php echo $month_stats['net_operating'] >= 0 ? 'Monthly Operating Surplus' : 'Monthly Operating Deficit'; ?></span>
                    </div>

                    <div class="card-stat" style="border: 1px solid rgba(251, 191, 36, 0.25);">
                        <span class="label">Assessable Base (Coll + Tithe)</span>
                        <div class="val" style="color: var(--accent);">$<?php echo number_format($month_stats['assessable_base'], 2); ?></div>
                        <span class="sub">Auto 10% Levy: <strong>$<?php echo number_format($month_stats['calculated_levy'], 2); ?></strong></span>
                    </div>
                </div>

                <!-- Filters & Controls Bar -->
                <div class="filter-panel no-print">
                    <form method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; width: 100%;">
                        <?php if ($is_admin): ?>
                            <div class="filter-group">
                                <label style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">PARISH:</label>
                                <select name="parish_id" onchange="this.form.submit()" class="input-field" style="padding: 6px 10px; font-size: 0.8rem; width: auto;">
                                    <?php foreach ($all_parishes as $ap): ?>
                                        <option value="<?php echo $ap['parish_id']; ?>" <?php echo $ap['parish_id'] == $parish_id ? 'selected' : ''; ?>>
                                            <?php echo h($ap['parish_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="filter-group">
                            <label style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">YEAR:</label>
                            <select name="year" onchange="this.form.submit()" class="input-field" style="padding: 6px 10px; font-size: 0.8rem; width: auto;">
                                <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $selected_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">MONTH:</label>
                            <select name="month" onchange="this.form.submit()" class="input-field" style="padding: 6px 10px; font-size: 0.8rem; width: auto;">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m == $selected_month ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0,0,0,$m,1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">TYPE:</label>
                            <select name="type" onchange="this.form.submit()" class="input-field" style="padding: 6px 10px; font-size: 0.8rem; width: auto;">
                                <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Transactions</option>
                                <option value="income" <?php echo $filter_type === 'income' ? 'selected' : ''; ?>>Income (+)</option>
                                <option value="expenditure" <?php echo $filter_type === 'expenditure' ? 'selected' : ''; ?>>Expenditure (-)</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">ACCOUNT:</label>
                            <select name="account" onchange="this.form.submit()" class="input-field" style="padding: 6px 10px; font-size: 0.8rem; width: auto;">
                                <option value="all" <?php echo $filter_account === 'all' ? 'selected' : ''; ?>>All Accounts</option>
                                <option value="cash_at_hand" <?php echo $filter_account === 'cash_at_hand' ? 'selected' : ''; ?>>Cash at Hand</option>
                                <option value="cash_in_bank" <?php echo $filter_account === 'cash_in_bank' ? 'selected' : ''; ?>>Cash in Bank</option>
                            </select>
                        </div>

                        <div style="flex: 1; min-width: 180px;">
                            <input type="text" name="q" value="<?php echo h($search_q); ?>" placeholder="Search narration, ref, category..." class="input-field" style="padding: 6px 12px; font-size: 0.8rem;">
                        </div>

                        <div>
                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">Filter</button>
                            <?php if ($filter_type !== 'all' || $filter_account !== 'all' || !empty($search_q)): ?>
                                <a href="daily_ledger.php?year=<?php echo $selected_year; ?>&month=<?php echo $selected_month; ?>" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.75rem; color: var(--text-muted);">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Ledger Transactions Table -->
                <div class="table-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 0.75rem; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <h3 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1.15rem;">Transactions Journal</h3>
                            <p style="font-size: 0.72rem; color: var(--text-muted); margin: 2px 0 0 0;">
                                Chronological ledger entries for <?php echo h($parish['parish_name']); ?>.
                            </p>
                        </div>
                        <span style="font-size: 0.75rem; color: var(--accent); font-weight: 700;">
                            <?php echo count($entries); ?> Transactions
                        </span>
                    </div>

                    <?php if (empty($entries)): ?>
                        <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                            <ion-icon name="journal-outline" style="font-size: 3rem; opacity: 0.3; margin-bottom: 10px;"></ion-icon>
                            <h4 style="color: white; margin: 0 0 6px 0;">No ledger entries found</h4>
                            <p style="font-size: 0.8rem; max-width: 450px; margin: 0 auto 1.5rem auto;">
                                No transactions have been logged for <?php echo date('F Y', mktime(0,0,0,$selected_month,1,$selected_year)); ?>. Click the button below to record daily cash receipts or expenditures.
                            </p>
                            <button onclick="openTransactionModal()" class="btn btn-primary" style="font-weight: 700;">+ Record First Transaction</button>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="ledger-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <th>Narration / Description</th>
                                        <th>Ref / Voucher</th>
                                        <th>Account</th>
                                        <th>Amount</th>
                                        <th>Recorded By</th>
                                        <th class="no-print">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($entries as $row): ?>
                                        <?php 
                                            $is_inc = ($row['entry_type'] === 'income');
                                            $amt_color = $is_inc ? '#10b981' : '#ef4444';
                                            $acct_label = ($row['account_type'] === 'cash_in_bank') ? 'Bank' : 'Cash';
                                        ?>
                                        <tr>
                                            <td style="font-weight: 700; white-space: nowrap; color: #fff;">
                                                <?php echo date('d M Y', strtotime($row['entry_date'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge-type <?php echo $is_inc ? 'badge-income' : 'badge-expenditure'; ?>">
                                                    <ion-icon name="<?php echo $is_inc ? 'arrow-down-outline' : 'arrow-up-outline'; ?>"></ion-icon>
                                                    <span><?php echo $is_inc ? 'Income' : 'Expense'; ?></span>
                                                </span>
                                            </td>
                                            <td style="font-weight: 600; color: #e2e8f0;">
                                                <?php echo h($row['category_name']); ?>
                                            </td>
                                            <td style="max-width: 320px; font-size: 0.78rem;">
                                                <?php echo h($row['description']); ?>
                                            </td>
                                            <td style="font-family: monospace; font-size: 0.75rem; color: var(--accent);">
                                                <?php echo h($row['reference_number'] ?: '—'); ?>
                                            </td>
                                            <td>
                                                <span class="badge-acct"><?php echo $acct_label; ?></span>
                                                <span style="font-size: 0.65rem; color: var(--text-muted);"><?php echo h($row['payment_method']); ?></span>
                                            </td>
                                            <td style="font-weight: 800; font-size: 0.95rem; color: <?php echo $amt_color; ?>; white-space: nowrap;">
                                                <?php echo $is_inc ? '+' : '-'; ?><?php echo h($row['currency'] ?? 'USD'); ?> <?php echo number_format((float)$row['amount'], 2); ?>
                                            </td>
                                            <td style="font-size: 0.7rem; color: var(--text-muted); white-space: nowrap;">
                                                <?php echo h($row['submitter_name'] ?? 'Parish'); ?><br>
                                                <span style="font-size: 0.62rem; opacity: 0.7;"><?php echo date('d M H:i', strtotime($row['created_at'])); ?></span>
                                            </td>
                                            <td class="no-print" style="white-space: nowrap;">
                                                <div style="display: inline-flex; gap: 6px;">
                                                    <button onclick='editTransaction(<?php echo json_encode($row); ?>)' class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.7rem;" title="Edit Entry">
                                                        <ion-icon name="create-outline"></ion-icon>
                                                    </button>
                                                    <form action="../actions/delete_daily_ledger_entry.php" method="POST" onsubmit="return confirm('Delete this ledger entry permanently?');" style="display: inline;">
                                                        <input type="hidden" name="entry_id" value="<?php echo $row['entry_id']; ?>">
                                                        <input type="hidden" name="return_to" value="dashboard/daily_ledger.php?year=<?php echo $selected_year; ?>&month=<?php echo $selected_month; ?>">
                                                        <button type="submit" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.7rem; color: #ef4444;" title="Delete Entry">
                                                            <ion-icon name="trash-outline"></ion-icon>
                                                        </button>
                                                    </form>
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

    <!-- Modal: Record / Edit Daily Transaction -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.75rem;">
                <h3 id="modalTitle" style="font-family: 'Outfit'; color: var(--accent); margin: 0; font-size: 1.25rem;">Record Daily Transaction</h3>
                <span onclick="closeTransactionModal()" style="font-size: 1.5rem; color: var(--text-muted); cursor: pointer;">&times;</span>
            </div>

            <form action="../actions/save_daily_ledger_entry.php" method="POST" id="tx-form">
                <input type="hidden" name="entry_id" id="tx_entry_id" value="0">
                <input type="hidden" name="parish_id" value="<?php echo $parish_id; ?>">
                <input type="hidden" name="entry_type" id="tx_entry_type" value="income">
                <input type="hidden" name="return_to" value="dashboard/daily_ledger.php?year=<?php echo $selected_year; ?>&month=<?php echo $selected_month; ?>">

                <!-- Type Selector -->
                <div class="type-toggle">
                    <button type="button" id="btn-toggle-income" class="type-btn active-income" onclick="setType('income')">
                        <ion-icon name="arrow-down-circle-outline"></ion-icon> INCOME (+)
                    </button>
                    <button type="button" id="btn-toggle-expenditure" class="type-btn" onclick="setType('expenditure')">
                        <ion-icon name="arrow-up-circle-outline"></ion-icon> EXPENDITURE (-)
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Transaction Date *</label>
                        <input type="date" name="entry_date" id="tx_entry_date" class="input-field" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Target Account *</label>
                        <select name="account_type" id="tx_account_type" class="input-field" required>
                            <option value="cash_at_hand">Cash at Hand (Local Safe / Box)</option>
                            <option value="cash_in_bank">Cash in Bank (Stanbic / CBZ / Bank Account)</option>
                        </select>
                    </div>
                </div>

                <!-- Category Selector -->
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label class="form-label" style="font-size: 0.75rem; margin: 0;">Financial Category *</label>
                        <a href="javascript:void(0)" onclick="openCategoryModal()" style="font-size: 0.7rem; color: #38bdf8; text-decoration: underline;">+ Add New Category</a>
                    </div>
                    <select name="category_code" id="tx_category_code" class="input-field" required>
                        <!-- Incomes Optgroup -->
                        <optgroup label="Standard Diocesan Income Lines" id="optgroup-income">
                            <?php foreach ($categories_grouped['income'] as $cat): ?>
                                <option value="<?php echo $cat['code']; ?>" class="opt-income">
                                    <?php echo h($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>

                        <!-- Special Collections Optgroup -->
                        <optgroup label="Annual Special Diocesan Collections (Pass-Through)" id="optgroup-special">
                            <?php foreach ($categories_grouped['special'] as $cat): ?>
                                <option value="<?php echo $cat['code']; ?>" class="opt-special">
                                    <?php echo h($cat['name']); ?> (<?php echo h($cat['season']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>

                        <!-- Expenditures Optgroup -->
                        <optgroup label="Standard Diocesan Expenditure Lines" id="optgroup-expenditure">
                            <?php foreach ($categories_grouped['expenditure'] as $cat): ?>
                                <option value="<?php echo $cat['code']; ?>" class="opt-expenditure">
                                    <?php echo h($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Amount *</label>
                        <input type="number" step="0.01" name="amount" id="tx_amount" class="input-field" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Currency</label>
                        <select name="currency" id="tx_currency" class="input-field">
                            <option value="USD">USD ($)</option>
                            <option value="ZiG">ZiG</option>
                            <option value="ZAR">ZAR (R)</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Payment Method</label>
                        <select name="payment_method" id="tx_payment_method" class="input-field">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="EcoCash / Mobile">EcoCash / Mobile</option>
                            <option value="InnBucks">InnBucks</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Voucher / Receipt / Ref #</label>
                        <input type="text" name="reference_number" id="tx_reference_number" class="input-field" placeholder="e.g. RCPT-102, PV-08">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Description / Narration *</label>
                    <textarea name="description" id="tx_description" class="input-field" rows="3" required placeholder="e.g. Sunday 1st Mass collection & Tithe envelopes; or Fuel for parish vehicle 40L..."></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeTransactionModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 800; padding: 0.7rem 1.5rem;">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Add New Custom Category -->
    <div id="categoryModal" class="modal">
        <div class="modal-content" style="max-width: 480px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.75rem;">
                <h3 style="font-family: 'Outfit'; color: #38bdf8; margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 6px;">
                    <ion-icon name="pricetag-outline"></ion-icon>
                    <span>Create New Category</span>
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
    <script>
        let currentType = 'income';

        function setType(type) {
            currentType = type;
            document.getElementById('tx_entry_type').value = type;
            
            const btnInc = document.getElementById('btn-toggle-income');
            const btnExp = document.getElementById('btn-toggle-expenditure');
            const optInc = document.getElementById('optgroup-income');
            const optSpec = document.getElementById('optgroup-special');
            const optExp = document.getElementById('optgroup-expenditure');
            const selectCat = document.getElementById('tx_category_code');

            if (type === 'income') {
                btnInc.className = 'type-btn active-income';
                btnExp.className = 'type-btn';
                optInc.style.display = '';
                optSpec.style.display = '';
                optExp.style.display = 'none';

                // Select first income option if current was expenditure
                if (selectCat.value.startsWith('exp_') || selectCat.value.startsWith('custom_exp_')) {
                    selectCat.value = 'inc_4'; // Default to Sunday Collection
                }
            } else {
                btnInc.className = 'type-btn';
                btnExp.className = 'type-btn active-expenditure';
                optInc.style.display = 'none';
                optSpec.style.display = 'none';
                optExp.style.display = '';

                // Select first expenditure option if current was income
                if (selectCat.value.startsWith('inc_') || selectCat.value.startsWith('custom_inc_') || selectCat.value.startsWith('spec_')) {
                    selectCat.value = 'exp_1'; // Default to Electricity
                }
            }
        }

        function openTransactionModal() {
            document.getElementById('tx_entry_id').value = '0';
            document.getElementById('modalTitle').textContent = 'Record Daily Transaction';
            document.getElementById('tx_amount').value = '';
            document.getElementById('tx_description').value = '';
            document.getElementById('tx_reference_number').value = '';
            document.getElementById('tx_entry_date').value = '<?php echo date('Y-m-d'); ?>';
            setType('income');
            document.getElementById('transactionModal').classList.add('active');
        }

        function closeTransactionModal() {
            document.getElementById('transactionModal').classList.remove('active');
        }

        function editTransaction(row) {
            document.getElementById('tx_entry_id').value = row.entry_id;
            document.getElementById('modalTitle').textContent = 'Edit Ledger Transaction #' + row.entry_id;
            document.getElementById('tx_entry_date').value = row.entry_date;
            document.getElementById('tx_amount').value = row.amount;
            document.getElementById('tx_currency').value = row.currency || 'USD';
            document.getElementById('tx_payment_method').value = row.payment_method || 'Cash';
            document.getElementById('tx_account_type').value = row.account_type || 'cash_at_hand';
            document.getElementById('tx_reference_number').value = row.reference_number || '';
            document.getElementById('tx_description').value = row.description;

            setType(row.entry_type);
            document.getElementById('tx_category_code').value = row.category_code;

            document.getElementById('transactionModal').classList.add('active');
        }

        function openCategoryModal() {
            document.getElementById('new_cat_name').value = '';
            document.getElementById('new_cat_type').value = currentType;
            document.getElementById('categoryModal').classList.add('active');
            setTimeout(() => document.getElementById('new_cat_name').focus(), 100);
        }

        function closeCategoryModal() {
            document.getElementById('categoryModal').classList.remove('active');
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
                    // Add new option to dropdown
                    const select = document.getElementById('tx_category_code');
                    const targetGroup = res.category.type === 'income' ? 
                                        document.getElementById('optgroup-income') : 
                                        document.getElementById('optgroup-expenditure');

                    const newOpt = document.createElement('option');
                    newOpt.value = res.category.code;
                    newOpt.textContent = '[Custom] ' + res.category.name;
                    targetGroup.appendChild(newOpt);

                    // Auto select the new category
                    setType(res.category.type);
                    select.value = res.category.code;

                    closeCategoryModal();
                    alert('Category "' + res.category.name + '" added successfully!');
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
            if (event.target === document.getElementById('transactionModal')) {
                closeTransactionModal();
            }
            if (event.target === document.getElementById('categoryModal')) {
                closeCategoryModal();
            }
        };

        // Initialize type view on load
        setType('income');
    </script>
</body>
</html>
