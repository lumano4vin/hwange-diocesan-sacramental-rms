<?php
require_once 'includes/db.php';
$u = getDB()->query("SELECT username FROM users WHERE username LIKE '% %'")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($u, JSON_PRETTY_PRINT);
