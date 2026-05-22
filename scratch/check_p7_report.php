<?php
require '../includes/db.php';
$res = db_fetch('SELECT * FROM annual_reports WHERE parish_id = 7');
if ($res) {
    echo "Report found for Parish 7!\n";
    $data = json_decode($res['report_data'], true);
    echo "Baptisms Infant: " . (($data['h_baptism_infant_male'] ?? 0) + ($data['h_baptism_infant_female'] ?? 0)) . "\n";
} else {
    echo "No report for Parish 7\n";
}
?>
