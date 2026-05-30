<?php
/**
 * Schema Checker
 */
$db = new SQLite3('database.sqlite');
$res = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='prenuptial_investigations'");
$row = $res->fetchArray(SQLITE3_ASSOC);
echo "--- prenuptial_investigations Schema ---\n";
echo $row['sql'] . "\n\n";

$res2 = $db->query("PRAGMA table_info(prenuptial_investigations)");
echo "--- Columns ---\n";
while ($col = $res2->fetchArray(SQLITE3_ASSOC)) {
    echo "{$col['name']} ({$col['type']})\n";
}
