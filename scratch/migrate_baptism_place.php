<?php
require_once 'includes/db.php';

$pdo = getDB();

try {
    // 1. Add place_of_baptism to parishioners table
    $pdo->exec("ALTER TABLE parishioners ADD COLUMN place_of_baptism VARCHAR(255)");
    echo "Added place_of_baptism to parishioners table.\n";
} catch (Exception $e) {
    echo "Error adding to parishioners: " . $e->getMessage() . "\n";
}

try {
    // 2. Add place_of_baptism to baptisms table
    $pdo->exec("ALTER TABLE baptisms ADD COLUMN place_of_baptism VARCHAR(255)");
    echo "Added place_of_baptism to baptisms table.\n";
} catch (Exception $e) {
    echo "Error adding to baptisms: " . $e->getMessage() . "\n";
}
