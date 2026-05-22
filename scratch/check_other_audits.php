<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT a.*, u.username FROM audit_logs a JOIN users u ON a.user_id = u.user_id WHERE u.username != "vincent_lumano" ORDER BY a.created_at DESC LIMIT 20');
foreach($res as $r) {
    echo "{$r['created_at']} | User: {$r['username']} | Action: {$r['action_type']} | Table: {$r['table_name']}\n";
}
?>
