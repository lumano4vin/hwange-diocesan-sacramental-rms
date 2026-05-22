<?php
require_once 'includes/db.php';
$u = getDB()->query("SELECT user_id, username, full_name, role, email FROM users")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($u, JSON_PRETTY_PRINT);
