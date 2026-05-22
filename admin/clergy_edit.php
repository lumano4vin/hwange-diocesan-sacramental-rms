<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Edit Clergy Dossier
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_admin();

$user_id = $_GET['id'] ?? null;
if (!$user_id) redirect("clergy_list.php");

// Fetch User and Profile
$cleric = db_fetch("
    SELECT u.*, cp.*
    FROM users u
    LEFT JOIN clergy_profiles cp ON u.user_id = cp.user_id
    WHERE u.user_id = ?
", [$user_id]);

if (!$cleric) die("Cleric not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $full_canonical_name = $_POST['full_canonical_name'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?: null;
    $date_of_ordination = $_POST['date_of_ordination'] ?: null;
    $place_of_ordination = $_POST['place_of_ordination'] ?? '';
    $ordaining_bishop = $_POST['ordaining_bishop'] ?? '';
    $incardination_status = $_POST['incardination_status'] ?? '';
    $faculties_status = $_POST['faculties_status'] ?? '';
    $special_faculties = $_POST['special_faculties'] ?? '';
    $health_notes = $_POST['health_notes'] ?? '';
    $emergency_contact = $_POST['emergency_contact'] ?? '';

    // UPSERT profile
    $check = db_fetch("SELECT cleric_id FROM clergy_profiles WHERE user_id = ?", [$user_id]);
    if ($check) {
        $sql = "UPDATE clergy_profiles SET 
                title = ?, full_canonical_name = ?, date_of_birth = ?, date_of_ordination = ?, 
                place_of_ordination = ?, ordaining_bishop = ?, incardination_status = ?, 
                faculties_status = ?, special_faculties = ?, health_notes = ?, emergency_contact = ?
                WHERE user_id = ?";
        db_query($sql, [$title, $full_canonical_name, $date_of_birth, $date_of_ordination, $place_of_ordination, $ordaining_bishop, $incardination_status, $faculties_status, $special_faculties, $health_notes, $emergency_contact, $user_id]);
    } else {
        $sql = "INSERT INTO clergy_profiles (user_id, title, full_canonical_name, date_of_birth, date_of_ordination, place_of_ordination, ordaining_bishop, incardination_status, faculties_status, special_faculties, health_notes, emergency_contact)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        db_query($sql, [$user_id, $title, $full_canonical_name, $date_of_birth, $date_of_ordination, $place_of_ordination, $ordaining_bishop, $incardination_status, $faculties_status, $special_faculties, $health_notes, $emergency_contact]);
    }

    set_flash("Clergy dossier for " . $cleric['full_name'] . " has been updated.");
    redirect("clergy_dossier.php?id=$user_id");
}

$header_title = "Edit Dossier";
$header_subtitle = "Updating canonical records for " . h($cleric['full_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Dossier - <?php echo h($cleric['full_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0; max-width: 900px; margin: 0 auto;">
                <div class="card bg-card" style="padding: 3rem; border-radius: 2rem;">
                    <form method="POST" class="premium-form">
                        <div class="form-section">
                            <h3 style="color: var(--accent); margin-bottom: 2rem;">Identity & Ordination</h3>
                            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Title (e.g. Rev. Fr.)</label>
                                    <input type="text" name="title" value="<?php echo h($cleric['title'] ?: 'Rev. Fr.'); ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Full Canonical Name</label>
                                    <input type="text" name="full_canonical_name" value="<?php echo h($cleric['full_canonical_name'] ?: $cleric['full_name']); ?>" class="input-field">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" value="<?php echo $cleric['date_of_birth']; ?>" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date of Ordination</label>
                                    <input type="date" name="date_of_ordination" value="<?php echo $cleric['date_of_ordination']; ?>" class="input-field">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Ordaining Bishop</label>
                                    <input type="text" name="ordaining_bishop" value="<?php echo h($cleric['ordaining_bishop']); ?>" class="input-field" placeholder="e.g. Most Rev. Albert Serrano">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Place of Ordination</label>
                                    <input type="text" name="place_of_ordination" value="<?php echo h($cleric['place_of_ordination']); ?>" class="input-field">
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-top: 3rem;">
                            <h3 style="color: var(--accent); margin-bottom: 2rem;">Canonical Status & Faculties</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Incardination Status</label>
                                    <select name="incardination_status" class="input-field">
                                        <option value="Incardinated" <?php echo $cleric['incardination_status'] == 'Incardinated' ? 'selected' : ''; ?>>Incardinated (Diocesan)</option>
                                        <option value="Religious" <?php echo $cleric['incardination_status'] == 'Religious' ? 'selected' : ''; ?>>Religious Congregation</option>
                                        <option value="Extern" <?php echo $cleric['incardination_status'] == 'Extern' ? 'selected' : ''; ?>>Extern Priest</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Faculties Status</label>
                                    <select name="faculties_status" class="input-field">
                                        <option value="Active" <?php echo $cleric['faculties_status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Retired" <?php echo $cleric['faculties_status'] == 'Retired' ? 'selected' : ''; ?>>Retired / Emeriti</option>
                                        <option value="Restricted" <?php echo $cleric['faculties_status'] == 'Restricted' ? 'selected' : ''; ?>>Restricted / Suspended</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Special Faculties & Appointments</label>
                                <textarea name="special_faculties" rows="4" class="input-field" placeholder="e.g. Vocation Director, Chancellor, Marriage Tribunal Judge..."><?php echo h($cleric['special_faculties']); ?></textarea>
                            </div>
                        </div>

                        <div class="form-section" style="margin-top: 3rem;">
                            <h3 style="color: var(--accent); margin-bottom: 2rem;">Private Archive (Confidential)</h3>
                            <div class="form-group">
                                <label class="form-label">Health Observations</label>
                                <textarea name="health_notes" rows="2" class="input-field"><?php echo h($cleric['health_notes']); ?></textarea>
                            </div>
                            <div class="form-group" style="margin-top: 1.5rem;">
                                <label class="form-label">Emergency Contact Info</label>
                                <textarea name="emergency_contact" rows="2" class="input-field"><?php echo h($cleric['emergency_contact']); ?></textarea>
                            </div>
                        </div>

                        <div style="margin-top: 3rem; display: flex; justify-content: flex-end; gap: 1rem;">
                            <a href="clergy_dossier.php?id=<?php echo $user_id; ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem;">Save Canonical File</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
