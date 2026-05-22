<?php
require_once 'includes/db.php';
$u = getDB()->query("SELECT user_id, username, full_name FROM users WHERE username IN ('Holy Family', 'Sacred Heart')")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($u, JSON_PRETTY_PRINT);
