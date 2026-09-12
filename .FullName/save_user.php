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
