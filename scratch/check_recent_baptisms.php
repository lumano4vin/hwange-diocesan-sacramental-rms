<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT baptism_id, person_id, parish_id, status FROM baptisms ORDER BY baptism_id DESC LIMIT 50');
foreach($res as $r) {
    echo "ID: {$r['baptism_id']} | Person: {$r['person_id']} | Parish ID: {$r['parish_id']} | Status: {$r['status']}\n";
}
?>
