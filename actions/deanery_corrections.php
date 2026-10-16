<?php
/**
 * Canonical Corrections Script
 */
require_once 'includes/db.php';

try {
    $pdo = new PDO('sqlite:database.sqlite');

    echo "===========================================\n";
    echo " CANONICAL DEANERY CORRECTIONS\n";
    echo "===========================================\n\n";

    // 1. St. Michael -> Makwa
    $pdo->exec("UPDATE parishes SET deanery = 'Makwa' WHERE parish_name LIKE '%St. Michael%'");
    echo "[SUCCESS] Moved St. Michael to Makwa Deanery.\n";

    // 2. Divine Mercy -> Dete
    $pdo->exec("UPDATE parishes SET deanery = 'Dete' WHERE parish_name LIKE '%Divine Mercy%'");
    echo "[SUCCESS] Moved Divine Mercy to Dete Deanery.\n";

    // 3. St. Teresa (Hwange) -> Empumalanga (keep St. Therese, Kamativi in Dete)
    $pdo->exec("UPDATE parishes SET deanery = 'Empumalanga' WHERE parish_name = 'St. Teresa'");
    echo "[SUCCESS] Moved St. Teresa to Empumalanga Deanery.\n";

    // 4. Mary Help of Christians -> Empumalanga
    $pdo->exec("UPDATE parishes SET deanery = 'Empumalanga' WHERE parish_name LIKE '%Mary Help of Christians%'");
    echo "[SUCCESS] Moved Mary Help of Christians to Empumalanga Deanery.\n";

    echo "\n===========================================\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
