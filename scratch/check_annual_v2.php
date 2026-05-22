<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT parish_id, report_year, submitted_at FROM annual_reports');
echo "Annual Reports count: " . count($res) . "\n";
foreach($res as $r) {
    echo "Parish: {$r['parish_id']} | Year: {$r['report_year']} | Submitted: {$r['submitted_at']}\n";
}
?>
