<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Database Backup Action
 * 
 * Provides a one-click download of the complete SQLite database.
 */

ob_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page - ADMINS & CHANCELLORS can backup
require_login();
if (!is_admin()) {
    set_flash("Unauthorized to perform database backups.", "error");
    redirect('../dashboard/settings.php');
}

$db_file = __DIR__ . '/../database.sqlite';

if (file_exists($db_file)) {
    // 1. Audit the backup action
    log_audit($_SESSION['user_id'], 'BACKUP', 'database', 0, "Full system backup executed.");

    // 2. Clear ALL buffers to ensure binary integrity
    while (ob_get_level()) {
        ob_end_clean();
    }

    // 3. Set standard download headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/x-sqlite3'); // Specific for SQLite
    $filename = 'Hwange_RMS_Backup_' . date('Y-m-d_Hi') . '.sqlite';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($db_file));
    
    // 4. Output file
    readfile($db_file);
    exit;
} else {
    set_flash("Critical Error: Database file not found at " . basename($db_file), "error");
    redirect('../dashboard/settings.php');
}
