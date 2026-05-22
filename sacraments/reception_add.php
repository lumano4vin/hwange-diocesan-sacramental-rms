<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Register Reception into Full Communion (Profession of Faith)
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Profession of Faith";
$header_subtitle = "Register reception into full communion with the Catholic Church.";

// Fetch parishioners and parishes for dropdowns
$parishioners = db_fetchAll("SELECT person_id, first_name, last_name, other_names FROM parishioners ORDER BY last_name, first_name");
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");

$preselected_id = $_GET['person_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person_id = $_POST['person_id'] ?? null;
    $parish_id = $_POST['parish_id'] ?? null;
    $date_of_reception = $_POST['date_of_reception'] ?? '';
    $minister = $_POST['minister'] ?? '';
    $previous_denomination = $_POST['previous_denomination'] ?? '';
    $godparents_sponsors = $_POST['godparents_sponsors'] ?? '';
    $register_book = $_POST['register_book_number'] ?? '';
    $page_number = $_POST['page_number'] ?? '';
    $entry_number = $_POST['entry_number'] ?? '';
    $prev_baptism_church = $_POST['previous_baptism_church'] ?? '';
    $prev_baptism_date = !empty($_POST['previous_baptism_date']) ? $_POST['previous_baptism_date'] : null;
    $prev_baptism_place = $_POST['previous_baptism_place'] ?? '';
    $status = 'Verified'; // Automatically verified since it's a direct entry

    if ($person_id && $parish_id && $date_of_reception) {
        $verification_hash = bin2hex(random_bytes(16));
        $guid = bin2hex(random_bytes(16));
        $diocese_id = 1;
        
        try {
            $sql = "INSERT INTO receptions (person_id, parish_id, date_of_reception, minister, previous_denomination, godparents_sponsors, register_book_number, page_number, entry_number, previous_baptism_church, previous_baptism_date, previous_baptism_place, verification_hash, status, guid, diocese_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            db_query($sql, [$person_id, $parish_id, $date_of_reception, $minister, $previous_denomination, $godparents_sponsors, $register_book, $page_number, $entry_number, $prev_baptism_church, $prev_baptism_date, $prev_baptism_place, $verification_hash, $status, $guid, $diocese_id]);
            
            $new_id = db_fetch("SELECT last_insert_rowid() as id")['id'];
            log_audit($_SESSION['user_id'], 'CREATE', 'receptions', $new_id, "Registered profession of faith for person_id: $person_id");
            
            set_flash("Profession of Faith has been successfully registered.");
            redirect("reception_list.php");
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
    <title>Register Profession of Faith - Hwange Diocesan RMS</title>
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
                    <a href="reception_list.php" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
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
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #8b5cf6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="person-outline"></ion-icon> Primary Subject Information
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Faithful Subject *</label>
                                    <select name="person_id" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <option value="">-- Select Parishioner --</option>
                                        <?php foreach ($parishioners as $p): ?>
                                            <option value="<?php echo $p['person_id']; ?>" <?php echo $preselected_id == $p['person_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($p['last_name'] . ', ' . $p['first_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div style="margin-top: 10px; font-size: 0.8rem;">
                                        <span style="color: var(--text-muted);">Person not listed?</span>
                                        <a href="../parishioners/add.php?ref=reception" style="color: #8b5cf6; text-decoration: none; font-weight: 700; margin-left: 5px;">+ Register New Person First</a>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Parish of Reception *</label>
                                    <select name="parish_id" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <option value="">-- Select Parish --</option>
                                        <?php foreach ($parishes as $pa): ?>
                                            <option value="<?php echo $pa['parish_id']; ?>" <?php echo (isset($_SESSION['parish_id']) && $_SESSION['parish_id'] == $pa['parish_id']) ? 'selected' : ''; ?>><?php echo h($pa['parish_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #8b5cf6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="shield-half-outline"></ion-icon> Canonical Reception Details
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Reception *</label>
                                    <input type="date" name="date_of_reception" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Previous Denomination</label>
                                    <input type="text" name="previous_denomination" placeholder="e.g. Anglican, Methodist" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Minister</label>
                                    <input type="text" name="minister" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Sponsors / Witnesses</label>
                                    <input type="text" name="godparents_sponsors" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #8b5cf6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="water-outline"></ion-icon> Original Baptismal Information
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Original Church/Place of Baptism</label>
                                    <input type="text" name="previous_baptism_church" placeholder="e.g. St. James Anglican Church" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Original Baptism</label>
                                    <input type="date" name="previous_baptism_date" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Location (City/Town/Country)</label>
                                <input type="text" name="previous_baptism_place" placeholder="e.g. Bulawayo, Zimbabwe" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">

                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #8b5cf6; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="archive-outline"></ion-icon> Registry Reference
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Book / Vol *</label>
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
                            <button type="submit" class="btn btn-primary" style="background: #8b5cf6; color: #fff; font-weight: 800; padding: 1.25rem 3rem; border-radius: 1rem; border: none; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);">Register Profession of Faith</button>
                        </div>
                    </form>

                </div>

                <?php include '../includes/privacy_footer.php'; ?>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
