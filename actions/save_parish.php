<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Parish Action (Create/Update)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Protect the action
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parish_id = $_POST['parish_id'] ?? null;
    $parish_name = $_POST['parish_name'] ?? '';
    $location = $_POST['location'] ?? '';
    $deanery = $_POST['deanery'] ?? '';
    $priest_in_charge_id = $_POST['priest_in_charge_id'] ?: null; // Handle empty string as NULL
    $contact_number = $_POST['contact_number'] ?? '';

    if (empty($parish_name) || empty($deanery)) {
        die("Error: Parish name and Deanery are required.");
    }

    try {
        if ($parish_id) {
            // Fetch old priest_in_charge_id
            $old_priest = db_fetch("SELECT priest_in_charge_id FROM parishes WHERE parish_id = ?", [$parish_id]);
            $old_priest_id = $old_priest ? $old_priest['priest_in_charge_id'] : null;

            // Update existing record
            $sql = "UPDATE parishes SET 
                    parish_name = ?, 
                    location = ?, 
                    deanery = ?, 
                    priest_in_charge_id = ?, 
                    contact_number = ? 
                    WHERE parish_id = ?";
            db_query($sql, [$parish_name, $location, $deanery, $priest_in_charge_id, $contact_number, $parish_id]);
            $message = "Parish updated successfully.";

            // Sync priest assignment changes if priest_in_charge_id changed
            if ($old_priest_id != $priest_in_charge_id) {
                $today = date('Y-m-d');
                
                // A. Clear old priest
                if ($old_priest_id) {
                    db_query("UPDATE users SET parish_id = NULL WHERE user_id = ? AND parish_id = ?", [$old_priest_id, $parish_id]);
                }
                
                // B. Assign new priest
                if ($priest_in_charge_id) {
                    // Update user parish
                    db_query("UPDATE users SET parish_id = ? WHERE user_id = ?", [$parish_id, $priest_in_charge_id]);
                    
                    // Clear new priest from other parishes
                    db_query("UPDATE parishes SET priest_in_charge_id = NULL WHERE priest_in_charge_id = ? AND parish_id != ?", [$priest_in_charge_id, $parish_id]);
                }
            }
        } else {
            // Create new record
            $sql = "INSERT INTO parishes (parish_name, location, deanery, priest_in_charge_id, contact_number) 
                    VALUES (?, ?, ?, ?, ?)";
            db_query($sql, [$parish_name, $location, $deanery, $priest_in_charge_id, $contact_number]);
            $new_parish_id = db_lastInsertId();
            $message = "Parish registered successfully.";

            // Sync priest assignment if priest_in_charge_id specified
            if ($priest_in_charge_id) {
                $today = date('Y-m-d');
                
                // Update user parish
                db_query("UPDATE users SET parish_id = ? WHERE user_id = ?", [$new_parish_id, $priest_in_charge_id]);
                
                // Clear priest from other parishes
                db_query("UPDATE parishes SET priest_in_charge_id = NULL WHERE priest_in_charge_id = ? AND parish_id != ?", [$priest_in_charge_id, $new_parish_id]);
            }
        }

        // Redirect back to the directory with a success message
        header("Location: ../dashboard/parishes.php?msg=" . urlencode($message));
        exit;

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../dashboard/parishes.php");
    exit;
}
