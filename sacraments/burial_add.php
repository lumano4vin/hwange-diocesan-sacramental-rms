<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Register Death/Burial - Modernized & Backend Polished
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Register Death & Burial";
$header_subtitle = "Enter canonical funeral details into the Diocesan archives.";

// Fetch parishioners and parishes for dropdowns
$parishioners = db_fetchAll("SELECT person_id, first_name, last_name, other_names FROM parishioners ORDER BY last_name, first_name");
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");

$preselected_id = $_GET['person_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_type = $_POST['registration_type'] ?? 'registered';
    $parish_id = $_POST['parish_id'] ?? null;
    $date_of_death = $_POST['date_of_death'] ?? '';
    $date_of_burial = $_POST['date_of_burial'] ?? '';
    $place_of_burial = $_POST['place_of_burial'] ?? '';
    $minister = $_POST['minister'] ?? '';
    $register_book = $_POST['register_book_number'] ?? '';
    $page_number = $_POST['page_number'] ?? '';
    $entry_number = $_POST['entry_number'] ?? '';

    $person_id = null;
    $error = '';

    if ($registration_type === 'unregistered') {
        $first_name = upper($_POST['unregistered_first_name'] ?? '');
        $other_names = upper($_POST['unregistered_other_names'] ?? '');
        $last_name = upper($_POST['unregistered_last_name'] ?? '');
        $gender = $_POST['unregistered_gender'] ?? '';
        $dob = $_POST['unregistered_dob'] ?? null;
        $place_of_birth = upper($_POST['unregistered_place_of_birth'] ?? '');
        $father_name = upper($_POST['unregistered_father_name'] ?? '');
        $mother_name = upper($_POST['unregistered_mother_name'] ?? '');
        $place_of_baptism = upper($_POST['unregistered_place_of_baptism'] ?? '');
        
        if ($first_name && $last_name && $gender && $dob) {
            try {
                $guid = bin2hex(random_bytes(16));
                $diocese_id = $primary_diocese['diocese_id'] ?? 1;
                
                // Insert a new parishioner record directly as Deceased
                $sql_p = "INSERT INTO parishioners (first_name, other_names, last_name, gender, dob, place_of_birth, father_name, mother_name, current_parish_id, status, guid, diocese_id, place_of_baptism) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Deceased', ?, ?, ?)";
                db_query($sql_p, [$first_name, $other_names, $last_name, $gender, $dob, $place_of_birth, $father_name, $mother_name, $parish_id, $guid, $diocese_id, $place_of_baptism]);
                
                // Fetch the newly generated person_id
                $person_id = db_fetch("SELECT last_insert_rowid() as id")['id'];
                log_audit($_SESSION['user_id'], 'CREATE', 'parishioners', $person_id, "Automatically registered unregistered/visiting parishioner for death record: $first_name $last_name");
            } catch (Exception $e) {
                $error = "Failed to create temporary parishioner record: " . $e->getMessage();
            }
        } else {
            $error = "Please provide all required biographical details for the unregistered/visiting parishioner.";
        }
    } else {
        $person_id = $_POST['person_id'] ?? null;
    }

    if (empty($error)) {
        if ($person_id && $parish_id && $date_of_death) {
            $verification_hash = bin2hex(random_bytes(16));
            $guid = bin2hex(random_bytes(16));
            $diocese_id = $primary_diocese['diocese_id'] ?? 1;
            
            try {
                $sql = "INSERT INTO deaths (person_id, parish_id, date_of_death, date_of_burial, place_of_burial, minister, register_book_number, page_number, entry_number, verification_hash, guid, diocese_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                db_query($sql, [$person_id, $parish_id, $date_of_death, $date_of_burial, $place_of_burial, $minister, $register_book, $page_number, $entry_number, $verification_hash, $guid, $diocese_id]);
                
                $new_id = db_fetch("SELECT last_insert_rowid() as id")['id'];
                log_audit($_SESSION['user_id'], 'CREATE', 'deaths', $new_id, "Registered death for person_id: $person_id");
                
                // If they were an existing registered parishioner, update status to 'Deceased'
                if ($registration_type !== 'unregistered') {
                    db_query("UPDATE parishioners SET status = 'Deceased' WHERE person_id = ?", [$person_id]);
                }
                
                set_flash("Death and Burial record has been successfully registered.");
                redirect("burial_list.php");
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
    <title>Register Burial - Hwange Diocesan RMS</title>
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
                    <a href="burial_list.php" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
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
                        
                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #94a3b8; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="person-outline"></ion-icon> Deceased Information
                            </h3>

                            <!-- Toggle registration type -->
                            <div class="form-group" style="margin-bottom: 2rem;">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Subject Registration Status *</label>
                                <div style="display: flex; gap: 1.5rem; background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); width: max-content;">
                                    <label style="display: flex; align-items: center; gap: 8px; color: white; cursor: pointer; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" id="lbl-reg-registered">
                                        <input type="radio" name="registration_type" value="registered" checked style="accent-color: var(--accent);"> Registered Parishioner
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; color: white; cursor: pointer; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" id="lbl-reg-unregistered">
                                        <input type="radio" name="registration_type" value="unregistered" style="accent-color: var(--accent);"> Unregistered / Visitor (Baptised Elsewhere)
                                    </label>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group" id="registered-subject-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Faithful Subject *</label>
                                    <select name="person_id" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <option value="">-- Select Parishioner --</option>
                                        <?php foreach ($parishioners as $p): ?>
                                            <option value="<?php echo $p['person_id']; ?>" <?php echo $preselected_id == $p['person_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($p['last_name'] . ', ' . $p['first_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Parish of Registry *</label>
                                    <select name="parish_id" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <option value="">-- Select Parish --</option>
                                        <?php foreach ($parishes as $pa): ?>
                                            <option value="<?php echo $pa['parish_id']; ?>" <?php echo (isset($_SESSION['parish_id']) && $_SESSION['parish_id'] == $pa['parish_id']) ? 'selected' : ''; ?>><?php echo h($pa['parish_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Unregistered Bio Inputs Container -->
                            <div id="unregistered-subject-group" style="display: none; flex-direction: column; gap: 1.5rem; border: 1px dashed rgba(56, 189, 248, 0.3); padding: 2rem; border-radius: 1.5rem; background: rgba(56, 189, 248, 0.02); margin-top: 1.5rem; margin-bottom: 1.5rem;">
                                <h4 style="font-family: 'Outfit', sans-serif; font-size: 1rem; color: var(--accent); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                                    <ion-icon name="information-circle-outline"></ion-icon> Unregistered Bio Details
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">First Name *</label>
                                        <input type="text" name="unregistered_first_name" class="force-caps" placeholder="e.g. JOHN" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Other Names</label>
                                        <input type="text" name="unregistered_other_names" class="force-caps" placeholder="e.g. SIBANDA" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Last Name *</label>
                                        <input type="text" name="unregistered_last_name" class="force-caps" placeholder="e.g. MOYO" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Gender *</label>
                                        <select name="unregistered_gender" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                            <option value="">-- Select --</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Birth *</label>
                                        <input type="date" name="unregistered_dob" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Birth</label>
                                        <input type="text" name="unregistered_place_of_birth" class="force-caps" placeholder="e.g. HWANGE HOSPITAL" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Father's Name</label>
                                        <input type="text" name="unregistered_father_name" class="force-caps" placeholder="Father's name" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Mother's Name (Maiden)</label>
                                        <input type="text" name="unregistered_mother_name" class="force-caps" placeholder="Mother's maiden name" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Baptism</label>
                                        <input type="text" name="unregistered_place_of_baptism" class="force-caps" placeholder="e.g. ST. IGNATIUS, HWANGE" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #94a3b8; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="calendar-outline"></ion-icon> Funeral Details
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Death *</label>
                                    <input type="date" name="date_of_death" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Burial</label>
                                    <input type="date" name="date_of_burial" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Burial</label>
                                    <input type="text" name="place_of_burial" placeholder="e.g. Parish Cemetery" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Minister (Officiant)</label>
                                    <input type="text" name="minister" placeholder="e.g. Fr. J. Moyo" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #94a3b8; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="archive-outline"></ion-icon> Registry Location
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
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Entry No. *</label>
                                    <input type="text" name="entry_number" required pattern="^\d{4}/\d+$" title="Use strictly format YYYY/Number (e.g. 2026/045 or 1956/29)" placeholder="e.g. 2026/045" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                        </div>

                        <div class="form-footer" style="padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="submit" class="btn btn-primary" style="background: #94a3b8; color: #000; font-weight: 800; padding: 1.25rem 3rem; border-radius: 1rem; border: none; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(148, 163, 184, 0.3);">Register Funeral Archive</button>
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
        const radioRegistered = document.querySelector('input[name="registration_type"][value="registered"]');
        const radioUnregistered = document.querySelector('input[name="registration_type"][value="unregistered"]');
        const registeredGroup = document.getElementById('registered-subject-group');
        const unregisteredGroup = document.getElementById('unregistered-subject-group');
        
        const selectPerson = document.querySelector('select[name="person_id"]');
        const inputUnregFirst = document.querySelector('input[name="unregistered_first_name"]');
        const inputUnregLast = document.querySelector('input[name="unregistered_last_name"]');
        const selectUnregGender = document.querySelector('select[name="unregistered_gender"]');
        const inputUnregDob = document.querySelector('input[name="unregistered_dob"]');

        function toggleRegistrationType() {
            if (radioRegistered.checked) {
                registeredGroup.style.display = 'block';
                unregisteredGroup.style.display = 'none';
                
                selectPerson.setAttribute('required', '');
                inputUnregFirst.removeAttribute('required');
                inputUnregLast.removeAttribute('required');
                selectUnregGender.removeAttribute('required');
                inputUnregDob.removeAttribute('required');
                
                document.getElementById('lbl-reg-registered').style.background = 'rgba(56, 189, 248, 0.15)';
                document.getElementById('lbl-reg-registered').style.border = '1px solid rgba(56, 189, 248, 0.3)';
                document.getElementById('lbl-reg-unregistered').style.background = 'transparent';
                document.getElementById('lbl-reg-unregistered').style.border = 'none';
            } else {
                registeredGroup.style.display = 'none';
                unregisteredGroup.style.display = 'flex';
                
                selectPerson.removeAttribute('required');
                inputUnregFirst.setAttribute('required', '');
                inputUnregLast.setAttribute('required', '');
                selectUnregGender.setAttribute('required', '');
                inputUnregDob.setAttribute('required', '');
                
                document.getElementById('lbl-reg-unregistered').style.background = 'rgba(56, 189, 248, 0.15)';
                document.getElementById('lbl-reg-unregistered').style.border = '1px solid rgba(56, 189, 248, 0.3)';
                document.getElementById('lbl-reg-registered').style.background = 'transparent';
                document.getElementById('lbl-reg-registered').style.border = 'none';
            }
        }

        radioRegistered.addEventListener('change', toggleRegistrationType);
        radioUnregistered.addEventListener('change', toggleRegistrationType);
        
        // Initial execution
        toggleRegistrationType();
    });
    </script>
</body>
</html>
