<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Marriage Entry Logic & Canonical Notations
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Prepare Spouse Data
    $g_first = trim($_POST['g_first_name']);
    $g_last = trim($_POST['g_last_name']);
    $g_dob = $_POST['g_dob'];
    
    $b_first = trim($_POST['b_first_name']);
    $b_last = trim($_POST['b_last_name']);
    $b_dob = $_POST['b_dob'];

    // 2. Prepare Marriage Data
    $date_of_marriage = $_POST['date_of_marriage'];
    $parish_id = $_POST['parish_id'] ?? $_SESSION['parish_id'];
    
    if (empty($parish_id)) {
        set_flash("Error: Missing Parish Identification. Please log in again.", "error");
        redirect('../dashboard/add_marriage.php');
    }
    $officiant = trim($_POST['officiant']);
    $witnesses = trim($_POST['witnesses']);
    $reg_book  = trim($_POST['reg_book'] ?? '');
    $reg_page  = trim($_POST['reg_page'] ?? '');
    $reg_entry = trim($_POST['reg_entry'] ?? '');

    // From autocomplete (preferred)
    $groom_person_id_form = intval($_POST['groom_person_id'] ?? 0);
    $bride_person_id_form = intval($_POST['bride_person_id'] ?? 0);
    
    try {
        $pdo->beginTransaction();

        // 3. Resolve Groom
        if ($groom_person_id_form > 0) {
            $groom_rec = db_fetch("SELECT person_id, first_name, last_name FROM parishioners WHERE person_id = ?", [$groom_person_id_form]);
            if ($groom_rec) {
                $groom_id = $groom_rec['person_id'];
                $g_first  = $groom_rec['first_name'];
                $g_last   = $groom_rec['last_name'];
            } else { $groom_person_id_form = 0; }
        }
        if ($groom_person_id_form === 0) {
            $groom = db_fetch("SELECT person_id FROM parishioners WHERE first_name = ? AND last_name = ? AND dob = ?", [$g_first, $g_last, $g_dob]);
            if (!$groom) {
                $guid = bin2hex(random_bytes(16));
                $diocese_id = $primary_diocese['diocese_id'] ?? 1;
                db_query("INSERT INTO parishioners (first_name, last_name, dob, gender, guid, diocese_id, current_parish_id, status) VALUES (?, ?, ?, 'Male', ?, ?, ?, 'Active')", [$g_first, $g_last, $g_dob, $guid, $diocese_id, $parish_id]);
                $groom_id = $pdo->lastInsertId();
            } else { $groom_id = $groom['person_id']; }
        }
 
        // 4. Resolve Bride
        if ($bride_person_id_form > 0) {
            $bride_rec = db_fetch("SELECT person_id, first_name, last_name FROM parishioners WHERE person_id = ?", [$bride_person_id_form]);
            if ($bride_rec) {
                $bride_id = $bride_rec['person_id'];
                $b_first  = $bride_rec['first_name'];
                $b_last   = $bride_rec['last_name'];
            } else { $bride_person_id_form = 0; }
        }
        if ($bride_person_id_form === 0) {
            $bride = db_fetch("SELECT person_id FROM parishioners WHERE first_name = ? AND last_name = ? AND dob = ?", [$b_first, $b_last, $b_dob]);
            if (!$bride) {
                $guid = bin2hex(random_bytes(16));
                $diocese_id = $primary_diocese['diocese_id'] ?? 1;
                db_query("INSERT INTO parishioners (first_name, last_name, dob, gender, guid, diocese_id, current_parish_id, status) VALUES (?, ?, ?, 'Female', ?, ?, ?, 'Active')", [$b_first, $b_last, $b_dob, $guid, $diocese_id, $parish_id]);
                $bride_id = $pdo->lastInsertId();
            } else { $bride_id = $bride['person_id']; }
        }

        // 5. Save Marriage Record
        $verification_hash = bin2hex(random_bytes(16));
        $user_role = $_SESSION['role'] ?? 'secretary';
        $status = ($user_role === 'admin' || $user_role === 'chancellor' || $user_role === 'priest' || $user_role === 'deacon') ? 'Verified' : 'Draft';
        $v_by = ($status === 'Verified') ? $_SESSION['user_id'] : null;
        $v_at = ($status === 'Verified') ? date('Y-m-d H:i:s') : null;

        $sql_m = "INSERT INTO marriages (groom_person_id, bride_person_id, parish_id, date_of_marriage, officiant, witnesses_names, register_book_number, page_number, entry_number, verification_hash, status, verified_by, verified_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        db_query($sql_m, [$groom_id, $bride_id, $parish_id, $date_of_marriage, $officiant, $witnesses, $reg_book, $reg_page, $reg_entry, $verification_hash, $status, $v_by, $v_at]);
        $marriage_id = $pdo->lastInsertId();

        // 6. Automated Canonical Notations (Canon 1122)
        // Check for Baptismal Records in our system
        $baptism_sql = "SELECT baptism_id, parish_id FROM baptisms WHERE person_id = ?";
        
        $parish_data = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$parish_id]);
        $parish_name = $parish_data['parish_name'] ?? 'Unknown Parish';

        // Groom Notation
        $g_baptism = db_fetch($baptism_sql, [$groom_id]);
        if ($g_baptism) {
            db_query("INSERT INTO sacraments_notations (baptism_id, sacrament_type, event_date, parish_name, details, status, verified_by, verified_at) 
                      VALUES (?, 'Marriage', ?, ?, ?, ?, ?, ?)", 
                      [$g_baptism['baptism_id'], $date_of_marriage, $parish_name, "Married to $b_first $b_last", $status, $v_by, $v_at]);
        }

        // Bride Notation
        $b_baptism = db_fetch($baptism_sql, [$bride_id]);
        if ($b_baptism) {
            db_query("INSERT INTO sacraments_notations (baptism_id, sacrament_type, event_date, parish_name, details, status, verified_by, verified_at) 
                      VALUES (?, 'Marriage', ?, ?, ?, ?, ?, ?)", 
                      [$b_baptism['baptism_id'], $date_of_marriage, $parish_name, "Married to $g_first $g_last", $status, $v_by, $v_at]);
        }

        $pdo->commit();

        log_audit($_SESSION['user_id'], 'CREATE', 'marriages', $marriage_id, "New marriage record for $g_first & $b_first");

        set_flash("Marriage of <b>$g_first & $b_first</b> recorded successfully. Canonical notations updated where applicable.", "success");
        redirect('../dashboard/marriages.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash("Error saving marriage: " . $e->getMessage(), "error");
        redirect('../dashboard/add_marriage.php');
    }

} else {
    redirect('../dashboard/marriages.php');
}
?>
