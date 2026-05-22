<?php
/**
 * Batch Account Updates & Deletions
 */
require_once 'includes/db.php';

try {
    $pdo = new PDO('sqlite:database.sqlite');
    $hash = password_hash("Mission2026!", PASSWORD_DEFAULT);

    echo "===========================================\n";
    echo " BATCH ACCOUNT UPDATES\n";
    echo "===========================================\n\n";

    // 1. Add Fr. Carlos Rilova (St. Martin de Porres)
    $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE parish_name LIKE '%St. Martin de Porres%' LIMIT 1");
    $stmt->execute();
    $parish_carlos = $stmt->fetchColumn();
    if ($parish_carlos) {
        $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, parish_id, must_change_password) VALUES (?, ?, ?, ?, 'priest', ?, 1)")
            ->execute(['carlos_rilova', $hash, 'Fr. Carlos Rilova', 'carlos_rilova@hwangediocese.org', $parish_carlos]);
        echo "[SUCCESS] Added: Fr. Carlos Rilova (St. Martin de Porres)\n";
    }

    // 2. Add Fr. Lungisani Moyo (St. Joachim and St Monica)
    // The system supports 1 primary parish per account, so we'll link him to St. Joachim.
    $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE parish_name LIKE '%St. Joachim%' LIMIT 1");
    $stmt->execute();
    $parish_lungisani = $stmt->fetchColumn();
    if ($parish_lungisani) {
        $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, parish_id, must_change_password) VALUES (?, ?, ?, ?, 'priest', ?, 1)")
            ->execute(['lungisani_moyo', $hash, 'Fr. Lungisani Moyo', 'lungisani_moyo@hwangediocese.org', $parish_lungisani]);
        echo "[SUCCESS] Added: Fr. Lungisani Moyo (Primary: St. Joachim)\n";
    }

    // 3. Replace Fr. Irvin Lumano with Fr. Simbarashe Muza
    $stmt = $pdo->prepare("UPDATE users SET full_name = 'Fr. Simbarashe Muza', username = 'simbarashe_muza', email = 'simbarashe_muza@hwangediocese.org' WHERE full_name LIKE '%Fr. Irvin Lumano%'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "[SUCCESS] Replaced Fr. Irvin Lumano with Fr. Simbarashe Muza\n";
    }

    // 4. Correct Spelling of Nyono to Nyoni
    $stmt = $pdo->prepare("UPDATE users SET full_name = 'Fr. Nkatazo Nyoni', username = 'nkatazo_nyoni', email = 'nkatazo_nyoni@hwangediocese.org' WHERE full_name LIKE '%Fr. Nkatazo Nyono%'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "[SUCCESS] Corrected spelling: Fr. Nkatazo Nyono -> Fr. Nkatazo Nyoni\n";
    }

    // 5. Remove Users: Fr. Thulani Sibanda, Rev. Dn. Moses Ncube, Mrs. Maria Moyo
    $users_to_remove = ['%Thulani Sibanda%', '%Moses Ncube%', '%Maria Moyo%'];
    foreach ($users_to_remove as $u) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE full_name LIKE ?");
        $stmt->execute([$u]);
        if ($stmt->rowCount() > 0) {
            echo "[SUCCESS] Removed: $u\n";
        }
    }

    echo "\n===========================================\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
