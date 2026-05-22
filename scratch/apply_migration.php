<?php
require_once '../includes/db.php';
$sql = file_get_contents('../migrations/handovers_migration.sql');
try {
    $pdo->exec($sql);
    echo "Migration successful: parish_handovers table created.";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
