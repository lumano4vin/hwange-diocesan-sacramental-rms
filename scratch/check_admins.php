<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT user_id, username, role FROM users WHERE role IN ("admin", "chancellor")');
foreach($res as $r) {
    echo "ID: {$r['user_id']} | User: {$r['username']} | Role: {$r['role']}\n";
}
?>
