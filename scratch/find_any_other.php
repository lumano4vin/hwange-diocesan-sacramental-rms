<?php
require '../includes/db.php';
$tables = ['baptisms', 'marriages', 'confirmations', 'deaths', 'parishioners', 'receptions', 'ordination_professions'];
foreach($tables as $t) {
    try {
        $res = db_fetchAll("SELECT * FROM $t WHERE parish_id != 22 OR current_parish_id != 22");
        if (count($res) > 0) {
            echo "Table $t has " . count($res) . " records outside Parish 22!\n";
            foreach($res as $r) {
                $pid = $r['parish_id'] ?? $r['current_parish_id'] ?? '??';
                echo "  ID: " . ($r['baptism_id'] ?? $r['person_id'] ?? '??') . " | Parish: $pid\n";
            }
        }
    } catch (Exception $e) {}
}
?>
