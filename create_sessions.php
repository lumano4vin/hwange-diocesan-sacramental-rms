<?php
require_once __DIR__ . '/includes/db.php';
try {
    getDB()->exec("CREATE TABLE IF NOT EXISTS sessions (
        session_id VARCHAR(128) PRIMARY KEY,
        session_data TEXT,
        expires_at INTEGER
    )");
    echo "Sessions table created successfully.\n";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
