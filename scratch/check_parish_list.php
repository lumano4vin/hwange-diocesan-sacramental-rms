<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT * FROM parishes');
foreach($res as $r) {
    echo "ID: {$r['parish_id']} | Name: {$r['parish_name']}\n";
}
?>
