<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Death Entry Logic & Canonical Notations
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Prepare Person Data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $dob = $_POST['dob'];
    
    // 2. Prepare Death Data
    $date_of_death = $_POST['date_of_death'];
    $date_of_burial = $_POST['date_of_burial'];
    $parish_id = $_POST['parish_id'];
    $place_of_burial = trim($_POST['place_of_burial']);
    $minister = trim($_POST['minister']);
    $reg_book = trim($_POST['reg_book']);
    $reg_page = trim($_POST['reg_page']);
    $reg_entry = trim($_POST['reg_entry']);

    try {
        $pdo->beginTransaction();

        // 3. Find/Create Parishioner
        $find_sql = "SELECT person_id FROM parishioners WHERE first_name = ? AND last_name = ? AND dob = ?";
        $person = db_fetch($find_sql, [$first_name, $last_name, $dob]);
        
        if (!$person) {
            db_query("INSERT INTO parishioners (first_name, last_name, dob, status) VALUES (?, ?, ?, 'Deceased')", [$first_name, $last_name, $dob]);
            $person_id = $pdo->lastInsertId();
        } else {
            $person_id = $person['person_id'];
            // Update Status to Deceased
            db_query("UPDATE parishioners SET status = 'Deceased' WHERE person_id = ?", [$person_id]);
        }

        // 4. Save Death Record
        $verification_hash = bin2hex(random_bytes(16));
        $user_role = $_SESSION['role'] ?? 'secretary';
        $status = ($user_role === 'admin' || $user_role === 'chancellor' || $user_role === 'priest' || $user_role === 'deacon') ? 'Verified' : 'Draft';
        $v_by = ($status === 'Verified') ? $_SESSION['user_id'] : null;
        $v_at = ($status === 'Verified') ? date('Y-m-d H:i:s') : null;

        $sql_d = "INSERT INTO deaths (person_id, parish_id, date_of_death, date_of_burial, place_of_burial, minister, register_book_number, page_number, entry_number, verification_hash, status, verified_by, verified_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        db_query($sql_d, [$person_id, $parish_id, $date_of_death, $date_of_burial, $place_of_burial, $minister, $reg_book, $reg_page, $reg_entry, $verification_hash, $status, $v_by, $v_at]);

        // 5. Automated Canonical Notations (Canon 535 §2)
        // Check for Baptismal Records in our system
        $baptism_sql = "SELECT baptism_id FROM baptisms WHERE person_id = ?";
        $baptism = db_fetch($baptism_sql, [$person_id]);

        if ($baptism) {
            $parish_data = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$parish_id]);
            $parish_name = $parish_data['parish_name'] ?? 'Unknown Parish';

            db_query("INSERT INTO sacraments_notations (baptism_id, sacrament_type, event_date, parish_name, details, status, verified_by, verified_at) 
                      VALUES (?, 'Death', ?, ?, ?, ?, ?, ?)", 
                      [$baptism['baptism_id'], $date_of_death, $parish_name, "Buried at $place_of_burial", $status, $v_by, $v_at]);
        }

        $pdo->commit();

        log_audit($_SESSION['user_id'], 'CREATE', 'deaths', $pdo->lastInsertId(), "New death record for $first_name $last_name");

        set_flash("Death record for <b>$first_name $last_name</b> recorded successfully. Baptism notation and parishioner status updated.", "success");
        redirect('../dashboard/deaths.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash("Error saving death record: " . $e->getMessage(), "error");
        redirect('../dashboard/add_death.php');
    }

} else {
    redirect('../dashboard/deaths.php');
}
?>
