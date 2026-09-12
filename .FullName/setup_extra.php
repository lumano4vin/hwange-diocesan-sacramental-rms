<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Extended Features Setup (Audit Logs)
 */

require_once '../includes/db.php';

try {
    // 1. Create Audit Logs Table
    $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
        log_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        action_type TEXT NOT NULL,
        table_name TEXT NOT NULL,
        record_id INTEGER NOT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "SUCCESS: Audit Logs table created.\n";

    // 2. Add verification_hash to all tables if not already present (Safety Check)
    $tables = ['baptisms', 'marriages', 'confirmations', 'ordinations_professions', 'deaths'];
    foreach ($tables as $table) {
        try {
            $pdo->exec("ALTER TABLE $table ADD COLUMN verification_hash TEXT UNIQUE");
            echo "INFO: Added verification_hash to $table.\n";
        } catch (Exception $e) {
            // Likely already exists
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
