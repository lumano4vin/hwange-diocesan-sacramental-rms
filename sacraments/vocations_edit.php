<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Edit Vocation Record (Ordination or Profession)
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$id = $_GET['id'] ?? null;
if (!$id) {
    set_flash("Record ID is required for editing.", "error");
    redirect("vocations_list.php");
}

$record = db_fetch("SELECT * FROM ordinations_professions WHERE record_id = ?", [$id]);

if (!$record) {
    set_flash("Canonical record not found.", "error");
    redirect("vocations_list.php");
}

// Check permission if status column exists (unlikely based on schema but being safe)
if (array_key_exists('status', $record) && !has_record_permission($record, 'edit')) {
    set_flash("Access Denied: You do not have permission to edit this verified record.", "error");
    redirect("vocations_list.php");
}

$header_title = "Edit Vocation Record";
$header_subtitle = "Update details for Holy Orders or Religious Profession.";

$parishioners = db_fetchAll("SELECT person_id, first_name, last_name FROM parishioners ORDER BY last_name, first_name");
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person_id = $_POST['person_id'] ?? null;
    $parish_id = $_POST['parish_id'] ?? null;
    $record_type = $_POST['record_type'] ?? '';
    $congregation = $_POST['congregation'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $celebrant_superior = $_POST['celebrant_superior'] ?? '';
    $place = $_POST['place'] ?? '';
    $register_book = $_POST['register_book_number'] ?? '';
    $page_number = $_POST['page_number'] ?? '';
    $entry_number = $_POST['entry_number'] ?? '';

    if ($person_id && $parish_id && $event_date && $record_type) {
        try {
            $sql = "UPDATE ordinations_professions SET 
                    person_id = ?, 
                    parish_id = ?, 
                    record_type = ?, 
                    congregation = ?, 
                    event_date = ?, 
                    celebrant_superior = ?, 
                    place = ?, 
                    register_book_number = ?, 
                    page_number = ?, 
                    entry_number = ?
                    WHERE record_id = ?";
            
            db_query($sql, [$person_id, $parish_id, $record_type, $congregation, $event_date, $celebrant_superior, $place, $register_book, $page_number, $entry_number, $id]);
            
            log_audit($_SESSION['user_id'], 'UPDATE', 'ordinations_professions', $id, "Updated vocation record ($record_type) for id: $id");
            
            set_flash("Vocation record updated successfully.");
            redirect("vocations_list.php");
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
    <title>Edit Vocation Record - Hwange Diocesan RMS</title>
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
                    <a href="vocations_list.php" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
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
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #34d399; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="person-outline"></ion-icon> Subject Information
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label>Faithful Subject *</label>
                                    <select name="person_id" required class="input-field">
                                        <option value="">-- Select Person --</option>
                                        <?php foreach ($parishioners as $p): ?>
                                            <option value="<?php echo $p['person_id']; ?>" <?php echo $record['person_id'] == $p['person_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($p['last_name'] . ', ' . $p['first_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Type of Record *</label>
                                    <select name="record_type" required class="input-field">
                                        <option value="Diaconate" <?php echo $record['record_type'] == 'Diaconate' ? 'selected' : ''; ?>>Diaconate (Ordination)</option>
                                        <option value="Priesthood" <?php echo $record['record_type'] == 'Priesthood' ? 'selected' : ''; ?>>Priesthood (Ordination)</option>
                                        <option value="Episcopate" <?php echo $record['record_type'] == 'Episcopate' ? 'selected' : ''; ?>>Episcopate (Consecration)</option>
                                        <option value="First Vows" <?php echo $record['record_type'] == 'First Vows' ? 'selected' : ''; ?>>First Vows (Religious)</option>
                                        <option value="Perpetual Profession" <?php echo $record['record_type'] == 'Perpetual Profession' ? 'selected' : ''; ?>>Perpetual Profession (Religious)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #34d399; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="ribbon-outline"></ion-icon> Vocation Details
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label>Date of Event *</label>
                                    <input type="date" name="event_date" required value="<?php echo h($record['event_date']); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label>Congregation (if religious)</label>
                                    <input type="text" name="congregation" value="<?php echo h($record['congregation']); ?>" class="input-field">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label>Celebrant / Superior</label>
                                    <input type="text" name="celebrant_superior" value="<?php echo h($record['celebrant_superior']); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label>Parish / Place of Event *</label>
                                    <select name="parish_id" required class="input-field">
                                        <option value="">-- Select Parish --</option>
                                        <?php foreach ($parishes as $pa): ?>
                                            <option value="<?php echo $pa['parish_id']; ?>" <?php echo $record['parish_id'] == $pa['parish_id'] ? 'selected' : ''; ?>><?php echo h($pa['parish_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Specific Place / Cathedral</label>
                                <input type="text" name="place" value="<?php echo h($record['place']); ?>" class="input-field">
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #34d399; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
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
                                    <label>Entry Number *</label>
                                    <input type="text" name="entry_number" required pattern="^\d{4}/\d+$" title="Use strictly format YYYY/Number (e.g. 2026/045 or 1956/29)" placeholder="e.g. 2026/045" value="<?php echo h($record['entry_number']); ?>" class="input-field">
                                </div>
                            </div>
                        </div>

                        <div class="form-footer" style="padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="submit" class="btn btn-primary" style="background: #34d399; color: #000; font-weight: 800; padding: 1.25rem 3rem; border-radius: 1rem; border: none; font-size: 1.1rem;">Save Vocation Updates</button>
                        </div>
                    </form>

                </div>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
