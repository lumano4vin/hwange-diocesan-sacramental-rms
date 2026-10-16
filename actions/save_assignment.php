<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Assignment - Clergy Movement Action
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page - Admin only
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_POST['user_id'] ?? null;
        $parish_id = $_POST['parish_id'] ?? null;
        $role = $_POST['role'] ?? 'Parish Priest';
        $start_date = $_POST['start_date'] ?? date('Y-m-d');

        if (!$user_id || !$parish_id) {
            throw new Exception("Missing required fields.");
        }

        // 1. Get the current active priest (outgoing) for this parish before deactivating
        // NOTE: Only if the role being assigned is 'Parish Priest' or 'Mission Administrator'
        $outgoing_priest_id = null;
        if (in_array($role, ['Parish Priest', 'Mission Administrator'])) {
            $current = $pdo->prepare("SELECT user_id FROM parish_assignments WHERE parish_id = ? AND role IN ('Parish Priest', 'Mission Administrator') AND status = 'Active'");
            $current->execute([$parish_id]);
            $outgoing_priest_id = $current->fetchColumn();
        }

        // 2. Deactivate outgoing priest's active assignment for this parish if being replaced
        if ($outgoing_priest_id && $outgoing_priest_id != $user_id) {
            $pdo->prepare("UPDATE parish_assignments SET status = 'Completed', end_date = ? WHERE user_id = ? AND parish_id = ? AND status = 'Active'")
                ->execute([$start_date, $outgoing_priest_id, $parish_id]);
            
            // Set the outgoing priest's users.parish_id to NULL
            $out_stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
            $out_stmt->execute([$outgoing_priest_id]);
            $out_role = strtolower($out_stmt->fetchColumn());
            if (in_array($out_role, ['priest', 'mission_admin'])) {
                $pdo->prepare("UPDATE users SET parish_id = NULL WHERE user_id = ?")
                    ->execute([$outgoing_priest_id]);
            }
        }

        // 3. Deactivate all existing active assignments for the incoming priest (they are moving FROM somewhere)
        $pdo->prepare("UPDATE parish_assignments SET status = 'Completed', end_date = ? WHERE user_id = ? AND status = 'Active'")
            ->execute([$start_date, $user_id]);

        // Clear the incoming priest's ID from any other parish's priest_in_charge_id
        $pdo->prepare("UPDATE parishes SET priest_in_charge_id = NULL WHERE priest_in_charge_id = ?")
            ->execute([$user_id]);

        // 4. Fetch incoming cleric's full_name and role
        $u_stmt = $pdo->prepare("SELECT full_name, role FROM users WHERE user_id = ?");
        $u_stmt->execute([$user_id]);
        $u_row = $u_stmt->fetch(PDO::FETCH_ASSOC);
        $priest_name_manual = $u_row['full_name'] ?? 'Assigned Cleric';
        $u_role = strtolower($u_row['role'] ?? 'priest');

        // Insert new assignment with priest_name_manual populated
        $sql = "INSERT INTO parish_assignments (user_id, parish_id, priest_name_manual, role, start_date, status, notes) VALUES (?, ?, ?, ?, ?, 'Active', ?)";
        $pdo->prepare($sql)->execute([$user_id, $parish_id, $priest_name_manual, $role, $start_date, "Assigned via Clergy Command Center"]);
        $new_assignment_id = $pdo->lastInsertId();

        // 5. Create Handover Protocol if it's a leadership role
        if (in_array($role, ['Parish Priest', 'Mission Administrator'])) {
            $handovers_sql = "INSERT INTO parish_handovers (parish_id, outgoing_priest_id, incoming_priest_id, assignment_id, status) VALUES (?, ?, ?, ?, 'Pending')";
            $pdo->prepare($handovers_sql)->execute([$parish_id, $outgoing_priest_id, $user_id, $new_assignment_id]);
        }

        // 6. Update user's parish_id
        if (in_array($u_role, ['priest', 'mission_admin', 'admin'])) {
            $pdo->prepare("UPDATE users SET parish_id = ? WHERE user_id = ?")
                ->execute([$parish_id, $user_id]);
        }

        // 7. Update parish's priest_in_charge_id if leadership role
        if (in_array($role, ['Parish Priest', 'Mission Administrator'])) {
            $pdo->prepare("UPDATE parishes SET priest_in_charge_id = ? WHERE parish_id = ?")
                ->execute([$user_id, $parish_id]);
        }

        // 8. Log action
        log_action($_SESSION['user_id'], 'assigned', 'parish_assignments', $new_assignment_id, "Assigned $priest_name_manual to parish_id $parish_id as $role");

        redirect("../admin/clergy_management.php?success=" . urlencode("Assignment updated successfully."));

    } catch (Exception $e) {
        redirect("../admin/clergy_management.php?error=" . urlencode($e->getMessage()));
    }
}
