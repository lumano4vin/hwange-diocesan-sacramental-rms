<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Update Staff Account - Administrative
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Secure the endpoint (Admin Only)
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = !empty(trim($_POST['email'] ?? '')) ? trim($_POST['email']) : null;
    $role = $_POST['role'];
    $parish_id = !empty($_POST['parish_id']) ? $_POST['parish_id'] : null;
    $status = $_POST['status'] ?? 'active';
    $new_password = $_POST['new_password'];

    try {
        // Fetch previous role and parish_id to detect changes
        $old_user = db_fetch("SELECT role, parish_id FROM users WHERE user_id = ?", [$user_id]);
        $old_parish_id = $old_user ? $old_user['parish_id'] : null;
        $old_role = $old_user ? strtolower($old_user['role']) : '';

        // 1. Basic Update (Always update these fields)
        $sql = "UPDATE users SET full_name = ?, username = ?, email = ?, role = ?, parish_id = ?, status = ? WHERE user_id = ?";
        $params = [$full_name, $username, $email, $role, $parish_id, $status, $user_id];
        db_query($sql, $params);

        // Sync parish assignments and priest_in_charge_id
        $new_role_lower = strtolower($role);
        $parish_changed = ($old_parish_id != $parish_id);

        if (in_array($new_role_lower, ['priest', 'mission_admin']) && $parish_changed) {
            $today = date('Y-m-d');
            
            // A. Deactivate all existing active assignments for this user
            db_query("UPDATE parish_assignments SET status = 'Completed', end_date = ? WHERE user_id = ? AND status = 'Active'", [$today, $user_id]);
            
            // B. If they were previously in charge of a parish, clear that parish's priest_in_charge_id
            if ($old_parish_id) {
                db_query("UPDATE parishes SET priest_in_charge_id = NULL WHERE parish_id = ? AND priest_in_charge_id = ?", [$old_parish_id, $user_id]);
            }
            
            // C. If a new parish is assigned
            if ($parish_id) {
                // Determine assignment role
                $assign_role = ($new_role_lower === 'priest') ? 'Parish Priest' : 'Mission Administrator';
                
                // Get current active priest in charge of the new parish
                $outgoing_priest_id = db_fetch("SELECT user_id FROM parish_assignments WHERE parish_id = ? AND role IN ('Parish Priest', 'Mission Administrator') AND status = 'Active'", [$parish_id]);
                $outgoing_priest_id = $outgoing_priest_id ? $outgoing_priest_id['user_id'] : null;
                
                if ($outgoing_priest_id && $outgoing_priest_id != $user_id) {
                    // Deactivate outgoing priest's assignment
                    db_query("UPDATE parish_assignments SET status = 'Completed', end_date = ? WHERE user_id = ? AND parish_id = ? AND status = 'Active'", [$today, $outgoing_priest_id, $parish_id]);
                    // Set outgoing priest's users.parish_id to NULL
                    db_query("UPDATE users SET parish_id = NULL WHERE user_id = ?", [$outgoing_priest_id]);
                }
                
                // Insert new active assignment
                db_query("INSERT INTO parish_assignments (user_id, parish_id, role, start_date, status, notes) VALUES (?, ?, ?, ?, 'Active', ?)", [$user_id, $parish_id, $assign_role, $today, "Assigned via user profile update"]);
                $new_assignment_id = db_lastInsertId();
                
                // Create handover protocol
                db_query("INSERT INTO parish_handovers (parish_id, outgoing_priest_id, incoming_priest_id, assignment_id, status) VALUES (?, ?, ?, ?, 'Pending')", [$parish_id, $outgoing_priest_id, $user_id, $new_assignment_id]);
                
                // Set the new parish's priest_in_charge_id
                db_query("UPDATE parishes SET priest_in_charge_id = ? WHERE parish_id = ?", [$user_id, $parish_id]);
            }
        } elseif (!in_array($new_role_lower, ['priest', 'mission_admin']) && in_array($old_role, ['priest', 'mission_admin'])) {
            $today = date('Y-m-d');
            db_query("UPDATE parish_assignments SET status = 'Completed', end_date = ? WHERE user_id = ? AND status = 'Active'", [$today, $user_id]);
            if ($old_parish_id) {
                db_query("UPDATE parishes SET priest_in_charge_id = NULL WHERE parish_id = ? AND priest_in_charge_id = ?", [$old_parish_id, $user_id]);
            }
        }

        // 2. Optional Password Update (If a new password is provided)
        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            db_query("UPDATE users SET password_hash = ? WHERE user_id = ?", [$password_hash, $user_id]);
        }

        // Redirect back with success
        header("Location: ../admin/users_list.php?msg=Staff+updated+successfully");
        exit;

    } catch (Exception $e) {
        die("Error updating staff member: " . $e->getMessage());
    }
} else {
    header("Location: ../admin/users_list.php");
    exit;
}
