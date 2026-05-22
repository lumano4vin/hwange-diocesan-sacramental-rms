<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Baptismal Entry Logic
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Sanitize & Prepare Candidate/Parishioner Data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $place_of_birth = trim($_POST['place_of_birth']);
    $father_name = trim($_POST['father_name']);
    $mother_name = trim($_POST['mother_name']);
    $mother_maiden_name = trim($_POST['mother_maiden_name']);
    $current_parish_id = $_POST['parish_id'] ?? $_SESSION['parish_id'];
    
    if (empty($current_parish_id)) {
        set_flash("Error: Missing Parish Identification. Please log in again.", "error");
        redirect('../dashboard/add_baptism.php');
    }

    // 2. Sanitize & Prepare Baptism Data
    $date_of_baptism = $_POST['date_of_baptism'];
    $minister = trim($_POST['minister']);
    $godparents = trim($_POST['godparents']);
    $witnesses = trim($_POST['witnesses']);
    $reg_book = trim($_POST['register_book_number']);
    $reg_page = trim($_POST['page_number']);
    $reg_entry = trim($_POST['entry_number']);

    try {
        $pdo->beginTransaction();

        // 3. Create/Link Parishioner
        // Note: For simplicity in this v1, we always create a new record. 
        // A more advanced version would check for existing persons.
        $sql_p = "INSERT INTO parishioners (first_name, last_name, gender, dob, place_of_birth, father_name, mother_name, mother_maiden_name, current_parish_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        db_query($sql_p, [$first_name, $last_name, $gender, $dob, $place_of_birth, $father_name, $mother_name, $mother_maiden_name, $current_parish_id]);
        $person_id = $pdo->lastInsertId();

        // 4. Create Baptismal Entry
        $verification_hash = bin2hex(random_bytes(16)); // Secure 32-char hex string
        
        // Determine role-based status
        $user_role = $_SESSION['role'] ?? 'secretary';
        $status = ($user_role === 'admin' || $user_role === 'chancellor' || $user_role === 'priest' || $user_role === 'deacon') ? 'Verified' : 'Draft';
        $verified_by = ($status === 'Verified') ? $_SESSION['user_id'] : null;
        $verified_at = ($status === 'Verified') ? date('Y-m-d H:i:s') : null;

        $sql_b = "INSERT INTO baptisms (person_id, parish_id, date_of_baptism, minister, godparents, witnesses, register_book_number, page_number, entry_number, verification_hash, status, verified_by, verified_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        db_query($sql_b, [
            $person_id, 
            $current_parish_id, 
            $date_of_baptism, 
            $minister, 
            $godparents, 
            $witnesses, 
            $reg_book, 
            $reg_page, 
            $reg_entry, 
            $verification_hash,
            $status,
            $verified_by,
            $verified_at
        ]);

        $pdo->commit();

        log_audit($_SESSION['user_id'], 'CREATE', 'baptisms', $pdo->lastInsertId(), "New baptism record for $first_name $last_name");

        set_flash("Baptismal record for <b>$first_name $last_name</b> saved successfully!", "success");
        redirect('../dashboard/baptisms.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash("Error saving record: " . $e->getMessage(), "error");
        redirect('../dashboard/add_baptism.php');
    }

} else {
    redirect('../dashboard/baptisms.php');
}
?>
