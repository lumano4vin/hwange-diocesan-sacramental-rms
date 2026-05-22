<?php
$sqlite_pdo = new PDO("sqlite:database.sqlite");
$sqlite_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $sqlite_pdo->query("SELECT name, sql FROM sqlite_master WHERE type='table'");
$out = "";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $out .= "=== Table: " . $row['name'] . " ===\n";
    $out .= $row['sql'] . "\n\n";
}
file_put_contents("scratch/sqlite_schemas.txt", $out);
echo "Written to scratch/sqlite_schemas.txt\n";
