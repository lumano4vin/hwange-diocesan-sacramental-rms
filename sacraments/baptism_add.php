<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Register Baptism - Modernized & Backend Polished
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Register New Baptism";
$header_subtitle = "Enter canonical baptismal details into the Diocesan archives.";

// Fetch parishioners and parishes for dropdowns
$parishioners = db_fetchAll("SELECT person_id, first_name, last_name, other_names FROM parishioners ORDER BY last_name, first_name");
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");

$preselected_id = $_GET['person_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_type = $_POST['registration_type'] ?? 'registered';
    $parish_id = $_POST['parish_id'] ?? null;
    $date_of_baptism = $_POST['date_of_baptism'] ?? '';
    $minister = upper($_POST['minister'] ?? '');
    $godparents = upper($_POST['godparents'] ?? '');
    $witnesses = upper($_POST['witnesses'] ?? '');
    $register_book = upper($_POST['register_book_number'] ?? '');
    $page_number = upper($_POST['page_number'] ?? '');
    $entry_number = upper($_POST['entry_number'] ?? '');
    $status = $_POST['status'] ?? 'Valid';

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
        $mother_maiden_name = upper($_POST['unregistered_mother_maiden_name'] ?? '');
        
        if ($first_name && $last_name && $gender && $dob) {
            try {
                $guid = bin2hex(random_bytes(16));
                $diocese_id = $primary_diocese['diocese_id'] ?? 1;
                
                $sql_p = "INSERT INTO parishioners (first_name, other_names, last_name, gender, dob, place_of_birth, father_name, mother_name, mother_maiden_name, current_parish_id, status, guid, diocese_id) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?)";
                db_query($sql_p, [$first_name, $other_names, $last_name, $gender, $dob, $place_of_birth, $father_name, $mother_name, $mother_maiden_name, $parish_id, $guid, $diocese_id]);
                
                $person_id = db_fetch("SELECT last_insert_rowid() as id")['id'];
                log_audit($_SESSION['user_id'], 'CREATE', 'parishioners', $person_id, "Automatically registered new candidate/person for baptism record: $first_name $last_name");
            } catch (Exception $e) {
                $error = "Failed to create parishioner record: " . $e->getMessage();
            }
        } else {
            $error = "Please provide all required biographical details for the new candidate.";
        }
    } else {
        $person_id = $_POST['person_id'] ?? null;
    }

    if (empty($error)) {
        if ($person_id && $parish_id && $date_of_baptism) {
            $verification_hash = bin2hex(random_bytes(16));
            $guid = bin2hex(random_bytes(16)); 
            $diocese_id = $primary_diocese['diocese_id'] ?? 1;
            $pobap = upper($_POST['place_of_baptism'] ?? '');
            
            try {
                $sql = "INSERT INTO baptisms (person_id, parish_id, date_of_baptism, minister, godparents, witnesses, register_book_number, page_number, entry_number, verification_hash, status, guid, diocese_id, place_of_baptism) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                db_query($sql, [$person_id, $parish_id, $date_of_baptism, $minister, $godparents, $witnesses, $register_book, $page_number, $entry_number, $verification_hash, $status, $guid, $diocese_id, $pobap]);
                
                $new_id = db_fetch("SELECT last_insert_rowid() as id")['id'];
                log_audit($_SESSION['user_id'], 'CREATE', 'baptisms', $new_id, "Registered baptism for person_id: $person_id");
                
                set_flash("Baptism record has been successfully registered in the canonical archives.");
                redirect("baptism_list.php");
            } catch (Exception $e) {
                $error = "System Error: " . $e->getMessage();
            }
        } else {
            $error = "Please provide all required canonical information (Faithful Subject, Parish, and Date).";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Baptism - Hwange Diocesan RMS</title>
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
                    <a href="baptism_list.php" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
                        <ion-icon name="arrow-back-outline"></ion-icon> Cancel & Return
                    </a>
                </div>

                <div class="card bg-card" style="padding: 3rem; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                    
                    <?php if (isset($error)): ?>
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: white; padding: 1rem; border-radius: 1rem; margin-bottom: 2rem; text-align: center;">
                            <?php echo h($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="baptism_add.php" method="POST" class="premium-form">
                        <!-- Hidden submit button for Enter key reliability -->
                        <button type="submit" style="display:none;"></button>
                        
                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #38bdf8; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="person-outline"></ion-icon> Primary Subject Information
                            </h3>

                            <!-- Toggle registration type for Baptism Candidate -->
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Candidate Registration Status *</label>
                                <div style="display: flex; gap: 1.5rem; background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); width: max-content;">
                                    <label style="display: flex; align-items: center; gap: 8px; color: white; cursor: pointer; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" id="lbl-registered">
                                        <input type="radio" name="registration_type" value="registered" checked style="accent-color: #38bdf8;"> Registered Parishioner
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; color: white; cursor: pointer; padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" id="lbl-unregistered">
                                        <input type="radio" name="registration_type" value="unregistered" style="accent-color: #38bdf8;"> Unregistered Candidate / Infant
                                    </label>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">
                                <!-- Registered Subject Selector -->
                                <div class="form-group" id="registered-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Select Parishioner *</label>
                                    <select name="person_id" id="person_id" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <option value="">-- Select Parishioner --</option>
                                        <?php foreach ($parishioners as $p): ?>
                                            <option value="<?php echo $p['person_id']; ?>" <?php echo $preselected_id == $p['person_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($p['last_name'] . ', ' . $p['first_name'] . ($p['other_names'] ? ' ' . $p['other_names'] : '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">Subject not listed? Select "Unregistered Candidate / Infant" above to enter details directly.</p>
                                </div>

                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Parish of Celebration *</label>
                                    <select name="parish_id" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <option value="">-- Select Parish --</option>
                                        <?php foreach ($parishes as $pa): ?>
                                            <option value="<?php echo $pa['parish_id']; ?>" <?php echo (isset($_SESSION['parish_id']) && $_SESSION['parish_id'] == $pa['parish_id']) ? 'selected' : ''; ?>><?php echo h($pa['parish_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Unregistered Subject Details -->
                            <div id="unregistered-group" style="display: none; flex-direction: column; gap: 1.5rem; border: 1px dashed rgba(56, 189, 248, 0.3); padding: 2rem; border-radius: 1.5rem; background: rgba(56, 189, 248, 0.02); margin-top: 1.5rem;">
                                <h4 style="font-family: 'Outfit', sans-serif; font-size: 1rem; color: #38bdf8; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                                    <ion-icon name="information-circle-outline"></ion-icon> Unregistered Candidate Details
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
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Gender *</label>
                                        <select name="unregistered_gender" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Birth *</label>
                                        <input type="date" name="unregistered_dob" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Birth</label>
                                        <input type="text" name="unregistered_place_of_birth" class="force-caps" placeholder="e.g. HWANGE HOSPITAL" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Father's Full Name</label>
                                        <input type="text" name="unregistered_father_name" class="force-caps" placeholder="Father's name" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Mother's Full Name</label>
                                        <input type="text" name="unregistered_mother_name" class="force-caps" placeholder="Mother's name" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Mother's Maiden Name</label>
                                        <input type="text" name="unregistered_mother_maiden_name" class="force-caps" placeholder="Maiden name (Canon 877)" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #38bdf8; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="calendar-outline"></ion-icon> Canonical Details
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Baptism *</label>
                                    <input type="date" name="date_of_baptism" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Place of Baptism (Specific Location)</label>
                                    <input type="text" name="place_of_baptism" class="force-caps" placeholder="e.g. St. Luke Mission / Hospital" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Minister of Sacrament</label>
                                    <input type="text" name="minister" class="force-caps" placeholder="e.g. Fr. J. Moyo" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Godparents</label>
                                    <input type="text" name="godparents" class="force-caps" placeholder="Separated by commas" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Witnesses</label>
                                    <input type="text" name="witnesses" class="force-caps" placeholder="Optional" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #38bdf8; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="archive-outline"></ion-icon> Registry Location & Archive
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Book / Volume *</label>
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
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Page Number *</label>
                                    <input type="number" name="page_number" min="1" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Entry No. *</label>
                                    <input type="text" name="entry_number" required pattern="^\d{4}/\d+$" title="Use strictly format YYYY/Number (e.g. 2026/045 or 1956/29)" placeholder="e.g. 2026/045" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                        </div>

                        <div class="form-footer" style="padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="submit" class="btn btn-primary" style="background: #38bdf8; color: #000; font-weight: 800; padding: 1.25rem 3rem; border-radius: 1rem; border: none; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(56, 189, 248, 0.3);">Register Archive Record</button>
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
            const registeredGroup = document.getElementById('registered-group');
            const unregisteredGroup = document.getElementById('unregistered-group');
            const selectPerson = document.getElementById('person_id');
            
            const inputFirst = document.querySelector('input[name="unregistered_first_name"]');
            const inputLast = document.querySelector('input[name="unregistered_last_name"]');
            const selectGender = document.querySelector('select[name="unregistered_gender"]');
            const inputDob = document.querySelector('input[name="unregistered_dob"]');

            function toggleRegistrationType() {
                if (radioRegistered.checked) {
                    registeredGroup.style.display = 'block';
                    unregisteredGroup.style.display = 'none';
                    
                    selectPerson.setAttribute('required', '');
                    inputFirst.removeAttribute('required');
                    inputLast.removeAttribute('required');
                    selectGender.removeAttribute('required');
                    inputDob.removeAttribute('required');
                    
                    document.getElementById('lbl-registered').style.background = 'rgba(56, 189, 248, 0.15)';
                    document.getElementById('lbl-registered').style.border = '1px solid rgba(56, 189, 248, 0.3)';
                    document.getElementById('lbl-unregistered').style.background = 'transparent';
                    document.getElementById('lbl-unregistered').style.border = 'none';
                } else {
                    registeredGroup.style.display = 'none';
                    unregisteredGroup.style.display = 'flex';
                    
                    selectPerson.removeAttribute('required');
                    inputFirst.setAttribute('required', '');
                    inputLast.setAttribute('required', '');
                    selectGender.setAttribute('required', '');
                    inputDob.setAttribute('required', '');
                    
                    document.getElementById('lbl-unregistered').style.background = 'rgba(56, 189, 248, 0.15)';
                    document.getElementById('lbl-unregistered').style.border = '1px solid rgba(56, 189, 248, 0.3)';
                    document.getElementById('lbl-registered').style.background = 'transparent';
                    document.getElementById('lbl-registered').style.border = 'none';
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
