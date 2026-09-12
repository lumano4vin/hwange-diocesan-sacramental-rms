<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Canonical Verification Action
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;

if (empty($type) || empty($id)) {
    redirect('../dashboard/index.php');
}

// Map types to tables
$tables = [
    'baptism' => ['table' => 'baptisms', 'pk' => 'baptism_id', 'redirect' => 'view_baptism.php'],
    'marriage' => ['table' => 'marriages', 'pk' => 'marriage_id', 'redirect' => 'view_marriage.php'],
    'confirmation' => ['table' => 'confirmations', 'pk' => 'confirmation_id', 'redirect' => 'confirmations.php'], // Assuming view page or list
    'death' => ['table' => 'deaths', 'pk' => 'death_id', 'redirect' => 'deaths.php'],
    'ordination' => ['table' => 'ordinations_professions', 'pk' => 'record_id', 'redirect' => 'ordinations.php']
];

if (!isset($tables[$type])) {
    set_flash("Invalid record type specified.", "error");
    redirect('../dashboard/index.php');
}

$config = $tables[$type];
$table = $config['table'];
$pk = $config['pk'];

// 1. Fetch Record
$record = db_fetch("SELECT * FROM $table WHERE $pk = ?", [$id]);

if (!$record) {
    set_flash("Record not found.", "error");
    redirect("../dashboard/{$config['redirect']}");
}

// 2. Check Permission
if (!has_record_permission($record, 'verify')) {
    set_flash("Access Denied: You do not have permission to verify this record.", "error");
    redirect("../dashboard/{$config['redirect']}?id=$id");
}

// 3. Update Status
try {
    $sql = "UPDATE $table SET status = 'Verified', verified_by = ?, verified_at = CURRENT_TIMESTAMP WHERE $pk = ?";
    db_query($sql, [$_SESSION['user_id'], $id]);
    
    // Also verify any associated notations if it's a baptism-related event?
    // For now, only verify the main record.
    
    log_audit($_SESSION['user_id'], 'VERIFY', $table, $id, "Canonically verified $type record #$id");
    
    set_flash("Record verified successfully! It is now canonized in the register.", "success");
} catch (Exception $e) {
    set_flash("System Error: " . $e->getMessage(), "error");
}

redirect("../dashboard/{$config['redirect']}?id=$id");
