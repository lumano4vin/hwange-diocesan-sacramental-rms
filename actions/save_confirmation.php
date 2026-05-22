<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Confirmation Entry Logic & Canonical Notations (v2 - Smart Lookup)
 * 
 * Fix: Uses person_id directly when supplied by autocomplete to prevent
 * mismatched DOB creating ghost/nameless records.
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Prepare Candidate Data
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $dob        = $_POST['dob'];
    $person_id_from_form = intval($_POST['person_id'] ?? 0); // From autocomplete selection

    // 2. Prepare Confirmation Data
    $date_of_confirmation = $_POST['date_of_confirmation'];
    $parish_id = $_POST['parish_id'];
    $minister  = trim($_POST['minister']);
    $sponsor   = trim($_POST['sponsor']);
    $reg_book  = trim($_POST['reg_book']);
    $reg_page  = trim($_POST['reg_page']);
    $reg_entry = trim($_POST['reg_entry']);

    try {
        $pdo->beginTransaction();

        // 3. Resolve Parishioner
        if ($person_id_from_form > 0) {
            // --- PREFERRED PATH: Use the person selected from the autocomplete ---
            $verified = db_fetch("SELECT person_id, first_name, last_name FROM parishioners WHERE person_id = ?", [$person_id_from_form]);
            
            if ($verified) {
                $person_id  = $verified['person_id'];
                $first_name = $verified['first_name']; // Use canonical name from DB
                $last_name  = $verified['last_name'];
            } else {
                // person_id was invalid, fall back to name lookup
                $person_id_from_form = 0;
            }
        }

        if ($person_id_from_form === 0) {
            // --- FALLBACK PATH: Manual entry - find or create by name + DOB ---
            $find_sql = "SELECT person_id FROM parishioners WHERE first_name = ? AND last_name = ? AND dob = ?";
            $person = db_fetch($find_sql, [$first_name, $last_name, $dob]);

            if (!$person) {
                // Truly new person — create them
                db_query("INSERT INTO parishioners (first_name, last_name, dob) VALUES (?, ?, ?)", [$first_name, $last_name, $dob]);
                $person_id = $pdo->lastInsertId();
            } else {
                $person_id = $person['person_id'];
            }
        }

        // 4. Check for duplicate confirmation for the same person
        $duplicate = db_fetch(
            "SELECT confirmation_id FROM confirmations WHERE person_id = ?",
            [$person_id]
        );

        if ($duplicate) {
            $pdo->rollBack();
            set_flash("⚠ A confirmation record for <b>$first_name $last_name</b> already exists in the registry (ID: #{$duplicate['confirmation_id']}). Duplicate records are not permitted.", "error");
            redirect('../dashboard/add_confirmation.php');
            exit;
        }

        // 5. Save Confirmation Record
        $verification_hash = bin2hex(random_bytes(16));
        $user_role = $_SESSION['role'] ?? 'secretary';
        $status = ($user_role === 'admin' || $user_role === 'chancellor' || $user_role === 'priest' || $user_role === 'deacon') ? 'Verified' : 'Draft';
        $v_by = ($status === 'Verified') ? $_SESSION['user_id'] : null;
        $v_at = ($status === 'Verified') ? date('Y-m-d H:i:s') : null;

        $sql_c = "INSERT INTO confirmations (person_id, parish_id, date_of_confirmation, minister, sponsor, register_book_number, page_number, entry_number, verification_hash, status, verified_by, verified_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        db_query($sql_c, [$person_id, $parish_id, $date_of_confirmation, $minister, $sponsor, $reg_book, $reg_page, $reg_entry, $verification_hash, $status, $v_by, $v_at]);
        $confirmation_id = $pdo->lastInsertId();

        // 6. Automated Canonical Notations (Canon 895/535 §2)
        $baptism = db_fetch("SELECT baptism_id FROM baptisms WHERE person_id = ?", [$person_id]);

        if ($baptism) {
            $parish_data = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$parish_id]);
            $parish_name = $parish_data['parish_name'] ?? 'Unknown Parish';

            db_query("INSERT INTO sacraments_notations (baptism_id, sacrament_type, event_date, parish_name, details, status, verified_by, verified_at) 
                      VALUES (?, 'Confirmation', ?, ?, ?, ?, ?, ?)", 
                      [$baptism['baptism_id'], $date_of_confirmation, $parish_name, "Confirmed with sponsor $sponsor", $status, $v_by, $v_at]);
        }

        $pdo->commit();

        log_audit($_SESSION['user_id'], 'CREATE', 'confirmations', $confirmation_id, "New confirmation record for $first_name $last_name");

        $notation_msg = $baptism ? " Baptism notation updated." : " <em>Note: No linked baptism found — notation skipped.</em>";
        set_flash("Confirmation for <b>$first_name $last_name</b> recorded successfully.$notation_msg", "success");
        redirect('../dashboard/confirmations.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash("Error saving confirmation: " . $e->getMessage(), "error");
        redirect('../dashboard/add_confirmation.php');
    }

} else {
    redirect('../dashboard/confirmations.php');
}
?>
