<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Schema Patch: Fix confirmations table column mismatch
 */

require_once '../includes/db.php';

$patches = [
    "ALTER TABLE confirmations ADD COLUMN register_book_number VARCHAR(50)",
    "ALTER TABLE confirmations ADD COLUMN page_number VARCHAR(50)",
    "ALTER TABLE confirmations ADD COLUMN entry_number VARCHAR(50)",
];

foreach ($patches as $sql) {
    try {
        $pdo->exec($sql);
        echo "SUCCESS: $sql\n";
    } catch (Exception $e) {
        // Column likely already exists
        echo "SKIP (already exists): " . $e->getMessage() . "\n";
    }
}

// Verify all columns now present
echo "\n=== confirmations table (after patch) ===\n";
$cols = db_fetchAll("PRAGMA table_info(confirmations)");
foreach ($cols as $col) {
    echo "  {$col['name']} ({$col['type']})\n";
}
?>
