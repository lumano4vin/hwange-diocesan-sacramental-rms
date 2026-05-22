<?php
require '../includes/db.php';
$res = db_fetchAll('PRAGMA table_info(annual_reports)');
print_r($res);
?>
