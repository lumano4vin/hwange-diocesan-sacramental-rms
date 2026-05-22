<?php
require_once 'includes/db.php';
$u = getDB()->query("SELECT * FROM users WHERE username = 'stanislaus_lumano'")->fetch(PDO::FETCH_ASSOC);
var_dump($u);
