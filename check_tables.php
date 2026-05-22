<?php
$db = new PDO('sqlite:database.sqlite');
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
echo implode(", ", $tables);
?>
