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
    
    $error = '';

    // Handle Unregistered Groom
    $groom_registration_type = $_POST['groom_registration_type'] ?? 'registered';
    if ($groom_registration_type === 'unregistered') {
        $g_first = upper($_POST['groom_unregistered_first_name'] ?? '');
        $g_other = upper($_POST['groom_unregistered_other_names'] ?? '');
        $g_last = upper($_POST['groom_unregistered_last_name'] ?? '');
        $g_dob = $_POST['groom_unregistered_dob'] ?? '';
        $g_pob = upper($_POST['groom_unregistered_place_of_birth'] ?? '');
        $g_father = upper($_POST['groom_unregistered_father_name'] ?? '');
        $g_mother = upper($_POST['groom_unregistered_mother_name'] ?? '');
        $g_baptism = upper($_POST['groom_unregistered_place_of_baptism'] ?? '');
        
        if ($g_first && $g_last && $g_dob) {
            try {
                $guid = bin2hex(random_bytes(16));
                $diocese_id = $primary_diocese['diocese_id'] ?? 1;
                
                $sql_p = "INSERT INTO parishioners (first_name, other_names, last_name, gender, dob, place_of_birth, father_name, mother_name, current_parish_id, status, guid, diocese_id, place_of_baptism) 
                          VALUES (?, ?, ?, 'Male', ?, ?, ?, ?, ?, 'Active', ?, ?, ?)";
                db_query($sql_p, [$g_first, $g_other, $g_last, $g_dob, $g_pob, $g_father, $g_mother, $parish_id, $guid, $diocese_id, $g_baptism]);
                
                $groom_id = db_fetch("SELECT last_insert_rowid() as id")['id'];
                log_audit($_SESSION['user_id'], 'CREATE', 'parishioners', $groom_id, "Automatically registered unregistered groom for PNI: $g_first $g_last");
            } catch (Exception $e) {
                $error = "Failed to register Groom: " . $e->getMessage();
            }
        } else {
            $error = "Please provide all required biographical details for the unregistered Groom.";
        }
    }

    // Handle Unregistered Bride
    $bride_registration_type = $_POST['bride_registration_type'] ?? 'registered';
    if (empty($error) && $bride_registration_type === 'unregistered') {
        $b_first = upper($_POST['bride_unregistered_first_name'] ?? '');
        $b_other = upper($_POST['bride_unregistered_other_names'] ?? '');
        $b_last = upper($_POST['bride_unregistered_last_name'] ?? '');
        $b_dob = $_POST['bride_unregistered_dob'] ?? '';
        $b_pob = upper($_POST['bride_unregistered_place_of_birth'] ?? '');
        $b_father = upper($_POST['bride_unregistered_father_name'] ?? '');
        $b_mother = upper($_POST['bride_unregistered_mother_name'] ?? '');
        $b_baptism = upper($_POST['bride_unregistered_place_of_baptism'] ?? '');
        
        if ($b_first && $b_last && $b_dob) {
            try {
                $guid = bin2hex(random_bytes(16));
                $diocese_id = $primary_diocese['diocese_id'] ?? 1;
                
                $sql_p = "INSERT INTO parishioners (first_name, other_names, last_name, gender, dob, place_of_birth, father_name, mother_name, current_parish_id, status, guid, diocese_id, place_of_baptism) 
                          VALUES (?, ?, ?, 'Female', ?, ?, ?, ?, ?, 'Active', ?, ?, ?)";
                db_query($sql_p, [$b_first, $b_other, $b_last, $b_dob, $b_pob, $b_father, $b_mother, $parish_id, $guid, $diocese_id, $b_baptism]);
                
                $bride_id = db_fetch("SELECT last_insert_rowid() as id")['id'];
                log_audit($_SESSION['user_id'], 'CREATE', 'parishioners', $bride_id, "Automatically registered unregistered bride for PNI: $b_first $b_last");
            } catch (Exception $e) {
                $error = "Failed to register Bride: " . $e->getMessage();
            }
        } else {
            $error = "Please provide all required biographical details for the unregistered Bride.";
        }
    }

    if (empty($error)) {
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
        
        $b_date1 = ($_POST['banns_date_1'] ?? '') ?: null;
        $b_date2 = ($_POST['banns_date_2'] ?? '') ?: null;
        $b_date3 = ($_POST['banns_date_3'] ?? '') ?: null;
        $b_parish = ($_POST['banns_parish_id'] ?? '') ?: null;

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
                        
                        <!-- The Groom (Husband-to-be) Section -->
                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit'; color: var(--accent); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="man-outline"></ion-icon> The Groom (Husband-to-be)
                            </h3>
                            
                            <!-- Toggle registration type for Groom -->
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Groom Registration Status *</label>
                                <div style="display: flex; gap: 1.5rem; background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); width: max-content;">
                                    <label style="display: flex; align-items: center; gap: 8px; color: white; cursor: pointer; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" id="lbl-groom-registered">
                                        <input type="radio" name="groom_registration_type" value="registered" checked style="accent-color: var(--accent);"> Registered Parishioner
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; color: white; cursor: pointer; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" id="lbl-groom-unregistered">
                                        <input type="radio" name="groom_registration_type" value="unregistered" style="accent-color: var(--accent);"> Unregistered / Visitor
                                    </label>
                                </div>
                            </div>

                            <!-- Registered Groom Selector -->
                            <div class="form-group" id="groom-registered-group">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Select Groom *</label>
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

                            <!-- Unregistered Groom Bio Details -->
                            <div id="groom-unregistered-group" style="display: none; flex-direction: column; gap: 1.5rem; border: 1px dashed rgba(56, 189, 248, 0.3); padding: 2rem; border-radius: 1.5rem; background: rgba(56, 189, 248, 0.02); margin-top: 1.5rem;">
                                <h4 style="font-family: 'Outfit', sans-serif; font-size: 1rem; color: var(--accent); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                                    <ion-icon name="information-circle-outline"></ion-icon> Unregistered Groom Bio Details
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">First Name *</label>
                                        <input type="text" name="groom_unregistered_first_name" class="input-field force-caps" placeholder="e.g. JOHN">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Other Names</label>
                                        <input type="text" name="groom_unregistered_other_names" class="input-field force-caps" placeholder="e.g. SIBANDA">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Last Name *</label>
                                        <input type="text" name="groom_unregistered_last_name" class="input-field force-caps" placeholder="e.g. MOYO">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Birth *</label>
                                        <input type="date" name="groom_unregistered_dob" class="input-field">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Birth</label>
                                        <input type="text" name="groom_unregistered_place_of_birth" class="input-field force-caps" placeholder="e.g. HWANGE HOSPITAL">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Father's Name</label>
                                        <input type="text" name="groom_unregistered_father_name" class="input-field force-caps" placeholder="Father's name">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Mother's Name</label>
                                        <input type="text" name="groom_unregistered_mother_name" class="input-field force-caps" placeholder="Mother's name">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Baptism / Denomination</label>
                                        <input type="text" name="groom_unregistered_place_of_baptism" class="input-field force-caps" placeholder="e.g. METHODIST / NOT BAPTIZED">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- The Bride (Wife-to-be) Section -->
                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit'; color: var(--accent); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="woman-outline"></ion-icon> The Bride (Wife-to-be)
                            </h3>
                            
                            <!-- Toggle registration type for Bride -->
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Bride Registration Status *</label>
                                <div style="display: flex; gap: 1.5rem; background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); width: max-content;">
                                    <label style="display: flex; align-items: center; gap: 8px; color: white; cursor: pointer; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" id="lbl-bride-registered">
                                        <input type="radio" name="bride_registration_type" value="registered" checked style="accent-color: var(--accent);"> Registered Parishioner
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; color: white; cursor: pointer; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" id="lbl-bride-unregistered">
                                        <input type="radio" name="bride_registration_type" value="unregistered" style="accent-color: var(--accent);"> Unregistered / Visitor
                                    </label>
                                </div>
                            </div>

                            <!-- Registered Bride Selector -->
                            <div class="form-group" id="bride-registered-group">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Select Bride *</label>
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

                            <!-- Unregistered Bride Bio Details -->
                            <div id="bride-unregistered-group" style="display: none; flex-direction: column; gap: 1.5rem; border: 1px dashed rgba(56, 189, 248, 0.3); padding: 2rem; border-radius: 1.5rem; background: rgba(56, 189, 248, 0.02); margin-top: 1.5rem;">
                                <h4 style="font-family: 'Outfit', sans-serif; font-size: 1rem; color: var(--accent); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                                    <ion-icon name="information-circle-outline"></ion-icon> Unregistered Bride Bio Details
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">First Name *</label>
                                        <input type="text" name="bride_unregistered_first_name" class="input-field force-caps" placeholder="e.g. MARY">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Other Names</label>
                                        <input type="text" name="bride_unregistered_other_names" class="input-field force-caps" placeholder="e.g. SIBANDA">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Last Name *</label>
                                        <input type="text" name="bride_unregistered_last_name" class="input-field force-caps" placeholder="e.g. NDLOVU">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Birth *</label>
                                        <input type="date" name="bride_unregistered_dob" class="input-field">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Birth</label>
                                        <input type="text" name="bride_unregistered_place_of_birth" class="input-field force-caps" placeholder="e.g. HWANGE HOSPITAL">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Father's Name</label>
                                        <input type="text" name="bride_unregistered_father_name" class="input-field force-caps" placeholder="Father's name">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Mother's Name (Maiden)</label>
                                        <input type="text" name="bride_unregistered_mother_name" class="input-field force-caps" placeholder="Mother's maiden name">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Baptism / Denomination</label>
                                        <input type="text" name="bride_unregistered_place_of_baptism" class="input-field force-caps" placeholder="e.g. METHODIST / NOT BAPTIZED">
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

        document.addEventListener('DOMContentLoaded', () => {
            // Groom Toggling Elements
            const radioGroomRegistered = document.querySelector('input[name="groom_registration_type"][value="registered"]');
            const radioGroomUnregistered = document.querySelector('input[name="groom_registration_type"][value="unregistered"]');
            const groomRegisteredGroup = document.getElementById('groom-registered-group');
            const groomUnregisteredGroup = document.getElementById('groom-unregistered-group');
            const selectGroom = document.getElementById('groom_id');
            const groomSacraments = document.getElementById('groom_sacraments');
            
            const inputGroomUnregFirst = document.querySelector('input[name="groom_unregistered_first_name"]');
            const inputGroomUnregLast = document.querySelector('input[name="groom_unregistered_last_name"]');
            const inputGroomUnregDob = document.querySelector('input[name="groom_unregistered_dob"]');

            function toggleGroomRegistrationType() {
                if (radioGroomRegistered.checked) {
                    groomRegisteredGroup.style.display = 'block';
                    groomUnregisteredGroup.style.display = 'none';
                    
                    selectGroom.setAttribute('required', '');
                    inputGroomUnregFirst.removeAttribute('required');
                    inputGroomUnregLast.removeAttribute('required');
                    inputGroomUnregDob.removeAttribute('required');
                    
                    document.getElementById('lbl-groom-registered').style.background = 'rgba(56, 189, 248, 0.15)';
                    document.getElementById('lbl-groom-registered').style.border = '1px solid rgba(56, 189, 248, 0.3)';
                    document.getElementById('lbl-groom-unregistered').style.background = 'transparent';
                    document.getElementById('lbl-groom-unregistered').style.border = 'none';
                    
                    // Show sacrament preview if a groom is selected
                    if (selectGroom.value) {
                        groomSacraments.style.display = 'block';
                    }
                } else {
                    groomRegisteredGroup.style.display = 'none';
                    groomUnregisteredGroup.style.display = 'flex';
                    
                    selectGroom.removeAttribute('required');
                    inputGroomUnregFirst.setAttribute('required', '');
                    inputGroomUnregLast.setAttribute('required', '');
                    inputGroomUnregDob.setAttribute('required', '');
                    
                    document.getElementById('lbl-groom-unregistered').style.background = 'rgba(56, 189, 248, 0.15)';
                    document.getElementById('lbl-groom-unregistered').style.border = '1px solid rgba(56, 189, 248, 0.3)';
                    document.getElementById('lbl-groom-registered').style.background = 'transparent';
                    document.getElementById('lbl-groom-registered').style.border = 'none';
                    
                    // Hide sacrament preview since groom is not registered
                    groomSacraments.style.display = 'none';
                }
            }

            radioGroomRegistered.addEventListener('change', toggleGroomRegistrationType);
            radioGroomUnregistered.addEventListener('change', toggleGroomRegistrationType);

            // Bride Toggling Elements
            const radioBrideRegistered = document.querySelector('input[name="bride_registration_type"][value="registered"]');
            const radioBrideUnregistered = document.querySelector('input[name="bride_registration_type"][value="unregistered"]');
            const brideRegisteredGroup = document.getElementById('bride-registered-group');
            const brideUnregisteredGroup = document.getElementById('bride-unregistered-group');
            const selectBride = document.getElementById('bride_id');
            const brideSacraments = document.getElementById('bride_sacraments');
            
            const inputBrideUnregFirst = document.querySelector('input[name="bride_unregistered_first_name"]');
            const inputBrideUnregLast = document.querySelector('input[name="bride_unregistered_last_name"]');
            const inputBrideUnregDob = document.querySelector('input[name="bride_unregistered_dob"]');

            function toggleBrideRegistrationType() {
                if (radioBrideRegistered.checked) {
                    brideRegisteredGroup.style.display = 'block';
                    brideUnregisteredGroup.style.display = 'none';
                    
                    selectBride.setAttribute('required', '');
                    inputBrideUnregFirst.removeAttribute('required');
                    inputBrideUnregLast.removeAttribute('required');
                    inputBrideUnregDob.removeAttribute('required');
                    
                    document.getElementById('lbl-bride-registered').style.background = 'rgba(56, 189, 248, 0.15)';
                    document.getElementById('lbl-bride-registered').style.border = '1px solid rgba(56, 189, 248, 0.3)';
                    document.getElementById('lbl-bride-unregistered').style.background = 'transparent';
                    document.getElementById('lbl-bride-unregistered').style.border = 'none';
                    
                    // Show sacrament preview if a bride is selected
                    if (selectBride.value) {
                        brideSacraments.style.display = 'block';
                    }
                } else {
                    brideRegisteredGroup.style.display = 'none';
                    brideUnregisteredGroup.style.display = 'flex';
                    
                    selectBride.removeAttribute('required');
                    inputBrideUnregFirst.setAttribute('required', '');
                    inputBrideUnregLast.setAttribute('required', '');
                    inputBrideUnregDob.setAttribute('required', '');
                    
                    document.getElementById('lbl-bride-unregistered').style.background = 'rgba(56, 189, 248, 0.15)';
                    document.getElementById('lbl-bride-unregistered').style.border = '1px solid rgba(56, 189, 248, 0.3)';
                    document.getElementById('lbl-bride-registered').style.background = 'transparent';
                    document.getElementById('lbl-bride-registered').style.border = 'none';
                    
                    // Hide sacrament preview since bride is not registered
                    brideSacraments.style.display = 'none';
                }
            }

            radioBrideRegistered.addEventListener('change', toggleBrideRegistrationType);
            radioBrideUnregistered.addEventListener('change', toggleBrideRegistrationType);

            // Initial execution
            toggleGroomRegistrationType();
            toggleBrideRegistrationType();

            // Trigger on load if preselected
            const gId = document.getElementById('groom_id').value;
            const bId = document.getElementById('bride_id').value;
            if (gId) fetchSacraments(gId, 'groom');
            if (bId) fetchSacraments(bId, 'bride');
        });
    </script>
</body>
</html>
