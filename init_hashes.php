<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Initialize Verification Hashes for existing records
 */

require_once 'includes/db.php';

try {
    // 1. Alter Table
    db_query("ALTER TABLE baptisms ADD COLUMN verification_hash VARCHAR(64)");
    echo "SUCCESS: Column 'verification_hash' added to baptisms table.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "INFO: 'verification_hash' column already exists.\n";
    } else {
        echo "ERROR (Alter): " . $e->getMessage() . "\n";
    }
}

try {
    // 2. Fetch all records without a hash
    $records = db_fetchAll("SELECT baptism_id FROM baptisms WHERE verification_hash IS NULL OR verification_hash = ''");
    
    if (empty($records)) {
        echo "INFO: All records already have verification hashes.\n";
        exit;
    }

    echo "Processing " . count($records) . " records...\n";

    foreach ($records as $row) {
        $hash = bin2hex(random_bytes(16)); // Secure 32-char hex string
        db_query("UPDATE baptisms SET verification_hash = ? WHERE baptism_id = ?", [$hash, $row['baptism_id']]);
    }

    echo "SUCCESS: All baptismal records initialized with secure hashes.\n";
} catch (Exception $e) {
    echo "ERROR (Update): " . $e->getMessage() . "\n";
}
