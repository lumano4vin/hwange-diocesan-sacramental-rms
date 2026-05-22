<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Edit First Holy Communion Record
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$id = $_GET['id'] ?? null;
if (!$id) {
    set_flash("Communion ID is required for editing.", "error");
    redirect("communion_list.php");
}

$record = db_fetch("SELECT * FROM first_holy_communions WHERE communion_id = ?", [$id]);

if (!$record) {
    set_flash("Canonical record not found.", "error");
    redirect("communion_list.php");
}

// First Holy Communion table doesn't have a 'status' column in schema, but view uses it.
// I'll check if I should handle status. The view_communion.php might show it if added later.
// Based on schema.sql, first_holy_communions lacks a status column.
// I'll skip permission check if status doesn't exist, or use a default if it does.
// Actually, I'll check if the column exists in the record.

$has_status = array_key_exists('status', $record);

if ($has_status && !has_record_permission($record, 'edit')) {
    set_flash("Access Denied: You do not have permission to edit this verified record.", "error");
    redirect("view_communion.php?id=" . $id);
}

$header_title = "Edit First Communion";
$header_subtitle = "Update canonical first holy communion details.";

$parishioners = db_fetchAll("SELECT person_id, first_name, last_name FROM parishioners ORDER BY last_name, first_name");
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person_id = $_POST['person_id'] ?? null;
    $parish_id = $_POST['parish_id'] ?? null;
    $date_of_communion = $_POST['date_of_communion'] ?? '';
    $minister_name = $_POST['minister_name'] ?? '';
    $register_book = $_POST['register_book_number'] ?? '';
    $page_number = $_POST['page_number'] ?? '';
    $entry_number = $_POST['entry_number'] ?? '';

    if ($person_id && $parish_id && $date_of_communion) {
        try {
            $sql = "UPDATE first_holy_communions SET 
                    person_id = ?, 
                    parish_id = ?, 
                    date_of_communion = ?, 
                    minister_name = ?, 
                    register_book_number = ?, 
                    page_number = ?, 
                    entry_number = ?
                    WHERE communion_id = ?";
            
            db_query($sql, [$person_id, $parish_id, $date_of_communion, $minister_name, $register_book, $page_number, $entry_number, $id]);
            
            log_audit($_SESSION['user_id'], 'UPDATE', 'first_holy_communions', $id, "Updated first communion record for id: $id");
            
            set_flash("First Communion record updated successfully.");
            redirect("view_communion.php?id=" . $id);
        } catch (Exception $e) {
            $error = "System Error: " . $e->getMessage();
        }
    } else {
        $error = "Please provide all required canonical information.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit First Communion - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <?php include '../includes/header.php'; ?>
            
            <div class="content-body" style="padding: 2rem 0; max-width: 900px; margin: 0 auto;">
                
                <div class="action-bar" style="margin-bottom: 2rem;">
                    <a href="view_communion.php?id=<?php echo $id; ?>" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
                        <ion-icon name="arrow-back-outline"></ion-icon> Cancel & Return
                    </a>
                </div>

                <div class="card bg-card" style="padding: 3rem; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05);">
                    
                    <?php if ($error): ?>
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: white; padding: 1rem; border-radius: 1rem; margin-bottom: 2rem; text-align: center;">
                            <?php echo h($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="premium-form">
                        
                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: var(--accent); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="nutrition-outline"></ion-icon> Subject Information
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label>Faithful Subject *</label>
                                    <select name="person_id" required class="input-field">
                                        <option value="">-- Select Parishioner --</option>
                                        <?php foreach ($parishioners as $p): ?>
                                            <option value="<?php echo $p['person_id']; ?>" <?php echo $record['person_id'] == $p['person_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($p['last_name'] . ', ' . $p['first_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Parish of Celebration *</label>
                                    <select name="parish_id" required class="input-field">
                                        <option value="">-- Select Parish --</option>
                                        <?php foreach ($parishes as $pa): ?>
                                            <option value="<?php echo $pa['parish_id']; ?>" <?php echo $record['parish_id'] == $pa['parish_id'] ? 'selected' : ''; ?>><?php echo h($pa['parish_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: var(--accent); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="calendar-outline"></ion-icon> Celebration Details
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label>Date of Communion *</label>
                                    <input type="date" name="date_of_communion" required value="<?php echo h($record['date_of_communion']); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label>Minister of Sacraments</label>
                                    <input type="text" name="minister_name" value="<?php echo h($record['minister_name']); ?>" class="input-field">
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: var(--accent); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="archive-outline"></ion-icon> Registry Location
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label>Book Number *</label>
                                    <select name="register_book_number" required class="input-field">
                                        <option value="">-- Select Volume --</option>
                                        <?php 
                                        $vols = ['Volume I', 'Volume II', 'Volume III', 'Volume IV', 'Volume V', 'Volume VI', 'Volume VII', 'Volume VIII', 'Volume IX', 'Volume X'];
                                        foreach($vols as $vol): 
                                        ?>
                                            <option value="<?php echo $vol; ?>" <?php echo $record['register_book_number'] === $vol ? 'selected' : ''; ?>><?php echo $vol; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Page *</label>
                                    <input type="number" name="page_number" min="1" required value="<?php echo h($record['page_number']); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label>Entry No. *</label>
                                    <input type="text" name="entry_number" required pattern="^\d{4}/\d+$" title="Use strictly format YYYY/Number (e.g. 2026/045 or 1956/29)" placeholder="e.g. 2026/045" value="<?php echo h($record['entry_number']); ?>" class="input-field">
                                </div>
                            </div>
                        </div>

                        <div class="form-footer" style="padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="submit" class="btn btn-primary" style="background: var(--accent); color: #000; font-weight: 800; padding: 1.25rem 3rem; border-radius: 1rem; border: none; font-size: 1.1rem;">Save Communion Updates</button>
                        </div>
                    </form>

                </div>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
