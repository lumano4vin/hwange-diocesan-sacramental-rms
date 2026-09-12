<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Parishioner Action (Create/Update)
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the action
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person_id = $_POST['person_id'] ?? null;
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $place_of_birth = $_POST['place_of_birth'] ?? '';
    $father_name = $_POST['father_name'] ?? '';
    $mother_name = $_POST['mother_name'] ?? '';
    $mother_maiden_name = $_POST['mother_maiden_name'] ?? '';
    $current_parish_id = $_POST['current_parish_id'] ?: null;
    $status = $_POST['status'] ?? 'Active';

    if (empty($first_name) || empty($last_name) || empty($gender) || empty($dob)) {
        die("Error: Core personal details are required.");
    }

    try {
        if ($person_id) {
            // Update existing record
            $sql = "UPDATE parishioners SET 
                    first_name = ?, last_name = ?, gender = ?, dob = ?, 
                    place_of_birth = ?, father_name = ?, mother_name = ?, 
                    mother_maiden_name = ?, current_parish_id = ?, status = ? 
                    WHERE person_id = ?";
            db_query($sql, [
                $first_name, $last_name, $gender, $dob, 
                $place_of_birth, $father_name, $mother_name, 
                $mother_maiden_name, $current_parish_id, $status, $person_id
            ]);
            $message = "Parishioner updated successfully.";
        } else {
            // Create new record
            $sql = "INSERT INTO parishioners (
                    first_name, last_name, gender, dob, 
                    place_of_birth, father_name, mother_name, 
                    mother_maiden_name, current_parish_id, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            db_query($sql, [
                $first_name, $last_name, $gender, $dob, 
                $place_of_birth, $father_name, $mother_name, 
                $mother_maiden_name, $current_parish_id, $status
            ]);
            $message = "Parishioner registered successfully.";
        }

        // Redirect back to the directory
        header("Location: ../dashboard/parishioners.php?msg=" . urlencode($message));
        exit;

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../dashboard/parishioners.php");
    exit;
}
