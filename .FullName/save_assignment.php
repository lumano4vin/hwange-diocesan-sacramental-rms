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
            $current = $pdo->prepare("SELECT user_id FROM parish_assignments WHERE parish_id = ? AND role IN ('Parish Priest', 'Mission Administrator') AND status = 'Active'") ;
            $current->execute([$parish_id]);
            $outgoing_priest_id = $current->fetchColumn();
        }

        // 2. Deactivate all existing active assignments for this user (they are moving FROM somewhere)
        $pdo->prepare("UPDATE parish_assignments SET status = 'Completed', end_date = ? WHERE user_id = ? AND status = 'Active'")
            ->execute([$start_date, $user_id]);

        // 3. Insert new assignment
        $sql = "INSERT INTO parish_assignments (user_id, parish_id, role, start_date, status) VALUES (?, ?, ?, ?, 'Active')";
        $pdo->prepare($sql)->execute([$user_id, $parish_id, $role, $start_date]);
        $new_assignment_id = $pdo->lastInsertId();

        // 4. Create Handover Protocol if it's a leadership role
        if (in_array($role, ['Parish Priest', 'Mission Administrator'])) {
            $handovers_sql = "INSERT INTO parish_handovers (parish_id, outgoing_priest_id, incoming_priest_id, assignment_id, status) VALUES (?, ?, ?, ?, 'Pending')";
            $pdo->prepare($handovers_sql)->execute([$parish_id, $outgoing_priest_id, $user_id, $new_assignment_id]);
        }

        // 5. Log action
        log_action($_SESSION['user_id'], 'assigned', 'parish_assignments', $new_assignment_id);

        header("Location: ../admin/clergy_management.php?success=Assignment updated successfully.");
        exit;

    } catch (Exception $e) {
        header("Location: ../admin/clergy_management.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}
