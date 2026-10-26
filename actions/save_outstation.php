<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Parish Outstation / Rural Mass Centre
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $outstation_id = isset($_POST['outstation_id']) ? (int)$_POST['outstation_id'] : 0;
    $outstation_name = trim($_POST['outstation_name'] ?? '');
    $patron_saint = trim($_POST['patron_saint'] ?? '');
    $distance_km = !empty($_POST['distance_km']) ? (float)$_POST['distance_km'] : null;
    $catechist_name = trim($_POST['catechist_name'] ?? '');
    $catechist_phone = trim($_POST['catechist_phone'] ?? '');
    $chairperson_name = trim($_POST['chairperson_name'] ?? '');
    $chairperson_phone = trim($_POST['chairperson_phone'] ?? '');
    $mass_schedule = trim($_POST['mass_schedule'] ?? '');
    $active_families_count = isset($_POST['active_families_count']) ? (int)$_POST['active_families_count'] : 0;
    $has_church_building = isset($_POST['has_church_building']) ? (int)$_POST['has_church_building'] : 1;
    $notes = trim($_POST['notes'] ?? '');
    $return_to = trim($_POST['return_to'] ?? '');

    // Determine Parish ID
    $parish_id = (int)($_SESSION['parish_id'] ?? 0);
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chancellor') {
        if (!empty($_POST['parish_id'])) {
            $parish_id = (int)$_POST['parish_id'];
        }
    }

    if ($parish_id <= 0) {
        set_flash("Invalid parish assignment.", "error");
        redirect($return_to ?: "dashboard/outstations.php");
        exit;
    }

    if (empty($outstation_name)) {
        set_flash("Outstation or Mass Centre name is required.", "error");
        redirect($return_to ?: "dashboard/outstations.php");
        exit;
    }

    try {
        if ($outstation_id > 0) {
            // Update Existing Outstation
            $existing = db_fetch("SELECT * FROM parish_outstations WHERE outstation_id = ?", [$outstation_id]);
            if (!$existing) {
                set_flash("Outstation record not found.", "error");
                redirect($return_to ?: "dashboard/outstations.php");
                exit;
            }

            if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chancellor' && (int)$existing['parish_id'] !== $parish_id) {
                set_flash("Unauthorized to edit outstations for this parish.", "error");
                redirect($return_to ?: "dashboard/outstations.php");
                exit;
            }

            $sql = "UPDATE parish_outstations SET 
                        outstation_name = ?, patron_saint = ?, distance_km = ?, catechist_name = ?,
                        catechist_phone = ?, chairperson_name = ?, chairperson_phone = ?,
                        mass_schedule = ?, active_families_count = ?, has_church_building = ?, notes = ?
                    WHERE outstation_id = ?";
            db_query($sql, [
                $outstation_name, $patron_saint, $distance_km, $catechist_name,
                $catechist_phone, $chairperson_name, $chairperson_phone,
                $mass_schedule, $active_families_count, $has_church_building, $notes, $outstation_id
            ]);

            log_audit($_SESSION['user_id'], 'UPDATE', 'parish_outstations', $outstation_id, 
                "Updated outstation: $outstation_name (Catechist: $catechist_name)");

            set_flash("Outstation profile updated successfully.", "success");
        } else {
            // Insert New Outstation
            $sql = "INSERT INTO parish_outstations 
                    (parish_id, outstation_name, patron_saint, distance_km, catechist_name, catechist_phone, chairperson_name, chairperson_phone, mass_schedule, active_families_count, has_church_building, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            db_query($sql, [
                $parish_id, $outstation_name, $patron_saint, $distance_km, $catechist_name,
                $catechist_phone, $chairperson_name, $chairperson_phone,
                $mass_schedule, $active_families_count, $has_church_building, $notes
            ]);

            $new_id = db_last_insert_id();

            log_audit($_SESSION['user_id'], 'CREATE', 'parish_outstations', $new_id, 
                "Registered new outstation: $outstation_name (Distance: {$distance_km}km)");

            set_flash("Outstation registered successfully.", "success");
        }

        redirect($return_to ?: "dashboard/outstations.php");
        exit;

    } catch (Exception $e) {
        set_flash("Database error: " . $e->getMessage(), "error");
        redirect($return_to ?: "dashboard/outstations.php");
        exit;
    }
}

redirect("dashboard/outstations.php");
exit;
