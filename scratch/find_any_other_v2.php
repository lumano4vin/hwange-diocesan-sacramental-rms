<?php
require '../includes/db.php';
$tables = [
    'baptisms' => 'parish_id',
    'marriages' => 'parish_id',
    'confirmations' => 'parish_id',
    'deaths' => 'parish_id',
    'parishioners' => 'current_parish_id',
    'receptions' => 'parish_id',
    'ordination_professions' => 'parish_id'
];
foreach($tables as $t => $col) {
    try {
        $res = db_fetchAll("SELECT * FROM $t WHERE $col != 22");
        if (count($res) > 0) {
            echo "Table $t has " . count($res) . " records outside Parish 22!\n";
            foreach($res as $r) {
                echo "  ID: " . ($r[0] ?? '??') . " | Parish: {$r[$col]}\n";
            }
        }
    } catch (Exception $e) {
        echo "Table $t error: " . $e->getMessage() . "\n";
    }
}
?>
