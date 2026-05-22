<?php
/**
 * ZCRE - Add New Parishioner
 * Unified registry for the faithful.
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$pdo = getDB();
// Standardized schema: parish_id, parish_name
$parishes = $pdo->query("SELECT parish_id as id, parish_name as name FROM parishes ORDER BY parish_name")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = upper($_POST['first_name'] ?? '');
    $middle_name = upper($_POST['middle_name'] ?? '');
    $last_name = upper($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? null;
    $pob = upper($_POST['pob'] ?? '');
    $father = upper($_POST['father'] ?? '');
    $mother = upper($_POST['mother'] ?? '');
    $scc = upper($_POST['scc'] ?? '');
    $parish_id = $_POST['parish_id'] ?? null;
    $address = $_POST['address'] ?? '';
    $residence = upper($_POST['residence'] ?? '');

    if ($first_name && $last_name) {
        try {
            // Standardized schema: current_parish_id, other_names (middle_name)
            // Fetch diocese_id from primary diocese
            $diocese_id = $primary_diocese['diocese_id'] ?? 1;
            $guid = bin2hex(random_bytes(16)); // Simple GUID

            $status = $_POST['status'] ?? 'Active';
            $pobap = upper($_POST['pobap'] ?? '');

            $stmt = $pdo->prepare("
                INSERT INTO parishioners (first_name, other_names, last_name, gender, dob, place_of_birth, father_name, mother_name, scc_name, current_parish_id, address, status, guid, diocese_id, place_of_residence, place_of_baptism) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$first_name, $middle_name, $last_name, $gender, $dob, $pob, $father, $mother, $scc, $parish_id, $address, $status, $guid, $diocese_id, $residence, $pobap]);
            
            $new_id = $pdo->lastInsertId();
            log_audit($_SESSION['user_id'], "CREATE", "parishioners", $new_id, "Added Parishioner: $first_name $last_name");
            
            set_flash("Parishioner registered successfully in the Diocesan archives!", "success");
            
            // Smart redirection for sacramental workflows
            $ref = $_GET['ref'] ?? '';
            if ($ref === 'reception') {
                header("Location: ../sacraments/reception_add.php?person_id=" . $new_id);
            } else {
                header("Location: view.php?id=" . $new_id);
            }
            exit;
        } catch (Exception $e) {
            $error = "Canonical Error: " . $e->getMessage();
        }
    } else {
        $error = "First name and Last name are required for canonical registration.";
    }
}

// Header metadata
$header_title = "Register Faithful";
$header_subtitle = "Entry of a new member into the " . get_diocese_branding() . " registry.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Parishioner - <?php echo get_diocese_branding(); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0;">
                
                <div style="max-width: 900px; margin: 0 auto;">
                    <?php if ($success): ?>
                        <div class="card" style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: white; padding: 1.5rem; border-radius: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <ion-icon name="checkmark-circle" style="font-size: 2rem; color: var(--success);"></ion-icon>
                                <div>
                                    <h4 style="margin-bottom: 4px;"><?php echo h($success); ?></h4>
                                    <p style="font-size: 0.9rem; color: var(--text-muted);">The record has been securely archived.</p>
                                </div>
                            </div>
                            <a href="add.php" class="btn btn-sm btn-primary">Register Another</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="card" style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--error); color: white; padding: 1.5rem; border-radius: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                            <ion-icon name="alert-circle" style="font-size: 2rem; color: var(--error);"></ion-icon>
                            <div>
                                <h4 style="margin-bottom: 4px;">Archival Failure</h4>
                                <p style="font-size: 0.9rem; color: var(--text-muted);"><?php echo h($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card bg-card" style="border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05); padding: 3rem;">
                        <form method="POST" action="add.php" style="display: flex; flex-direction: column; gap: 2.5rem;">
                            <!-- Hidden submit button for Enter key reliability -->
                            <button type="submit" style="display:none;"></button>
                            
                            <!-- Identity Section -->
                            <div class="form-section">
                                <h3 style="font-family: 'Outfit'; font-size: 1.25rem; margin-bottom: 1.5rem; color: var(--accent); display: flex; align-items: center; gap: 10px;">
                                    <ion-icon name="person-outline"></ion-icon> Primary Identity
                                </h3>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">First Name *</label>
                                        <input type="text" id="first_name_input" name="first_name" class="force-caps" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Other Names</label>
                                        <input type="text" name="middle_name" class="force-caps" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Last Name *</label>
                                        <input type="text" id="last_name_input" name="last_name" class="force-caps" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                    </div>
                                </div>
                                
                                <!-- Real-time duplicate checking warning alert box -->
                                <div id="duplicate-warning-container" style="display: none; margin-top: 1.5rem; background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; color: white; padding: 1.5rem; border-radius: 1.5rem;">
                                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                                        <ion-icon name="warning-outline" style="font-size: 2rem; color: #f59e0b; flex-shrink: 0; margin-top: 2px;"></ion-icon>
                                        <div style="flex: 1;">
                                            <h4 style="margin: 0 0 6px 0; color: #f59e0b; font-family: 'Outfit'; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: 6px;">
                                                ⚠️ Potential Duplicate Record Detected
                                            </h4>
                                            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0 0 10px 0;">
                                                A person with the exact same name is already registered in the Diocesan archives. Please review to avoid duplicate canonical listings:
                                            </p>
                                            <div id="duplicate-records-list" style="display: flex; flex-direction: column; gap: 8px;">
                                                <!-- Dynamic entries rendered via JS -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bio Section -->
                            <div class="form-section">
                                <h3 style="font-family: 'Outfit'; font-size: 1.25rem; margin-bottom: 1.5rem; color: var(--accent); display: flex; align-items: center; gap: 10px;">
                                    <ion-icon name="calendar-outline"></ion-icon> Biographical Data
                                </h3>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Gender</label>
                                        <select name="gender" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                            <option value="">Select...</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Unknown">Unknown / Not Specified</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Date of Birth</label>
                                        <input type="date" name="dob" id="dob-input" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Calculated Age</label>
                                        <input type="text" id="age-display" readonly placeholder="Calculated automatically" style="width: 100%; padding: 1rem; background: rgba(56, 189, 248, 0.05); border: 1px solid rgba(56, 189, 248, 0.2); border-radius: 1rem; color: var(--accent); font-weight: 700;">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Place of Birth</label>
                                        <input type="text" name="pob" class="force-caps" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Place of Baptism</label>
                                        <input type="text" name="pobap" class="force-caps" placeholder="If already baptized elsewhere" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                    </div>
                                    <div class="form-group" style="grid-column: span 3;">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Place of Residence (Village/Area)</label>
                                        <input type="text" name="residence" class="force-caps" placeholder="e.g. LUKOSI VILLAGE / STAND 452 CHISUMA" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                    </div>
                                </div>
                            </div>

                            <!-- Lineage Section -->
                            <div class="form-section">
                                <h3 style="font-family: 'Outfit'; font-size: 1.25rem; margin-bottom: 1.5rem; color: var(--accent); display: flex; align-items: center; gap: 10px;">
                                    <ion-icon name="git-network-outline"></ion-icon> Lineage & SCC
                                </h3>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Father's Name</label>
                                        <input type="text" name="father" class="force-caps" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Mother's Name (Maiden)</label>
                                        <input type="text" name="mother" class="force-caps" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Small Christian Community (SCC)</label>
                                        <input type="text" name="scc" class="force-caps" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Home Parish</label>
                                        <select name="parish_id" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                            <option value="">Select Parish...</option>
                                            <?php foreach ($parishes as $p): ?>
                                                <option value="<?php echo $p['id']; ?>" <?php echo (isset($_SESSION['parish_id']) && $_SESSION['parish_id'] == $p['id']) ? 'selected' : ''; ?>><?php echo h($p['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Membership Status</label>
                                        <select name="status" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                                            <option value="Active">Active (Local Member)</option>
                                            <option value="Transferred In">Immigrant (Transferred In)</option>
                                            <option value="Transferred Out">Migrant (Transferred Out)</option>
                                            <option value="Deceased">Deceased</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div style="text-align: right; margin-top: 1rem;">
                                <button type="submit" class="btn btn-primary" style="padding: 1.25rem 3rem; border-radius: 1.25rem; font-weight: 900; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 2px;">
                                    Complete Registration
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php include '../includes/privacy_footer.php'; ?>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <script>
    document.getElementById('dob-input').addEventListener('change', function() {
        const dobVal = this.value;
        if (!dobVal) return;
        
        const dob = new Date(dobVal);
        const today = new Date();
        
        if (dob > today) {
            document.getElementById('age-display').value = "INFANT (FUTURE DATE)";
            return;
        }

        let years = today.getFullYear() - dob.getFullYear();
        let months = today.getMonth() - dob.getMonth();
        let days = today.getDate() - dob.getDate();

        if (days < 0) {
            months--;
            const lastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
            days += lastMonth.getDate();
        }

        if (months < 0) {
            years--;
            months += 12;
        }

        let ageStr = "";
        if (years >= 18) {
            ageStr = years + (years === 1 ? " YEAR OLD" : " YEARS OLD");
        } else if (years >= 1) {
            ageStr = years + (years === 1 ? " YEAR" : " YEARS");
            if (months > 0) ageStr += ", " + months + (months === 1 ? " MONTH" : " MONTHS");
            ageStr += " OLD";
        } else if (months > 0) {
            ageStr = months + (months === 1 ? " MONTH" : " MONTHS");
            if (days > 0) ageStr += ", " + days + (days === 1 ? " DAY" : " DAYS");
            ageStr += " OLD";
        } else {
            ageStr = days + (days === 1 ? " DAY" : " DAYS");
            ageStr += " OLD";
        }

        document.getElementById('age-display').value = ageStr.toUpperCase();
    });

    // Real-time Duplicate Checker Integration
    const firstNameInput = document.getElementById('first_name_input');
    const lastNameInput = document.getElementById('last_name_input');
    const warningContainer = document.getElementById('duplicate-warning-container');
    const duplicatesList = document.getElementById('duplicate-records-list');

    function checkDuplicates() {
        const firstName = firstNameInput.value.trim();
        const lastName = lastNameInput.value.trim();

        if (firstName.length < 2 || lastName.length < 2) {
            warningContainer.style.display = 'none';
            return;
        }

        fetch(`../api/check_duplicate_parishioner.php?first_name=${encodeURIComponent(firstName)}&last_name=${encodeURIComponent(lastName)}`)
            .then(res => res.json())
            .then(data => {
                if (data.duplicates && data.duplicates.length > 0) {
                    duplicatesList.innerHTML = '';
                    data.duplicates.forEach(dup => {
                        const dobStr = dup.dob ? new Date(dup.dob).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : 'Unknown DOB';
                        const otherNames = dup.other_names ? ` (${dup.other_names})` : '';
                        const item = document.createElement('div');
                        item.style.display = 'flex';
                        item.style.justifyContent = 'space-between';
                        item.style.alignItems = 'center';
                        item.style.background = 'rgba(255,255,255,0.05)';
                        item.style.padding = '0.75rem 1rem';
                        item.style.borderRadius = '0.75rem';
                        item.style.border = '1px solid rgba(255,255,255,0.03)';
                        
                        item.innerHTML = `
                            <div>
                                <span style="font-weight: 700; color: white;">${dup.last_name}, ${dup.first_name}${otherNames}</span>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">DOB: ${dobStr} • Gender: ${dup.gender} • Parish: ${dup.parish_name || 'Diocesan Registry'}</span>
                            </div>
                            <span style="background: rgba(148, 163, 184, 0.1); color: #94a3b8; padding: 2px 8px; border-radius: 4px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">${dup.status || 'Active'}</span>
                        `;
                        duplicatesList.appendChild(item);
                    });
                    warningContainer.style.display = 'block';
                } else {
                    warningContainer.style.display = 'none';
                }
            })
            .catch(err => {
                console.error("Duplicate check error:", err);
            });
    }

    firstNameInput.addEventListener('blur', checkDuplicates);
    lastNameInput.addEventListener('blur', checkDuplicates);
    firstNameInput.addEventListener('input', checkDuplicates);
    lastNameInput.addEventListener('input', checkDuplicates);
    </script>
</body>
</html>
