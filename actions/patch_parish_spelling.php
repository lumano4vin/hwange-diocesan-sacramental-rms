<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Quick Data Patch - Correct Parish Spelling
 */

require_once '../includes/db.php';

try {
    $rows = db_query(
        "UPDATE parishes SET parish_name = 'St. Francis of Assisi' WHERE parish_name = 'St. Francis of Assis'"
    );
    echo "SUCCESS: Parish name corrected in the live database.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
