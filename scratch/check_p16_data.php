<?php
require '../includes/db.php';
$res = db_fetch('SELECT report_data FROM annual_reports WHERE parish_id = 16 AND report_year = 2026');
if ($res) {
    $data = json_decode($res['report_data'], true);
    echo "Baptisms Adult Male: " . ($data['h_baptism_adult_male'] ?? 0) . "\n";
    echo "Baptisms Adult Female: " . ($data['h_baptism_adult_female'] ?? 0) . "\n";
    echo "Baptisms Infant Male: " . ($data['h_baptism_infant_male'] ?? 0) . "\n";
    echo "Baptisms Infant Female: " . ($data['h_baptism_infant_female'] ?? 0) . "\n";
} else {
    echo "No report found for Parish 16 / 2026\n";
}
?>
