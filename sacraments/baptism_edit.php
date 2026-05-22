<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Edit Baptismal Record - Modernized & Permission-Aware
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? null;
if (!$id) {
    set_flash("Baptism ID is required for editing.", "error");
    redirect("baptism_list.php");
}

// Fetch existing record
$record = db_fetch("SELECT * FROM baptisms WHERE baptism_id = ?", [$id]);

if (!$record) {
    set_flash("Canonical record not found.", "error");
    redirect("baptism_list.php");
}

// Permission Check
if (!has_record_permission($record, 'edit')) {
    set_flash("Access Denied: You do not have permission to edit this verified record.", "error");
    redirect("view_baptism.php?id=" . $id);
}

// Header metadata
$header_title = "Edit Baptismal Record";
$header_subtitle = "Modify canonical baptismal details in the Diocesan archives.";

// Fetch parishioners and parishes for dropdowns
$parishioners = db_fetchAll("SELECT person_id, first_name, last_name FROM parishioners ORDER BY last_name, first_name");
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person_id = $_POST['person_id'] ?? null;
    $parish_id = $_POST['parish_id'] ?? null;
    $date_of_baptism = $_POST['date_of_baptism'] ?? '';
    $minister = $_POST['minister'] ?? '';
    $godparents = $_POST['godparents'] ?? '';
    $witnesses = $_POST['witnesses'] ?? '';
    $register_book = $_POST['register_book_number'] ?? '';
    $page_number = $_POST['page_number'] ?? '';
    $entry_number = $_POST['entry_number'] ?? '';
    $status = $_POST['status'] ?? 'Draft';

    if ($person_id && $parish_id && $date_of_baptism) {
        try {
            $sql = "UPDATE baptisms SET 
                    person_id = ?, 
                    parish_id = ?, 
                    date_of_baptism = ?, 
                    minister = ?, 
                    godparents = ?, 
                    witnesses = ?, 
                    register_book_number = ?, 
                    page_number = ?, 
                    entry_number = ?,
                    status = ?,
                    place_of_baptism = ?
                    WHERE baptism_id = ?";
            
            db_query($sql, [$person_id, $parish_id, $date_of_baptism, $minister, $godparents, $witnesses, $register_book, $page_number, $entry_number, $status, $_POST['place_of_baptism'] ?? '', $id]);
            
            log_audit($_SESSION['user_id'], 'UPDATE', 'baptisms', $id, "Updated baptismal record for person_id: $person_id");
            
            set_flash("Baptismal record updated successfully.");
            redirect("view_baptism.php?id=" . $id);
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
    <title>Edit Baptism - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Header -->
            <?php include '../includes/header.php'; ?>
            
            <div class="content-body" style="padding: 2rem 0; max-width: 900px; margin: 0 auto;">
                
                <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <a href="view_baptism.php?id=<?php echo $id; ?>" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
                        <ion-icon name="arrow-back-outline"></ion-icon> Cancel & Return
                    </a>
                    <?php if (is_admin()): ?>
                        <a href="../actions/delete_record.php?type=baptism&id=<?php echo $id; ?>" 
                           onclick="return confirm('WARNING: Permanent deletion of canonical records is logged and IRREVERSIBLE. Proceed?')" 
                           class="btn btn-danger" style="display: flex; align-items: center; gap: 8px;">
                            <ion-icon name="trash-outline"></ion-icon> Delete Record
                        </a>
                    <?php endif; ?>
                </div>

                <div class="card bg-card" style="padding: 3rem; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                    
                    <?php if ($error): ?>
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: white; padding: 1rem; border-radius: 1rem; margin-bottom: 2rem; text-align: center;">
                            <?php echo h($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="premium-form">
                        
                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #38bdf8; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="person-outline"></ion-icon> Primary Subject Information
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Faithful Subject *</label>
                                    <select name="person_id" required class="input-field">
                                        <option value="">-- Select Parishioner --</option>
                                        <?php foreach ($parishioners as $p): ?>
                                            <option value="<?php echo $p['person_id']; ?>" <?php echo $record['person_id'] == $p['person_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($p['last_name'] . ', ' . $p['first_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 8px;">Subject not listed? <a href="../parishioners/add.php" style="color: var(--accent);">Add to Registry First</a></p>
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Parish of Baptism *</label>
                                    <select name="parish_id" required class="input-field">
                                        <option value="">-- Select Parish --</option>
                                        <?php foreach ($parishes as $pa): ?>
                                            <option value="<?php echo $pa['parish_id']; ?>" <?php echo $record['parish_id'] == $pa['parish_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($pa['parish_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #38bdf8; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="water-outline"></ion-icon> Canonical Details
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Baptism *</label>
                                    <input type="date" name="date_of_baptism" required value="<?php echo h($record['date_of_baptism']); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Baptism (Specific Location)</label>
                                    <input type="text" name="place_of_baptism" value="<?php echo h($record['place_of_baptism'] ?? ''); ?>" placeholder="e.g. St. Luke Mission / Hospital" class="input-field">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Minister of Sacraments</label>
                                    <input type="text" name="minister" placeholder="e.g. Fr. J. Moyo" value="<?php echo h($record['minister']); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Godparents</label>
                                    <input type="text" name="godparents" value="<?php echo h($record['godparents']); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Witnesses</label>
                                    <input type="text" name="witnesses" value="<?php echo h($record['witnesses']); ?>" class="input-field">
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #38bdf8; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="archive-outline"></ion-icon> Registry Reference & Status
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Book Number *</label>
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
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Page *</label>
                                    <input type="number" name="page_number" min="1" required value="<?php echo h($record['page_number']); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Entry No. *</label>
                                    <input type="text" name="entry_number" required pattern="^\d{4}/\d+$" title="Use strictly format YYYY/Number (e.g. 2026/045 or 1956/29)" placeholder="e.g. 2026/045" value="<?php echo h($record['entry_number']); ?>" class="input-field">
                                </div>
                            </div>
                            <div class="form-group">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Record Status</label>
                                <select name="status" class="input-field">
                                    <option value="Draft" <?php echo $record['status'] == 'Draft' ? 'selected' : ''; ?>>Draft (Pending Verification)</option>
                                    <option value="Verified" <?php echo $record['status'] == 'Verified' ? 'selected' : ''; ?>>Verified (Canonical Record)</option>
                                    <option value="Conditional" <?php echo $record['status'] == 'Conditional' ? 'selected' : ''; ?>>Conditional Baptism</option>
                                    <option value="Private" <?php echo $record['status'] == 'Private' ? 'selected' : ''; ?>>Private (Restricted)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-footer" style="padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="submit" class="btn btn-primary" style="background: #38bdf8; color: #000; font-weight: 800; padding: 1.25rem 3rem; border-radius: 1rem; border: none; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(56, 189, 248, 0.3);">Save Canonical Updates</button>
                        </div>
                    </form>

                </div>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
