<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT p.* FROM parishioners p LEFT JOIN baptisms b ON p.person_id = b.person_id WHERE b.baptism_id IS NULL');
echo "Parishioners without baptism record: " . count($res) . "\n";
foreach($res as $r) {
    echo "ID: {$r['person_id']} | Name: {$r['first_name']} {$r['last_name']} | Parish: {$r['current_parish_id']} | Created: {$r['created_at']}\n";
}
?>
