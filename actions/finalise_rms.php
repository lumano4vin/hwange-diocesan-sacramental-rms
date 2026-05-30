<?php
/**
 * Hwange Diocese RMS - Final Touch Credential Provisioning & Resets
 * 
 * 1. Inserts/Ensures Fr. Tarcius Munkombwe is registered and assigned to Our Lady of Fatima (parish_id = 20)
 *    as an associate to Fr. Tendai Dube.
 * 2. Auto-Populates/Syncs parish_assignments table for all active priests to ensure dashboard displays match.
 * 3. Resets passwords of all clergy and users to 'Hwange2026!' and sets must_change_password = 1,
 *    EXCEPT for Fr. Stanislaus Lumano (stanislaus_lumano) and the admin/creator accounts (vincent_lumano, admin).
 */

require_once 'includes/db.php';

try {
    $pdo = getDB();
    $default_pass = "Hwange2026!";
    $hash = password_hash($default_pass, PASSWORD_DEFAULT);

    echo "==================================================\n";
    echo "       RMS FINAL TOUCHES & CREDENTIAL RESETS      \n";
    echo "==================================================\n\n";

    // 1. Check if Fatima Mission exists (parish_id = 20)
    $fatima = db_fetch("SELECT parish_id, parish_name FROM parishes WHERE parish_id = 20");
    if (!$fatima) {
        echo "[ERROR] Fatima Mission (parish_id = 20) not found in parishes!\n";
        exit(1);
    }
    echo "[INFO] Verified: " . $fatima['parish_name'] . " (ID: " . $fatima['parish_id'] . ") is active.\n";

    // 2. Add or Update Fr. Tarcius Munkombwe
    $tarcius_username = 'tarcius_munkombwe';
    $tarcius = db_fetch("SELECT user_id FROM users WHERE username = ?", [$tarcius_username]);

    if ($tarcius) {
        // Update
        db_query("
            UPDATE users 
            SET full_name = 'Fr. Tarcius Munkombwe', 
                email = 'tarcius_munkombwe@hwangediocese.org', 
                role = 'priest', 
                parish_id = 20 
            WHERE username = ?
        ", [$tarcius_username]);
        echo "[SUCCESS] Updated existing account for Fr. Tarcius Munkombwe.\n";
    } else {
        // Insert
        db_query("
            INSERT INTO users (username, password_hash, full_name, email, role, parish_id, must_change_password)
            VALUES (?, ?, 'Fr. Tarcius Munkombwe', 'tarcius_munkombwe@hwangediocese.org', 'priest', 20, 1)
        ", [$tarcius_username, $hash]);
        echo "[SUCCESS] Created new account for Fr. Tarcius Munkombwe (Fatima Mission associate).\n";
    }

    // Get Tarcius user ID
    $tarcius_id = db_fetch("SELECT user_id FROM users WHERE username = ?", [$tarcius_username])['user_id'];

    // 3. Sync parish_assignments for all priests where parish_id is set
    echo "\n[INFO] Synchronizing parish assignments...\n";
    $priests = db_fetchAll("SELECT user_id, username, full_name, role, parish_id FROM users WHERE role = 'priest' AND parish_id IS NOT NULL");
    
    $assignment_added = 0;
    $assignment_updated = 0;

    foreach ($priests as $p) {
        $user_id = $p['user_id'];
        $parish_id = $p['parish_id'];
        $full_name = $p['full_name'];
        
        // Determine role for assignment
        $assignment_role = ($p['username'] === $tarcius_username) ? 'Associate Priest' : 'Parish Priest';
        $notes = ($p['username'] === $tarcius_username) ? 'Associate to Fr. Tendai Dube' : 'Assigned in-charge of parish';

        // Check if an active assignment already exists for this user
        $assignment = db_fetch("SELECT assignment_id, parish_id FROM parish_assignments WHERE user_id = ? AND status = 'Active'", [$user_id]);

        if ($assignment) {
            if ($assignment['parish_id'] != $parish_id) {
                // Relocated or mismatched: mark old as Historical and insert new active one
                db_query("UPDATE parish_assignments SET status = 'Historical', end_date = ? WHERE assignment_id = ?", [date('Y-m-d'), $assignment['assignment_id']]);
                db_query("
                    INSERT INTO parish_assignments (parish_id, user_id, priest_name_manual, start_date, role, status, notes)
                    VALUES (?, ?, ?, ?, ?, 'Active', ?)
                ", [$parish_id, $user_id, $full_name, date('Y-m-d'), $assignment_role, $notes]);
                $assignment_updated++;
            }
        } else {
            // No active assignment: create one
            db_query("
                INSERT INTO parish_assignments (parish_id, user_id, priest_name_manual, start_date, role, status, notes)
                VALUES (?, ?, ?, ?, ?, 'Active', ?)
            ", [$parish_id, $user_id, $full_name, date('Y-m-d'), $assignment_role, $notes]);
            $assignment_added++;
        }
    }
    echo "[SUCCESS] Assignments Sync complete: Added $assignment_added, Updated/Moved $assignment_updated.\n";

    // 4. Reset credentials for all EXCEPT Stanislaus Lumano, Vincent Lumano, and admin
    $excluded_usernames = ['stanislaus_lumano', 'vincent_lumano', 'admin'];
    
    // Fetch all users to update
    $users = db_fetchAll("SELECT user_id, username, full_name FROM users");
    $updated_count = 0;
    $skipped = [];

    foreach ($users as $user) {
        if (in_array($user['username'], $excluded_usernames)) {
            $skipped[] = $user['full_name'] . " (" . $user['username'] . ")";
            continue;
        }

        // Update password
        db_query("
            UPDATE users 
            SET password_hash = ?, must_change_password = 1 
            WHERE user_id = ?
        ", [$hash, $user['user_id']]);
        $updated_count++;
    }

    echo "\n[SUMMARY] Credential Reset Completed:\n";
    echo "--------------------------------------------------\n";
    echo "Total accounts updated to default password: $updated_count\n";
    echo "Default Password: $default_pass (must change on first login)\n";
    echo "Accounts SKIPPED (Preserved):\n";
    foreach ($skipped as $s) {
        echo "  - $s\n";
    }
    echo "--------------------------------------------------\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
