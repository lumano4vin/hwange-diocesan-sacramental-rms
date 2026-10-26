<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Delete Parish Asset / Temporal Good
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_id = isset($_POST['asset_id']) ? (int)$_POST['asset_id'] : 0;
    $return_to = trim($_POST['return_to'] ?? '');

    if ($asset_id <= 0) {
        set_flash("Invalid asset specified.", "error");
        redirect($return_to ?: "dashboard/assets.php");
        exit;
    }

    $asset = db_fetch("SELECT * FROM parish_assets WHERE id = ?", [$asset_id]);
    if (!$asset) {
        set_flash("Asset record not found.", "error");
        redirect($return_to ?: "dashboard/assets.php");
        exit;
    }

    $user_parish_id = (int)($_SESSION['parish_id'] ?? 0);
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chancellor' && (int)$asset['parish_id'] !== $user_parish_id) {
        set_flash("Unauthorized to delete assets for this parish.", "error");
        redirect($return_to ?: "dashboard/assets.php");
        exit;
    }

    try {
        db_query("DELETE FROM parish_assets WHERE id = ?", [$asset_id]);

        log_audit($_SESSION['user_id'], 'DELETE', 'parish_assets', $asset_id, 
            "Deleted parish asset: {$asset['item_name']} [{$asset['category']}]");

        set_flash("Asset removed from inventory.", "success");
    } catch (Exception $e) {
        set_flash("Failed to delete asset: " . $e->getMessage(), "error");
    }

    redirect($return_to ?: "dashboard/assets.php");
    exit;
}

redirect("dashboard/assets.php");
exit;
