<?php
require '../includes/db.php';
try {
    $pdo->beginTransaction();
    
    // Create Parishioner
    $sql_p = "INSERT INTO parishioners (first_name, last_name, gender, dob, current_parish_id) 
              VALUES (?, ?, ?, ?, ?)";
    db_query($sql_p, ['Test', 'Isolation', 'Male', '2000-01-01', 7]);
    $person_id = $pdo->lastInsertId();
    
    // Create Baptism
    $sql_b = "INSERT INTO baptisms (person_id, parish_id, date_of_baptism, minister, status) 
              VALUES (?, ?, ?, ?, ?)";
    db_query($sql_b, [$person_id, 7, '2026-05-05', 'Test Priest', 'Verified']);
    
    $pdo->commit();
    echo "Success! Inserted Person: $person_id\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
