<?php
$sqlite_pdo = new PDO("sqlite:database.sqlite");
$sqlite_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$queries = [
    'parishioners_gender' => "SELECT DISTINCT gender FROM parishioners",
    'parishioners_status' => "SELECT DISTINCT status FROM parishioners",
    'baptisms_status' => "SELECT DISTINCT status FROM baptisms",
    'sacraments_notations_type' => "SELECT DISTINCT sacrament_type FROM sacraments_notations",
    'ordinations_professions_type' => "SELECT DISTINCT record_type FROM ordinations_professions"
];

foreach ($queries as $name => $sql) {
    echo "=== $name ===\n";
    try {
        $stmt = $sqlite_pdo->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            echo "  " . current($row) . "\n";
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}
