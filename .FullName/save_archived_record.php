<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Archived Record - Transcription Action
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check authorization
if (!is_admin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Archivist privileges required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $parish_id = $_POST['parish_id'] ?? null;
    $first_name = $_POST['first_name'] ?? '';
    $baptism_date = $_POST['baptism_date'] ?? null;
    $father_name = $_POST['father_name'] ?? '';
    $mother_name = $_POST['mother_name'] ?? '';
    $godparents = $_POST['godparents'] ?? '';
    $minister = $_POST['minister'] ?? '';
    $sacrament_type = $_POST['sacrament_type'] ?? 'baptism';

    if (!$parish_id || !$first_name || !$baptism_date) {
        throw new Exception("Missing required fields (Parish, Name, or Date).");
    }

    // 1. Create a "Shadow Parishioner" record
    // Historical registers often lack full birth details, so we create a skeleton profile.
    $sql_p = "INSERT INTO parishioners (parish_id, first_name, last_name, father_name, mother_name, created_at) VALUES (?, ?, 'Archived', ?, ?, CURRENT_TIMESTAMP)";
    
    $stmt_p = $pdo->prepare($sql_p);
    $stmt_p->execute([$parish_id, $first_name, $father_name, $mother_name]);
    $person_id = $pdo->lastInsertId();

    // 2. Insert into the specific sacrament table
    if ($sacrament_type === 'baptism') {
        $guid = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
        
        $sql_b = "INSERT INTO baptisms (person_id, parish_id, date_of_baptism, minister, sponsors, register_book_number, guid, status) VALUES (?, ?, ?, ?, ?, 'ARCHIVE-I', ?, 'Verified')";
        
        $stmt_b = $pdo->prepare($sql_b);
        $stmt_b->execute([$person_id, $parish_id, $baptism_date, $minister, $godparents, $guid]);
        $record_id = $pdo->lastInsertId();
        
        // Log the action
        log_action($_SESSION['user_id'], 'transcribed', 'baptisms', $record_id);
        
    } else {
        throw new Exception("The sacrament type '{$sacrament_type}' is not yet supported for automated archiving.");
    }

    echo json_encode(['success' => true, 'id' => $record_id]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
