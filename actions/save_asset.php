<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Save Parish Asset / Temporal Good (Canon 1283)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_id = isset($_POST['asset_id']) ? (int)$_POST['asset_id'] : 0;
    $item_name = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? 'Furniture & Equipment');
    $serial_or_reg_number = trim($_POST['serial_or_reg_number'] ?? '');
    $location_stored = trim($_POST['location_stored'] ?? '');
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    $acquisition_date = !empty($_POST['acquisition_date']) ? trim($_POST['acquisition_date']) : null;
    $estimated_value = isset($_POST['estimated_value']) ? (float)$_POST['estimated_value'] : 0.0;
    $currency = trim($_POST['currency'] ?? 'USD');
    $current_status = trim($_POST['current_status'] ?? 'Good');
    $is_sacred = !empty($_POST['is_sacred']) ? 1 : 0;
    $description = trim($_POST['description'] ?? '');
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
        redirect($return_to ?: "dashboard/assets.php");
        exit;
    }

    if (empty($item_name)) {
        set_flash("Asset name is required.", "error");
        redirect($return_to ?: "dashboard/assets.php");
        exit;
    }

    // Optional Photo Upload
    $photo_path = null;
    if (!empty($_FILES['asset_photo']['name']) && $_FILES['asset_photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['asset_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_exts) && $_FILES['asset_photo']['size'] <= 10 * 1024 * 1024) {
            $upload_dir = dirname(__DIR__) . '/uploads/assets';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0777, true);
            }
            $safe_name = 'asset_' . $parish_id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            if (move_uploaded_file($_FILES['asset_photo']['tmp_name'], $upload_dir . '/' . $safe_name)) {
                $photo_path = 'uploads/assets/' . $safe_name;
            }
        }
    }

    try {
        if ($asset_id > 0) {
            // Update Existing Asset
            $existing = db_fetch("SELECT * FROM parish_assets WHERE id = ?", [$asset_id]);
            if (!$existing) {
                set_flash("Asset record not found.", "error");
                redirect($return_to ?: "dashboard/assets.php");
                exit;
            }

            // Authorization check
            if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chancellor' && (int)$existing['parish_id'] !== $parish_id) {
                set_flash("Unauthorized to edit assets for this parish.", "error");
                redirect($return_to ?: "dashboard/assets.php");
                exit;
            }

            $final_photo = $photo_path !== null ? $photo_path : ($existing['photo_path'] ?? null);

            $sql = "UPDATE parish_assets SET 
                        item_name = ?, category = ?, serial_or_reg_number = ?, location_stored = ?,
                        quantity = ?, acquisition_date = ?, estimated_value = ?, currency = ?,
                        current_status = ?, is_sacred = ?, description = ?, photo_path = ?
                    WHERE id = ?";
            db_query($sql, [
                $item_name, $category, $serial_or_reg_number, $location_stored,
                $quantity, $acquisition_date, $estimated_value, $currency,
                $current_status, $is_sacred, $description, $final_photo, $asset_id
            ]);

            log_audit($_SESSION['user_id'], 'UPDATE', 'parish_assets', $asset_id, 
                "Updated parish asset: $item_name [$category] (Qty: $quantity)");

            set_flash("Asset inventory record updated successfully.", "success");
        } else {
            // Insert New Asset
            $sql = "INSERT INTO parish_assets 
                    (parish_id, item_name, category, serial_or_reg_number, location_stored, quantity, acquisition_date, estimated_value, currency, current_status, is_sacred, description, photo_path, recorded_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            db_query($sql, [
                $parish_id, $item_name, $category, $serial_or_reg_number, $location_stored,
                $quantity, $acquisition_date, $estimated_value, $currency,
                $current_status, $is_sacred, $description, $photo_path, $_SESSION['user_id']
            ]);

            $new_id = db_last_insert_id();

            log_audit($_SESSION['user_id'], 'CREATE', 'parish_assets', $new_id, 
                "Recorded new parish asset: $item_name [$category] (Qty: $quantity)");

            set_flash("Asset recorded in Parish Inventory successfully.", "success");
        }

        redirect($return_to ?: "dashboard/assets.php");
        exit;

    } catch (Exception $e) {
        set_flash("Database error: " . $e->getMessage(), "error");
        redirect($return_to ?: "dashboard/assets.php");
        exit;
    }
}

redirect("dashboard/assets.php");
exit;
