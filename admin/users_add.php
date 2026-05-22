<?php
/**
 * Add New User with Auto-Generated Password
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();
if (!isDiocesanAdmin()) {
    die("Access Denied.");
}

$pdo = getDB();
$parishes = $pdo->query("SELECT id, name FROM parishes ORDER BY name")->fetchAll();
$deaneries = $pdo->query("SELECT id, name FROM deaneries ORDER BY name")->fetchAll();

$error = '';
$success_msg = '';
$temp_pass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? 'viewer';
    $parish_id = !empty($_POST['parish_id']) ? $_POST['parish_id'] : null;
    $deanery_id = !empty($_POST['deanery_id']) ? $_POST['deanery_id'] : null;
    
    if ($username && $email && $full_name) {
        $temp_pass = generateTempPassword();
        $hash = password_hash($temp_pass, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password_hash, email, full_name, role, parish_id, deanery_id, must_change_password)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$username, $hash, $email, $full_name, $role, $parish_id, $deanery_id]);
            $success_msg = "User created successfully!";
            logAction("Created user: $username");
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User - Hwange SRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0;">
                
                <div class="action-bar" style="margin-bottom: 2rem;">
                    <a href="users_list.php" class="btn btn-secondary"><ion-icon name="arrow-back-outline"></ion-icon> Back to List</a>
                </div>

                <div class="card bg-card" style="padding: 3rem; border-radius: 2rem;">
                    <h2 style="color: white; font-family: 'Outfit'; margin-bottom: 2rem;">Register New Staff Account</h2>

                    <?php if ($success_msg): ?>
                        <div style="background: var(--success); color: white; padding: 2rem; border-radius: 1.5rem; margin-bottom: 2rem; text-align: center;">
                            <ion-icon name="checkmark-circle" style="font-size: 3rem; margin-bottom: 1rem;"></ion-icon>
                            <h2 style="margin: 0;"><?= h($success_msg) ?></h2>
                            <p style="margin-top: 1rem; font-size: 1.25rem;">
                                Temporary Password: <strong style="background: rgba(0,0,0,0.2); padding: 5px 10px; border-radius: 4px; font-family: monospace;"><?= h($temp_pass) ?></strong>
                            </p>
                            <p style="font-size: 0.875rem; opacity: 0.9;">Please share this password with the user. They will be forced to change it upon login.</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-error"><?= h($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" class="entry-form">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" required placeholder="e.g. Maria Sibanda" class="input-field">
                            </div>
                            <div class="form-group">
                                <label>Official Email *</label>
                                <input type="email" name="email" required placeholder="name@hwangediocese.org" class="input-field">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label>Username *</label>
                                <input type="text" name="username" required placeholder="e.g. maria_s" class="input-field">
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" class="input-field">
                                    <option value="viewer">Viewer (Read Only)</option>
                                    <option value="deacon">Deacon</option>
                                    <option value="secretary">Parish Secretary</option>
                                    <option value="priest">Priest-in-Charge</option>
                                    <option value="admin">Diocesan Administrator</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 2rem;">
                            <div class="form-group">
                                <label>Assigned Parish (for Parish Staff)</label>
                                <select name="parish_id" class="input-field">
                                    <option value="">-- None / Diocese Level --</option>
                                    <?php 
                                    $all_p = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");
                                    foreach ($all_p as $p): 
                                    ?>
                                        <option value="<?= $p['parish_id'] ?>"><?= h($p['parish_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Security Notice</label>
                                <div style="padding: 1rem; background: rgba(251, 191, 36, 0.05); border-left: 3px solid var(--accent); border-radius: 8px;">
                                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Passwords are encrypted at rest. User must reset upon first entry.</p>
                                </div>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <button type="submit" class="btn btn-primary" style="padding: 1rem 2rem;">Create Account & Generate Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
