<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT person_id, first_name, last_name, current_parish_id, created_at FROM parishioners ORDER BY person_id DESC LIMIT 20');
foreach($res as $r) {
    echo "ID: {$r['person_id']} | Name: {$r['first_name']} {$r['last_name']} | Parish: {$r['current_parish_id']} | Created: {$r['created_at']}\n";
}
?>
