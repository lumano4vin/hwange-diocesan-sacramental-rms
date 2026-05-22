<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Edit Parishioner Profile
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? null;
if (!$id) {
    set_flash("Parishioner ID is required.", "error");
    redirect("view.php");
}

$pdo = getDB();

// Fetch existing data
$stmt = $pdo->prepare("SELECT * FROM parishioners WHERE person_id = ?");
$stmt->execute([$id]);
$parishioner = $stmt->fetch();

if (!$parishioner) {
    set_flash("Faithful record not found.", "error");
    redirect("view.php");
}

// Permission check: Admins can edit anything. Parish priests can edit their own parishioners.
$user_role = strtolower($_SESSION['role'] ?? '');
$is_admin = ($user_role === 'admin' || $user_role === 'chancellor');
$is_own_parish = ($parishioner['current_parish_id'] == $_SESSION['parish_id'] && !empty($_SESSION['parish_id']));

if (!$is_admin && !$is_own_parish) {
    set_flash("Access Denied: You can only edit parishioners from your own parish.", "error");
    redirect("view.php?id=" . $id);
}

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
    $residence = upper($_POST['residence'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    $pobap = upper($_POST['pobap'] ?? '');

    if ($first_name && $last_name) {
        try {
            $stmt = $pdo->prepare("
                UPDATE parishioners SET 
                    first_name = ?, 
                    other_names = ?, 
                    last_name = ?, 
                    gender = ?, 
                    dob = ?, 
                    place_of_birth = ?, 
                    father_name = ?, 
                    mother_name = ?, 
                    scc_name = ?, 
                    current_parish_id = ?, 
                    status = ?,
                    place_of_residence = ?,
                    place_of_baptism = ?
                WHERE person_id = ?
            ");
            $stmt->execute([$first_name, $middle_name, $last_name, $gender, $dob, $pob, $father, $mother, $scc, $parish_id, $status, $residence, $pobap, $id]);
            
            log_audit($_SESSION['user_id'], "UPDATE", "parishioners", $id, "Updated Parishioner Profile: $first_name $last_name");
            
            set_flash("Parishioner profile updated successfully.");
            redirect("view.php?id=" . $id);
        } catch (Exception $e) {
            $error = "Update Error: " . $e->getMessage();
        }
    } else {
        $error = "First name and Last name are required.";
    }
}

// Header metadata
$header_title = "Edit Faithful Profile";
$header_subtitle = "Update canonical details for " . h($parishioner['first_name'] . ' ' . $parishioner['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Parishioner - <?php echo get_diocese_branding(); ?></title>
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

            <div class="content-body" style="padding: 2rem 0;">
                
                <div style="max-width: 900px; margin: 0 auto;">
                    
                    <div class="action-bar" style="margin-bottom: 2rem;">
                        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
                            <ion-icon name="arrow-back-outline"></ion-icon> Cancel & Return
                        </a>
                    </div>

                    <?php if ($error): ?>
                        <div class="card" style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--error); color: white; padding: 1.5rem; border-radius: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                            <ion-icon name="alert-circle" style="font-size: 2rem; color: var(--error);"></ion-icon>
                            <div>
                                <h4 style="margin-bottom: 4px;">Update Failed</h4>
                                <p style="font-size: 0.9rem; color: var(--text-muted);"><?php echo h($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card bg-card" style="border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05); padding: 3rem;">
                        <form method="POST" action="edit.php?id=<?php echo $id; ?>" style="display: flex; flex-direction: column; gap: 2.5rem;">
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
                                        <input type="text" name="first_name" required value="<?php echo h($parishioner['first_name']); ?>" class="input-field force-caps">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Other Names</label>
                                        <input type="text" name="middle_name" value="<?php echo h($parishioner['other_names']); ?>" class="input-field force-caps">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Last Name *</label>
                                        <input type="text" name="last_name" required value="<?php echo h($parishioner['last_name']); ?>" class="input-field force-caps">
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
                                        <select name="gender" class="input-field">
                                            <option value="Male" <?php echo $parishioner['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $parishioner['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Unknown" <?php echo $parishioner['gender'] == 'Unknown' ? 'selected' : ''; ?>>Unknown / Not Specified</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Date of Birth</label>
                                        <input type="date" name="dob" id="dob-input" value="<?php echo h($parishioner['dob']); ?>" class="input-field">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Calculated Age</label>
                                        <input type="text" id="age-display" readonly style="width: 100%; padding: 1rem; background: rgba(56, 189, 248, 0.05); border: 1px solid rgba(56, 189, 248, 0.2); border-radius: 1rem; color: var(--accent); font-weight: 700;">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Place of Birth</label>
                                        <input type="text" name="pob" value="<?php echo h($parishioner['place_of_birth']); ?>" class="input-field force-caps">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Place of Baptism</label>
                                        <input type="text" name="pobap" value="<?php echo h($parishioner['place_of_baptism'] ?? ''); ?>" class="input-field force-caps" placeholder="If already baptized elsewhere">
                                    </div>
                                    <div class="form-group" style="grid-column: span 3;">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Place of Residence (Village/Area)</label>
                                        <input type="text" name="residence" value="<?php echo h($parishioner['place_of_residence'] ?? ''); ?>" class="input-field force-caps">
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
                                        <input type="text" name="father" value="<?php echo h($parishioner['father_name']); ?>" class="input-field force-caps">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Mother's Name (Maiden)</label>
                                        <input type="text" name="mother" value="<?php echo h($parishioner['mother_name']); ?>" class="input-field force-caps">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Small Christian Community (SCC)</label>
                                        <input type="text" name="scc" value="<?php echo h($parishioner['scc_name']); ?>" class="input-field force-caps">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Home Parish</label>
                                        <select name="parish_id" class="input-field">
                                            <option value="">Select Parish...</option>
                                            <?php foreach ($parishes as $p): ?>
                                                <option value="<?php echo $p['id']; ?>" <?php echo $parishioner['current_parish_id'] == $p['id'] ? 'selected' : ''; ?>><?php echo h($p['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;">Membership Status</label>
                                        <select name="status" class="input-field">
                                            <option value="Active" <?php echo $parishioner['status'] == 'Active' ? 'selected' : ''; ?>>Active (Local Member)</option>
                                            <option value="Transferred In" <?php echo $parishioner['status'] == 'Transferred In' ? 'selected' : ''; ?>>Immigrant (Transferred In)</option>
                                            <option value="Transferred Out" <?php echo $parishioner['status'] == 'Transferred Out' ? 'selected' : ''; ?>>Migrant (Transferred Out)</option>
                                            <option value="Deceased" <?php echo $parishioner['status'] == 'Deceased' ? 'selected' : ''; ?>>Deceased</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div style="text-align: right; margin-top: 1rem;">
                                <button type="submit" class="btn btn-primary" style="padding: 1.25rem 3rem; border-radius: 1.25rem; font-weight: 900; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 2px;">
                                    Save Changes
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
    function calculateAge() {
        const dobVal = document.getElementById('dob-input').value;
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
    }
    document.getElementById('dob-input').addEventListener('change', calculateAge);
    window.addEventListener('DOMContentLoaded', calculateAge);
    </script>
</body>
</html>
