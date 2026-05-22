<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

try {
    $pdo->exec("DELETE FROM parish_assignments");
    echo "Successfully cleared all clergy assignments.";
} catch (Exception $e) {
    echo "Error clearing assignments: " . $e->getMessage();
}
