<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT parish_id, report_year FROM annual_reports');
foreach($res as $r) {
    echo "Parish: {$r['parish_id']} | Year: {$r['report_year']}\n";
}
?>
