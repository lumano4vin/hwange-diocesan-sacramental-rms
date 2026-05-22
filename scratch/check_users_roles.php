<?php
$sqlite_pdo = new PDO("sqlite:database.sqlite");
$sqlite_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $sqlite_pdo->query("SELECT DISTINCT role FROM users");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "Role: " . $row['role'] . "\n";
}
echo "\nFirst 5 users:\n";
$stmt2 = $sqlite_pdo->query("SELECT user_id, username, role FROM users LIMIT 5");
foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
    print_r($row);
}
