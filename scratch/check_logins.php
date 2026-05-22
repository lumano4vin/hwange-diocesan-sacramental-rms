<?php
require '../includes/db.php';
$res = db_fetchAll('SELECT user_id, username, last_login FROM users WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT 20');
foreach($res as $r) {
    echo "User: {$r['username']} | Last Login: {$r['last_login']}\n";
}
?>
