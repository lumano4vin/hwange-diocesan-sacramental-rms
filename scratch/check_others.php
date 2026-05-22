<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT b.*, pr.parish_name FROM baptisms b JOIN parishes pr ON b.parish_id = pr.parish_id WHERE b.parish_id != 22');
echo "Records outside Parish 22: " . count($res) . "\n";
foreach($res as $r) {
    echo "ID: {$r['baptism_id']} | Parish: {$r['parish_name']} | Date: {$r['date_of_baptism']}\n";
}
?>
