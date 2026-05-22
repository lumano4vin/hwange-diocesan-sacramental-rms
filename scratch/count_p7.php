<?php
require '../includes/db.php';
$res = db_fetch('SELECT COUNT(*) as count FROM parishioners WHERE current_parish_id = 7');
print_r($res);
?>
