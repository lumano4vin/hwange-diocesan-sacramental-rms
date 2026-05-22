<?php
require '../includes/db.php';
$res = db_fetch('SELECT * FROM users WHERE username = ?', ['stanislaus_lumano']);
print_r($res);
?>
