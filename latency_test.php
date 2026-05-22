<?php
$start = microtime(true);
require_once 'includes/db.php';
$db_connect = microtime(true);

$stmt = $pdo->query("SELECT 1");
$stmt->fetch();
$first_query = microtime(true);

$stmt = $pdo->query("SELECT COUNT(*) FROM parishes");
$stmt->fetch();
$second_query = microtime(true);

echo "DB Connect: " . ($db_connect - $start) . "s\n";
echo "Query 1: " . ($first_query - $db_connect) . "s\n";
echo "Query 2: " . ($second_query - $first_query) . "s\n";
echo "Total: " . ($second_query - $start) . "s\n";
?>
