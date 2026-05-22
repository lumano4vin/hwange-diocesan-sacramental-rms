<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT b.baptism_id, b.parish_id, b.status, pr.parish_name FROM baptisms b JOIN parishes pr ON b.parish_id = pr.parish_id');
echo "Count: " . count($res) . "\n";
foreach($res as $r) {
    echo "ID: {$r['baptism_id']} | Parish: {$r['parish_name']} (ID: {$r['parish_id']}) | Status: {$r['status']}\n";
}
?>
