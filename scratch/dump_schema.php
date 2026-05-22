<?php
require_once 'includes/db.php';
$tables = db_fetchAll("SELECT name FROM sqlite_master WHERE type='table'");
foreach ($tables as $t) {
    $name = $t['name'];
    $schema = db_fetch("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$name]);
    echo "--- TABLE: $name ---\n";
    echo $schema['sql'] . "\n\n";
}
