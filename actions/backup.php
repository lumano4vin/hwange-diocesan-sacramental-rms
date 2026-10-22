<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Database Backup Action
 * 
 * Provides a one-click download of the complete SQLite database archive.
 * Available to Admins, Chancellors, and Parish Priests for offline data stewardship.
 */

ob_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();
$user_role = strtolower($_SESSION['role'] ?? '');

// Allow admins, chancellors, and priests to download their mission/diocesan backup
if (!in_array($user_role, ['admin', 'priest', 'mission_admin', 'chancellor'])) {
    set_flash("Unauthorized to perform database backups.", "error");
    redirect('../dashboard/settings.php');
}

// 1. Resolve the active database file (supports local Windows, Vercel /tmp, and fallback)
$db_file = $GLOBALS['rms_sqlite_path'] ?? '';
if (empty($db_file) || !file_exists($db_file) || filesize($db_file) === 0) {
    $db_file = dirname(__DIR__) . '/database.sqlite';
}
if (!file_exists($db_file) || filesize($db_file) === 0) {
    $db_file = dirname(__DIR__) . '/data/fallback.sqlite';
}

if (file_exists($db_file) && filesize($db_file) > 0) {
    // 2. Checkpoint SQLite WAL mode if connected
    try {
        $pdo = getDB();
        if ($pdo && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $pdo->query("PRAGMA wal_checkpoint(PASSIVE)");
        }
    } catch (Exception $e) {}

    // 3. Audit log
    if (function_exists('log_audit')) {
        log_audit($_SESSION['user_id'], 'BACKUP', 'database', 0, "Full system backup downloaded by " . ($_SESSION['full_name'] ?? 'Cleric'));
    }

    // 4. Clear all buffers to ensure binary integrity
    while (ob_get_level()) {
        if (!@ob_end_clean()) break;
    }

    $parish_slug = '';
    if (!empty($_SESSION['parish_id'])) {
        $p = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$_SESSION['parish_id']]);
        if (!empty($p['parish_name'])) {
            $parish_slug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $p['parish_name']) . '_';
        }
    }
    $filename = 'Hwange_RMS_Backup_' . $parish_slug . date('Y-m-d_Hi') . '.sqlite';

    // 5. Send file download headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.sqlite3');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($db_file));

    readfile($db_file);
    exit;
} else {
    set_flash("Database archive could not be located.", "error");
    redirect('../dashboard/settings.php');
}
