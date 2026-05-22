<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Register Religious Profession (Vows)
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Register Religious Profession";
$header_subtitle = "Record first or perpetual vows for religious brothers and sisters.";

$pdo = getDB();
// Fetch parishioners and parishes for dropdowns (not deceased)
$parishioners = db_fetchAll("SELECT person_id, first_name, last_name FROM parishioners WHERE status != 'Deceased' ORDER BY last_name, first_name");
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");

$congregations = ['OFM Capuchin', 'FMDM sisters', 'SMI', 'AMR'];

$preselected_id = $_GET['person_id'] ?? null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person_id = $_POST['person_id'] ?? null;
    $parish_id = $_POST['parish_id'] ?? null;
    $record_type = $_POST['record_type'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $celebrant_superior = $_POST['celebrant_superior'] ?? '';
    $place = $_POST['place'] ?? '';
    $register_book = $_POST['register_book_number'] ?? '';
    $page_number = $_POST['page_number'] ?? '';
    $entry_number = $_POST['entry_number'] ?? '';
    $congregation_select = $_POST['congregation_select'] ?? '';
    $congregation_custom = $_POST['congregation_custom'] ?? '';
    $religious_name = $_POST['religious_name'] ?? '';
    $status = 'Verified'; // Automatically verified since it's a direct entry

    $congregation = ($congregation_select === 'Other') ? $congregation_custom : $congregation_select;

    if ($person_id && $parish_id && $event_date && $record_type && $congregation) {
        $verification_hash = bin2hex(random_bytes(16));
        $guid = bin2hex(random_bytes(16));
        $v_by = $_SESSION['user_id'];
        $v_at = date('Y-m-d H:i:s');
        
        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO ordinations_professions (person_id, parish_id, record_type, congregation, event_date, celebrant_superior, place, register_book_number, page_number, entry_number, verification_hash, status, verified_by, verified_at, guid) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            db_query($sql, [$person_id, $parish_id, $record_type, $congregation, $event_date, $celebrant_superior, $place, $register_book, $page_number, $entry_number, $verification_hash, $status, $v_by, $v_at, $guid]);
            $new_id = $pdo->lastInsertId();

            // Update title/name in parishioners if final profession or religious name taken
            if (!empty($religious_name)) {
                // If religious name is taken, update the title to Sr. or Br. depending on gender or congregation
                $subject = db_fetch("SELECT gender FROM parishioners WHERE person_id = ?", [$person_id]);
                $new_title = ($subject['gender'] === 'Female') ? 'Sr.' : 'Br.';
                
                db_query("UPDATE parishioners SET title = ?, other_names = ? WHERE person_id = ?", [$new_title, $religious_name, $person_id]);
            }

            // Add Marginal Notation to Baptismal Record
            $baptism = db_fetch("SELECT baptism_id FROM baptisms WHERE person_id = ?", [$person_id]);
            if ($baptism) {
                $parish_data = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$parish_id]);
                $parish_name = $parish_data['parish_name'] ?? 'Unknown Parish';
                $details = "Religious Profession ($record_type) in $congregation. Name: $religious_name. Superior: $celebrant_superior";
                
                db_query("INSERT INTO sacraments_notations (baptism_id, sacrament_type, event_date, parish_name, details, status) 
                          VALUES (?, 'Other', ?, ?, ?, ?)", 
                          [$baptism['baptism_id'], $event_date, $parish_name, $details, $status]);
            }

            $pdo->commit();

            log_audit($_SESSION['user_id'], 'CREATE', 'ordinations_professions', $new_id, "Registered Vows ($record_type) for person_id: $person_id");
            
            set_flash("Religious Profession has been successfully registered.");
            redirect("vocations_list.php");
        } catch (Exception $e) {
            $pdo->rollBack();
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
    <title>Register Religious Vows - Hwange Diocesan RMS</title>
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
                    <a href="vocations_list.php" class="btn btn-secondary" style="display: flex; width: max-content; align-items: center; gap: 8px;">
                        <ion-icon name="arrow-back-outline"></ion-icon> Cancel & Return
                    </a>
                </div>

                <div class="card bg-card" style="padding: 3rem; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                    
                    <?php if ($error): ?>
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: white; padding: 1rem; border-radius: 1rem; margin-bottom: 2rem; text-align: center;">
                            <?php echo h($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="premium-form">
                        
                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #fbbf24; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="person-outline"></ion-icon> Subject Information
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Faithful Subject *</label>
                                    <select name="person_id" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <option value="">-- Select Person --</option>
                                        <?php foreach ($parishioners as $p): ?>
                                            <option value="<?php echo $p['person_id']; ?>" <?php echo $preselected_id == $p['person_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($p['last_name'] . ', ' . $p['first_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div style="margin-top: 10px; font-size: 0.8rem;">
                                        <span style="color: var(--text-muted);">Person not listed?</span>
                                        <a href="../parishioners/add.php?ref=vows" style="color: #fbbf24; text-decoration: none; font-weight: 700; margin-left: 5px;">+ Register New Person First</a>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Vow Type *</label>
                                    <select name="record_type" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <option value="First Vows">First Vows (Religious)</option>
                                        <option value="Perpetual Profession">Perpetual Profession (Religious)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Religious / Devotional Name (if taken)</label>
                                <input type="text" name="religious_name" placeholder="e.g. Sister Maria de Fatima or Brother Dominic" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #fbbf24; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="flame-outline"></ion-icon> Vocation Details
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Date of Vows *</label>
                                    <input type="date" name="event_date" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Congregation *</label>
                                    <select name="congregation_select" id="congregation_select" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;" onchange="toggleCustomCongregation(this.value)">
                                        <option value="">-- Select Congregation --</option>
                                        <?php foreach ($congregations as $c): ?>
                                            <option value="<?php echo $c; ?>"><?php echo h($c); ?></option>
                                        <?php endforeach; ?>
                                        <option value="Other">Other (Specify below)</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group" id="custom_congregation_group" style="display: none;">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Specify Congregation *</label>
                                    <input type="text" name="congregation_custom" id="congregation_custom" placeholder="e.g. Salesians of Don Bosco" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Superior / Officiant *</label>
                                    <input type="text" name="celebrant_superior" required placeholder="e.g. Mother Superior or Bishop" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Parish of Celebration *</label>
                                    <select name="parish_id" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <option value="">-- Select Parish --</option>
                                        <?php foreach ($parishes as $pa): ?>
                                            <option value="<?php echo $pa['parish_id']; ?>" <?php echo (isset($_SESSION['parish_id']) && $_SESSION['parish_id'] == $pa['parish_id']) ? 'selected' : ''; ?>><?php echo h($pa['parish_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Specific Place / Convent</label>
                                    <input type="text" name="place" placeholder="e.g. SMI Mother House Chapel, Hwange" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 3rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: #fbbf24; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="archive-outline"></ion-icon> Registry Location
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
                            <button type="submit" class="btn btn-primary" style="background: #fbbf24; color: #000; font-weight: 800; padding: 1.25rem 3rem; border-radius: 1rem; border: none; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(251, 191, 36, 0.3);">Register Vows</button>
                        </div>
                    </form>

                </div>

                <?php include '../includes/privacy_footer.php'; ?>

            </div>

        </main>
    </div>

    <script>
        function toggleCustomCongregation(val) {
            var customGroup = document.getElementById('custom_congregation_group');
            var customInput = document.getElementById('congregation_custom');
            if (val === 'Other') {
                customGroup.style.display = 'block';
                customInput.setAttribute('required', 'required');
            } else {
                customGroup.style.display = 'none';
                customInput.removeAttribute('required');
            }
        }
    </script>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
