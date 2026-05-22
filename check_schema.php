<?php
require_once 'includes/db.php';
$cols = db_fetchAll("PRAGMA table_info(audit_logs)");
print_r($cols);
?>
