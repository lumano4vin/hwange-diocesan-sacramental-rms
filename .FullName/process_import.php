<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Bulk CSV Processing Logic
 * 
 * Securely imports legacy baptismal records and generates verification hashes.
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the action - ADMIN ONLY
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    
    $parish_id = $_POST['parish_id'];
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (!is_uploaded_file($file)) {
        set_flash("Invalid file upload.", "error");
        redirect('../dashboard/import_records.php');
    }

    $handle = fopen($file, "r");
    $headers = fgetcsv($handle); // Skip header row
    
    $row_count = 0;
    
    try {
        $pdo->beginTransaction();
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            // Expected columns: first_name, last_name, dob, baptism_date, minister (optional), godparents (optional)
            $first_name = trim($data[0] ?? '');
            $last_name = trim($data[1] ?? '');
            $dob = $data[2] ?? NULL;
            $baptism_date = $data[3] ?? NULL;
            $minister = $data[4] ?? 'Unknown Minister (Legacy)';
            $godparents = $data[5] ?? 'Not recorded in legacy archive';
            
            if (empty($first_name) || empty($last_name)) continue;

            // 1. Create Parishioner
            $sql_p = "INSERT INTO parishioners (first_name, last_name, dob, current_parish_id) VALUES (?, ?, ?, ?)";
            db_query($sql_p, [$first_name, $last_name, $dob, $parish_id]);
            $person_id = $pdo->lastInsertId();

            // 2. Create Baptism with Hash
            $verification_hash = bin2hex(random_bytes(16));
            $sql_b = "INSERT INTO baptisms (person_id, parish_id, date_of_baptism, minister, godparents, verification_hash) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            db_query($sql_b, [$person_id, $parish_id, $baptism_date, $minister, $godparents, $verification_hash]);
            
            $row_count++;
        }
        
        $pdo->commit();
        
        // Log the bulk action
        log_audit($_SESSION['user_id'], 'IMPORT', 'baptisms', 0, "Bulk legacy import of $row_count records executed successfully.");
        
        redirect("../dashboard/import_records.php?success=1&count=$row_count");

    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash("Archival Import Failed: " . $e->getMessage(), "error");
        redirect('../dashboard/import_records.php');
    } finally {
        fclose($handle);
    }

} else {
    redirect('../dashboard/import_records.php');
}
?>
