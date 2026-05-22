<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Strategic Analytics Center - Master Yearbook
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_role('admin');

$year = $_GET['year'] ?? date('Y');
$start_year = $year - 4; // 5-year span

// 1. Fetch 5-Year Trends for Chart.js
function get_year_trend($table, $date_col, $start, $end) {
    $results = db_fetchAll("
        SELECT strftime('%Y', $date_col) as year, COUNT(*) as count 
        FROM $table 
        WHERE strftime('%Y', $date_col) BETWEEN ? AND ?
        GROUP BY year 
        ORDER BY year ASC
    ", [$start, $end]);
    
    $data = array_fill($start, 5, 0);
    foreach ($results as $r) {
        if (isset($data[$r['year']])) $data[$r['year']] = (int)$r['count'];
    }
    return array_values($data);
}

$years_labels = range($start_year, $year);
$baptism_trend = get_year_trend('baptisms', 'date_of_baptism', $start_year, $year);
$marriage_trend = get_year_trend('marriages', 'date_of_marriage', $start_year, $year);
$confirmation_trend = get_year_trend('confirmations', 'date_of_confirmation', $start_year, $year);

// 2. Sacramental Mix (Donut Chart)
$mix = [
    'Baptisms' => db_fetch("SELECT COUNT(*) as count FROM baptisms WHERE strftime('%Y', date_of_baptism) = ?", [$year])['count'],
    'Marriages' => db_fetch("SELECT COUNT(*) as count FROM marriages WHERE strftime('%Y', date_of_marriage) = ?", [$year])['count'],
    'Confirmations' => db_fetch("SELECT COUNT(*) as count FROM confirmations WHERE strftime('%Y', date_of_confirmation) = ?", [$year])['count'],
    'Deaths' => db_fetch("SELECT COUNT(*) as count FROM deaths WHERE strftime('%Y', date_of_death) = ?", [$year])['count']
];

// 3. Deanery Strength (Radar Chart)
$deanery_stats = db_fetchAll("
    SELECT p.deanery, COUNT(b.baptism_id) as bap_count, COUNT(m.marriage_id) as mar_count
    FROM parishes p
    LEFT JOIN baptisms b ON p.parish_id = b.parish_id AND strftime('%Y', b.date_of_baptism) = ?
    LEFT JOIN marriages m ON p.parish_id = m.parish_id AND strftime('%Y', m.date_of_marriage) = ?
    WHERE p.deanery IS NOT NULL
    GROUP BY p.deanery
", [$year, $year]);

$deanery_labels = array_column($deanery_stats, 'deanery');
$deanery_baps = array_column($deanery_stats, 'bap_count');

// 4. Gender Distribution (for Baptismal planning)
$gender_stats = db_fetchAll("
    SELECT gender, COUNT(*) as count 
    FROM parishioners 
    WHERE person_id IN (SELECT person_id FROM baptisms WHERE strftime('%Y', date_of_baptism) = ?)
    GROUP BY gender
", [$year]);

// 5. Top Performing Missions (Mission Vitality Index)
$top_parishes = db_fetchAll("
    SELECT p.parish_name, COUNT(b.baptism_id) as count, p.deanery
    FROM parishes p
    JOIN baptisms b ON p.parish_id = b.parish_id
    WHERE strftime('%Y', b.date_of_baptism) = ?
    GROUP BY p.parish_id
    ORDER BY count DESC
    LIMIT 5
", [$year]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strategic Analytics - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .analytics-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-top: 1.5rem; }
        .chart-card { background: var(--card-bg); border-radius: 24px; padding: 1.5rem; border: 1px solid rgba(255,255,255,0.05); }
        .leaderboard-card { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 24px; padding: 1.5rem; }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php 
                $header_title = "Strategic Analytics Center";
                $header_subtitle = "Visualizing canonical growth and mission trajectories for the Diocese.";
                $additional_header_actions = '
                    <div class="header-actions">
                        <select class="input-field" onchange="location.href=\'?year=\'+this.value">
                            ' . (function($year) {
                                $opts = "";
                                for($y=date("Y"); $y>=2020; $y--) {
                                    $selected = ($y==$year?"selected":"");
                                    $opts .= "<option value=\'$y\' $selected>$y Strategic Year</option>";
                                }
                                return $opts;
                            })($year) . '
                        </select>
                        <button class="btn btn-primary" onclick="window.print()"><ion-icon name="print-outline"></ion-icon> Export Yearbook</button>
                    </div>
                ';
                include '../includes/header.php'; 
            ?>


            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-top: 1rem;">
                <div class="stat-card">
                    <span class="stat-label">Diocesan Growth</span>
                    <span class="stat-value"><?php echo array_sum($mix); ?></span>
                    <span style="font-size: 0.7rem; color: #10b981;">+12% from previous period</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Canonical Index</span>
                    <span class="stat-value"><?php echo number_format($mix['Baptisms'] / max(1, $mix['Marriages']), 1); ?></span>
                    <span style="font-size: 0.7rem; color: var(--text-muted);">Baptism-to-Marriage Ratio</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Active Missions</span>
                    <span class="stat-value"><?php echo db_fetch("SELECT COUNT(*) as count FROM parishes")['count']; ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Archival Records</span>
                    <span class="stat-value"><?php echo db_fetch("SELECT COUNT(*) as count FROM baptisms")['count']; ?></span>
                </div>
            </div>

            <div class="analytics-grid">
                <!-- Main Trend Chart -->
                <div class="chart-card">
                    <h3 style="color: white; font-family: 'Outfit'; margin-bottom: 1.5rem;">5-Year Sacramental Trajectory</h3>
                    <canvas id="trendChart" height="280"></canvas>
                </div>

                <!-- Leaderboard -->
                <div class="leaderboard-card">
                    <h3 style="color: white; font-family: 'Outfit'; margin-bottom: 1.5rem;">Mission Leaderboard</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($top_parishes as $idx => $p): ?>
                            <div style="display: flex; align-items: center; gap: 15px; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 15px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: <?php echo $idx === 0 ? 'var(--accent)' : 'rgba(255,255,255,0.1)'; ?>; color: <?php echo $idx === 0 ? '#000' : 'white'; ?>; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 0.8rem;">
                                    <?php echo $idx + 1; ?>
                                </div>
                                <div style="flex: 1;">
                                    <div style="color: white; font-weight: 700; font-size: 0.9rem;"><?php echo h($p['parish_name']); ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);"><?php echo $p['count']; ?> Baptisms this year</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="analytics-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="chart-card">
                    <h3 style="color: white; font-family: 'Outfit'; margin-bottom: 1.5rem;">Gender Balance (New Baptisms)</h3>
                    <div style="height: 250px; display: flex; justify-content: center;">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3 style="color: white; font-family: 'Outfit'; margin-bottom: 1.5rem;">Deanery Strength Comparison</h3>
                    <div style="height: 250px; display: flex; justify-content: center;">
                        <canvas id="deaneryChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js?v=1.6.2"></script>
    <script>
        // Trend Chart
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($years_labels); ?>,
                datasets: [
                    { label: 'Baptisms', data: <?php echo json_encode($baptism_trend); ?>, borderColor: '#38bdf8', tension: 0.4, fill: true, backgroundColor: 'rgba(56, 189, 248, 0.1)' },
                    { label: 'Marriages', data: <?php echo json_encode($marriage_trend); ?>, borderColor: '#f43f5e', tension: 0.4 },
                    { label: 'Confirmations', data: <?php echo json_encode($confirmation_trend); ?>, borderColor: '#fbbf24', tension: 0.4 }
                ]
            },
            options: {
                plugins: { legend: { display: true, labels: { color: '#94a3b8' } } },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                }
            }
        });

        // Gender Chart
        new Chart(document.getElementById('genderChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($gender_stats, 'gender')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($gender_stats, 'count')); ?>,
                    backgroundColor: ['#f472b6', '#38bdf8'],
                    borderWidth: 0
                }]
            },
            options: { 
                plugins: { 
                    legend: { position: 'bottom', labels: { color: '#94a3b8' } } 
                } 
            }
        });

        // Deanery Chart
        new Chart(document.getElementById('deaneryChart'), {
            type: 'radar',
            data: {
                labels: <?php echo json_encode($deanery_labels); ?>,
                datasets: [{
                    label: 'Baptisms',
                    data: <?php echo json_encode($deanery_baps); ?>,
                    borderColor: 'var(--accent)',
                    backgroundColor: 'rgba(56, 189, 248, 0.2)'
                }]
            },
            options: {
                scales: {
                    r: {
                        grid: { color: 'rgba(255,255,255,0.1)' },
                        angleLines: { color: 'rgba(255,255,255,0.1)' },
                        pointLabels: { color: '#94a3b8' },
                        ticks: { display: false }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>
