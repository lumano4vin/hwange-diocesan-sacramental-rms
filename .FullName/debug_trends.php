<?php
require 'includes/db.php';
$trends = db_fetchAll("
    SELECT strftime('%Y-%m', event_date) as month, type, COUNT(*) as c 
    FROM (SELECT 'baptisms' as type, date_of_baptism as event_date FROM baptisms) t 
    GROUP BY month, type
");
print_r($trends);
echo "All Baptisms:\n";
$bap = db_fetchAll("SELECT baptism_id, date_of_baptism, created_at FROM baptisms");
print_r($bap);
?>
