<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Main Dashboard - Unified Command Center
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// 1. Fetch Statistical Totals (Parish-Aware)
$params_stats = [];
$stats_filter = get_parish_filter($params_stats);
// For these specific queries, we need to handle the WHERE clause carefully
$where_stats = !empty($stats_filter) ? " WHERE " . ltrim($stats_filter, ' AND ') : "";

$sql = "SELECT 
    (SELECT COUNT(*) FROM parishioners WHERE status IN ('Active', 'Transferred In') $stats_filter) as parishioners,
    (SELECT COUNT(*) FROM baptisms $where_stats) as baptisms,
    (SELECT COUNT(*) FROM confirmations $where_stats) as confirmations,
    (SELECT COUNT(*) FROM marriages $where_stats) as marriages,
    (SELECT COUNT(*) FROM ordinations_professions $where_stats) as ordinations,
    (SELECT COUNT(*) FROM deaths $where_stats) as deaths,
    (SELECT COUNT(*) FROM parishioners WHERE status = 'Transferred In' $stats_filter) as transfers_in,
    (SELECT COUNT(*) FROM parishioners WHERE status IN ('Transferred Out', 'Moved') $stats_filter) as transfers_out
";
$p_full = array_merge($params_stats, $params_stats, $params_stats, $params_stats, $params_stats, $params_stats, $params_stats, $params_stats);
$res = db_fetch($sql, $p_full);

$totals = [
    'parishioners' => $res['parishioners'] ?? 0,
    'baptisms'     => $res['baptisms'] ?? 0,
    'confirmations'=> $res['confirmations'] ?? 0,
    'marriages'    => $res['marriages'] ?? 0,
    'ordinations'  => $res['ordinations'] ?? 0,
    'deaths'       => $res['deaths'] ?? 0,
    'transfers_in' => $res['transfers_in'] ?? 0,
    'transfers_out'=> $res['transfers_out'] ?? 0,
];

// 2. Fetch Monthly Trends (Canonical Event Dates, Parish-Aware Benchmark)
$params = [];
$filter = get_parish_filter($params);
$is_admin = is_admin();
$selected_year = date('Y');
$reports_count = db_fetch("SELECT COUNT(*) as count FROM annual_reports WHERE report_year = ?", [$selected_year])['count'] ?? 0;
$total_parish_count = db_fetch("SELECT COUNT(*) as count FROM parishes")['count'] ?? 1;
$reporting_percentage = ($reports_count / $total_parish_count) * 100;

// Fetch missing reports (for admins)
$missing_reports = [];
if ($is_admin) {
    $missing_reports = db_fetchAll("
        SELECT parish_name FROM parishes 
        WHERE parish_id NOT IN (SELECT parish_id FROM annual_reports WHERE report_year = ?)
        ORDER BY parish_name ASC LIMIT 8
    ", [$selected_year]);
}
$chart_data = [];

try {
    // 2a. Fetch Parish Data (Filtered) - Now with age grouping for baptisms
    $parish_query = "
        SELECT event_date, type, dob FROM (
            SELECT b.date_of_baptism as event_date, 'b' as type, b.parish_id, b.status, p.dob 
            FROM baptisms b JOIN parishioners p ON b.person_id = p.person_id
            UNION ALL
            SELECT date_of_marriage as event_date, 'm' as type, parish_id, status, NULL as dob FROM marriages
            UNION ALL
            SELECT date_of_confirmation as event_date, 'c' as type, parish_id, status, NULL as dob FROM confirmations
            UNION ALL
            SELECT event_date, 'o' as type, parish_id, status, NULL as dob FROM ordinations_professions
            UNION ALL
            SELECT date_of_reception as event_date, 'r' as type, parish_id, status, NULL as dob FROM receptions
            UNION ALL
            SELECT date_of_death as event_date, 'd' as type, parish_id, status, NULL as dob FROM deaths
        ) t WHERE status != 'Draft' AND event_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH) $filter
    ";
    $p_events = db_fetchAll($parish_query, $params);
    
    // 2b. Fetch Diocesan Data (Global) for Benchmarking
    $diocese_query = "
        SELECT event_date, type FROM (
            SELECT date_of_baptism as event_date, 'b' as type, status FROM baptisms
            UNION ALL
            SELECT date_of_marriage as event_date, 'm' as type, status FROM marriages
            UNION ALL
            SELECT date_of_confirmation as event_date, 'c' as type, status FROM confirmations
            UNION ALL
            SELECT event_date, 'o' as type, status FROM ordinations_professions
            UNION ALL
            SELECT date_of_reception as event_date, 'r' as type, status FROM receptions
            UNION ALL
            SELECT date_of_death as event_date, 'd' as type, status FROM deaths
        ) t WHERE status != 'Draft' AND event_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    ";
    $d_events = db_fetchAll($diocese_query);
    
    // Check total parishes
    $total_parishes = $total_parish_count;

    $all_stats = [];
    // Process Parish Events
    foreach ($p_events as $r) {
        if (!empty($r['event_date'])) {
            $m = substr($r['event_date'], 0, 7);
            $type = $r['type'];
            
            if ($type === 'b' && !empty($r['dob'])) {
                // Calculate age at baptism
                $bap_date = new DateTime($r['event_date']);
                $birth_date = new DateTime($r['dob']);
                $age = $birth_date->diff($bap_date)->y;
                
                if ($age < 1) $age_cat = 'infant';
                elseif ($age <= 12) $age_cat = 'child';
                elseif ($age <= 24) $age_cat = 'youth';
                else $age_cat = 'adult';
                
                $key = 'p_b_' . $age_cat;
                $all_stats[$m][$key] = ($all_stats[$m][$key] ?? 0) + 1;
                $all_stats[$m]['p_b_total'] = ($all_stats[$m]['p_b_total'] ?? 0) + 1;
            } else {
                $key = 'p_' . $type;
                $all_stats[$m][$key] = ($all_stats[$m][$key] ?? 0) + 1;
            }
        }
    }
    // Process Diocesan Events (Calculate Averages)
    foreach ($d_events as $r) {
        if (!empty($r['event_date'])) {
            $m = substr($r['event_date'], 0, 7);
            $type = $r['type'];
            $key = 'd_' . $type;
            $all_stats[$m][$key] = ($all_stats[$m][$key] ?? 0) + 1;
        }
    }

    krsort($all_stats);
    $keys = array_keys($all_stats);
    $latest_month = !empty($keys) ? $keys[0] : date('Y-m');

    for ($i = 5; $i >= 0; $i--) {
        $m = date('Y-m', strtotime($latest_month . "-01 -$i months"));
        $b_total = $all_stats[$m]['p_b_total'] ?? 0;
        
        $chart_data[] = [
            'month' => $m,
            'p_b_infant' => $all_stats[$m]['p_b_infant'] ?? 0,
            'p_b_child' => $all_stats[$m]['p_b_child'] ?? 0,
            'p_b_youth' => $all_stats[$m]['p_b_youth'] ?? 0,
            'p_b_adult' => $all_stats[$m]['p_b_adult'] ?? 0,
            'p_b_total' => $b_total,
            'p_m' => $all_stats[$m]['p_m'] ?? 0,
            'd_avg_b' => round(($all_stats[$m]['d_b'] ?? 0) / $total_parishes, 1),
            'd_avg_m' => round(($all_stats[$m]['d_m'] ?? 0) / $total_parishes, 1)
        ];
    }
} catch (Exception $e) {
    for ($i = 5; $i >= 0; $i--) {
        $chart_data[] = [
            'month' => date('Y-m', strtotime("-$i months")), 
            'p_b_infant' => 0, 'p_b_child' => 0, 'p_b_youth' => 0, 'p_b_adult' => 0, 'p_b_total' => 0,
            'p_m' => 0, 'd_avg_b' => 0, 'd_avg_m' => 0
        ];
    }
}

