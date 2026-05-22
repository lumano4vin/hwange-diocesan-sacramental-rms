<?php
/**
 * Batch Onboarding Script for 18 Additional Priests
 */
require_once 'includes/db.php';

$new_priests = [
    ['name' => 'Fr. Simelinkosi Masango', 'username' => 'simelinkosi_masango', 'parish_search' => '%Divine Mercy%'],
    ['name' => 'Fr. Oscar Bitu', 'username' => 'oscar_bitu', 'parish_search' => '%St. Kizito%'],
    ['name' => 'Fr. Vusumuzi Ndlovu', 'username' => 'vusumuzi_ndlovu', 'parish_search' => '%Sacred Heart%'],
    ['name' => 'Fr. Trust Mweembe', 'username' => 'trust_mweembe', 'parish_search' => '%St. Faustine%'],
    ['name' => 'Fr. Bonesya Mweembe', 'username' => 'bonesya_mweembe', 'parish_search' => '%Minor Seminary%'],
    ['name' => 'Fr. Firtzgerald Chipangura', 'username' => 'firtzgerald_chipangura', 'parish_search' => '%Holy Cross%'],
    ['name' => 'Fr. Ian Muaenda', 'username' => 'ian_muaenda', 'parish_search' => '%St. Mary%'],
    ['name' => 'Fr. Dumisani Ndlovu', 'username' => 'dumisani_ndlovu', 'parish_search' => '%St. Luke%'],
    ['name' => 'Fr. Brian Ngwenya', 'username' => 'brian_ngwenya', 'parish_search' => '%St. Francis of Assisi%'],
    ['name' => 'Fr. Tendai Dube', 'username' => 'tendai_dube', 'parish_search' => '%Our Lady of Fatima%'],
    ['name' => 'Fr. Linus Dube', 'username' => 'linus_dube', 'parish_search' => '%St. Joseph%'],
    ['name' => 'Fr. Edson Tshuma', 'username' => 'edson_tshuma', 'parish_search' => '%Mary Immaculate%'],
    ['name' => 'Fr. Moment Mwinde', 'username' => 'moment_mwinde', 'parish_search' => '%St. Mathew%'],
    ['name' => 'Fr. Marko Rumuma', 'username' => 'marko_rumuma', 'parish_search' => '%Peter and Paul%'],
    ['name' => 'Fr. Jabulani Nyoni', 'username' => 'jabulani_nyoni', 'parish_search' => '%Bakhita%'],
    ['name' => 'Fr. Nkatazo Nyono', 'username' => 'nkatazo_nyono', 'parish_search' => '%Teresa%'], // Or Therese
    ['name' => 'Fr. Praise Muunga', 'username' => 'praise_muunga', 'parish_search' => '%St. Padre Pio%'],
    ['name' => 'Fr. Brillie Chuma', 'username' => 'brillie_chuma', 'parish_search' => '%St. Mark%']
];

$temp_pass = "Mission2026!";

try {
    $pdo = new PDO('sqlite:database.sqlite');
    $hash = password_hash($temp_pass, PASSWORD_DEFAULT);

    echo "===========================================\n";
    echo " BATCH ONBOARDING - 18 ADDITIONAL PRIESTS\n";
    echo "===========================================\n\n";

    foreach ($new_priests as $p) {
        // Find Parish ID
        $stmt = $pdo->prepare("SELECT parish_id, parish_name FROM parishes WHERE parish_name LIKE ? LIMIT 1");
        $stmt->execute([$p['parish_search']]);
        $parish = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$parish) {
            echo "[ERROR] Could not find parish matching: " . $p['parish_search'] . " for " . $p['name'] . "\n";
            continue;
        }

        $parish_id = $parish['parish_id'];
        $parish_name = $parish['parish_name'];

        try {
            $insert_stmt = $pdo->prepare("
                INSERT INTO users (username, password_hash, full_name, email, role, parish_id, must_change_password)
                VALUES (?, ?, ?, ?, 'priest', ?, 1)
            ");
            $insert_stmt->execute([
                $p['username'], 
                $hash, 
                $p['name'], 
                $p['username'] . "@hwangediocese.org", 
                $parish_id
            ]);
            echo "[SUCCESS] Created: " . $p['name'] . " (" . $p['username'] . ") -> Assigned to: " . $parish_name . "\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'UNIQUE') !== false) {
                echo "[SKIP] " . $p['name'] . " already exists.\n";
            } else {
                echo "[ERROR] Failed to create " . $p['name'] . ": " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\nInitial Password for all new accounts: $temp_pass\n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
?>
