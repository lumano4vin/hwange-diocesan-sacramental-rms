<?php
/**
 * Hwange Diocese SRMS - Database Seeder
 * Aligned with SQLite schema (parish_id, user_id, etc.)
 */

require_once 'includes/db.php';

$data = [
    'Binga deanery' => [
        'All Souls', 'St. Mark\'s', 'St. John Mary Vianney', 'Holy Cross', 'St. Padre Pio'
    ],
    'Hwange Urban Deanery' => [
        'St. Ignatius Cathedral', 'Holy family', 'St. Peter and Paul', 'St. Joseph\'s', 'St. Georges', 'St. Monica', 'St. Joachim'
    ],
    'Empumalanga deanery' => [
        'St. Teresa', 'Mary help of Christians', 'St. Mary\'s', 'St. Charles Lwanga'
    ],
    'Makwa deanery' => [
        'St. Johns Makwa', 'St. Mathews Dambwamukulu', 'St. Michael'
    ],
    'Victoria falls deanery' => [
        'St. Josephine bakhita', 'St. Kizito', 'Our Lady of Peace'
    ],
    'Jambezi Deanery' => [
        'Sacred heart', 'St. Luke\'s Chisuma', 'St. Faustina'
    ],
    'Lupane deanery' => [
        'St. Martin de Porres', 'St. Cecilia', 'St. Augustine', 'Fatima', 'St. John', 'Mary Immaculate'
    ],
    'Dete deanery' => [
        'St Francis Xavier', 'Assisi Assisi', 'St. Theresa Kamativi', 'Divine Mercy'
    ]
];

try {
    $pdo = getDB();
    
    echo "Seeding Parishes...\n";
    foreach ($data as $deaneryName => $parishes) {
        foreach ($parishes as $parishName) {
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO parishes (parish_name, deanery) VALUES (?, ?)");
            $stmt->execute([$parishName, $deaneryName]);
        }
    }
    
    echo "Creating Admin User...\n";
    $adminUser = 'admin';
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $adminEmail = 'admin@hwangediocese.org';
    
    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO users (username, password_hash, email, full_name, role) 
        VALUES (?, ?, ?, 'Diocesan Administrator', 'admin')
    ");
    $stmt->execute([$adminUser, $adminPass, $adminEmail]);
    
    echo "Seeding Complete. Default credentials: admin / admin123\n";

} catch (Exception $e) {
    die("Seeding failed: " . $e->getMessage());
}
