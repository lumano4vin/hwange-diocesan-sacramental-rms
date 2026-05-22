<?php
require '../includes/db.php';
$res = db_fetch('SELECT COUNT(*) as c FROM baptisms WHERE parish_id IS NULL OR parish_id = 0');
print_r($res);
?>
