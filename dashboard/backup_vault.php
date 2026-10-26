<?php
/**
 * Hwange Diocese Records Management System
 * Data Vault - Backups & CSV Export
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Clergy & Administrators can access the data vault
require_login();
$user_role = strtolower($_SESSION['role'] ?? '');
if (!in_array($user_role, ['admin', 'priest', 'mission_admin', 'chancellor', 'bookkeeper'])) {
    die("Access denied. Authorized clergy and administrators only.");
}

$db_file = $GLOBALS['rms_sqlite_path'] ?? '';
if (empty($db_file) || !file_exists($db_file)) {
    $db_file = dirname(__DIR__) . '/database.sqlite';
}
if (!file_exists($db_file)) {
    $db_file = dirname(__DIR__) . '/data/fallback.sqlite';
}

$db_size = file_exists($db_file) ? filesize($db_file) : 0;
$db_size_mb = number_format($db_size / 1048576, 2);
$db_last_modified = file_exists($db_file) ? date('d M Y H:i:s', filemtime($db_file)) : 'N/A';

// Count records for display
$counts = [
    'Baptisms' => db_fetch("SELECT COUNT(*) as c FROM baptisms")['c'] ?? 0,
    'Confirmations' => db_fetch("SELECT COUNT(*) as c FROM confirmations")['c'] ?? 0,
    'Marriages' => db_fetch("SELECT COUNT(*) as c FROM marriages")['c'] ?? 0,
    'Parishioners' => db_fetch("SELECT COUNT(*) as c FROM parishioners")['c'] ?? 0,
    'Daily Ledger' => db_fetch("SELECT COUNT(*) as c FROM parish_daily_ledger")['c'] ?? 0,
    'Cashbook Returns' => db_fetch("SELECT COUNT(*) as c FROM parish_financial_submissions")['c'] ?? 0,
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Vault - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .vault-card { background: var(--card-bg); padding: 2rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); text-align: center; }
        .vault-icon { font-size: 4rem; color: #38bdf8; margin-bottom: 1rem; }
        .stat-badge { background: rgba(56, 189, 248, 0.1); color: #38bdf8; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 700; display: inline-block; margin-bottom: 1rem; }
        .export-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-top: 2rem; }
        .export-btn { background: #334155; color: white; border: 1px solid #475569; padding: 1rem; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; transition: all 0.2s; }
        .export-btn:hover { background: #475569; border-color: #38bdf8; transform: translateY(-2px); }
        .export-btn ion-icon { font-size: 1.5rem; color: #38bdf8; }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <?php 
                $header_title = "Data Vault";
                $header_subtitle = "Secure backups and data exportation for Diocesan Archives.";
                include '../includes/header.php'; 
            ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                
                <!-- Full Database Backup -->
                <div class="vault-card">
                    <ion-icon name="server-outline" class="vault-icon"></ion-icon>
                    <h2 style="font-family: 'Outfit'; margin: 0 0 0.5rem 0;">System Database Backup</h2>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Download a complete encrypted snapshot of the entire Sacramental RMS SQLite database. Store this file securely.</p>
                    
                    <div class="stat-badge">Size: <?php echo $db_size_mb; ?> MB</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">Last Modified: <?php echo $db_last_modified; ?></div>
                    
                    <a href="../database.sqlite" download="ZCRE_Hwange_Backup_<?php echo date('Y-m-d'); ?>.sqlite" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem; width: 100%; display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
                        <ion-icon name="cloud-download-outline"></ion-icon> Download Full SQLite Backup
                    </a>
                </div>

                <!-- CSV Exports -->
                <div class="vault-card" style="text-align: left;">
                    <h2 style="font-family: 'Outfit'; margin: 0 0 0.5rem 0; text-align: center;">Export Registers (CSV)</h2>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem; text-align: center;">Download individual sacramental registers in Excel/CSV format for reporting and analysis.</p>

                    <div class="export-grid">
                        <a href="../actions/export_vault_csv.php?table=baptisms" class="export-btn">
                            <ion-icon name="water-outline"></ion-icon>
                            Baptisms (<?php echo $counts['Baptisms']; ?>)
                        </a>
                        <a href="../actions/export_vault_csv.php?table=marriages" class="export-btn">
                            <ion-icon name="heart-outline"></ion-icon>
                            Marriages (<?php echo $counts['Marriages']; ?>)
                        </a>
                        <a href="../actions/export_vault_csv.php?table=confirmations" class="export-btn">
                            <ion-icon name="flame-outline"></ion-icon>
                            Confirmations (<?php echo $counts['Confirmations']; ?>)
                        </a>
                        <a href="../actions/export_vault_csv.php?table=parishioners" class="export-btn">
                            <ion-icon name="people-outline"></ion-icon>
                            Parishioners (<?php echo $counts['Parishioners']; ?>)
                        </a>
                        <a href="../actions/export_vault_csv.php?table=parish_daily_ledger" class="export-btn" style="border-color: rgba(56, 189, 248, 0.4);">
                            <ion-icon name="journal-outline" style="color: #38bdf8;"></ion-icon>
                            Daily Ledger (<?php echo $counts['Daily Ledger']; ?>)
                        </a>
                        <a href="../actions/export_vault_csv.php?table=parish_financial_submissions" class="export-btn" style="border-color: rgba(251, 191, 36, 0.4);">
                            <ion-icon name="cash-outline" style="color: var(--accent);"></ion-icon>
                            Monthly Returns (<?php echo $counts['Cashbook Returns']; ?>)
                        </a>
                    </div>
                </div>

            </div>

            <!-- Cloud Vault Cross-Deployment Persistence -->
            <div class="vault-card" style="margin-top: 2rem; text-align: left; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(56, 189, 248, 0.3);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 style="font-family: 'Outfit'; margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 10px;">
                            <ion-icon name="cloud-done-outline" style="color: #38bdf8;"></ion-icon> Cloud Vault Persistence
                        </h2>
                        <p style="color: var(--text-muted); margin: 0; max-width: 600px;">
                            Guarantees all records and changes survive serverless redeployments on Vercel by syncing database snapshots with the encrypted GitHub Cloud Vault.
                        </p>
                    </div>
                    <div>
                        <a href="../actions/check_cloud_vault.php" class="btn btn-primary" style="padding: 0.8rem 1.5rem; display: inline-flex; align-items: center; gap: 8px;">
                            <ion-icon name="shield-checkmark-outline"></ion-icon> Open Cloud Vault Status & Setup
                        </a>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
