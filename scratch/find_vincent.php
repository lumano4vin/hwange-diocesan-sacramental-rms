<?php
require_once 'includes/db.php';
$v = getDB()->query("SELECT * FROM users WHERE full_name LIKE '%Vincent%' OR username LIKE '%vincent%'")->fetch();
echo json_encode($v, JSON_PRETTY_PRINT);
