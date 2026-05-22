<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Register Confirmation - Modernized & Backend Polished
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Register Confirmation";
$header_subtitle = "Enter canonical confirmation details into the Diocesan archives.";

// Fetch parishioners and parishes for dropdowns
$parishioners = db_fetchAll("SELECT person_id, first_name, last_name, other_names FROM parishioners ORDER BY last_name, first_name");
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");

$preselected_id = $_GET['person_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person_id = $_POST['person_id'] ?? null;
    $parish_id = $_POST['parish_id'] ?? null;
    $date_of_confirmation = $_POST['date_of_confirmation'] ?? '';
    $minister = $_POST['minister'] ?? '';
    $sponsor = $_POST['sponsor'] ?? '';
    $register_book = $_POST['register_book_number'] ?? '';
    $page_number = $_POST['page_number'] ?? '';
    $entry_number = $_POST['entry_number'] ?? '';

    if ($person_id && $parish_id && $date_of_confirmation) {
        $verification_hash = bin2hex(random_bytes(16));
        $guid = bin2hex(random_bytes(16));
        $diocese_id = $primary_diocese['diocese_id'] ?? 1;
        
        try {
            $sql = "INSERT INTO confirmations (person_id, parish_id, date_of_confirmation, minister, sponsor, register_book_number, page_number, entry_number, verification_hash, guid, diocese_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            db_query($sql, [$person_id, $parish_id, $date_of_confirmation, $minister, $sponsor, $register_book, $page_number, $entry_number, $verification_hash, $guid, $diocese_id]);
            
            $new_id = db_fetch("SELECT last_insert_rowid() as id")['id'];
            log_audit($_SESSION['user_id'], 'CREATE', 'confirmations', $new_id, "Registered confirmation for person_id: $person_id");
            
            set_flash("Confirmation record has been successfully registered.");
            redirect("confirmation_list.php");
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
    <title>Register Confirmation - Hwange Diocesan RMS</title>
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
                    <a href="confirmation_list.php" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
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
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #ef4444; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="flame-outline"></ion-icon> Primary Subject Information
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Faithful Subject *</label>
                                    <select name="person_id" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <option value="">-- Select Parishioner --</option>
                                        <?php foreach ($parishioners as $p): ?>
                                            <option value="<?php echo $p['person_id']; ?>" <?php echo $preselected_id == $p['person_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($p['last_name'] . ', ' . $p['first_name'] . ($p['other_names'] ? ' ' . $p['other_names'] : '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #ef4444; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="calendar-outline"></ion-icon> Canonical Details
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Confirmation *</label>
                                    <input type="date" name="date_of_confirmation" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Minister (Bishop/Priest)</label>
                                    <input type="text" name="minister" placeholder="e.g. Rt. Rev. Bishop" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Sponsor (Confirmation Name/Godparent)</label>
                                    <input type="text" name="sponsor" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #ef4444; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
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
                            <button type="submit" class="btn btn-primary" style="background: #ef4444; color: white; font-weight: 800; padding: 1.25rem 3rem; border-radius: 1rem; border: none; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);">Confirm Registry Entry</button>
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
