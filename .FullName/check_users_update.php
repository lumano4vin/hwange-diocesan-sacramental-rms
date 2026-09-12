<?php
require_once 'includes/db.php';

try {
    $pdo = new PDO('sqlite:database.sqlite');

    echo "--- USERS SEARCH ---\n";
    $stmt = $pdo->query("SELECT user_id, username, full_name FROM users WHERE full_name LIKE '%Lumano%' OR full_name LIKE '%Sibanda%' OR full_name LIKE '%Ncube%' OR full_name LIKE '%Moyo%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['user_id'] . " | " . $row['username'] . " | " . $row['full_name'] . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
