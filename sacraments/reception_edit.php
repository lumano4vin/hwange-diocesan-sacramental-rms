<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Edit Profession of Faith Record
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$id = $_GET['id'] ?? null;
if (!$id) {
    set_flash("Reception ID is required for editing.", "error");
    redirect("reception_list.php");
}

$record = db_fetch("SELECT * FROM receptions WHERE reception_id = ?", [$id]);

if (!$record) {
    set_flash("Canonical record not found.", "error");
    redirect("reception_list.php");
}

if (!has_record_permission($record, 'edit')) {
    set_flash("Access Denied: You do not have permission to edit this verified record.", "error");
    redirect("view_reception.php?id=" . $id);
}

$header_title = "Edit Profession of Faith";
$header_subtitle = "Update details for reception into full communion.";

$parishioners = db_fetchAll("SELECT person_id, first_name, last_name FROM parishioners ORDER BY last_name, first_name");
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person_id = $_POST['person_id'] ?? null;
    $parish_id = $_POST['parish_id'] ?? null;
    $date_of_reception = $_POST['date_of_reception'] ?? '';
    $minister = $_POST['minister'] ?? '';
    $previous_denomination = $_POST['previous_denomination'] ?? '';
    $godparents_sponsors = $_POST['godparents_sponsors'] ?? '';
    $register_book = $_POST['register_book_number'] ?? '';
    $page_number = $_POST['page_number'] ?? '';
    $entry_number = $_POST['entry_number'] ?? '';
    $status = $_POST['status'] ?? 'Draft';

    if ($person_id && $parish_id && $date_of_reception) {
        try {
            $sql = "UPDATE receptions SET 
                    person_id = ?, 
                    parish_id = ?, 
                    date_of_reception = ?, 
                    minister = ?, 
                    previous_denomination = ?, 
                    godparents_sponsors = ?, 
                    register_book_number = ?, 
                    page_number = ?, 
                    entry_number = ?,
                    status = ?
                    WHERE reception_id = ?";
            
            db_query($sql, [$person_id, $parish_id, $date_of_reception, $minister, $previous_denomination, $godparents_sponsors, $register_book, $page_number, $entry_number, $status, $id]);
            
            log_audit($_SESSION['user_id'], 'UPDATE', 'receptions', $id, "Updated reception record for id: $id");
            
            set_flash("Profession of Faith updated successfully.");
            redirect("view_reception.php?id=" . $id);
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
    <title>Edit Profession of Faith - Hwange Diocesan RMS</title>
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
                    <a href="view_reception.php?id=<?php echo $id; ?>" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
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
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #8b5cf6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="person-outline"></ion-icon> Subject Information
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
                                    <label>Parish of Reception *</label>
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
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #8b5cf6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="shield-half-outline"></ion-icon> Reception Details
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label>Date of Reception *</label>
                                    <input type="date" name="date_of_reception" required value="<?php echo h($record['date_of_reception']); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label>Previous Denomination</label>
                                    <input type="text" name="previous_denomination" value="<?php echo h($record['previous_denomination']); ?>" class="input-field">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label>Minister of Sacraments</label>
                                    <input type="text" name="minister" value="<?php echo h($record['minister']); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label>Sponsors / Witnesses</label>
                                    <input type="text" name="godparents_sponsors" value="<?php echo h($record['godparents_sponsors']); ?>" class="input-field">
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #8b5cf6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="archive-outline"></ion-icon> Registry & Status
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label>Book / Vol *</label>
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
                            <div class="form-group">
                                <label>Record Status</label>
                                <select name="status" class="input-field">
                                    <option value="Draft" <?php echo $record['status'] == 'Draft' ? 'selected' : ''; ?>>Draft (Pending Verification)</option>
                                    <option value="Verified" <?php echo $record['status'] == 'Verified' ? 'selected' : ''; ?>>Verified (Canonical Record)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-footer" style="padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="submit" class="btn btn-primary" style="background: #8b5cf6; color: #fff; font-weight: 800; padding: 1.25rem 3rem; border-radius: 1rem; border: none; font-size: 1.1rem;">Save Profession Updates</button>
                        </div>
                    </form>

                </div>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
