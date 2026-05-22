<?php
require_once 'includes/db.php';
$cols = $pdo->query("PRAGMA table_info(receptions)")->fetchAll();
print_r($cols);
?>
