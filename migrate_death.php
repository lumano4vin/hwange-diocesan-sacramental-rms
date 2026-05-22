<?php
require_once 'includes/db.php';

try {
    // 1. Add status column to parishioners
    $pdo->exec("ALTER TABLE parishioners ADD COLUMN status ENUM('Active', 'Deceased', 'Moved') DEFAULT 'Active'");
    echo "Column 'status' added to parishioners.\n";

    // 2. Create deaths table
    $sql_deaths = "CREATE TABLE IF NOT EXISTS deaths (
        death_id INT AUTO_INCREMENT PRIMARY KEY,
        person_id INT NOT NULL,
        parish_id INT NOT NULL,
        date_of_death DATE NOT NULL,
        date_of_burial DATE,
        place_of_burial VARCHAR(255),
        minister VARCHAR(255),
        register_book_number VARCHAR(50),
        page_number VARCHAR(50),
        entry_number VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (person_id) REFERENCES parishioners(person_id),
        FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
    )";
    $pdo->exec($sql_deaths);
    echo "Table 'deaths' created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
