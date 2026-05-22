<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Register Prenuptial Investigation (PNI)
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page - PNI is restricted to Clergy/Admins
require_login();
if (!is_cleric()) {
    set_flash("Access Denied: Only Clergy can conduct Prenuptial Investigations.", "error");
    redirect("../dashboard/index.php");
}

// Fetch parishioners for Groom and Bride
$parishioners = db_fetchAll("SELECT person_id, first_name, last_name, other_names, gender FROM parishioners WHERE status = 'Active' ORDER BY last_name, first_name");
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");

$preselected_groom = $_GET['groom_id'] ?? null;
$preselected_bride = $_GET['bride_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groom_id = $_POST['groom_id'] ?? null;
    $bride_id = $_POST['bride_id'] ?? null;
    $parish_id = $_POST['parish_id'] ?? null;
    $investigation_date = $_POST['investigation_date'] ?? '';
    
    // Checkboxes/Enums
    $groom_free = isset($_POST['groom_free_to_marry']) ? 1 : 0;
    $bride_free = isset($_POST['bride_free_to_marry']) ? 1 : 0;
    $groom_prev = isset($_POST['groom_previous_marriage']) ? 1 : 0;
    $bride_prev = isset($_POST['bride_previous_marriage']) ? 1 : 0;
    
    $c_unity = isset($_POST['consent_unity']) ? 1 : 0;
    $c_indissolubility = isset($_POST['consent_indissolubility']) ? 1 : 0;
    $c_procreation = isset($_POST['consent_procreation']) ? 1 : 0;
    
    $impediments = $_POST['impediments_noted'] ?? '';
    $dispensations = $_POST['dispensations_required'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $status = $_POST['status'] ?? 'Draft';
    $priest_user_id = $_SESSION['user_id'];
    
    $b_date1 = $_POST['banns_date_1'] ?: null;
    $b_date2 = $_POST['banns_date_2'] ?: null;
    $b_date3 = $_POST['banns_date_3'] ?: null;
    $b_parish = $_POST['banns_parish_id'] ?: null;

    if ($groom_id && $bride_id && $parish_id && $investigation_date) {
        try {
            $sql = "INSERT INTO prenuptial_investigations (
                        groom_id, bride_id, parish_id, priest_user_id, investigation_date,
                        groom_free_to_marry, bride_free_to_marry, groom_previous_marriage, bride_previous_marriage,
                        consent_unity, consent_indissolubility, consent_procreation,
                        banns_date_1, banns_date_2, banns_date_3, banns_parish_id,
                        impediments_noted, dispensations_required, status, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            db_query($sql, [
                $groom_id, $bride_id, $parish_id, $priest_user_id, $investigation_date,
                $groom_free, $bride_free, $groom_prev, $bride_prev,
                $c_unity, $c_indissolubility, $c_procreation,
                $b_date1, $b_date2, $b_date3, $b_parish,
                $impediments, $dispensations, $status, $notes
            ]);
            
            $new_id = db_fetch("SELECT last_insert_rowid() as id")['id'];
            log_audit($_SESSION['user_id'], 'CREATE', 'prenuptial_investigations', $new_id, "Completed PNI for Groom ID $groom_id and Bride ID $bride_id");
            
            set_flash("Prenuptial Investigation (PNI) record has been saved successfully.");
            redirect("marriage_pni_list.php");
        } catch (Exception $e) {
            $error = "System Error: " . $e->getMessage();
        }
    } else {
        $error = "Please provide all required canonical information.";
    }
}

$header_title = "Prenuptial Statement (PNI)";
$header_subtitle = "Canonical investigation of parties intending to contract Holy Matrimony.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PNI Registration - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=1.1">
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
            
            <div class="content-body" style="padding: 2rem 0; max-width: 1000px; margin: 0 auto;">
                
                <div class="action-bar" style="margin-bottom: 2rem;">
                    <a href="marriage_pni_list.php" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
                        <ion-icon name="arrow-back-outline"></ion-icon> Cancel & Return
                    </a>
                </div>

                <div class="card bg-card" style="padding: 3rem; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error"><?php echo h($error); ?></div>
                    <?php endif; ?>

                    <form action="marriage_pni_add.php" method="POST" class="premium-form">
                        
                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit'; color: var(--accent); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="people-outline"></ion-icon> Contracting Parties
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                <div class="form-group">
                                    <label class="form-label">Groom (Husband-to-be) *</label>
                                    <select name="groom_id" id="groom_id" required class="input-field" onchange="fetchSacraments(this.value, 'groom')">
                                        <option value="">-- Select Groom --</option>
                                        <?php foreach ($parishioners as $p): if($p['gender'] == 'Male'): ?>
                                            <option value="<?php echo $p['person_id']; ?>" <?php echo $preselected_groom == $p['person_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($p['last_name'] . ', ' . $p['first_name'] . ' ' . $p['other_names']); ?>
                                            </option>
                                        <?php endif; endforeach; ?>
                                    </select>
                                    <div id="groom_sacraments" class="sacrament-preview" style="margin-top: 1rem; font-size: 0.8rem; display: none;">
                                        <div class="sacrament-item" id="groom_baptism"></div>
                                        <div class="sacrament-item" id="groom_confirmation"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Bride (Wife-to-be) *</label>
                                    <select name="bride_id" id="bride_id" required class="input-field" onchange="fetchSacraments(this.value, 'bride')">
                                        <option value="">-- Select Bride --</option>
                                        <?php foreach ($parishioners as $p): if($p['gender'] == 'Female'): ?>
                                            <option value="<?php echo $p['person_id']; ?>" <?php echo $preselected_bride == $p['person_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($p['last_name'] . ', ' . $p['first_name'] . ' ' . $p['other_names']); ?>
                                            </option>
                                        <?php endif; endforeach; ?>
                                    </select>
                                    <div id="bride_sacraments" class="sacrament-preview" style="margin-top: 1rem; font-size: 0.8rem; display: none;">
                                        <div class="sacrament-item" id="bride_baptism"></div>
                                        <div class="sacrament-item" id="bride_confirmation"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem; background: rgba(255,255,255,0.02); padding: 2rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05);">
                            <h3 style="font-family: 'Outfit'; color: var(--accent); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="checkmark-done-circle-outline"></ion-icon> Freedom to Marry (Canon 1067)
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                <div class="checks-column">
                                    <p style="font-weight: 700; font-size: 0.9rem; margin-bottom: 1rem; color: white;">Groom's Declaration</p>
                                    <label style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px; cursor: pointer;">
                                        <input type="checkbox" name="groom_free_to_marry" checked style="width: 20px; height: 20px; accent-color: var(--accent);">
                                        <span>Confirmed: Free to Marry (No Impediments)</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="groom_previous_marriage" style="width: 20px; height: 20px; accent-color: var(--error);">
                                        <span>Previously Married? (Civil or Religious)</span>
                                    </label>
                                </div>
                                <div class="checks-column">
                                    <p style="font-weight: 700; font-size: 0.9rem; margin-bottom: 1rem; color: white;">Bride's Declaration</p>
                                    <label style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px; cursor: pointer;">
                                        <input type="checkbox" name="bride_free_to_marry" checked style="width: 20px; height: 20px; accent-color: var(--accent);">
                                        <span>Confirmed: Free to Marry (No Impediments)</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="bride_previous_marriage" style="width: 20px; height: 20px; accent-color: var(--error);">
                                        <span>Previously Married? (Civil or Religious)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit'; color: var(--accent); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="heart-half-outline"></ion-icon> Intentions & Consent
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                <label style="background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 1rem; display: flex; flex-direction: column; gap: 10px; cursor: pointer; border: 1px solid rgba(255,255,255,0.05);">
                                    <input type="checkbox" name="consent_unity" checked style="width: 20px; height: 20px;">
                                    <span style="font-weight: 700; font-size: 0.9rem;">UNITY</span>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">Understand and accept the unity of marriage.</span>
                                </label>
                                <label style="background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 1rem; display: flex; flex-direction: column; gap: 10px; cursor: pointer; border: 1px solid rgba(255,255,255,0.05);">
                                    <input type="checkbox" name="consent_indissolubility" checked style="width: 20px; height: 20px;">
                                    <span style="font-weight: 700; font-size: 0.9rem;">INDISSOLUBILITY</span>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">Understand marriage as a lifelong bond.</span>
                                </label>
                                <label style="background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 1rem; display: flex; flex-direction: column; gap: 10px; cursor: pointer; border: 1px solid rgba(255,255,255,0.05);">
                                    <input type="checkbox" name="consent_procreation" checked style="width: 20px; height: 20px;">
                                    <span style="font-weight: 700; font-size: 0.9rem;">PROCREATION</span>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">Acceptance of children and their education.</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem; background: rgba(251, 191, 36, 0.03); padding: 2rem; border-radius: 1.5rem; border: 1px solid rgba(251, 191, 36, 0.1);">
                            <h3 style="font-family: 'Outfit'; color: #fbbf24; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="megaphone-outline"></ion-icon> Publication of Banns
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">First Publication Date</label>
                                    <input type="date" name="banns_date_1" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Second Publication Date</label>
                                    <input type="date" name="banns_date_2" class="input-field">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Third Publication Date</label>
                                    <input type="date" name="banns_date_3" class="input-field">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Parish where Banns are Published</label>
                                <select name="banns_parish_id" class="input-field">
                                    <option value="">-- Select Parish --</option>
                                    <?php foreach ($parishes as $pa): ?>
                                        <option value="<?php echo $pa['parish_id']; ?>"><?php echo h($pa['parish_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit'; color: var(--accent); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="alert-circle-outline"></ion-icon> Impediments & Dispensations
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                <div class="form-group">
                                    <label class="form-label">Canonical Impediments (e.g. Consanguinity)</label>
                                    <textarea name="impediments_noted" class="input-field" rows="3" placeholder="Describe any impediments found or write 'NONE'"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Dispensations Required (e.g. Mixed Marriage)</label>
                                    <textarea name="dispensations_required" class="input-field" rows="3" placeholder="List dispensations granted by the Chancery"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit'; color: var(--accent); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="calendar-outline"></ion-icon> Administrative Details
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Investigation Date *</label>
                                    <input type="date" name="investigation_date" required class="input-field" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Parish / Mission *</label>
                                    <select name="parish_id" required class="input-field">
                                        <option value="">-- Select --</option>
                                        <?php foreach ($parishes as $pa): ?>
                                            <option value="<?php echo $pa['parish_id']; ?>" <?php echo (isset($_SESSION['parish_id']) && $_SESSION['parish_id'] == $pa['parish_id']) ? 'selected' : ''; ?>><?php echo h($pa['parish_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Process Status</label>
                                    <select name="status" class="input-field">
                                        <option value="Draft">In Progress (Draft)</option>
                                        <option value="Completed">Investigation Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" style="margin-top: 1.5rem;">
                                <label class="form-label">Confidential Notes</label>
                                <textarea name="notes" class="input-field" rows="3" placeholder="Additional observations by the priest..."></textarea>
                            </div>
                        </div>

                        <div class="form-footer" style="padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="submit" class="btn btn-primary" style="background: var(--accent); color: #000; font-weight: 800; padding: 1.25rem 3rem; border-radius: 1rem; border: none; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(56, 189, 248, 0.3);">Save Investigation Statement</button>
                        </div>
                    </form>

                </div>

            </div>
            <?php include '../includes/privacy_footer.php'; ?>
        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <script>
        function fetchSacraments(personId, type) {
            const previewDiv = document.getElementById(type + '_sacraments');
            const baptismDiv = document.getElementById(type + '_baptism');
            const confirmationDiv = document.getElementById(type + '_confirmation');

            if (!personId) {
                previewDiv.style.display = 'none';
                return;
            }

            fetch('../api/person_sacraments.php?person_id=' + personId)
                .then(response => response.json())
                .then(data => {
                    previewDiv.style.display = 'block';
                    
                    if (data.baptism) {
                        baptismDiv.innerHTML = `<span style="color: #10b981;">✔ Baptized:</span> ${data.baptism.date_of_baptism} (${data.baptism.parish_name})`;
                    } else {
                        baptismDiv.innerHTML = `<span style="color: #ef4444;">✘ No Baptism Record Found</span>`;
                    }

                    if (data.confirmation) {
                        confirmationDiv.innerHTML = `<span style="color: #10b981;">✔ Confirmed:</span> ${data.confirmation.date_of_confirmation} (${data.confirmation.parish_name})`;
                    } else {
                        confirmationDiv.innerHTML = `<span style="color: #ef4444;">✘ No Confirmation Record Found</span>`;
                    }
                });
        }

        // Trigger on load if preselected
        window.onload = () => {
            const gId = document.getElementById('groom_id').value;
            const bId = document.getElementById('bride_id').value;
            if (gId) fetchSacraments(gId, 'groom');
            if (bId) fetchSacraments(bId, 'bride');
        };
    </script>
    <?php include '../includes/privacy_footer.php'; ?>
</body>
</html>
