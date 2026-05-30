<?php
/**
 * Apply Database Changes for Hwange Diocesan RMS
 * 
 * 1. Fr. Vusumuzi Ndlovu (vusumuzi_ndlovu) -> St. Joseph (parish_id = 11)
 * 2. Fr. Trust Mweembe (trust_mweembe) -> Sacred Heart (parish_id = 21)
 * 3. St. Faustine (parish_id = 24) -> Unassigned (no priest in charge)
 * 4. Fr. Linus Dube -> Fr. Linos Dube (linos_dube), no parish (parish_id = NULL)
 */

require_once 'includes/db.php';

try {
    $pdo = getDB();
    
    echo "==================================================\n";
    echo "      APPLYING CANONICAL RESHUFFLE & FIXES        \n";
    echo "==================================================\n\n";

    // 1. Fr. Vusumuzi Ndlovu -> St. Joseph (parish_id = 11)
    $vusumuzi_username = 'vusumuzi_ndlovu';
    db_query("UPDATE users SET parish_id = 11 WHERE username = ?", [$vusumuzi_username]);
    
    // Update assignment
    db_query("UPDATE parish_assignments SET status = 'Historical', end_date = ? WHERE user_id = (SELECT user_id FROM users WHERE username = ?) AND status = 'Active'", [date('Y-m-d'), $vusumuzi_username]);
    $vus_id = db_fetch("SELECT user_id FROM users WHERE username = ?", [$vusumuzi_username])['user_id'] ?? null;
    if ($vus_id) {
        db_query("INSERT INTO parish_assignments (parish_id, user_id, priest_name_manual, start_date, role, status, notes) VALUES (11, ?, 'Fr. Vusumuzi Ndlovu', ?, 'Parish Priest', 'Active', 'Transferred to St. Joseph')", [$vus_id, date('Y-m-d')]);
    }
    echo "[SUCCESS] Fr. Vusumuzi Ndlovu transferred to St. Joseph (Parish ID 11).\n";

    // 2. Fr. Trust Mweembe -> Sacred Heart (parish_id = 21)
    $trust_username = 'trust_mweembe';
    db_query("UPDATE users SET parish_id = 21 WHERE username = ?", [$trust_username]);
    
    // Update assignment
    db_query("UPDATE parish_assignments SET status = 'Historical', end_date = ? WHERE user_id = (SELECT user_id FROM users WHERE username = ?) AND status = 'Active'", [date('Y-m-d'), $trust_username]);
    $trust_id = db_fetch("SELECT user_id FROM users WHERE username = ?", [$trust_username])['user_id'] ?? null;
    if ($trust_id) {
        db_query("INSERT INTO parish_assignments (parish_id, user_id, priest_name_manual, start_date, role, status, notes) VALUES (21, ?, 'Fr. Trust Mweembe', ?, 'Parish Priest', 'Active', 'Transferred to Sacred Heart')", [$trust_id, date('Y-m-d')]);
    }
    echo "[SUCCESS] Fr. Trust Mweembe transferred to Sacred Heart (Parish ID 21).\n";

    // 3. St. Faustine (parish_id = 24) -> Unassigned
    db_query("UPDATE parishes SET priest_in_charge_id = NULL WHERE parish_id = 24");
    echo "[SUCCESS] St. Faustine marked as Unassigned (no priest in charge).\n";

    // 4. Fr. Linus Dube -> Fr. Linos Dube, no parish (parish_id = NULL)
    $linus_username = 'linus_dube';
    $linos_username = 'linos_dube';
    
    $linus = db_fetch("SELECT user_id FROM users WHERE username = ?", [$linus_username]);
    if ($linus) {
        // Update user record with new spelling, email, and parish_id = NULL
        db_query("
            UPDATE users 
            SET username = ?,
                full_name = 'Fr. Linos Dube',
                email = 'linos_dube@hwangediocese.org',
                parish_id = NULL
            WHERE username = ?
        ", [$linos_username, $linus_username]);
        
        // Mark his active assignments as Historical/Completed
        db_query("
            UPDATE parish_assignments 
            SET status = 'Historical', 
                end_date = ?,
                notes = 'Relieved of parish duties / No active assignment'
            WHERE user_id = ? AND status = 'Active'
        ", [date('Y-m-d'), $linus['user_id']]);
        
        echo "[SUCCESS] Corrected spelling to Fr. Linos Dube, removed parish assignment, and struck active pastoral assignments.\n";
    } else {
        echo "[WARNING] Fr. Linus Dube (linus_dube) was not found in users table.\n";
    }

    echo "\nVerification of modified users:\n";
    $list = db_fetchAll("SELECT username, full_name, parish_id FROM users WHERE username IN ('vusumuzi_ndlovu', 'trust_mweembe', 'linos_dube')");
    foreach ($list as $u) {
        $p_name = $u['parish_id'] ? db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$u['parish_id']])['parish_name'] : 'None (Struck off)';
        echo "  - " . $u['full_name'] . " (" . $u['username'] . ") -> Parish: " . $p_name . PHP_EOL;
    }

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . PHP_EOL;
}
