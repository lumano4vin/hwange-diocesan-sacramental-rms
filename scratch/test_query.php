<?php
require_once 'includes/db.php';
try {
    $pdo = getDB();
    $res = $pdo->query("SELECT * FROM baptisms LIMIT 1")->fetch();
    echo "Baptisms columns:\n";
    print_r(array_keys($res));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
try {
    $res = $pdo->query("SELECT * FROM marriages LIMIT 1")->fetch();
    echo "Marriages columns:\n";
    print_r(array_keys($res));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
try {
    $res = $pdo->query("SELECT * FROM confirmations LIMIT 1")->fetch();
    echo "Confirmations columns:\n";
    print_r(array_keys($res));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
