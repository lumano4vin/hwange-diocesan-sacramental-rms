<?php
try {
    $pdo = new PDO("sqlite:database.sqlite");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Seed Confirmation
    // Let's check if person_id = 6 exists
    $stmt = $pdo->prepare("SELECT person_id FROM parishioners WHERE person_id = 6");
    $stmt->execute();
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("
            INSERT OR IGNORE INTO confirmations 
            (person_id, parish_id, date_of_confirmation, minister, sponsor, register_book_number, page_number, entry_number, verification_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            6, 22, '2026-05-15', 'Most Rev. Bishop Raphael M. Ncube', 'John Doe', 'CONF-2026', '12', '45', 'mock_conf_hash_123'
        ]);
        echo "Mock Confirmation seeded successfully.\n";
    }

    // 2. Seed Marriage
    // Let's query one male and one female parishioner
    $male = $pdo->query("SELECT person_id FROM parishioners WHERE gender = 'Male' LIMIT 1")->fetchColumn();
    $female = $pdo->query("SELECT person_id FROM parishioners WHERE gender = 'Female' LIMIT 1")->fetchColumn();

    if ($male && $female) {
        $stmt = $pdo->prepare("
            INSERT OR IGNORE INTO marriages 
            (groom_person_id, bride_person_id, parish_id, date_of_marriage, officiant, witnesses_names, register_book_number, page_number, entry_number, verification_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $male, $female, 22, '2026-05-16', 'Fr. Vincent Lumano', 'Peter Banda & Mary Moyo', 'MARR-2026', '08', '23', 'mock_marr_hash_456'
        ]);
        echo "Mock Marriage seeded successfully (Groom: $male, Bride: $female).\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