// Check for empty parishes (System Initialization Status)
        $parish_count = $total_parish_count;

// Automated Backup Checking Logic (Integrated with Audit Logs)
$last_backup_audit = db_fetch("SELECT created_at FROM audit_logs WHERE action_type = 'BACKUP' ORDER BY created_at DESC LIMIT 1");
$days_since_backup = 999;
$last_backup_date = "Never";

if ($last_backup_audit) {
    $days_since_backup = floor((time() - strtotime($last_backup_audit['created_at'])) / 86400);
    $last_backup_date = date('d M Y, H:i', strtotime($last_backup_audit['created_at']));
}

// Also check physical files in the Backups folder (for manual file-based backups)
$backup_dir = __DIR__ . '/../Backups';
if (is_dir($backup_dir)) {
    $backups = glob($backup_dir . '/database_backup_*.sqlite');
    if (!empty($backups)) {
        usort($backups, function($a, $b) { return filemtime($b) - filemtime($a); });
        $file_days = floor((time() - filemtime($backups[0])) / 86400);
        if ($file_days < $days_since_backup) {
            $days_since_backup = $file_days;
            $last_backup_date = date('d M Y, H:i', filemtime($backups[0]));
        }
    }
}
$needs_backup = ($days_since_backup >= 7);

// 3. Today at a Glance Stats
$today = date('Y-m-d');
$week_ago = date('Y-m-d', strtotime('-7 days'));
$today_count = db_fetch("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) = ? AND action_type = 'CREATE'", [$today])['count'] ?? 0;

// 4. Pending Verification Queue (Authorized Roles Only)
$pending_count = 0;
$can_verify_global = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chancellor' || $_SESSION['role'] === 'priest' || $_SESSION['role'] === 'deacon');
$pending_queue = [];

if ($can_verify_global) {
    $sql_drafts = "
        SELECT SUM(c) as total FROM (
            SELECT COUNT(*) as c FROM baptisms WHERE status = 'Draft' UNION ALL
            SELECT COUNT(*) as c FROM marriages WHERE status = 'Draft' UNION ALL
            SELECT COUNT(*) as c FROM confirmations WHERE status = 'Draft' UNION ALL
            SELECT COUNT(*) as c FROM deaths WHERE status = 'Draft' UNION ALL
            SELECT COUNT(*) as c FROM ordinations_professions WHERE status = 'Draft' UNION ALL
            SELECT COUNT(*) as c FROM receptions WHERE status = 'Draft'
        ) t
    ";
    $pending_count = db_fetch($sql_drafts)['total'] ?? 0;
    
    if ($_SESSION['role'] === 'admin') {
        $pending_count += db_fetch("SELECT COUNT(*) as count FROM communications WHERE status = 'Pending'")['count'] ?? 0;
    }


    if ($pending_count > 0) {
        $sql_queue = "
            SELECT 'Baptism' as type, b.baptism_id as id, p.first_name || ' ' || p.last_name as name, b.date_of_baptism as date, pr.parish_name, 'view_baptism.php' as link
            FROM baptisms b JOIN parishioners p ON b.person_id = p.person_id JOIN parishes pr ON b.parish_id = pr.parish_id WHERE b.status = 'Draft'
            UNION ALL
            SELECT 'Marriage' as type, m.marriage_id as id, p1.first_name || ' & ' || p2.first_name as name, m.date_of_marriage as date, pr.parish_name, 'view_marriage.php' as link
            FROM marriages m JOIN parishioners p1 ON m.groom_person_id = p1.person_id JOIN parishioners p2 ON m.bride_person_id = p2.person_id JOIN parishes pr ON m.parish_id = pr.parish_id WHERE m.status = 'Draft'
            UNION ALL
            SELECT 'Profession of Faith' as type, r.reception_id as id, p.first_name || ' ' || p.last_name as name, r.date_of_reception as date, pr.parish_name, 'view_reception.php' as link
            FROM receptions r JOIN parishioners p ON r.person_id = p.person_id JOIN parishes pr ON r.parish_id = pr.parish_id WHERE r.status = 'Draft'
            " . (($_SESSION['role'] === 'admin') ? "
            UNION ALL
            SELECT 'Mission Query' as type, c.comm_id as id, u.full_name as name, c.created_at as date, pr.parish_name, '../admin/communications.php' as link
            FROM communications c JOIN users u ON c.sender_id = u.user_id JOIN parishes pr ON c.parish_id = pr.parish_id WHERE c.status = 'Pending'
            " : "") . "
            ORDER BY date DESC LIMIT 5";
        $pending_queue = db_fetchAll($sql_queue);
    }
}

