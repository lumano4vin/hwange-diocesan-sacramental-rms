<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Ordination / Profession Entry Logic & Canonical Notations
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Prepare Candidate Data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $dob = $_POST['dob'];
    $new_title = $_POST['new_title'];
    
    // 2. Prepare Record Data
    $record_type = $_POST['record_type'];
    $congregation = trim($_POST['congregation']);
    $event_date = $_POST['event_date'];
    $parish_id = $_POST['parish_id'];
    $celebrant_superior = trim($_POST['celebrant_superior']);
    $place = trim($_POST['place']);
    $reg_book = trim($_POST['reg_book']);
    $reg_page = trim($_POST['reg_page']);
    $reg_entry = trim($_POST['reg_entry']);

    try {
        $pdo->beginTransaction();

        // 3. Find/Create Parishioner
        $find_sql = "SELECT person_id FROM parishioners WHERE first_name = ? AND last_name = ? AND dob = ?";
        $person = db_fetch($find_sql, [$first_name, $last_name, $dob]);
        
        if (!$person) {
            db_query("INSERT INTO parishioners (first_name, last_name, dob, title) VALUES (?, ?, ?, ?)", [$first_name, $last_name, $dob, $new_title]);
            $person_id = $pdo->lastInsertId();
        } else {
            $person_id = $person['person_id'];
            // Update Title
            db_query("UPDATE parishioners SET title = ? WHERE person_id = ?", [$new_title, $person_id]);
        }

        // 4. Save Ordination/Profession Record
        $verification_hash = bin2hex(random_bytes(16));
        $user_role = $_SESSION['role'] ?? 'secretary';
        $status = ($user_role === 'admin' || $user_role === 'chancellor' || $user_role === 'priest' || $user_role === 'deacon') ? 'Verified' : 'Draft';
        $v_by = ($status === 'Verified') ? $_SESSION['user_id'] : null;
        $v_at = ($status === 'Verified') ? date('Y-m-d H:i:s') : null;

        $sql_op = "INSERT INTO ordinations_professions (person_id, parish_id, record_type, congregation, event_date, celebrant_superior, place, register_book_number, page_number, entry_number, verification_hash, status, verified_by, verified_at) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        db_query($sql_op, [$person_id, $parish_id, $record_type, $congregation, $event_date, $celebrant_superior, $place, $reg_book, $reg_page, $reg_entry, $verification_hash, $status, $v_by, $v_at]);

        // 5. Automated Canonical Notations (Canon 535 §2 / 1053)
        // Check for Baptismal Records in our system
        $baptism_sql = "SELECT baptism_id FROM baptisms WHERE person_id = ?";
        $baptism = db_fetch($baptism_sql, [$person_id]);

        if ($baptism) {
            $parish_data = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$parish_id]);
            $parish_name = $parish_data['parish_name'] ?? 'Unknown Parish';

            $details = "$record_type recorded. ";
            if (!empty($congregation)) $details .= "Congregation: $congregation. ";
            $details .= "Minister/Superior: $celebrant_superior";

            db_query("INSERT INTO sacraments_notations (baptism_id, sacrament_type, event_date, parish_name, details, status, verified_by, verified_at) 
                      VALUES (?, 'Holy Orders', ?, ?, ?, ?, ?, ?)", 
                      [$baptism['baptism_id'], $event_date, $parish_name, $details, $status, $v_by, $v_at]);
        }

        $pdo->commit();

        log_audit($_SESSION['user_id'], 'CREATE', 'ordinations_professions', $pdo->lastInsertId(), "New $record_type recorded for $first_name $last_name");

        set_flash("Canonical record for <b>$first_name $last_name</b> recorded successfully. Title updated to $new_title.", "success");
        redirect('../dashboard/ordinations.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash("Error saving record: " . $e->getMessage(), "error");
        redirect('../dashboard/add_ordination.php');
    }

} else {
    redirect('../dashboard/ordinations.php');
}
?>
