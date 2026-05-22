<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Parish Action (Create/Update)
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

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
        } else {
            // Create new record
            $sql = "INSERT INTO parishes (parish_name, location, deanery, priest_in_charge_id, contact_number) 
                    VALUES (?, ?, ?, ?, ?)";
            db_query($sql, [$parish_name, $location, $deanery, $priest_in_charge_id, $contact_number]);
            $message = "Parish registered successfully.";
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
