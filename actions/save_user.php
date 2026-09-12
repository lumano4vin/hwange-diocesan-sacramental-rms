<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Staff Account Logic
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page (Admin Only)
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Sanitize Data
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = !empty(trim($_POST['email'] ?? '')) ? trim($_POST['email']) : null;
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $parish_id = !empty($_POST['parish_id']) ? $_POST['parish_id'] : null;

    // 2. Validate Password Length
    if (strlen($password) < 8) {
        set_flash("Password must be at least 8 characters long.", "error");
        redirect('../dashboard/add_user.php');
    }

    try {
        // 3. Hash Password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // 4. Check for duplicate username
        $check = db_fetch("SELECT user_id FROM users WHERE username = ?", [$username]);
        if ($check) {
            set_flash("Username already exists. Please choose another.", "error");
            redirect('../dashboard/add_user.php');
        }

        // 5. Save User
        $sql = "INSERT INTO users (username, password_hash, full_name, email, role, parish_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        db_query($sql, [$username, $password_hash, $full_name, $email, $role, $parish_id]);

        $new_user_id = db_lastInsertId();
        $new_role_lower = strtolower($role);
        
        if (in_array($new_role_lower, ['priest', 'mission_admin']) && $parish_id) {
            $today = date('Y-m-d');
            $assign_role = ($new_role_lower === 'priest') ? 'Parish Priest' : 'Mission Administrator';
            
            // Get current active priest in charge of the new parish
            $outgoing_priest_id = db_fetch("SELECT user_id FROM parish_assignments WHERE parish_id = ? AND role IN ('Parish Priest', 'Mission Administrator') AND status = 'Active'", [$parish_id]);
            $outgoing_priest_id = $outgoing_priest_id ? $outgoing_priest_id['user_id'] : null;
            
            if ($outgoing_priest_id) {
                // Deactivate outgoing priest's assignment
                db_query("UPDATE parish_assignments SET status = 'Completed', end_date = ? WHERE user_id = ? AND parish_id = ? AND status = 'Active'", [$today, $outgoing_priest_id, $parish_id]);
                // Set outgoing priest's users.parish_id to NULL
                db_query("UPDATE users SET parish_id = NULL WHERE user_id = ?", [$outgoing_priest_id]);
            }
            
            // Insert new active assignment
            db_query("INSERT INTO parish_assignments (user_id, parish_id, role, start_date, status, notes) VALUES (?, ?, ?, ?, 'Active', ?)", [$new_user_id, $parish_id, $assign_role, $today, "Initial assignment at account registration"]);
            $new_assignment_id = db_lastInsertId();
            
            // Create handover protocol
            db_query("INSERT INTO parish_handovers (parish_id, outgoing_priest_id, incoming_priest_id, assignment_id, status) VALUES (?, ?, ?, ?, 'Pending')", [$parish_id, $outgoing_priest_id, $new_user_id, $new_assignment_id]);
            
            // Set the new parish's priest_in_charge_id
            db_query("UPDATE parishes SET priest_in_charge_id = ? WHERE parish_id = ?", [$new_user_id, $parish_id]);
        }

        set_flash("Staff account for <b>$full_name</b> created successfully.", "success");
        redirect('../admin/users_list.php');

    } catch (Exception $e) {
        set_flash("Error creating account: " . $e->getMessage(), "error");
        redirect('../dashboard/add_user.php');
    }

} else {
    redirect('../admin/users_list.php');
}
?>
