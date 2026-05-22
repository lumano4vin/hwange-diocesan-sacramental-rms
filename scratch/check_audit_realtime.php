<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT a.*, u.username FROM audit_logs a JOIN users u ON a.user_id = u.user_id ORDER BY a.created_at DESC LIMIT 10');
foreach($res as $r) {
    echo "{$r['created_at']} | {$r['username']} | {$r['action_type']} | {$r['table_name']} | {$r['details']}\n";
}
?>
