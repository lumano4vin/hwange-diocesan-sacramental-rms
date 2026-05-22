<?php
require 'includes/db.php';
$bap_2026 = db_fetch("SELECT COUNT(*) as count FROM baptisms WHERE date_of_baptism LIKE '2026%'");
echo "Baptisms (2026): " . ($bap_2026['count'] ?? 0) . "\n";
?>
