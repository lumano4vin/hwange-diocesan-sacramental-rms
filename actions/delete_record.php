<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Universal Deletion Handler - Restricted to Diocesan Admin
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Strict Admin Only
require_admin();

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? null;

if (!$type || !$id) {
    set_flash("Invalid deletion request.", "error");
    redirect("../dashboard/index.php");
}

$table = '';
$redirect_to = '';
$pk = '';

switch ($type) {
    case 'baptism':
        $table = 'baptisms';
        $pk = 'baptism_id';
        $redirect_to = '../sacraments/baptism_list.php';
        break;
    case 'marriage':
        $table = 'marriages';
        $pk = 'marriage_id';
        $redirect_to = '../sacraments/marriage_list.php';
        break;
    case 'confirmation':
        $table = 'confirmations';
        $pk = 'confirmation_id';
        $redirect_to = '../sacraments/confirmation_list.php';
        break;
    case 'reception':
        $table = 'receptions';
        $pk = 'reception_id';
        $redirect_to = '../sacraments/reception_list.php';
        break;
    case 'death':
    case 'burial':
        $table = 'deaths';
        $pk = 'death_id';
        $redirect_to = '../sacraments/burial_list.php';
        break;
    case 'communion':
        $table = 'first_holy_communions';
        $pk = 'communion_id';
        $redirect_to = '../sacraments/communion_list.php';
        break;
    case 'ordination':
        $table = 'ordinations_professions';
        $pk = 'ordination_id';
        $redirect_to = '../sacraments/ordination_list.php';
        break;
    case 'pni':
        $table = 'prenuptial_investigations';
        $pk = 'pni_id';
        $redirect_to = '../sacraments/marriage_pni_list.php';
        break;
    case 'parishioner':
        $table = 'parishioners';
        $pk = 'person_id';
        $redirect_to = '../parishioners.php';
        break;
    default:
        set_flash("Unsupported record type for deletion.", "error");
        redirect("../dashboard/index.php");
}

// Perform Deletion
try {
    // Fetch details for logging
    $record = db_fetch("SELECT * FROM $table WHERE $pk = ?", [$id]);
    if (!$record) {
        set_flash("Record not found.", "error");
        redirect($redirect_to);
    }

    $sql = "DELETE FROM $table WHERE $pk = ?";
    db_query($sql, [$id]);

    // Audit Log
    log_audit($_SESSION['user_id'], 'DELETE', $table, $id, "Admin deleted canonical record. Previous data: " . json_encode($record));

    set_flash("Canonical record successfully purged from the archives.", "success");
    redirect($redirect_to);
} catch (Exception $e) {
    set_flash("Deletion failed: " . $e->getMessage(), "error");
    redirect($redirect_to);
}
