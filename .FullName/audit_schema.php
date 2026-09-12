<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Full Schema Audit: Check all tables for missing registry columns
 */

require_once '../includes/db.php';

$tables = [
    'baptisms'               => ['register_book_number', 'page_number', 'entry_number'],
    'marriages'              => ['register_book_number', 'page_number', 'entry_number'],
    'confirmations'          => ['register_book_number', 'page_number', 'entry_number'],
    'ordinations_professions'=> ['register_book_number', 'page_number', 'entry_number'],
    'deaths'                 => ['register_book_number', 'page_number', 'entry_number'],
];

$fixes_needed = [];

foreach ($tables as $table => $required_cols) {
    $existing = array_column(db_fetchAll("PRAGMA table_info($table)"), 'name');
    echo "=== $table ===\n";
    echo "  Columns: " . implode(', ', $existing) . "\n";
    foreach ($required_cols as $col) {
        if (!in_array($col, $existing)) {
            echo "  *** MISSING: $col\n";
            $fixes_needed[] = "ALTER TABLE $table ADD COLUMN $col VARCHAR(50)";
        }
    }
    echo "\n";
}

// Auto-apply any fixes
if (!empty($fixes_needed)) {
    echo "=== Applying " . count($fixes_needed) . " fixes ===\n";
    foreach ($fixes_needed as $sql) {
        try {
            $pdo->exec($sql);
            echo "FIXED: $sql\n";
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "All tables are correctly structured. No fixes needed.\n";
}
?>
