<?php
/**
 * Migration: Create annual_reports table
 */
require_once 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS annual_reports (
    report_id INTEGER PRIMARY KEY AUTOINCREMENT,
    parish_id INTEGER NOT NULL,
    report_year INTEGER NOT NULL,
    report_data TEXT NOT NULL, -- JSON formatted metrics
    submitted_by INTEGER,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'Final',
    UNIQUE(parish_id, report_year)
)";

try {
    $pdo = getDB();
    $pdo->exec($sql);
    echo "Migration Successful: annual_reports table created.\n";
} catch (Exception $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
?>
