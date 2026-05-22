<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Canonical Data Synchronization Script
 */

require_once 'includes/db.php';

echo "--- Synchronizing Canonical Data ---\n";

$parishes_data = [
    ['St. Ignatius Cathedral', 'Hwange', 'Hwange Urban'],
    ['Holy Family', 'Hwange', 'Hwange Urban'],
    ['Our Lady of Peace', 'Hwange', 'Hwange Urban'],
    ['Mary Help of Christians', 'Don Bosco, Hwange', 'Hwange Urban'],
    ['Ss. Peter and Paul', 'Hwange', 'Hwange Urban'],
    ['St. Charles Lwanga', 'Hwange', 'Hwange Urban'],
    ['St. Francis Xavier', 'Dete', 'Dete'],
    ['St. Francis of Assisi', 'Cross Dete', 'Dete'],
    ['St. George', 'Hwange', 'Hwange Urban'],
    ['St. Joachim', 'Hwange', 'Hwange Urban'],
    ['St. Joseph', 'Hwange', 'Hwange Urban'],
    ['St. Josephine Bakhita', 'Victoria Falls', 'Victoria Falls'],
    ['St. Kizito', 'Hwange', 'Hwange Urban'],
    ['St. Monica', 'Hwange', 'Hwange Urban'],
    ['St. Teresa', 'Hwange', 'Hwange Urban'],
    ['All Souls', 'Binga', 'Binga'],
    ['Divine Mercy', 'Lubimbi', 'Binga'],
    ['Holy Cross', 'Lusulu', 'Binga'],
    ['Mary Immaculate', 'Gomoza', 'Lupane'],
    ['Our Lady of Fatima', 'Fatima', 'Lupane'],
    ['Sacred Heart', 'Jambezi', 'Jambezi'],
    ['St. Augustine', 'Mzola', 'Lupane'],
    ['St. Cecilia', 'Tshongokwe', 'Lupane'],
    ['St. Faustine', 'Matetsi', 'Victoria Falls'],
    ['St. John the Baptist (Dandanda)', 'Dandanda', 'Lupane'],
    ['St. John the Baptist (Makwa)', 'Makwa', 'Makwa'],
    ['St. John Vianney', 'Kariangwe', 'Binga'],
    ['St. Luke', 'Chisuma', 'Victoria Falls'],
    ['St. Mathew', 'Dambwamkulu', 'Binga'],
    ['St. Mark', 'Nagangala', 'Binga'],
    ['St. Martin de Porres', 'Jotsholo', 'Lupane'],
    ['St. Mary', 'Lukosi', 'Hwange Urban'],
    ['St. Michael', 'Kasibo', 'Hwange Urban'],
    ['St. Padre Pio', 'Siacilaba', 'Binga'],
    ['St. Therese', 'Kamativi', 'Dete']
];

try {
    // 1. Clear existing parishes
    // Note: We use db_query instead of raw PDO for consistency with the app
    db_query("DELETE FROM parishes");
    db_query("DELETE FROM sqlite_sequence WHERE name='parishes'"); // Reset auto-increment
    
    echo "[OK] Cleared old parish data.\n";

    // 2. Insert new official parishes
    $sql = "INSERT INTO parishes (parish_name, location, deanery) VALUES (?, ?, ?)";
    
    foreach ($parishes_data as $row) {
        db_query($sql, $row);
        echo "Inserted: " . $row[0] . " (" . $row[2] . ")\n";
    }

    echo "\n[SUCCESS] Canonical synchronization complete. 35 records updated.\n";

} catch (Exception $e) {
    echo "\n[ERROR] Synchronization failed: " . $e->getMessage() . "\n";
}
