<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Edit Matrimonial Record - Modernized & Permission-Aware
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? null;
if (!$id) {
    set_flash("Marriage ID is required for editing.", "error");
    redirect("marriage_list.php");
}

// Fetch existing record
$record = db_fetch("SELECT * FROM marriages WHERE marriage_id = ?", [$id]);

if (!$record) {
    set_flash("Canonical record not found.", "error");
    redirect("marriage_list.php");
}

// Permission Check
if (!has_record_permission($record, 'edit')) {
    set_flash("Access Denied: You do not have permission to edit this verified record.", "error");
    redirect("view_marriage.php?id=" . $id);
}

// Header metadata
$header_title = "Edit Holy Matrimony";
$header_subtitle = "Modify canonical marriage details in the Diocesan archives.";

// Fetch parishioners (Grooms & Brides) and parishes
$grooms = db_fetchAll("SELECT person_id, first_name, last_name FROM parishioners WHERE gender = 'Male' AND status != 'Deceased' ORDER BY last_name, first_name");
$brides = db_fetchAll("SELECT person_id, first_name, last_name FROM parishioners WHERE gender = 'Female' AND status != 'Deceased' ORDER BY last_name, first_name");
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groom_id = $_POST['groom_person_id'] ?? null;
    $bride_id = $_POST['bride_person_id'] ?? null;
    $parish_id = $_POST['parish_id'] ?? null;
    $date_of_marriage = $_POST['date_of_marriage'] ?? '';
    $officiant = $_POST['officiant'] ?? '';
    $witnesses = $_POST['witnesses_names'] ?? '';
    $register_book = $_POST['register_book_number'] ?? '';
    $page_number = $_POST['page_number'] ?? '';
    $entry_number = $_POST['entry_number'] ?? '';
    $status = $_POST['status'] ?? 'Draft';

    if ($groom_id && $bride_id && $parish_id && $date_of_marriage) {
        try {
            $sql = "UPDATE marriages SET 
                    groom_person_id = ?, 
                    bride_person_id = ?, 
                    parish_id = ?, 
                    date_of_marriage = ?, 
                    officiant = ?, 
                    witnesses_names = ?, 
                    register_book_number = ?, 
                    page_number = ?, 
                    entry_number = ?,
                    status = ?
                    WHERE marriage_id = ?";
            
            db_query($sql, [$groom_id, $bride_id, $parish_id, $date_of_marriage, $officiant, $witnesses, $register_book, $page_number, $entry_number, $status, $id]);
            
            log_audit($_SESSION['user_id'], 'UPDATE', 'marriages', $id, "Updated matrimonial record for marriage_id: $id");
            
            set_flash("Matrimonial record updated successfully.");
            redirect("view_marriage.php?id=" . $id);
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
    <title>Edit Marriage - Hwange Diocesan RMS</title>
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
                    <a href="view_marriage.php?id=<?php echo $id; ?>" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
                        <ion-icon name="arrow-back-outline"></ion-icon> Cancel & Return
                    </a>
                    <?php if (is_admin()): ?>
                        <a href="../actions/delete_record.php?type=marriage&id=<?php echo $id; ?>" 
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
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #f472b6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="heart-outline"></ion-icon> The Contracting Parties
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Groom (Husband) *</label>
                                    <select name="groom_person_id" required class="input-field">
                                        <option value="">-- Select Groom --</option>
                                        <?php foreach ($grooms as $g): ?>
                                            <option value="<?php echo $g['person_id']; ?>" <?php echo $record['groom_person_id'] == $g['person_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($g['last_name'] . ', ' . $g['first_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Bride (Wife) *</label>
                                    <select name="bride_person_id" required class="input-field">
                                        <option value="">-- Select Bride --</option>
                                        <?php foreach ($brides as $b): ?>
                                            <option value="<?php echo $b['person_id']; ?>" <?php echo $record['bride_person_id'] == $b['person_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($b['last_name'] . ', ' . $b['first_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #f472b6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="calendar-outline"></ion-icon> Celebration Details
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Marriage *</label>
                                    <input type="date" name="date_of_marriage" required value="<?php echo h($record['date_of_marriage']); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Parish of Celebration *</label>
                                    <select name="parish_id" required class="input-field">
                                        <option value="">-- Select Parish --</option>
                                        <?php foreach ($parishes as $pa): ?>
                                            <option value="<?php echo $pa['parish_id']; ?>" <?php echo $record['parish_id'] == $pa['parish_id'] ? 'selected' : ''; ?>><?php echo h($pa['parish_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Minister of Sacraments</label>
                                    <input type="text" name="officiant" placeholder="e.g. Fr. J. Moyo" value="<?php echo h($record['officiant']); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Canonical Witnesses</label>
                                    <input type="text" name="witnesses_names" placeholder="Separated by commas" value="<?php echo h($record['witnesses_names']); ?>" class="input-field">
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #f472b6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="archive-outline"></ion-icon> Registry Archive & Status
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
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Entry Number *</label>
                                    <input type="text" name="entry_number" required pattern="^\d{4}/\d+$" title="Use strictly format YYYY/Number (e.g. 2026/045 or 1956/29)" placeholder="e.g. 2026/045" value="<?php echo h($record['entry_number']); ?>" class="input-field">
                                </div>
                            </div>
                            <div class="form-group">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Record Status</label>
                                <select name="status" class="input-field">
                                    <option value="Draft" <?php echo $record['status'] == 'Draft' ? 'selected' : ''; ?>>Draft (Pending Verification)</option>
                                    <option value="Verified" <?php echo $record['status'] == 'Verified' ? 'selected' : ''; ?>>Verified (Canonical Record)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-footer" style="padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="submit" class="btn btn-primary" style="background: #f472b6; color: white; font-weight: 800; padding: 1.25rem 3rem; border-radius: 1rem; border: none; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(244, 114, 182, 0.3);">Save Matrimonial Updates</button>
                        </div>
                    </form>

                </div>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
