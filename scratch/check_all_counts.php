<?php
require '../includes/db.php';
$tables = ['baptisms', 'marriages', 'confirmations', 'deaths', 'parishioners', 'ordination_professions'];
foreach($tables as $t) {
    try {
        $count = db_fetch("SELECT COUNT(*) as c FROM $t")['c'];
        echo "$t: $count\n";
        if ($count > 0) {
            $other_parish = db_fetch("SELECT COUNT(*) as c FROM $t WHERE parish_id != 22")['c'];
            echo "  -> Outside Parish 22: $other_parish\n";
        }
    } catch (Exception $e) {
        echo "$t: (Table missing or error)\n";
    }
}
?>
