<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Register New Parish/Mission
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_admin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['parish_name'] ?? '';
    $location = $_POST['location'] ?? '';
    $deanery = $_POST['deanery'] ?? '';
    $priest_id = $_POST['priest_in_charge_id'] ?? null;

    if ($name && $location && $deanery) {
        try {
            db_query("INSERT INTO parishes (parish_name, location, deanery, priest_in_charge_id) VALUES (?, ?, ?, ?)", 
                [$name, $location, $deanery, $priest_id]);
            
            $parish_id = db_fetch("SELECT parish_id FROM parishes WHERE parish_name = ? AND location = ?", [$name, $location])['parish_id'];
            
            if ($priest_id) {
                $priest = db_fetch("SELECT full_name FROM users WHERE user_id = ?", [$priest_id]);
                db_query("INSERT INTO parish_assignments (parish_id, user_id, priest_name_manual, start_date, status, notes) VALUES (?, ?, ?, ?, 'Active', ?)", 
                    [$parish_id, $priest_id, $priest['full_name'], date('Y-m-d'), "Initial assignment at parish registration"]);
            }

            $success = "Parish '$name' has been successfully registered.";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

$priests = db_fetchAll("SELECT user_id, full_name FROM users WHERE role = 'priest' ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Parish - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0; max-width: 800px; margin: 0 auto;">
                
                <div class="action-bar" style="margin-bottom: 2rem;">
                    <a href="parishes.php" class="btn btn-secondary"><ion-icon name="arrow-back-outline"></ion-icon> Back to Registry</a>
                </div>

                <div class="card bg-card" style="padding: 3rem; border-radius: 2rem;">
                    <h2 style="color: white; font-family: 'Outfit'; margin-bottom: 2rem;">Register New Parish / Mission</h2>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label style="display: block; color: var(--text-muted); margin-bottom: 8px;">Official Parish Name</label>
                            <input type="text" name="parish_name" required placeholder="e.g. St. John the Baptist (Dandanda)" style="width: 100%; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label style="display: block; color: var(--text-muted); margin-bottom: 8px;">Location / Village</label>
                                <input type="text" name="location" required placeholder="e.g. Dandanda" style="width: 100%; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                            </div>
                            <div class="form-group">
                                <label style="display: block; color: var(--text-muted); margin-bottom: 8px;">Deanery</label>
                                <select name="deanery" required style="width: 100%; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    <option value="Hwange">Hwange Deanery</option>
                                    <option value="Victoria Falls">Victoria Falls Deanery</option>
                                    <option value="Binga">Binga Deanery</option>
                                    <option value="Empumalanga">Empumalanga Deanery</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label style="display: block; color: var(--text-muted); margin-bottom: 8px;">Priest in Charge (Assignment)</label>
                            <select name="priest_in_charge_id" style="width: 100%; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                <option value="">-- Leave Unassigned for now --</option>
                                <?php foreach ($priests as $p): ?>
                                    <option value="<?php echo $p['user_id']; ?>"><?php echo h($p['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1.25rem;">Register Canonical Site</button>
                    </form>
                </div>

            </div>
        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
