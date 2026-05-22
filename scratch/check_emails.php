<?php
require_once 'includes/db.php';
$u = getDB()->query("SELECT email FROM users WHERE email IS NOT NULL AND email NOT LIKE '%@hwangediocese.org%'")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($u, JSON_PRETTY_PRINT);
