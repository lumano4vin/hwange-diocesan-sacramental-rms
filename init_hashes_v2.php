<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Universal Verification Hash Initialization (v2)
 * 
 * Extends security hashes to Marriages and Confirmations.
 */

require_once 'includes/db.php';

function add_column_safe($table, $column, $type) {
    try {
        db_query("ALTER TABLE $table ADD COLUMN $column $type");
        echo "SUCCESS: Column '$column' added to $table table.\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "INFO: '$column' column already exists in $table.\n";
        } else {
            echo "ERROR (Alter $table): " . $e->getMessage() . "\n";
        }
    }
}

function init_hashes_for_table($table, $pk, $hash_col = 'verification_hash') {
    try {
        $records = db_fetchAll("SELECT $pk FROM $table WHERE $hash_col IS NULL OR $hash_col = ''");
        
        if (empty($records)) {
            echo "INFO: Table $table already has all verification hashes.\n";
            return;
        }

        echo "Processing " . count($records) . " records for $table...\n";

        foreach ($records as $row) {
            $hash = bin2hex(random_bytes(16));
            db_query("UPDATE $table SET $hash_col = ? WHERE $pk = ?", [$hash, $row[$pk]]);
        }

        echo "SUCCESS: $table table initialized with secure hashes.\n";
    } catch (Exception $e) {
        echo "ERROR (Update $table): " . $e->getMessage() . "\n";
    }
}

// 1. Add Columns
add_column_safe('marriages', 'verification_hash', 'VARCHAR(64)');
add_column_safe('confirmations', 'verification_hash', 'VARCHAR(64)');

// 2. Initialize Hashes
init_hashes_for_table('baptisms', 'baptism_id');
init_hashes_for_table('marriages', 'marriage_id');
init_hashes_for_table('confirmations', 'confirmation_id');

echo "\nUniversal Verification Hash Initialization Complete.\n";
?>
