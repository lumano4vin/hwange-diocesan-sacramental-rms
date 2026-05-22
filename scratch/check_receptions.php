<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT * FROM receptions');
echo "Receptions count: " . count($res) . "\n";
foreach($res as $r) {
    echo "ID: {$r['reception_id']} | Parish: {$r['parish_id']} | Date: {$r['date_of_reception']}\n";
}
?>
