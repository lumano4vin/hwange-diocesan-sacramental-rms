<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT user_id, username, role, parish_id FROM users WHERE parish_id IS NULL');
foreach($res as $r) {
    echo "ID: {$r['user_id']} | User: {$r['username']} | Role: {$r['role']} | Parish: NULL\n";
}
?>