$recent_logs = db_fetchAll("
    SELECT a.*, u.full_name 
    FROM audit_logs a 
    JOIN users u ON a.user_id = u.user_id 
    ORDER BY a.created_at DESC 
    LIMIT 5
");

// 5. Last User Activity (To remind where they left off)
$last_user_activity = db_fetch("
    SELECT a.*, u.full_name 
    FROM audit_logs a 
    JOIN users u ON a.user_id = u.user_id 
    WHERE a.user_id = ? 
    ORDER BY a.created_at DESC 
    LIMIT 1
", [$_SESSION['user_id']]);

$mission_tips = [
    "Always double-check the spelling of family names against the physical register.",
    "Remember to verify the 'Draft' records to finalize their canonical status.",
    "Use the Global Search (Alt + S) to quickly find any record across the Diocese.",
    "Ensure all marriage records include the accurate Parish Registry number.",
    "Your work ensures the canonical legacy of the Diocese is safe for 100+ years."
];
$random_tip = $mission_tips[array_rand($mission_tips)];

// Mapping for Resume Mission Links
$table_links = [
    'baptisms' => '../sacraments/view_baptism.php',
    'marriages' => '../sacraments/view_marriage.php',
    'confirmations' => '../sacraments/view_confirmation.php',
    'deaths' => '../sacraments/view_burial.php',
    'ordinations_professions' => '../sacraments/vocations_list.php',
    'receptions' => '../sacraments/view_reception.php',
    'parishioners' => '../parishioners/view.php',
    'database' => 'settings.php',
    'communications' => 'communication_hub.php',
    'users' => '../admin/users_list.php',
    'parishes' => '../admin/parishes.php'
];

$base_url = $table_links[$last_user_activity['table_name'] ?? ''] ?? '#';
$resume_url = '#';

if ($base_url !== '#') {
    if (in_array($last_user_activity['table_name'], ['ordinations_professions', 'database', 'users', 'parishes', 'communications']) || empty($last_user_activity['record_id'])) {
        $resume_url = $base_url;
    } else {
        $resume_url = $base_url . '?id=' . $last_user_activity['record_id'];
    }
}

// 5. Fetch Deanery Heatmap Data
$heatmap_data = [];
$max_activity = 1;
$deanery_sql = "
    SELECT p.deanery, COUNT(*) as count FROM (
        SELECT parish_id FROM baptisms 
        UNION ALL SELECT parish_id FROM marriages 
        UNION ALL SELECT parish_id FROM confirmations
    ) t JOIN parishes p ON t.parish_id = p.parish_id 
    WHERE p.deanery IS NOT NULL 
    GROUP BY p.deanery
";
try {
    $results = db_fetchAll($deanery_sql);
    foreach ($results as $row) {
        $heatmap_data[$row['deanery']] = $row['count'];
        if ($row['count'] > $max_activity) $max_activity = $row['count'];
    }
} catch (Exception $e) {}

// 6. Fetch Mission Feed (Recent Sacramental Events - Parish Aware)
$params_feed = [];
$feed_filter = get_parish_filter($params_feed);
$feed_where = !empty($feed_filter) ? " WHERE " . ltrim($feed_filter, ' AND ') : "";

// We need to duplicate params for each UNION part
$params_feed_full = array_merge($params_feed, $params_feed, $params_feed);

$mission_feed = db_fetchAll("
    SELECT 'Baptism' as type, b.baptism_id as id, p.first_name || ' ' || p.last_name as name, b.date_of_baptism as date, pr.parish_name, 'water-outline' as icon, '#38bdf8' as color
    FROM baptisms b JOIN parishioners p ON b.person_id = p.person_id JOIN parishes pr ON b.parish_id = pr.parish_id $feed_where
    UNION ALL
    SELECT 'Marriage' as type, m.marriage_id as id, p1.first_name || ' & ' || p2.first_name as name, m.date_of_marriage as date, pr.parish_name, 'heart-outline' as icon, '#f472b6' as color
    FROM marriages m JOIN parishioners p1 ON m.groom_person_id = p1.person_id JOIN parishioners p2 ON m.bride_person_id = p2.person_id JOIN parishes pr ON m.parish_id = pr.parish_id $feed_where
    UNION ALL
    SELECT 'Confirmation' as type, c.confirmation_id as id, p.first_name || ' ' || p.last_name as name, c.date_of_confirmation as date, pr.parish_name, 'flame-outline' as icon, '#fbbf24' as color
    FROM confirmations c JOIN parishioners p ON c.person_id = p.person_id JOIN parishes pr ON c.parish_id = pr.parish_id $feed_where
    ORDER BY date DESC LIMIT 5
", $params_feed_full);

$feed_title = $is_admin ? "Global Mission Feed" : "Local Mission Activity";

$user_full_name = $_SESSION['full_name'] ?? 'User';
$user_role = ucfirst($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo get_diocese_branding(); ?> Records Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.4">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <!-- Session Welcome Toast -->
        <?php if (isset($_SESSION['login_success_popup']) && $_SESSION['login_success_popup'] === true): ?>
            <?php unset($_SESSION['login_success_popup']); ?>
            
            <script>
                window.addEventListener('load', () => {
                    setTimeout(() => {
                        if (typeof showToast === 'function') {
                            showToast('Welcome back, <?php echo h(get_user_greeting_name($user_full_name)); ?>! Peace be with you.', 'success');
                        }
                    }, 500);
                });
            </script>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Header (Unified) -->
            <?php 
                $header_title = "Diocesan Command Center";
                $header_subtitle = "Welcome, " . h($user_full_name) . ". Here is the canonical status of " . get_diocese_branding() . " at a glance.";
                include '../includes/header.php'; 
            ?>

            <!-- Canonical Command Center Quick Access -->
            <div class="command-center-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; margin-bottom: 2rem; position: relative; z-index: 10;">
                <?php if (in_array($_SESSION['role'], ['admin', 'chancellor', 'observer'])): ?>
                <a href="observer.php" onclick="window.location.href='observer.php'" class="command-card" style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 20px; padding: 1.25rem; text-decoration: none; transition: all 0.3s ease; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #10b981; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <ion-icon name="eye-outline"></ion-icon>
                    </div>
                    <div>
                        <div style="color: white; font-weight: 700; font-family: 'Outfit'; font-size: 0.95rem;">Bishop's Oversight Portal</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Total Diocesan Activities</div>
                    </div>
                </a>
                <?php endif; ?>
                <a href="../admin/clergy_management.php" onclick="window.location.href='../admin/clergy_management.php'" class="command-card" style="background: rgba(56, 189, 248, 0.05); border: 1px solid rgba(56, 189, 248, 0.1); border-radius: 20px; padding: 1.25rem; text-decoration: none; transition: all 0.3s ease; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: var(--accent); color: #000; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <ion-icon name="people-circle-outline"></ion-icon>
                    </div>
                    <div>
                        <div style="color: white; font-weight: 700; font-family: 'Outfit'; font-size: 0.95rem;">Clergy Management</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Assignments & Appointments</div>
                    </div>
                </a>
                <a href="reports.php" onclick="window.location.href='reports.php'" class="command-card" style="background: rgba(244, 63, 94, 0.05); border: 1px solid rgba(244, 63, 94, 0.1); border-radius: 20px; padding: 1.25rem; text-decoration: none; transition: all 0.3s ease; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #f43f5e; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <ion-icon name="analytics-outline"></ion-icon>
                    </div>
                    <div>
                        <div style="color: white; font-weight: 700; font-family: 'Outfit'; font-size: 0.95rem;">Strategic Analytics</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Growth & Trajectories</div>
                    </div>
                </a>
                <a href="archive_ocr.php" onclick="window.location.href='archive_ocr.php'" class="command-card" style="background: rgba(251, 191, 36, 0.05); border: 1px solid rgba(251, 191, 36, 0.1); border-radius: 20px; padding: 1.25rem; text-decoration: none; transition: all 0.3s ease; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #fbbf24; color: #000; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <ion-icon name="scan-outline"></ion-icon>
                    </div>
                    <div>
                        <div style="color: white; font-weight: 700; font-family: 'Outfit'; font-size: 0.95rem;">Digital Scriptorium</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Archival Digitization</div>
                    </div>
                </a>
                <a href="../communication_hub.php" onclick="window.location.href='../communication_hub.php'" class="command-card" style="background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.1); border-radius: 20px; padding: 1.25rem; text-decoration: none; transition: all 0.3s ease; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #8b5cf6; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <ion-icon name="chatbubbles-outline"></ion-icon>
                    </div>
                    <div>
                        <div style="color: white; font-weight: 700; font-family: 'Outfit'; font-size: 0.95rem;">Canonical Ledger</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Secure Mission Feed</div>
                    </div>
                </a>
            </div>
            <style>
                .command-card, .stat-card {
                    position: relative;
                    z-index: 100;
                    pointer-events: auto !important;
                    cursor: pointer !important;
                    display: flex !important;
                }
                .command-card *, .stat-card * {
                    pointer-events: none !important;
                }
                .command-card:hover, .stat-card:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 15px 30px rgba(0,0,0,0.4);
                    border-color: var(--accent) !important;
                }
            </style>
            
            <?php if ($needs_backup): ?>
            <!-- Automated Backup Reminder -->
            <div class="card" style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--error); margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; padding: 1.5rem; gap: 1.5rem; border-radius: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <div style="font-size: 2.5rem; color: var(--error);"><ion-icon name="warning-outline"></ion-icon></div>
                    <div>
                        <h3 style="color: white; margin-bottom: 4px;">Offline Backup Recommended</h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                            It has been <strong style="color: var(--error);"><?php echo $days_since_backup >= 999 ? 'a very long time' : $days_since_backup . ' days'; ?></strong> since your last secure backup. 
                            To protect canonical records from hard drive failures, please insert a Flash Drive and run the Backup Utility.
                            <br><span style="font-size: 0.8rem; color: rgba(255,255,255,0.4);">Last verified backup: <?php echo $last_backup_date; ?></span>
                        </p>
                    </div>
                </div>
                <div style="text-align: right; min-width: 220px; display: flex; flex-direction: column; gap: 10px;">
                    <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 8px; border: 1px dashed var(--error);">
                        <p style="color: white; font-size: 0.75rem; font-family: monospace; margin: 0;">Offline Archive Utility:</p>
                        <p style="color: var(--error); font-size: 0.85rem; font-weight: 700; margin: 4px 0 0 0;">BACKUP_DATABASE.bat</p>
                    </div>
                    <?php if (is_admin()): ?>
                        <a href="../actions/backup.php" class="btn btn-primary" style="background: var(--error); border-color: var(--error); color: white; width: 100%; font-size: 0.8rem; padding: 10px;">
                            <ion-icon name="cloud-download-outline"></ion-icon>
                            Download Secure Backup
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($parish_count == 0): ?>
            <!-- System Initialization Alert -->
            <div class="card" style="background: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning); margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; padding: 1.5rem; gap: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <div style="font-size: 2.5rem; color: var(--warning);"><ion-icon name="alert-circle-outline"></ion-icon></div>
                    <div>
                        <h3 style="color: white; margin-bottom: 4px;">System Initialization Required</h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">The canonical list of parishes and missions is currently empty. Please restore the default diocesan structure to proceed with record entry.</p>
                    </div>
                </div>
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chancellor'): ?>
                    <a href="restore_parishes.php" class="btn btn-primary" style="background: var(--warning); border-color: var(--warning); color: #000; font-weight: 800;">
                        Initialize Parish Data
                    </a>
                <?php else: ?>
                    <p style="color: var(--warning); font-size: 0.8rem; font-weight: 700;">Please contact your Diocesan Administrator.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php 
            // Check for pending handovers for the current user
            $pending_handover = db_fetch("
                SELECT COUNT(*) as count 
                FROM parish_handovers 
                WHERE (outgoing_priest_id = ? AND status = 'Pending') 
                   OR (incoming_priest_id = ? AND status = 'Signed_Off')
            ", [$_SESSION['user_id'], $_SESSION['user_id']])['count'] ?? 0;
            
            if ($pending_handover > 0): 
            ?>
            <div class="card" style="background: rgba(56, 189, 248, 0.1); border: 1px solid var(--accent); margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; padding: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 2rem; color: var(--accent);"><ion-icon name="swap-horizontal-outline"></ion-icon></div>
                    <div>
                        <h4 style="color: white; margin-bottom: 2px;">Canonical Handover Required</h4>
                        <p style="color: var(--text-muted); font-size: 0.85rem;">You have <?php echo $pending_handover; ?> mission transition(s) awaiting your digital sign-off or acceptance.</p>
                    </div>
                </div>
                <a href="handover_hub.php" class="btn btn-primary" style="font-size: 0.8rem; padding: 8px 20px;">Open Handover Hub</a>
            </div>
            <?php endif; ?>

            <!-- Premium Quick Actions (Phase 3) -->
            <?php if (can_edit()): ?>
            <div style="margin-bottom: 2rem;">
                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; color: white; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                    <ion-icon name="flash-outline" style="color: var(--accent);"></ion-icon> Quick Actions
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                    <a href="../sacraments/baptism_add.php" class="quick-action-btn" style="--btn-color: #fb923c;">
                        <div class="icon-wrap"><ion-icon name="water-outline"></ion-icon></div>
                        <span>Add Baptism</span>
                    </a>
                    <a href="../sacraments/marriage_add.php" class="quick-action-btn" style="--btn-color: #f43f5e;">
                        <div class="icon-wrap"><ion-icon name="heart-outline"></ion-icon></div>
                        <span>Add Marriage</span>
                    </a>
                    <a href="../parishioners/add.php" class="quick-action-btn" style="--btn-color: #10b981;">
                        <div class="icon-wrap"><ion-icon name="person-add-outline"></ion-icon></div>
                        <span>Register Faithful</span>
                    </a>
                    <a href="parish_reports.php" class="quick-action-btn" style="--btn-color: #8b5cf6;">
                        <div class="icon-wrap"><ion-icon name="document-text-outline"></ion-icon></div>
                        <span>Generate Report</span>
                    </a>
                </div>
                <style>
                    .quick-action-btn {
                        background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
                        border: 1px solid rgba(255,255,255,0.05);
                        border-radius: 16px;
                        padding: 1.25rem;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 12px;
                        text-decoration: none;
                        color: white;
                        font-family: 'Outfit', sans-serif;
                        font-weight: 700;
                        font-size: 0.9rem;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        position: relative;
                        overflow: hidden;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                    }
                    .quick-action-btn::before {
                        content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
                        background: radial-gradient(circle at center, var(--btn-color) 0%, transparent 70%);
                        opacity: 0; transition: opacity 0.3s; z-index: 0;
                    }
                    .quick-action-btn:hover {
                        transform: translateY(-5px);
                        border-color: var(--btn-color);
                        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
                    }
                    .quick-action-btn:hover::before { opacity: 0.15; }
                    .quick-action-btn .icon-wrap {
                        width: 48px; height: 48px; border-radius: 12px;
                        background: rgba(255,255,255,0.03);
                        display: flex; align-items: center; justify-content: center;
                        font-size: 1.5rem; color: var(--btn-color);
                        position: relative; z-index: 1;
                        transition: transform 0.3s;
                    }
                    .quick-action-btn:hover .icon-wrap { transform: scale(1.1); background: var(--btn-color); color: #fff; }
                    .quick-action-btn span { position: relative; z-index: 1; }
                </style>
            </div>
            <?php endif; ?>

            <!-- Top Row: Stats + At a Glance -->
            <div class="dashboard-top-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                
                <!-- Main Stats Cards Grid -->
                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 0;">
                    <a href="../parishioners.php" class="stat-card card-faithful" style="display: flex; flex-direction: column; justify-content: space-between;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
                            <div class="stat-icon" style="background: rgba(99, 102, 241, 0.1); color: var(--accent);"><ion-icon name="people-outline"></ion-icon></div>
                            <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-end;">
                                <span style="font-size: 0.65rem; background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 2px 6px; border-radius: 4px; font-weight: 700; border: 1px solid rgba(16,185,129,0.2);"><ion-icon name="log-in-outline" style="vertical-align: text-bottom;"></ion-icon> <?php echo number_format($totals['transfers_in']); ?> Immigrants</span>
                                <span style="font-size: 0.65rem; background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 2px 6px; border-radius: 4px; font-weight: 700; border: 1px solid rgba(245,158,11,0.2);"><ion-icon name="log-out-outline" style="vertical-align: text-bottom;"></ion-icon> <?php echo number_format($totals['transfers_out']); ?> Migrants</span>
                            </div>
                        </div>
                        <div class="stat-info" style="margin-top: auto; padding-top: 1rem;">
                            <h3><?php echo number_format($totals['parishioners']); ?></h3>
                            <p>Total Faithful</p>
                        </div>
                    </a>
                    <a href="../sacraments/baptism_list.php" class="stat-card card-baptism">
                        <div class="stat-icon" style="background: #fb923c;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 22C12 22 19 18 19 12C19 11 18.5 10.5 18 10C18 7 16 4 12 4C8 4 6 7 6 10C5.5 10.5 5 11 5 12C5 18 12 22 12 22Z" fill="#fffef0" />
                                <path d="M12 4V22M12 4C14 4 16 6 16 9M12 4C10 4 8 6 8 9M12 4C14.5 4 17.5 7 17.5 11M12 4C9.5 4 6.5 7 6.5 11" stroke="#fb923c" stroke-width="0.5"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($totals['baptisms']); ?></h3>
                            <p>Baptisms</p>
                        </div>
                    </a>
                    <a href="../sacraments/marriage_list.php" class="stat-card card-marriage">
                        <div class="stat-icon" style="background: #334155;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 21C12 21 20 16 20 10C20 6.5 17 4 14 4C12.5 4 11.5 4.5 11 5C10.5 4.5 9.5 4 8 4C5 4 2 6.5 2 10C2 16 12 21 12 21Z" fill="#ef4444" />
                                <circle cx="10" cy="11" r="3.5" stroke="#fbbf24" stroke-width="2" />
                                <circle cx="14" cy="11" r="3.5" stroke="#fbbf24" stroke-width="2" />
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($totals['marriages']); ?></h3>
                            <p>Marriages</p>
                        </div>
                    </a>
                    <a href="../sacraments.php" class="stat-card card-confirmation">
                        <div class="stat-icon" style="background: #10b981;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 18C12 18 19 14 19 10C19 6 16 3 12 3C8 3 5 6 5 10C5 14 12 18 12 18Z" fill="white" />
                                <path d="M12 12C12.5523 12 13 11.5523 13 11C13 10.4477 12.5523 10 12 10C11.4477 10 11 10.4477 11 11C11 11.5523 11.4477 12 12 12Z" fill="#ef4444" />
                                <path d="M8 10L5 8M16 10L19 8M12 18V21M9 19L12 18L15 19" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($totals['confirmations']); ?></h3>
                            <p>Confirmations</p>
                        </div>
                    </a>
                    <a href="../sacraments.php" class="stat-card card-ordination">
                        <div class="stat-icon" style="background: #facc15;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 4V16C8 18 10 20 12 20C14 20 16 18 16 16V4" stroke="#059669" stroke-width="4" stroke-linecap="round"/>
                                <path d="M10 8H14M10 14H14" stroke="white" stroke-width="1"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($totals['ordinations']); ?></h3>
                            <p>Holy Orders</p>
                        </div>
                    </a>
                    <a href="../sacraments.php" class="stat-card card-death">
                        <div class="stat-icon" style="background: #94a3b8;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 22V14M12 14C12 14 19 12 19 8C19 4 16 2 12 2C8 2 5 4 5 8C5 12 12 14 12 14Z" fill="rgba(255,255,255,0.2)" stroke="white" stroke-width="2"/>
                                <path d="M8 7L11 9L8 11" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($totals['deaths']); ?></h3>
                            <p>Deaths</p>
                        </div>
                    </a>
                </div>

                <!-- MISSION PROGRESS (Giving Life) -->
                <div class="card bg-card" style="border-top: 4px solid var(--accent); background: linear-gradient(180deg, rgba(251, 191, 36, 0.05) 0%, rgba(15, 23, 42, 0.9) 100%);">
                    <div class="card-header" style="padding-bottom: 0;">
                        <h3 style="font-size: 0.75rem; text-transform: uppercase; color: var(--accent); letter-spacing: 2px; font-weight: 800;">Mission Progress</h3>
                    </div>
                    <div class="card-body" style="padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px;">
                            <span style="font-size: 1.5rem; font-weight: 900; color: white;"><?php echo $today_count; ?><small style="font-size: 0.8rem; color: var(--text-muted); font-weight: 400; margin-left: 4px;">/ 10</small></span>
                            <span style="font-size: 0.7rem; color: var(--accent); font-weight: 700; text-transform: uppercase;">Daily Goal</span>
                        </div>
                        <div class="mission-progress-container">
                            <div class="mission-progress-fill" style="width: <?php echo min(100, ($today_count / 10) * 100); ?>%;"></div>
                        </div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">
                            <?php if ($today_count >= 10): ?>
                                <span style="color: var(--success); font-weight: 700;">Excellent!</span> You have surpassed your daily record target.
                            <?php else: ?>
                                <span style="color: var(--accent); font-weight: 700;"><?php echo 10 - $today_count; ?> more</span> to reach today's canonical goal.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Deanery Activity Heatmap -->
            <section class="deanery-heatmap-section" style="margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; color: white;">
                        <ion-icon name="map-outline" style="color: var(--accent);"></ion-icon>
                        Status Animarum: Deanery Activity Heatmap
                    </h3>
                    <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Canonical Intensity Across Diocese</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                    <?php foreach ($heatmap_data as $name => $count): 
                        $intensity = ($count / ($max_activity ?: 1)) * 100;
                        $opacity = 0.03 + (($intensity / 100) * 0.15);
                    ?>
                        <div class="card" style="background: rgba(56, 189, 248, <?php echo $opacity; ?>); border: 1px solid rgba(56, 189, 248, <?php echo $opacity + 0.1; ?>); padding: 1.5rem; border-radius: 1.25rem; position: relative; overflow: hidden; transition: all 0.3s ease; backdrop-filter: blur(10px);">
                            <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: var(--accent); opacity: <?php echo $opacity; ?>; filter: blur(40px); border-radius: 50%;"></div>
                            <h4 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1rem;"><?php echo h($name); ?></h4>
                            <p style="color: var(--text-muted); font-size: 0.7rem; margin: 4px 0 1rem; text-transform: uppercase; letter-spacing: 1px;">Deanery Sector</p>
                            <div style="display: flex; align-items: baseline; gap: 6px;">
                                <span style="font-size: 1.75rem; font-weight: 900; color: white;"><?php echo number_format($count); ?></span>
                                <span style="font-size: 0.75rem; color: var(--accent); font-weight: 700;">Records</span>
                            </div>
                            <div style="width: 100%; height: 4px; background: rgba(255,255,255,0.05); border-radius: 2px; margin-top: 1rem; overflow: hidden;">
                                <div style="width: <?php echo $intensity; ?>%; height: 100%; background: var(--accent); border-radius: 2px; box-shadow: 0 0 10px var(--accent);"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Analytics & Activity Row -->
            <section class="activity-section" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <!-- Resume Mission (Last Activity Reminder) -->
                    <?php if ($last_user_activity): ?>
                    <div class="card" style="background: linear-gradient(90deg, rgba(16, 185, 129, 0.1) 0%, rgba(15, 23, 42, 0.8) 100%); border-left: 4px solid var(--success); padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <ion-icon name="arrow-undo-outline" style="font-size: 1.5rem; color: var(--success);"></ion-icon>
                            <div>
                                <h4 style="font-size: 0.8rem; text-transform: uppercase; color: var(--success); margin-bottom: 2px;">Resume Mission</h4>
                                <p style="font-size: 0.95rem; color: white;">You last worked on <strong style="color: var(--accent);"><?php echo h($last_user_activity['table_name']); ?></strong> (<?php echo strtolower(h($last_user_activity['action_type'])); ?>d). Ready to continue?</p>
                            </div>
                        </div>
                        <a href="<?php echo $resume_url; ?>" class="btn btn-sm" style="background: var(--success); color: #000; font-weight: 800; border: none; padding: 0.6rem 1.2rem; border-radius: 8px;">Resume Now →</a>
                    </div>
                    <?php endif; ?>

                    <!-- Main Chart -->
                    <div class="card bg-card" style="flex: 1;">
                        <div class="card-header">
                            <h3><ion-icon name="trending-up-outline" style="color: var(--accent); vertical-align: middle; margin-right: 8px;"></ion-icon> Canonical Growth Trend</h3>
                        </div>
                        <div class="card-body" style="height: 220px;">
                            <canvas id="growthChart"></canvas>
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <!-- Mission Success Tip -->
                    <div class="card" style="background: rgba(251, 191, 36, 0.1); border: 2px solid var(--accent); padding: 1.5rem; box-shadow: 0 10px 20px rgba(0,0,0,0.3); border-radius: 1.5rem;">
                        <h4 style="font-size: 0.85rem; text-transform: uppercase; color: var(--accent); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; font-weight: 800;">
                            <ion-icon name="bulb" style="font-size: 1.2rem;"></ion-icon> Mission Success Tip
                        </h4>
                        <p style="font-size: 1rem; color: #ffffff; font-weight: 600; line-height: 1.6; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">"<?php echo $random_tip; ?>"</p>
                    </div>

                    <!-- Global Mission Feed (Unified Overview) -->
                    <div class="card bg-card" style="flex: 1; display: flex; flex-direction: column; border: 1px solid rgba(255,255,255,0.05); background: linear-gradient(180deg, rgba(30, 41, 59, 0.4) 0%, rgba(15, 23, 42, 0.9) 100%);">
                        <div class="card-header" style="border-bottom: 1px solid rgba(255,255,255,0.05); padding: 1.25rem;">
                            <h3 style="font-size: 0.9rem; font-family: 'Outfit'; display: flex; align-items: center; gap: 10px;">
                                <span class="pulse-dot"></span> <?php echo h($feed_title); ?>
                            </h3>
                        </div>
                        <div class="card-body p-0" style="flex: 1; overflow-y: auto; max-height: 400px;">
                            <div class="mission-feed">
                                <?php if (empty($mission_feed)): ?>
                                    <div style="padding: 2rem; text-align: center; color: var(--text-muted);">
                                        <p>No recent sacramental activities recorded in the last 30 days.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($mission_feed as $item): ?>
                                        <div class="mission-feed-item" style="padding: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.03); display: flex; gap: 1rem; align-items: flex-start; transition: all 0.3s ease;">
                                            <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: <?php echo $item['color']; ?>; flex-shrink: 0;">
                                                <ion-icon name="<?php echo $item['icon']; ?>"></ion-icon>
                                            </div>
                                            <div style="flex: 1;">
                                                <p style="font-size: 0.85rem; color: white; margin-bottom: 4px; line-height: 1.4;">
                                                    <strong style="color: <?php echo $item['color']; ?>;"><?php echo h($item['type']); ?></strong> recorded for 
                                                    <strong><?php echo h($item['name']); ?></strong>
                                                </p>
                                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                                    <span style="font-size: 0.7rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px;">
                                                        <ion-icon name="business-outline"></ion-icon> <?php echo h($item['parish_name']); ?>
                                                    </span>
                                                    <span style="font-size: 0.65rem; color: rgba(255,255,255,0.3);"><?php echo time_elapsed_string($item['date']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer" style="padding: 1rem; border-top: 1px solid rgba(255,255,255,0.05); text-align: center;">
                            <a href="../sacraments.php" style="font-size: 0.75rem; color: var(--accent); text-decoration: none; font-weight: 700;">View All Sacramental Hub →</a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CENTRALIZED VERIFICATION QUEUE (The Point 1/2 Fulfillment) -->
            <?php if ($can_verify_global && $pending_count > 0): ?>
            <section class="verification-queue-section">
                <div class="queue-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <h3 style="font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 10px;">
                        <ion-icon name="shield-checkmark-outline" style="color: var(--accent);"></ion-icon>
                        Canonical Verification Queue
                    </h3>
                    <a href="sacraments.php" style="font-size: 0.8rem; color: var(--accent); text-decoration: none;">View All Pending →</a>
                </div>
                <div class="card bg-card no-padding overflow-hidden">
                    <table class="data-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: rgba(255,255,255,0.02);">
                                <th style="padding: 1rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Sacrament</th>
                                <th style="padding: 1rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Subject</th>
                                <th style="padding: 1rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Celebration Date</th>
                                <th style="padding: 1rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Parish / Mission</th>
                                <th style="padding: 1rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_queue as $item): ?>
                            <tr>
                                <td style="padding: 1rem;"><span class="status-badge draft"><?php echo $item['type']; ?></span></td>
                                <td style="padding: 1rem;"><strong><?php echo h($item['name']); ?></strong></td>
                                <td style="padding: 1rem; font-size: 0.85rem;"><?php echo date('d M Y', strtotime($item['date'])); ?></td>
                                <td style="padding: 1rem; font-size: 0.85rem; color: var(--text-muted);"><?php echo h($item['parish_name']); ?></td>
                                <td style="padding: 1rem;">
                                    <a href="<?php echo $item['link']; ?>?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm" style="padding: 0.4rem 0.8rem; font-size: 0.7rem; border-radius: 4px;">Review Entry</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <!-- NEW: Diocesan Reporting Vault (Total Accountability) -->
            <?php if ($is_admin): ?>
            <section class="reporting-vault-section" style="margin-top: 2rem; margin-bottom: 3rem;">
                <div class="card" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.9) 100%); border: 1px solid rgba(251, 191, 36, 0.2); border-radius: 2rem; padding: 2.5rem; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: var(--accent); opacity: 0.05; filter: blur(60px); border-radius: 50%;"></div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 3rem; align-items: center;">
                        <div>
                            <h3 style="font-family: 'Outfit'; font-size: 1.5rem; color: white; margin-bottom: 1rem; display: flex; align-items: center; gap: 12px;">
                                <ion-icon name="archive-outline" style="color: var(--accent);"></ion-icon>
                                Diocesan Reporting Vault
                            </h3>
                            <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 2rem;">
                                Monitoring the <b>Annua Statistica OMEGA</b> submissions for the <?php echo $selected_year; ?> canonical cycle. 
                                Total diocesan accountability ensures data integrity for the Bishop's Archive.
                            </p>
                            
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                <div style="font-size: 3rem; font-weight: 900; color: white;"><?php echo $reports_count; ?></div>
                                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">
                                    Reports<br>Archived
                                </div>
                            </div>
                            
                            <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px; margin-bottom: 1rem; overflow: hidden;">
                                <div style="width: <?php echo $reporting_percentage; ?>%; height: 100%; background: var(--accent); box-shadow: 0 0 10px var(--accent);"></div>
                            </div>
                            <span style="font-size: 0.75rem; color: var(--accent); font-weight: 700;"><?php echo round($reporting_percentage, 1); ?>% Participation rate</span>
                        </div>
                        
                        <div style="background: rgba(0,0,0,0.2); padding: 2rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.03);">
                            <h4 style="font-size: 0.8rem; color: white; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 1rem;">
                                Missing Mission Submissions (<?php echo count($missing_reports); ?>+)
                            </h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                <?php foreach ($missing_reports as $mr): ?>
                                <div style="display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,0.5); font-size: 0.8rem; padding: 4px 0;">
                                    <ion-icon name="alert-circle-outline" style="color: #ef4444; font-size: 1rem;"></ion-icon>
                                    <?php echo h($mr['parish_name']); ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($missing_reports) == 0): ?>
                                <div style="text-align: center; padding: 2rem; color: var(--success);">
                                    <ion-icon name="checkmark-done-circle" style="font-size: 3rem; margin-bottom: 1rem;"></ion-icon>
                                    <p>All Parish Missions have successfully archived their <?php echo $selected_year; ?> reports.</p>
                                </div>
                            <?php else: ?>
                                <div style="margin-top: 1.5rem; text-align: right;">
                                    <a href="parish_reports.php" style="color: var(--accent); font-size: 0.75rem; font-weight: 700; text-decoration: none;">View Detailed Audit →</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php include '../includes/privacy_footer.php'; ?>

        </main>
    </div>


    <style>
        .card-faithful { background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%); border-color: rgba(99, 102, 241, 0.4); border-left: 4px solid #6366f1; }
        .card-baptism { background: linear-gradient(135deg, rgba(56, 189, 248, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%); border-color: rgba(56, 189, 248, 0.4); border-left: 4px solid #38bdf8; }
        .card-marriage { background: linear-gradient(135deg, rgba(244, 114, 182, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%); border-color: rgba(244, 114, 182, 0.4); border-left: 4px solid #f472b6; }
        .card-confirmation { background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%); border-color: rgba(251, 191, 36, 0.4); border-left: 4px solid #fbbf24; }
        .card-ordination { background: linear-gradient(135deg, rgba(52, 211, 153, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%); border-color: rgba(52, 211, 153, 0.4); border-left: 4px solid #34d399; }
        .card-death { background: linear-gradient(135deg, rgba(148, 163, 184, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%); border-color: rgba(148, 163, 184, 0.4); border-left: 4px solid #94a3b8; }
        
        .stat-card .stat-icon { background: rgba(255,255,255,0.05) !important; width: 3.5rem; height: 3.5rem; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-card h3 { font-family: 'Outfit', sans-serif; font-size: 1.75rem; margin-bottom: 2px; }
        .stat-card p { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); }
        .stat-card:hover { transform: translateY(-5px); border-color: white !important; box-shadow: 0 15px 30px rgba(0,0,0,0.4); background-opacity: 1; }
    </style>

    <script>
        // 1. Dynamic Greeting & "Talk to the User" logic
        const greetingData = {
            morning: { text: "Good Morning", insight: "A fresh day for the mission, Minister of Sacraments. May your records be clear and your heart be light." },
            afternoon: { text: "Good Afternoon", insight: "The sun is high, and the records are growing. Keep up the good work, Minister of Sacraments." },
            evening: { text: "Good Evening", insight: "Peace be with you, Minister of Sacraments. A perfect time for reflection and final oversight." }
        };

        const updateDynamicText = () => {
            const hour = new Date().getHours();
            let period = 'morning';
            if (hour >= 12 && hour < 17) period = 'afternoon';
            if (hour >= 17) period = 'evening';

            const welcomeHeader = document.querySelector('.content-header h2');
            const welcomeSub = document.querySelector('.content-header p');
            const userFirstName = "<?php echo get_user_greeting_name($user_full_name); ?>";

            if (welcomeHeader) welcomeHeader.innerText = `${greetingData[period].text}, ${userFirstName}`;
            if (welcomeSub) welcomeSub.innerHTML = `<span style="color: var(--accent); font-weight: 700;">Mission Insight:</span> ${greetingData[period].insight}`;
        };

        // 2. Statistics Counter-Up Animation
        const animateCounters = () => {
            const counters = document.querySelectorAll('.stat-card h3, .glance-item span');
            counters.forEach(counter => {
                const target = +counter.innerText.replace(/,/g, '');
                if (isNaN(target)) return;
                
                const speed = 200;
                const updateCount = () => {
                    const count = +counter.innerText.replace(/,/g, '');
                    const inc = target / speed;

                    if (count < target) {
                        counter.innerText = Math.ceil(count + inc).toLocaleString();
                        setTimeout(updateCount, 1);
                    } else {
                        counter.innerText = target.toLocaleString();
                    }
                };
                counter.innerText = '0';
                updateCount();
            });
        };

        // 3. Premium Trajectory Growth Chart
        window.addEventListener('load', () => {
            updateDynamicText();
            animateCounters();
            
            const chartCanvas = document.getElementById('growthChart');
            if (chartCanvas) {
                const chartCtx = chartCanvas.getContext('2d');
                const chartData = <?php echo json_encode($chart_data); ?>;
                
                function getGradient(color, opacity = 0.2) {
                    const gradient = chartCtx.createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, color.replace(')', `, ${opacity})`).replace('rgb', 'rgba'));
                    gradient.addColorStop(1, 'rgba(15, 23, 42, 0)');
                    return gradient;
                }

                new Chart(chartCtx, {
                    type: 'bar',
                    data: {
                        labels: chartData.map(d => new Date(d.month + '-01').toLocaleString('default', { month: 'short', year: '2-digit' })),
                        datasets: [
                            { 
                                label: 'Infants (<1y)', 
                                data: chartData.map(d => d.p_b_infant), 
                                backgroundColor: '#38bdf8',
                                stack: 'parish',
                                borderRadius: 4
                            },
                            { 
                                label: 'Children (1-12y)', 
                                data: chartData.map(d => d.p_b_child), 
                                backgroundColor: '#60a5fa',
                                stack: 'parish',
                                borderRadius: 4
                            },
                            { 
                                label: 'Youth (13-24y)', 
                                data: chartData.map(d => d.p_b_youth), 
                                backgroundColor: '#818cf8',
                                stack: 'parish',
                                borderRadius: 4
                            },
                            { 
                                label: 'Adults (25+y)', 
                                data: chartData.map(d => d.p_b_adult), 
                                backgroundColor: '#a78bfa',
                                stack: 'parish',
                                borderRadius: 4
                            },
                            { 
                                label: 'Parish Marriages', 
                                data: chartData.map(d => d.p_m), 
                                backgroundColor: '#f472b6',
                                stack: 'marriage',
                                borderRadius: 4
                            },
                            { 
                                label: 'Diocesan Avg (Bap)', 
                                data: chartData.map(d => d.d_avg_b), 
                                backgroundColor: 'rgba(56, 189, 248, 0.2)',
                                borderColor: '#38bdf8',
                                borderDash: [5, 5],
                                borderWidth: 1,
                                type: 'line',
                                pointRadius: 2,
                                tension: 0.4
                            },
                            { 
                                label: 'Diocesan Avg (Mar)', 
                                data: chartData.map(d => d.d_avg_m), 
                                backgroundColor: 'rgba(244, 114, 182, 0.2)',
                                borderColor: '#f472b6',
                                borderDash: [5, 5],
                                borderWidth: 1,
                                type: 'line',
                                pointRadius: 2,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: { 
                            legend: { 
                                display: true, 
                                position: 'bottom', 
                                labels: { 
                                    color: '#94a3b8', 
                                    font: { size: 9 },
                                    usePointStyle: true,
                                    padding: 10
                                } 
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        let value = context.parsed.y || 0;
                                        
                                        // Calculate percentage for age groups
                                        if (context.dataset.stack === 'parish') {
                                            const total = chartData[context.dataIndex].p_b_total || 1;
                                            const pct = ((value / total) * 100).toFixed(1);
                                            return `${label}: ${value} (${pct}%)`;
                                        }
                                        
                                        return `${label}: ${value}`;
                                    },
                                    footer: function(tooltipItems) {
                                        let bTotal = 0;
                                        tooltipItems.forEach(item => {
                                            if (item.dataset.stack === 'parish') {
                                                bTotal += item.parsed.y;
                                            }
                                        });
                                        return bTotal > 0 ? `Total Baptisms: ${bTotal}` : '';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { 
                                stacked: true,
                                beginAtZero: true,
                                grid: { color: 'rgba(255, 255, 255, 0.05)' }, 
                                ticks: { color: '#94a3b8', stepSize: 1, precision: 0 } 
                            },
                            x: { 
                                stacked: true,
                                grid: { display: false }, 
                                ticks: { color: '#94a3b8' } 
                            }
                        }
                    }
                });
            }
        });
    </script>
    <style>
        .stat-card { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .stat-card:hover { transform: scale(1.05) translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.5), 0 0 20px rgba(251, 191, 36, 0.1); }
        .activity-item { animation: slideIn 0.5s ease backwards; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }
        
        .pulse-dot {
            display: inline-block;
            width: 8px; height: 8px;
            background: #10b981;
            border-radius: 50%;
            margin-right: 8px;
            box-shadow: 0 0 0 rgba(16, 185, 129, 0.4);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    </style>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
