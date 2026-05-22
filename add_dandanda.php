<?php
require_once 'includes/db.php';

echo "--- Adding Dandanda Mission ---\n";

// 1. Create Fr. Prichard Shoko
$password = password_hash('Hwange@2024', PASSWORD_DEFAULT);
db_query("INSERT INTO users (full_name, username, password, role, status) VALUES (?, ?, ?, 'priest', 'Active')", 
    ['Fr. Prichard Shoko', 'prichard.shoko', $password]);
$userId = db_fetch("SELECT user_id FROM users WHERE username = 'prichard.shoko'")['user_id'];

// 2. Create St. John the Baptist (Dandanda)
// Let's check which deanery other similar missions are in
$deanery = db_fetch("SELECT deanery FROM parishes WHERE location LIKE '%Dandanda%' LIMIT 1")['deanery'] ?? 'Hwange';

db_query("INSERT INTO parishes (parish_name, location, deanery, priest_in_charge_id) VALUES (?, ?, ?, ?)", 
    ['St. John the Baptist (Dandanda)', 'Dandanda', $deanery, $userId]);
$parishId = db_fetch("SELECT parish_id FROM parishes WHERE parish_name LIKE '%St. John the Baptist%' AND location = 'Dandanda'")['parish_id'];

// 3. Update User with ParishID
db_query("UPDATE users SET parish_id = ? WHERE user_id = ?", [$parishId, $userId]);

// 4. Add to Pastoral History
db_query("INSERT INTO parish_assignments (parish_id, user_id, priest_name_manual, start_date, status, notes) VALUES (?, ?, ?, ?, 'Active', ?)", 
    [$parishId, $userId, 'Fr. Prichard Shoko', date('Y-m-d'), "Initial assignment at mission creation"]);

echo "Mission and Priest created successfully.\n";
unlink(__FILE__);
