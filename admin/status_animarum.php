<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Status Animarum (State of Souls) Diagnostic Report - Modernized
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$year = $_GET['year'] ?? date('Y');

// Header metadata
$header_title = "Status Animarum $year";
$header_subtitle = "Annual canonical diagnostic report for the Diocese of Hwange.";

$year_baptism = db_year_sql('date_of_baptism');
$year_confirmation = db_year_sql('date_of_confirmation');
$year_marriage = db_year_sql('date_of_marriage');
$year_communion = db_year_sql('date_of_communion');
$year_death = db_year_sql('date_of_death');

// Statistics queries for the selected year
$stats = [
    'infant_baptisms' => db_fetch("SELECT COUNT(*) as count FROM baptisms WHERE {$year_baptism} = ? AND baptism_rite = 'Infant'", [$year])['count'],
    'adult_baptisms' => db_fetch("SELECT COUNT(*) as count FROM baptisms WHERE {$year_baptism} = ? AND baptism_rite = 'Adult'", [$year])['count'],
    'confirmations' => db_fetch("SELECT COUNT(*) as count FROM confirmations WHERE {$year_confirmation} = ?", [$year])['count'],
    'marriages_catholic' => db_fetch("SELECT COUNT(*) as count FROM marriages WHERE {$year_marriage} = ?", [$year])['count'],
    'first_communions' => db_fetch("SELECT COUNT(*) as count FROM first_holy_communions WHERE {$year_communion} = ?", [$year])['count'],
    'funerals' => db_fetch("SELECT COUNT(*) as count FROM deaths WHERE {$year_death} = ?", [$year])['count'],
];

$total_baptisms = $stats['infant_baptisms'] + $stats['adult_baptisms'];

// Breakdown by Deanery (Using text column from parishes)
$deanery_stats = db_fetchAll("
    SELECT pa.deanery as deanery_name, COUNT(b.baptism_id) as total_baptisms
    FROM parishes pa
    LEFT JOIN baptisms b ON pa.parish_id = b.parish_id AND {$year_baptism} = ?
    GROUP BY pa.deanery ORDER BY total_baptisms DESC
", [$year]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Animarum <?php echo $year; ?> - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Header -->
            <?php include '../includes/header.php'; ?>
            
            <div class="content-body" style="padding: 2rem 0;">
                
                <div class="report-controls" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;">Reporting Year:</span>
                        <form method="GET">
                            <select name="year" onchange="this.form.submit()" style="background: var(--navy); color: white; border: 1px solid var(--accent); padding: 0.5rem 1rem; border-radius: 8px; font-weight: 700;">
                                <?php for($i=date('Y'); $i>=2020; $i--): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $year == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </form>
                    </div>
                    <button class="btn btn-primary" onclick="window.print()" style="display: flex; align-items: center; gap: 8px; font-weight: 800;">
                        <ion-icon name="print-outline"></ion-icon> Print Official Report
                    </button>
                </div>

                <!-- Stats Grid -->
                <div class="stats-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="card bg-card" style="border-left: 4px solid var(--accent); padding: 1.5rem;">
                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Total Baptisms</span>
                        <span style="display: block; font-size: 2rem; font-weight: 900; color: white;"><?php echo $total_baptisms; ?></span>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 5px;">
                            <?php echo $stats['infant_baptisms']; ?> Infants | <?php echo $stats['adult_baptisms']; ?> Adults
                        </div>
                    </div>
                    <div class="card bg-card" style="border-left: 4px solid #ef4444; padding: 1.5rem;">
                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Confirmations</span>
                        <span style="display: block; font-size: 2rem; font-weight: 900; color: white;"><?php echo $stats['confirmations']; ?></span>
                    </div>
                    <div class="card bg-card" style="border-left: 4px solid #f472b6; padding: 1.5rem;">
                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Marriages</span>
                        <span style="display: block; font-size: 2rem; font-weight: 900; color: white;"><?php echo $stats['marriages_catholic']; ?></span>
                    </div>
                    <div class="card bg-card" style="border-left: 4px solid #fbbf24; padding: 1.5rem;">
                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">First Communions</span>
                        <span style="display: block; font-size: 2rem; font-weight: 900; color: white;"><?php echo $stats['first_communions']; ?></span>
                    </div>
                    <div class="card bg-card" style="border-left: 4px solid #94a3b8; padding: 1.5rem;">
                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Funerals</span>
                        <span style="display: block; font-size: 2rem; font-weight: 900; color: white;"><?php echo $stats['funerals']; ?></span>
                    </div>
                </div>

                <!-- Deanery Breakdown -->
                <div class="card bg-card" style="padding: 2rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05);">
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px;">
                        <ion-icon name="map-outline" style="color: var(--accent);"></ion-icon>
                        Deanery Growth Comparison (Baptisms)
                    </h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <?php foreach ($deanery_stats as $d): 
                            $percent = $total_baptisms > 0 ? ($d['total_baptisms'] / $total_baptisms * 100) : 0;
                        ?>
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-weight: 700; color: white;"><?php echo h($d['deanery_name'] ?: 'Unassigned'); ?> Deanery</span>
                                    <span style="font-weight: 900; color: var(--accent);"><?php echo $d['total_baptisms']; ?></span>
                                </div>
                                <div style="width: 100%; height: 12px; background: rgba(255,255,255,0.05); border-radius: 6px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05);">
                                    <div style="width: <?php echo $percent; ?>%; height: 100%; background: linear-gradient(90deg, var(--accent), #d97706); border-radius: 6px; box-shadow: 0 0 10px rgba(251, 191, 36, 0.3);"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 3rem; color: var(--text-muted); font-size: 0.8rem; font-style: italic;">
                    Canonical Archive Data Extract • Generated on <?php echo date('d M Y, H:i'); ?> • Diocese of Hwange
                </div>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <style>
        @media print {
            .dashboard-layout { display: block; }
            .sidebar, .no-print, .report-controls, .header-actions { display: none !important; }
            .main-content { padding: 0 !important; margin: 0 !important; width: 100% !important; }
            .card { background: white !important; color: black !important; border: 1px solid #ddd !important; box-shadow: none !important; }
            .card * { color: black !important; }
            .content-body { padding: 0 !important; }
            body { background: white !important; }
        }
    </style>
</body>
</html>
