<?php
require '../includes/db.php';
$res = db_fetch('SELECT * FROM users WHERE username = ?', ['vincent_lumano']);
print_r($res);
?>
