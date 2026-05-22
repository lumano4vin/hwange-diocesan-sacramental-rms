<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Financial Assessment & Levy Oversight
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_admin();

$year = $_GET['year'] ?? date('Y');

$year_payment = db_year_sql('payment_date');
$year_baptism = db_year_sql('date_of_baptism');

// Fetch Parish Financial Health
$finance_data = db_fetchAll("
    SELECT p.parish_id, p.parish_name,
           (SELECT SUM(total_collections) FROM parish_financial_submissions WHERE parish_id = p.parish_id AND fiscal_year = ?) as annual_collections,
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
        .finance-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
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
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0;">
                
                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="stat-card">
                        <span class="stat-label">Total Diocesan Levy ($year)</span>
                        <span class="stat-value" style="color: var(--accent);">$<?php 
                            $total = array_sum(array_column($finance_data, 'target_levy'));
                            echo number_format($total, 2);
                        ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Collected to Date</span>
                        <span class="stat-value" style="color: #10b981;">$<?php 
                            $paid = array_sum(array_column($finance_data, 'paid_levy'));
                            echo number_format($paid, 2);
                        ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Collection Rate</span>
                        <span class="stat-value"><?php echo $total > 0 ? round(($paid / $total) * 100) : 0; ?>%</span>
                    </div>
                </div>

                <div class="finance-grid">
                    <?php foreach ($finance_data as $p): ?>
                        <?php 
                            $target = $p['target_levy'] ?: 0;
                            $paid = $p['paid_levy'] ?: 0;
                            $percent = $target > 0 ? min(100, ($paid / $target) * 100) : 0;
                            $standing_color = ($percent >= 90) ? '#10b981' : (($percent >= 50) ? '#fbbf24' : '#ef4444');
                        ?>
                        <div class="parish-finance-card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <h3 style="font-family: 'Outfit'; color: white;"><?php echo h($p['parish_name']); ?></h3>
                                <div class="status-badge" style="background: <?php echo $standing_color; ?>22; color: <?php echo $standing_color; ?>; border: 1px solid <?php echo $standing_color; ?>44;">
                                    <?php echo $p['bap_count']; ?> Bap
                                </div>
                            </div>

                            <div class="metric-row">
                                <span class="metric-label">Levy Target</span>
                                <span class="metric-value">$<?php echo number_format($target, 2); ?></span>
                            </div>
                            <div class="metric-row">
                                <span class="metric-label">Actual Paid</span>
                                <span class="metric-value" style="color: <?php echo $standing_color; ?>;">$<?php echo number_format($paid, 2); ?></span>
                            </div>

                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percent; ?>%; background: <?php echo $standing_color; ?>;"></div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                                <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;"><?php echo round($percent); ?>% REMITTED</span>
                                <div style="display: flex; gap: 8px;">
                                    <a href="parish_finance_details.php?id=<?php echo $p['parish_id']; ?>&year=<?php echo $year; ?>" class="btn btn-secondary" style="font-size: 0.65rem; padding: 6px 12px;">Review Audit</a>
                                    <button class="btn btn-primary" style="font-size: 0.65rem; padding: 6px 12px; background: <?php echo $standing_color; ?>;">Record Payment</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </main>
    </div>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
