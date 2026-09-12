<?php
require_once 'includes/db.php';

try {
    // Add reset_token
    db_query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL");
    // Add reset_token_expiry
    db_query("ALTER TABLE users ADD COLUMN reset_token_expiry DATETIME NULL");
    
    echo "Migration successful: reset_token and reset_token_expiry added to users table.";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        echo "Columns already exist. Skipping migration.";
    } else {
        echo "Migration failed: " . $e->getMessage();
    }
}
?>
