<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Register Holy Matrimony - Modernized & Backend Polished
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Register Holy Matrimony";
$header_subtitle = "Enter canonical marriage details into the Diocesan archives.";

// Fetch parishioners (Grooms & Brides) and parishes
$grooms = db_fetchAll("SELECT person_id, first_name, last_name FROM parishioners WHERE gender = 'Male' AND status != 'Deceased' ORDER BY last_name, first_name");
$brides = db_fetchAll("SELECT person_id, first_name, last_name FROM parishioners WHERE gender = 'Female' AND status != 'Deceased' ORDER BY last_name, first_name");
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");

$preselected_id = $_GET['person_id'] ?? null;
$preselected_pni = $_GET['pni_id'] ?? null;

// Fetch completed PNIs that haven't been linked to a marriage yet
$pnis = db_fetchAll("
    SELECT pi.*, 
           g.first_name as g_first, g.last_name as g_last,
           b.first_name as b_first, b.last_name as b_last
    FROM prenuptial_investigations pi
    JOIN parishioners g ON pi.groom_id = g.person_id
    JOIN parishioners b ON pi.bride_id = b.person_id
    LEFT JOIN marriages m ON pi.pni_id = m.pni_id
    WHERE pi.status = 'Completed' AND m.marriage_id IS NULL
    ORDER BY pi.investigation_date DESC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pni_id = $_POST['pni_id'] ?: null;
    $groom_id = $_POST['groom_person_id'] ?? null;
    $bride_id = $_POST['bride_person_id'] ?? null;
    $parish_id = $_POST['parish_id'] ?? null;
    $date_of_marriage = $_POST['date_of_marriage'] ?? '';
    $officiant = upper($_POST['officiant'] ?? '');
    $witnesses = upper($_POST['witnesses_names'] ?? '');
    $register_book = upper($_POST['register_book_number'] ?? '');
    $page_number = upper($_POST['page_number'] ?? '');
    $entry_number = upper($_POST['entry_number'] ?? '');

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
                log_audit($_SESSION['user_id'], 'CREATE', 'parishioners', $groom_id, "Automatically registered unregistered groom for marriage: $g_first $g_last");
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
                log_audit($_SESSION['user_id'], 'CREATE', 'parishioners', $bride_id, "Automatically registered unregistered bride for marriage: $b_first $b_last");
            } catch (Exception $e) {
                $error = "Failed to register Bride: " . $e->getMessage();
            }
        } else {
            $error = "Please provide all required biographical details for the unregistered Bride.";
        }
    }

    if (empty($error)) {
        if ($groom_id && $bride_id && $parish_id && $date_of_marriage) {
            $verification_hash = bin2hex(random_bytes(16));
            $guid = bin2hex(random_bytes(16));
            $diocese_id = $primary_diocese['diocese_id'] ?? 1;
            
            try {
                $sql = "INSERT INTO marriages (groom_person_id, bride_person_id, parish_id, pni_id, date_of_marriage, officiant, witnesses_names, register_book_number, page_number, entry_number, verification_hash, guid, diocese_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                db_query($sql, [$groom_id, $bride_id, $parish_id, $pni_id, $date_of_marriage, $officiant, $witnesses, $register_book, $page_number, $entry_number, $verification_hash, $guid, $diocese_id]);
                
                $new_id = db_fetch("SELECT last_insert_rowid() as id")['id'];
                log_audit($_SESSION['user_id'], 'CREATE', 'marriages', $new_id, "Registered marriage for groom_id: $groom_id, bride_id: $bride_id");
                
                set_flash("Matrimonial record has been successfully registered.");
                redirect("marriage_list.php");
            } catch (Exception $e) {
                $error = "System Error: " . $e->getMessage();
            }
        } else {
            $error = "Please provide all required canonical information.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Marriage - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.5">
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
                
                <div class="action-bar" style="margin-bottom: 2rem;">
                    <a href="marriage_list.php" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
                        <ion-icon name="arrow-back-outline"></ion-icon> Cancel & Return
                    </a>
                </div>

                <div class="card bg-card" style="padding: 3rem; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                    
                    <?php if (isset($error)): ?>
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: white; padding: 1rem; border-radius: 1rem; margin-bottom: 2rem; text-align: center;">
                            <?php echo h($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="premium-form">
                        
                        <div class="form-section" style="margin-bottom: 3rem; background: rgba(251, 191, 36, 0.03); padding: 2rem; border-radius: 1.5rem; border: 1px solid rgba(251, 191, 36, 0.1);">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #fbbf24; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="document-text-outline"></ion-icon> Prenuptial Investigation (PNI)
                            </h3>
                            <div class="form-group">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Linked PNI Record (Optional)</label>
                                <select name="pni_id" id="pni_id" onchange="autoFillParties(this)" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 12px; color: white;">
                                    <option value="">-- Select Completed PNI --</option>
                                    <?php foreach ($pnis as $pni): ?>
                                        <option value="<?php echo $pni['pni_id']; ?>" 
                                                data-groom="<?php echo $pni['groom_id']; ?>" 
                                                data-bride="<?php echo $pni['bride_id']; ?>"
                                                data-parish="<?php echo $pni['parish_id']; ?>"
                                                <?php echo $preselected_pni == $pni['pni_id'] ? 'selected' : ''; ?>>
                                            PNI #<?php echo $pni['pni_id']; ?>: <?php echo h($pni['g_last'] . ' & ' . $pni['b_last']); ?> (<?php echo date('d M Y', strtotime($pni['investigation_date'])); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p style="font-size: 0.7rem; color: #fbbf24; margin-top: 8px; opacity: 0.8;">
                                    <ion-icon name="information-circle-outline"></ion-icon> Leaving this blank allows you to manually select the Groom and Bride below.
                                </p>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #f472b6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="man-outline"></ion-icon> The Groom (Husband)
                            </h3>
                            
                            <!-- Toggle registration type for Groom -->
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Groom Registration Status *</label>
                                <div style="display: flex; gap: 1.5rem; background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); width: max-content;">
                                    <label style="display: flex; align-items: center; gap: 8px; color: white; cursor: pointer; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" id="lbl-groom-registered">
                                        <input type="radio" name="groom_registration_type" value="registered" checked style="accent-color: #f472b6;"> Registered Parishioner
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; color: white; cursor: pointer; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" id="lbl-groom-unregistered">
                                        <input type="radio" name="groom_registration_type" value="unregistered" style="accent-color: #f472b6;"> Unregistered / Visitor
                                    </label>
                                </div>
                            </div>

                            <!-- Registered Groom Selector -->
                            <div class="form-group" id="groom-registered-group">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Select Groom *</label>
                                <select name="groom_person_id" id="groom_person_id" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    <option value="">-- Select Groom --</option>
                                    <?php foreach ($grooms as $g): ?>
                                        <option value="<?php echo $g['person_id']; ?>" <?php echo $preselected_id == $g['person_id'] ? 'selected' : ''; ?>>
                                            <?php echo h($g['last_name'] . ', ' . $g['first_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Unregistered Groom Bio Details -->
                            <div id="groom-unregistered-group" style="display: none; flex-direction: column; gap: 1.5rem; border: 1px dashed rgba(244, 114, 182, 0.3); padding: 2rem; border-radius: 1.5rem; background: rgba(244, 114, 182, 0.02); margin-top: 1.5rem;">
                                <h4 style="font-family: 'Outfit', sans-serif; font-size: 1rem; color: #f472b6; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                                    <ion-icon name="information-circle-outline"></ion-icon> Unregistered Groom Bio Details
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">First Name *</label>
                                        <input type="text" name="groom_unregistered_first_name" class="force-caps" placeholder="e.g. JOHN" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Other Names</label>
                                        <input type="text" name="groom_unregistered_other_names" class="force-caps" placeholder="e.g. SIBANDA" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Last Name *</label>
                                        <input type="text" name="groom_unregistered_last_name" class="force-caps" placeholder="e.g. MOYO" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Birth *</label>
                                        <input type="date" name="groom_unregistered_dob" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Birth</label>
                                        <input type="text" name="groom_unregistered_place_of_birth" class="force-caps" placeholder="e.g. HWANGE HOSPITAL" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Father's Name</label>
                                        <input type="text" name="groom_unregistered_father_name" class="force-caps" placeholder="Father's name" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Mother's Name</label>
                                        <input type="text" name="groom_unregistered_mother_name" class="force-caps" placeholder="Mother's name" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Baptism</label>
                                        <input type="text" name="groom_unregistered_place_of_baptism" class="force-caps" placeholder="e.g. ST. IGNATIUS, HWANGE" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #f472b6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="woman-outline"></ion-icon> The Bride (Wife)
                            </h3>
                            
                            <!-- Toggle registration type for Bride -->
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Bride Registration Status *</label>
                                <div style="display: flex; gap: 1.5rem; background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); width: max-content;">
                                    <label style="display: flex; align-items: center; gap: 8px; color: white; cursor: pointer; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" id="lbl-bride-registered">
                                        <input type="radio" name="bride_registration_type" value="registered" checked style="accent-color: #f472b6;"> Registered Parishioner
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; color: white; cursor: pointer; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" id="lbl-bride-unregistered">
                                        <input type="radio" name="bride_registration_type" value="unregistered" style="accent-color: #f472b6;"> Unregistered / Visitor
                                    </label>
                                </div>
                            </div>

                            <!-- Registered Bride Selector -->
                            <div class="form-group" id="bride-registered-group">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Select Bride *</label>
                                <select name="bride_person_id" id="bride_person_id" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    <option value="">-- Select Bride --</option>
                                    <?php foreach ($brides as $b): ?>
                                        <option value="<?php echo $b['person_id']; ?>">
                                            <?php echo h($b['last_name'] . ', ' . $b['first_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Unregistered Bride Bio Details -->
                            <div id="bride-unregistered-group" style="display: none; flex-direction: column; gap: 1.5rem; border: 1px dashed rgba(244, 114, 182, 0.3); padding: 2rem; border-radius: 1.5rem; background: rgba(244, 114, 182, 0.02); margin-top: 1.5rem;">
                                <h4 style="font-family: 'Outfit', sans-serif; font-size: 1rem; color: #f472b6; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                                    <ion-icon name="information-circle-outline"></ion-icon> Unregistered Bride Bio Details
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">First Name *</label>
                                        <input type="text" name="bride_unregistered_first_name" class="force-caps" placeholder="e.g. MARY" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Other Names</label>
                                        <input type="text" name="bride_unregistered_other_names" class="force-caps" placeholder="e.g. SIBANDA" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Last Name *</label>
                                        <input type="text" name="bride_unregistered_last_name" class="force-caps" placeholder="e.g. NDLOVU" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Birth *</label>
                                        <input type="date" name="bride_unregistered_dob" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Birth</label>
                                        <input type="text" name="bride_unregistered_place_of_birth" class="force-caps" placeholder="e.g. HWANGE HOSPITAL" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Father's Name</label>
                                        <input type="text" name="bride_unregistered_father_name" class="force-caps" placeholder="Father's name" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Mother's Name (Maiden)</label>
                                        <input type="text" name="bride_unregistered_mother_name" class="force-caps" placeholder="Mother's maiden name" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Baptism</label>
                                        <input type="text" name="bride_unregistered_place_of_baptism" class="force-caps" placeholder="e.g. ST. IGNATIUS, HWANGE" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
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
                                    <input type="date" name="date_of_marriage" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Parish of Celebration *</label>
                                    <select name="parish_id" id="parish_id" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <option value="">-- Select Parish --</option>
                                        <?php foreach ($parishes as $pa): ?>
                                            <option value="<?php echo $pa['parish_id']; ?>" <?php echo (isset($_SESSION['parish_id']) && $_SESSION['parish_id'] == $pa['parish_id']) ? 'selected' : ''; ?>><?php echo h($pa['parish_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Officiating Priest</label>
                                    <input type="text" name="officiant" class="force-caps" placeholder="e.g. Fr. J. Moyo" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Canonical Witnesses</label>
                                    <input type="text" name="witnesses_names" class="force-caps" placeholder="Separated by commas" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #f472b6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="archive-outline"></ion-icon> Registry Archive
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Book Number *</label>
                                    <select name="register_book_number" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <option value="">-- Select Volume --</option>
                                        <option value="Volume I">Volume I</option>
                                        <option value="Volume II">Volume II</option>
                                        <option value="Volume III">Volume III</option>
                                        <option value="Volume IV">Volume IV</option>
                                        <option value="Volume V">Volume V</option>
                                        <option value="Volume VI">Volume VI</option>
                                        <option value="Volume VII">Volume VII</option>
                                        <option value="Volume VIII">Volume VIII</option>
                                        <option value="Volume IX">Volume IX</option>
                                        <option value="Volume X">Volume X</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Page *</label>
                                    <input type="number" name="page_number" min="1" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Entry Number *</label>
                                    <input type="text" name="entry_number" required pattern="^\d{4}/\d+$" title="Use strictly format YYYY/Number (e.g. 2026/045 or 1956/29)" placeholder="e.g. 2026/045" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                        </div>

                        <div class="form-footer" style="padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="submit" class="btn btn-primary" style="background: #f472b6; color: white; font-weight: 800; padding: 1.25rem 3rem; border-radius: 1rem; border: none; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(244, 114, 182, 0.3);">Register Matrimonial Archive</button>
                        </div>
                    </form>

                </div>

                <?php include '../includes/privacy_footer.php'; ?>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const radioGroomRegistered = document.querySelector('input[name="groom_registration_type"][value="registered"]');
            const radioGroomUnregistered = document.querySelector('input[name="groom_registration_type"][value="unregistered"]');
            const groomRegisteredGroup = document.getElementById('groom-registered-group');
            const groomUnregisteredGroup = document.getElementById('groom-unregistered-group');
            const selectGroom = document.getElementById('groom_person_id');
            
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
                    
                    document.getElementById('lbl-groom-registered').style.background = 'rgba(244, 114, 182, 0.15)';
                    document.getElementById('lbl-groom-registered').style.border = '1px solid rgba(244, 114, 182, 0.3)';
                    document.getElementById('lbl-groom-unregistered').style.background = 'transparent';
                    document.getElementById('lbl-groom-unregistered').style.border = 'none';
                } else {
                    groomRegisteredGroup.style.display = 'none';
                    groomUnregisteredGroup.style.display = 'flex';
                    
                    selectGroom.removeAttribute('required');
                    inputGroomUnregFirst.setAttribute('required', '');
                    inputGroomUnregLast.setAttribute('required', '');
                    inputGroomUnregDob.setAttribute('required', '');
                    
                    document.getElementById('lbl-groom-unregistered').style.background = 'rgba(244, 114, 182, 0.15)';
                    document.getElementById('lbl-groom-unregistered').style.border = '1px solid rgba(244, 114, 182, 0.3)';
                    document.getElementById('lbl-groom-registered').style.background = 'transparent';
                    document.getElementById('lbl-groom-registered').style.border = 'none';
                }
            }

            radioGroomRegistered.addEventListener('change', toggleGroomRegistrationType);
            radioGroomUnregistered.addEventListener('change', toggleGroomRegistrationType);

            // Bride elements
            const radioBrideRegistered = document.querySelector('input[name="bride_registration_type"][value="registered"]');
            const radioBrideUnregistered = document.querySelector('input[name="bride_registration_type"][value="unregistered"]');
            const brideRegisteredGroup = document.getElementById('bride-registered-group');
            const brideUnregisteredGroup = document.getElementById('bride-unregistered-group');
            const selectBride = document.getElementById('bride_person_id');
            
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
                    
                    document.getElementById('lbl-bride-registered').style.background = 'rgba(244, 114, 182, 0.15)';
                    document.getElementById('lbl-bride-registered').style.border = '1px solid rgba(244, 114, 182, 0.3)';
                    document.getElementById('lbl-bride-unregistered').style.background = 'transparent';
                    document.getElementById('lbl-bride-unregistered').style.border = 'none';
                } else {
                    brideRegisteredGroup.style.display = 'none';
                    brideUnregisteredGroup.style.display = 'flex';
                    
                    selectBride.removeAttribute('required');
                    inputBrideUnregFirst.setAttribute('required', '');
                    inputBrideUnregLast.setAttribute('required', '');
                    inputBrideUnregDob.setAttribute('required', '');
                    
                    document.getElementById('lbl-bride-unregistered').style.background = 'rgba(244, 114, 182, 0.15)';
                    document.getElementById('lbl-bride-unregistered').style.border = '1px solid rgba(244, 114, 182, 0.3)';
                    document.getElementById('lbl-bride-registered').style.background = 'transparent';
                    document.getElementById('lbl-bride-registered').style.border = 'none';
                }
            }

            radioBrideRegistered.addEventListener('change', toggleBrideRegistrationType);
            radioBrideUnregistered.addEventListener('change', toggleBrideRegistrationType);

            // Initial execution
            toggleGroomRegistrationType();
            toggleBrideRegistrationType();

            window.toggleGroomRegistrationType = toggleGroomRegistrationType;
            window.toggleBrideRegistrationType = toggleBrideRegistrationType;

            // Trigger on load if PNI is preselected
            const pniSelect = document.getElementById('pni_id');
            if (pniSelect.value) {
                autoFillParties(pniSelect);
            }
        });

        function autoFillParties(select) {
            const option = select.options[select.selectedIndex];
            if (!option.value) return;

            const groomId = option.getAttribute('data-groom');
            const brideId = option.getAttribute('data-bride');
            const parishId = option.getAttribute('data-parish');

            document.querySelector('input[name="groom_registration_type"][value="registered"]').checked = true;
            document.querySelector('input[name="bride_registration_type"][value="registered"]').checked = true;
            
            if (window.toggleGroomRegistrationType) window.toggleGroomRegistrationType();
            if (window.toggleBrideRegistrationType) window.toggleBrideRegistrationType();

            document.getElementById('groom_person_id').value = groomId;
            document.getElementById('bride_person_id').value = brideId;
            document.getElementById('parish_id').value = parishId;
        }
    </script>
</body>
</html>
