<?php
require_once 'includes/db.php';
$cols = $pdo->query("PRAGMA table_info(deaths)")->fetchAll();
print_r($cols);
?>
