<?php
require '../includes/db.php';
try {
    $res = db_fetchAll('SELECT parish_id, report_year, created_at FROM annual_reports');
    echo "Annual Reports: " . count($res) . "\n";
    foreach($res as $r) {
        echo "Parish: {$r['parish_id']} | Year: {$r['report_year']} | Created: {$r['created_at']}\n";
    }
} catch (Exception $e) {
    echo "Table annual_reports missing or error: " . $e->getMessage() . "\n";
}
?>
