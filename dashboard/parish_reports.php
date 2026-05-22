<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Annua Statistica OMEGA TOTAL ACCOUNTABILITY (V27.0)
 * NO ABBREVIATIONS - FULL TITLES - ZERO SUPPRESSION - EXHAUSTIVE HEALTH MATRIX
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

// 1. Core Selection & Isolation
$parishes = db_fetchAll("SELECT * FROM parishes ORDER BY parish_name ASC");
$user_parish_id = $_SESSION['parish_id'] ?? null;
$is_admin = is_admin();

$selected_parish_id = $_GET['parish_id'] ?? $_POST['parish_id'] ?? $user_parish_id;
$selected_year = $_GET['year'] ?? $_POST['year'] ?? date('Y');

// Force isolation for non-admins
if (!$is_admin && $user_parish_id) {
    $selected_parish_id = $user_parish_id;
}

// 2. Handle POST (Save Report)
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['m'])) {
    try {
        $m_data = $_POST['m'];
        $json_data = json_encode($m_data);
        
        // Use UPSERT logic (SQLite: INSERT OR REPLACE)
        db_query("INSERT OR REPLACE INTO annual_reports (parish_id, report_year, report_data, submitted_by) VALUES (?, ?, ?, ?)", [
            $selected_parish_id,
            $selected_year,
            $json_data,
            $_SESSION['user_id']
        ]);
        
        $success_msg = "OMEGA Archive for $selected_year successfully committed to the Diocesan Vault.";
        $m = $m_data; // Use the posted data for display
    } catch (Exception $e) {
        $error_msg = "Archive Failure: " . $e->getMessage();
    }
}

// 3. THE TOTAL EXHAUSTIVE DEFAULTS
$defaults = [
    // I. CHURCHES & PASTORAL CENTRES
    'inst_ch_bs_list' => '', 'inst_ch_perm_no_bs' => 0, 'inst_mass_regular_count' => 0, 'inst_other_centres_names' => '',
    'inst_pastoral_centres_count' => 0, 'inst_pastoral_centres_list' => '',
    'inst_pastoral_attendees_male' => 0, 'inst_pastoral_attendees_female' => 0,
    'c_pop_total' => 0, 'c_households' => 0, 

    // II. PERSONNEL (FULL TITLES)
    'p_priest_diocesan' => 0, 'p_priest_religious' => 0,
    'p_deacon_diocesan' => 0, 'p_deacon_religious' => 0,
    'p_brother_diocesan' => 0, 'p_brother_religious' => 0,
    'p_sister_diocesan' => 0, 'p_sister_religious' => 0,
    'p_catechist_fulltime' => 0, 'p_catechist_parttime' => 0,

    // III. VOCATIONS (FULL TITLES)
    'voc_priest_diocesan' => 0, 'voc_priest_religious' => 0,
    'voc_seminarian_philosophy' => 0, 'voc_seminarian_theology' => 0,
    'voc_seminarian_major' => 0, 'voc_seminarian_minor' => 0,
    'voc_professed_brother' => 0, 'voc_professed_sister' => 0,
    'voc_novice_brother' => 0, 'voc_novice_sister' => 0,
    'voc_postulant_brother' => 0, 'voc_postulant_sister' => 0,
    'voc_candidate_brother' => 0, 'voc_candidate_sister' => 0,

    // III-B. GOVERNANCE / IN CHARGE
    'gov_in_charge' => '',

    // IV. THE EXHAUSTIVE HEALTH MATRIX (TOTAL RESTORATION)
    'h_hospitals' => 0, 'h_clinics' => 0, 'h_maternity_units' => 0, 'h_leprosaria' => 0,
    'h_beds_total' => 0,
    // Deaths in Facilities
    'h_death_resident_male' => 0, 'h_death_resident_female' => 0, 'h_death_nonresident_male' => 0, 'h_death_nonresident_female' => 0,
    // Care Homes
    'h_care_elderly_resident_male' => 0, 'h_care_elderly_resident_female' => 0, 'h_care_elderly_nonresident_male' => 0, 'h_care_elderly_nonresident_female' => 0,
    'h_care_disabled_resident_male' => 0, 'h_care_disabled_resident_female' => 0, 'h_care_disabled_nonresident_male' => 0, 'h_care_disabled_nonresident_female' => 0,
    'h_care_orphans_resident_male' => 0, 'h_care_orphans_resident_female' => 0, 'h_care_orphans_nonresident_male' => 0, 'h_care_orphans_nonresident_female' => 0,
    'h_nurseries' => 0, 'h_social_centres' => 0,
    // Patient Matrix
    'h_in_catholic_male' => 0, 'h_in_catholic_female' => 0, 'h_in_noncatholic_male' => 0, 'h_in_noncatholic_female' => 0,
    'h_out_catholic_male' => 0, 'h_out_catholic_female' => 0, 'h_out_noncatholic_male' => 0, 'h_out_noncatholic_female' => 0,
    // Maternity & Pediatrics
    'h_ante_natal' => 0, 'h_baby_clinic_boys' => 0, 'h_baby_clinic_girls' => 0,
    'h_birth_male' => 0, 'h_birth_female' => 0,
    // Medical Personnel
    'h_doc_religious' => 0, 'h_doc_lay' => 0,
    'h_nurse_qualified_religious' => 0, 'h_nurse_qualified_lay' => 0,
    'h_nurse_primary_religious' => 0, 'h_nurse_primary_lay' => 0,
    'h_paramedical_religious' => 0, 'h_paramedical_lay' => 0,
    // Hospital Pastoral Care
    'h_baptism_adult_male' => 0, 'h_baptism_adult_female' => 0, 'h_baptism_infant_male' => 0, 'h_baptism_infant_female' => 0,
    'h_anointing' => 0,

    // V. FINANCE LEDGER (FULL TITLES)
    'f_tithe' => 0.00, 'f_offertory_cash' => 0.00, 'f_offertory_kind' => 0.00, 'f_missionary_childhood' => 0.00, 'f_lenten_sacrifice' => 0.00, 
    'f_holy_land' => 0.00, 'f_seminary' => 0.00, 'f_social_communications' => 0.00, 'f_mission_sunday' => 0.00, 
    'f_caritas' => 0.00, 'f_peters_pence' => 0.00, 'f_donations_cash' => 0.00, 'f_donations_kind' => 0.00,
    'f_other_value' => 0.00, 'f_other_specification' => '',
    'f_currency' => 'USD',

    // VI. GUILDS
    'g_legion_of_mary' => 0, 'g_st_anne' => 0, 'g_st_joseph' => 0, 'g_catholic_youth_association' => 0, 'g_catholic_charismatic_renewal' => 0, 'g_sacred_heart' => 0, 'g_jufras' => 0, 'g_choir' => 0, 'g_altar_servers' => 0, 'g_missionary_childhood' => 0,
    'sac_first_communion' => 0, 'sac_anointing_total' => 0, 'sac_rcia_candidates' => 0,

    // VIII. STATUS ANIMARUM (POPULATION DYNAMICS)
    'pop_catholic_start' => 0,
    'pop_inc_baptisms' => 0,
    'pop_inc_immigrants' => 0,
    'pop_inc_others' => 0,
    'pop_dec_deaths' => 0,
    'pop_dec_emigrants' => 0,
    'pop_dec_others' => 0,
    'pop_catholic_end' => 0,
    'pop_catechumens' => 0,

    // IX. MARRIAGES (CHURCH TERMINOLOGY)
    'm_catholic_catholic' => 0,
    'm_mixed_religion' => 0,
    'm_disparity_cult' => 0,
    'm_total_marriages' => 0,

    // X. OBSERVATIONS
    'report_observations' => ''
];

// VII. EDUCATION MATRIX
$edu_levels = ['pre_school' => 'Pre-School', 'primary' => 'Primary School', 'secondary' => 'Secondary School', 'vocational' => 'Vocational Training', 'adult_literacy' => 'Adult Literacy', 'commercial' => 'Commercial School'];
foreach ($edu_levels as $key => $name) {
    $defaults["e_{$key}_catholic_male"] = 0; $defaults["e_{$key}_catholic_female"] = 0; 
    $defaults["e_{$key}_noncatholic_male"] = 0; $defaults["e_{$key}_noncatholic_female"] = 0;
    $defaults["e_{$key}_teacher_catholic_male"] = 0; $defaults["e_{$key}_teacher_catholic_female"] = 0;
    $defaults["e_{$key}_teacher_noncatholic_male"] = 0; $defaults["e_{$key}_teacher_noncatholic_female"] = 0;
    $defaults["e_{$key}_teacher_religious"] = 0;
}

// 4. LOAD SAVED DATA (if exists)
if (!isset($m)) {
    $saved_report = db_fetch("SELECT report_data FROM annual_reports WHERE parish_id = ? AND report_year = ?", [
        $selected_parish_id,
        $selected_year
    ]);
    
    if ($saved_report) {
        $m = json_decode($saved_report['report_data'], true);
    } else {
        $m = $_GET['m'] ?? [];
    }
}

$m = array_merge($defaults, $m);

if ($selected_parish_id) {
    $parish_info = db_fetch("SELECT * FROM parishes WHERE parish_id = ?", [$selected_parish_id]);
    
    // Auto-fetch Baptisms and Deaths for this parish and year if not already saved
    if (!isset($saved_report)) {
        $db_baptisms = db_fetch("SELECT COUNT(*) as count FROM baptisms WHERE parish_id = ? AND strftime('%Y', date_of_baptism) = ?", [$selected_parish_id, $selected_year])['count'];
        $db_deaths = db_fetch("SELECT COUNT(*) as count FROM deaths WHERE parish_id = ? AND strftime('%Y', date_of_death) = ?", [$selected_parish_id, $selected_year])['count'];
        $db_marriages = db_fetch("SELECT COUNT(*) as count FROM marriages WHERE parish_id = ? AND strftime('%Y', date_of_marriage) = ?", [$selected_parish_id, $selected_year])['count'];
        
        $m['pop_inc_baptisms'] = $m['pop_inc_baptisms'] ?: $db_baptisms;
        $m['pop_dec_deaths'] = $m['pop_dec_deaths'] ?: $db_deaths;
        $m['m_total_marriages'] = $m['m_total_marriages'] ?: $db_marriages;
    }

    $report_data = ['parish' => $parish_info, 'year' => $selected_year, 'm' => $m];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Annua Statistica OMEGA TOTAL v27.0</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .omega-layout { display: grid; grid-template-columns: 550px 1fr; gap: 2rem; align-items: start; }
        .omega-sidebar { background: var(--card-bg); padding: 1.5rem; border-radius: 24px; border: 1px solid rgba(255,255,255,0.1); height: calc(100vh - 120px); overflow-y: auto; position: sticky; top: 1rem; }
        .omega-card { background: rgba(0,0,0,0.4); padding: 1.5rem; border-radius: 20px; margin-bottom: 2rem; border: 1px solid rgba(255,255,255,0.05); }
        .omega-card h5 { color: var(--accent); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .f-label { font-size: 0.6rem; color: #94a3b8; display: block; margin-bottom: 4px; font-weight: 700; text-transform: uppercase; }
        .seal-wrapper { display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; height: 90px; width: 90px; border-radius: 50%; overflow: hidden; border: 2px solid #e2e8f0; box-shadow: 0 5px 15px rgba(0,0,0,0.2); background: #fff; }
        .seal-img { height: auto; width: 100%; object-fit: contain; transform: scale(1.05); }
        .f-input, .f-area { width: 100%; padding: 0.6rem; background: #000; border: 1px solid #333; color: #fff; border-radius: 8px; font-size: 0.8rem; margin-bottom: 10px; }
        .f-area { min-height: 80px; resize: vertical; }
        .f-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; background: rgba(0,0,0,0.2); }
        .f-table th { font-size: 0.45rem; color: var(--accent); padding: 6px; text-transform: uppercase; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .f-table td { padding: 4px; border-bottom: 1px solid rgba(255,255,255,0.02); }
        .f-table input { width: 100%; padding: 0.5rem; background: #000; border: 1px solid #222; color: #fff; text-align: center; font-size: 0.75rem; }
        .row-lbl { font-size: 0.55rem; font-weight: 700; color: #64748b; padding-left: 5px !important; }

        .doc-canvas { 
            background: white;
            color: #0f172a; 
            padding: 30px; 
            font-family: 'Inter', sans-serif; 
            box-shadow: 0 50px 100px rgba(0,0,0,0.5); 
            border-radius: 4px; 
        }
        .doc-header { text-align: center; border-bottom: 8px double #000; padding-bottom: 2rem; margin-bottom: 3.5rem; position: relative; z-index: 10; }
        .doc-sec { background: #f1f5f9; padding: 12px 15px; font-weight: 900; text-transform: uppercase; margin: 3rem 0 1.5rem; border-left: 12px solid #0f172a; font-family: 'Outfit'; font-size: 1.1rem; position: relative; z-index: 10; }
        .doc-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; position: relative; z-index: 10; background: rgba(255,255,255,0.7); }
        .doc-table th { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px; font-size: 0.65rem; color: #64748b; text-transform: uppercase; }
        .doc-table td { border: 1px solid #e2e8f0; padding: 10px; font-size: 0.85rem; }
        .page-break { page-break-before: always; margin-top: 3rem; }
    </style>
</head>
<body class="dashboard-body">
    <div id="app-layout" class="dashboard-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="font-family: 'Outfit'; color: #fff; margin: 0;">Annua Statistica OMEGA</h2>
                <a href="omega_manual.php" target="_blank" class="btn" style="background: var(--accent); color: #fff; font-size: 0.8rem; padding: 10px 20px; border-radius: 12px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 15px rgba(185, 28, 28, 0.3);">
                    <ion-icon name="book-outline" style="vertical-align: middle; margin-right: 5px;"></ion-icon>
                    View OMEGA User Manual
                </a>
            </div>
                <?php if ($success_msg): ?>
                    <div class="alert-banner" style="background: rgba(16,185,129,0.1); border: 1px solid #10b981; color: #10b981; padding: 1rem; border-radius: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                        <ion-icon name="checkmark-circle-outline" style="font-size: 1.5rem;"></ion-icon>
                        <strong>Archive Success:</strong> <?php echo h($success_msg); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_msg): ?>
                    <div class="alert-banner" style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #ef4444; padding: 1rem; border-radius: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                        <ion-icon name="alert-circle-outline" style="font-size: 1.5rem;"></ion-icon>
                        <strong>Archive Error:</strong> <?php echo h($error_msg); ?>
                    </div>
                <?php endif; ?>

                <div class="omega-layout">
                    <!-- THE TOTAL CANONICAL FORM - V27.0 - ZERO SUPPRESSION -->
                    <aside class="omega-sidebar">
                        <form method="POST" id="omega-total-form">
                            <!-- I. CONTEXT -->
                            <div class="omega-card">
                                <h5>I. Archival Context</h5>
                                <label class="f-label">Parish / Mission Name</label>
                                <?php if ($is_admin): ?>
                                    <select name="parish_id" class="f-input" required onchange="window.location.href='?parish_id=' + this.value + '&year=<?php echo $selected_year; ?>'">
                                        <?php foreach($parishes as $p): ?>
                                            <option value="<?php echo $p['parish_id']; ?>" <?php echo $selected_parish_id == $p['parish_id'] ? 'selected' : ''; ?>>
                                                <?php echo h($p['parish_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" class="f-input" value="<?php echo h($report_data['parish']['parish_name'] ?? ''); ?>" readonly style="opacity: 0.7; cursor: not-allowed;">
                                    <input type="hidden" name="parish_id" value="<?php echo h($selected_parish_id); ?>">
                                <?php endif; ?>

                                <label class="f-label">Reporting Year</label>
                                <select name="year" class="f-input" onchange="window.location.href='?parish_id=<?php echo $selected_parish_id; ?>&year=' + this.value">
                                    <?php for($y=date('Y'); $y>=2020; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- II. CHURCHES & PASTORAL CENTRES -->
                            <div class="omega-card">
                                <h5>I. Churches and Pastoral Centres</h5>
                                <label class="f-label">Churches with Blessed Sacrament (Name and Place)</label>
                                <textarea name="m[inst_ch_bs_list]" class="f-area"><?php echo h($m['inst_ch_bs_list']); ?></textarea>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                    <div><label class="f-label">Number of Pastoral Centres</label><input type="number" name="m[inst_pastoral_centres_count]" value="<?php echo $m['inst_pastoral_centres_count']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Regular Mass Centres</label><input type="number" name="m[inst_mass_regular_count]" value="<?php echo $m['inst_mass_regular_count']; ?>" class="f-input"></div>
                                </div>
                                <label class="f-label">Locations of Pastoral Centres</label>
                                <textarea name="m[inst_pastoral_centres_list]" class="f-area"><?php echo h($m['inst_pastoral_centres_list']); ?></textarea>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                    <div><label class="f-label">Attendees: Male</label><input type="number" name="m[inst_pastoral_attendees_male]" value="<?php echo $m['inst_pastoral_attendees_male']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Attendees: Female</label><input type="number" name="m[inst_pastoral_attendees_female]" value="<?php echo $m['inst_pastoral_attendees_female']; ?>" class="f-input"></div>
                                </div>
                            </div>

                            <!-- III. PERSONNEL -->
                            <div class="omega-card">
                                <h5>II. Governance and Personnel</h5>
                                
                                <label class="f-label">Ecclesiastical Governance (In Charge)</label>
                                <select name="m[gov_in_charge]" class="f-input" style="margin-bottom: 20px;">
                                    <option value="">-- Select Governance Status --</option>
                                    <optgroup label="Parish/Mission with resident Priest(s)">
                                        <option value="Resident: Diocesan Clergy" <?php echo ($m['gov_in_charge'] == 'Resident: Diocesan Clergy') ? 'selected' : ''; ?>>Under direction of diocesan clergy</option>
                                        <option value="Resident: Religious Priests" <?php echo ($m['gov_in_charge'] == 'Resident: Religious Priests') ? 'selected' : ''; ?>>Under direction of religious priests</option>
                                    </optgroup>
                                    <optgroup label="Parish/Mission without resident Priest(s)">
                                        <option value="Non-Resident: Permanent Deacon" <?php echo ($m['gov_in_charge'] == 'Non-Resident: Permanent Deacon') ? 'selected' : ''; ?>>In the care of a permanent deacon</option>
                                        <option value="Non-Resident: Professed Man Religious" <?php echo ($m['gov_in_charge'] == 'Non-Resident: Professed Man Religious') ? 'selected' : ''; ?>>In care of professed man religious (other than priest/deacon)</option>
                                        <option value="Non-Resident: Professed Woman Religious" <?php echo ($m['gov_in_charge'] == 'Non-Resident: Professed Woman Religious') ? 'selected' : ''; ?>>In care of a professed woman religious</option>
                                        <option value="Non-Resident: Lay Person" <?php echo ($m['gov_in_charge'] == 'Non-Resident: Lay Person') ? 'selected' : ''; ?>>In care of a lay person</option>
                                        <option value="Non-Resident: Completely Vacant" <?php echo ($m['gov_in_charge'] == 'Non-Resident: Completely Vacant') ? 'selected' : ''; ?>>Completely vacant</option>
                                    </optgroup>
                                </select>

                                <table class="f-table">
                                    <tr><th>Category</th><th>Diocesan</th><th>Religious</th></tr>
                                    <tr><td class="row-lbl">Priests</td><td><input type="number" name="m[p_priest_diocesan]" value="<?php echo $m['p_priest_diocesan']; ?>"></td><td><input type="number" name="m[p_priest_religious]" value="<?php echo $m['p_priest_religious']; ?>"></td></tr>
                                    <tr><td class="row-lbl">Deacons</td><td><input type="number" name="m[p_deacon_diocesan]" value="<?php echo $m['p_deacon_diocesan']; ?>"></td><td><input type="number" name="m[p_deacon_religious]" value="<?php echo $m['p_deacon_religious']; ?>"></td></tr>
                                    <tr><td class="row-lbl">Brothers</td><td><input type="number" name="m[p_brother_diocesan]" value="<?php echo $m['p_brother_diocesan']; ?>"></td><td><input type="number" name="m[p_brother_religious]" value="<?php echo $m['p_brother_religious']; ?>"></td></tr>
                                    <tr><td class="row-lbl">Sisters</td><td><input type="number" name="m[p_sister_diocesan]" value="<?php echo $m['p_sister_diocesan']; ?>"></td><td><input type="number" name="m[p_sister_religious]" value="<?php echo $m['p_sister_religious']; ?>"></td></tr>
                                    <tr><td class="row-lbl">Catechists</td><td><input type="number" name="m[p_catechist_fulltime]" value="<?php echo $m['p_catechist_fulltime']; ?>"></td><td><input type="number" name="m[p_catechist_parttime]" value="<?php echo $m['p_catechist_parttime']; ?>"></td></tr>
                                </table>
                            </div>

                            <!-- IV. VOCATIONS -->
                            <div class="omega-card">
                                <h5>III. Vocations Pipeline</h5>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                    <div><label class="f-label">Major: Philosophy</label><input type="number" name="m[voc_seminarian_philosophy]" value="<?php echo $m['voc_seminarian_philosophy']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Major: Theology</label><input type="number" name="m[voc_seminarian_theology]" value="<?php echo $m['voc_seminarian_theology']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Total Major (Calculated)</label><input type="number" value="<?php echo $m['voc_seminarian_philosophy'] + $m['voc_seminarian_theology']; ?>" class="f-input" readonly style="opacity: 0.6;"></div>
                                    <div><label class="f-label">Seminarian Minor</label><input type="number" name="m[voc_seminarian_minor]" value="<?php echo $m['voc_seminarian_minor']; ?>" class="f-input"></div>
                                </div>
                                <p style="font-size:0.55rem; color:var(--accent); font-weight:900; margin:10px 0;">Religious Life Training</p>
                                <table class="f-table">
                                    <tr><th>Stage</th><th>Brothers</th><th>Sisters</th></tr>
                                    <tr><td class="row-lbl">Professed</td><td><input type="number" name="m[voc_professed_brother]" value="<?php echo $m['voc_professed_brother']; ?>"></td><td><input type="number" name="m[voc_professed_sister]" value="<?php echo $m['voc_professed_sister']; ?>"></td></tr>
                                    <tr><td class="row-lbl">Novices</td><td><input type="number" name="m[voc_novice_brother]" value="<?php echo $m['voc_novice_brother']; ?>"></td><td><input type="number" name="m[voc_novice_sister]" value="<?php echo $m['voc_novice_sister']; ?>"></td></tr>
                                    <tr><td class="row-lbl">Postulants</td><td><input type="number" name="m[voc_postulant_brother]" value="<?php echo $m['voc_postulant_brother']; ?>"></td><td><input type="number" name="m[voc_postulant_sister]" value="<?php echo $m['voc_postulant_sister']; ?>"></td></tr>
                                    <tr><td class="row-lbl">Candidates</td><td><input type="number" name="m[voc_candidate_brother]" value="<?php echo $m['voc_candidate_brother']; ?>"></td><td><input type="number" name="m[voc_candidate_sister]" value="<?php echo $m['voc_candidate_sister']; ?>"></td></tr>
                                </table>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px;">
                                    <div><label class="f-label">Priests (Diocesan)</label><input type="number" name="m[voc_priest_diocesan]" value="<?php echo $m['voc_priest_diocesan']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Priests (Religious)</label><input type="number" name="m[voc_priest_religious]" value="<?php echo $m['voc_priest_religious']; ?>" class="f-input"></div>
                                </div>
                            </div>

                            <!-- V. EDUCATION MATRIX -->
                            <div class="omega-card">
                                <h5>V. Education Matrix (Detailed)</h5>
                                <?php foreach ($edu_levels as $key => $name): ?>
                                    <p style="font-size:0.55rem; color:var(--accent); font-weight:900; margin:10px 0 5px;"><?php echo $name; ?></p>
                                    <table class="f-table" style="margin-bottom: 15px;">
                                        <tr><th>Students</th><th>Cath M</th><th>Cath F</th><th>Non-Cath M</th><th>Non-Cath F</th></tr>
                                        <tr>
                                            <td class="row-lbl">Enrolled</td>
                                            <td><input type="number" name="m[e_<?php echo $key; ?>_catholic_male]" value="<?php echo $m["e_{$key}_catholic_male"]; ?>"></td>
                                            <td><input type="number" name="m[e_<?php echo $key; ?>_catholic_female]" value="<?php echo $m["e_{$key}_catholic_female"]; ?>"></td>
                                            <td><input type="number" name="m[e_<?php echo $key; ?>_noncatholic_male]" value="<?php echo $m["e_{$key}_noncatholic_male"]; ?>"></td>
                                            <td><input type="number" name="m[e_<?php echo $key; ?>_noncatholic_female]" value="<?php echo $m["e_{$key}_noncatholic_female"]; ?>"></td>
                                        </tr>
                                    </table>
                                    <table class="f-table" style="margin-bottom: 25px;">
                                        <tr><th>Teachers</th><th>Cath M</th><th>Cath F</th><th>Non-Cath M</th><th>Non-Cath F</th><th>Religious</th></tr>
                                        <tr>
                                            <td class="row-lbl">Staff</td>
                                            <td><input type="number" name="m[e_<?php echo $key; ?>_teacher_catholic_male]" value="<?php echo $m["e_{$key}_teacher_catholic_male"]; ?>"></td>
                                            <td><input type="number" name="m[e_<?php echo $key; ?>_teacher_catholic_female]" value="<?php echo $m["e_{$key}_teacher_catholic_female"]; ?>"></td>
                                            <td><input type="number" name="m[e_<?php echo $key; ?>_teacher_noncatholic_male]" value="<?php echo $m["e_{$key}_teacher_noncatholic_male"]; ?>"></td>
                                            <td><input type="number" name="m[e_<?php echo $key; ?>_teacher_noncatholic_female]" value="<?php echo $m["e_{$key}_teacher_noncatholic_female"]; ?>"></td>
                                            <td><input type="number" name="m[e_<?php echo $key; ?>_teacher_religious]" value="<?php echo $m["e_{$key}_teacher_religious"]; ?>"></td>
                                        </tr>
                                    </table>
                                <?php endforeach; ?>
                            </div>

                             <!-- IV. GUILDS & SACRAMENTS -->
                             <div class="omega-card">
                                 <h5>IV. Parish Guilds and Sacramental Lifecycle</h5>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                    <div><label class="f-label">Legion of Mary</label><input type="number" name="m[g_legion_of_mary]" value="<?php echo $m['g_legion_of_mary']; ?>" class="f-input"></div>
                                    <div><label class="f-label">St. Anne</label><input type="number" name="m[g_st_anne]" value="<?php echo $m['g_st_anne']; ?>" class="f-input"></div>
                                    <div><label class="f-label">St. Joseph</label><input type="number" name="m[g_st_joseph]" value="<?php echo $m['g_st_joseph']; ?>" class="f-input"></div>
                                    <div><label class="f-label">C.Y.A (Youth)</label><input type="number" name="m[g_catholic_youth_association]" value="<?php echo $m['g_catholic_youth_association']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Charismatic Renewal</label><input type="number" name="m[g_catholic_charismatic_renewal]" value="<?php echo $m['g_catholic_charismatic_renewal']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Sacred Heart</label><input type="number" name="m[g_sacred_heart]" value="<?php echo $m['g_sacred_heart']; ?>" class="f-input"></div>
                                    <div><label class="f-label">JUFRAS</label><input type="number" name="m[g_jufras]" value="<?php echo $m['g_jufras']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Parish Choir</label><input type="number" name="m[g_choir]" value="<?php echo $m['g_choir']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Altar Servers</label><input type="number" name="m[g_altar_servers]" value="<?php echo $m['g_altar_servers']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Missionary Childhood</label><input type="number" name="m[g_missionary_childhood]" value="<?php echo $m['g_missionary_childhood']; ?>" class="f-input"></div>
                                </div>
                                <p style="font-size:0.55rem; color:var(--accent); font-weight:900; margin:15px 0 5px;">Sacramental Milestone Counts</p>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                    <div><label class="f-label">First Holy Communion</label><input type="number" name="m[sac_first_communion]" value="<?php echo $m['sac_first_communion']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Anointing of the Sick (Parish-wide)</label><input type="number" name="m[sac_anointing_total]" value="<?php echo $m['sac_anointing_total']; ?>" class="f-input"></div>
                                    <div><label class="f-label">RCIA Candidates</label><input type="number" name="m[sac_rcia_candidates]" value="<?php echo $m['sac_rcia_candidates']; ?>" class="f-input"></div>
                                </div>
                            </div>

                            <!-- VI. THE TOTAL HEALTH & CARE MATRIX -->
                            <div class="omega-card">
                                <h5>VI. Exhaustive Health and Care Matrix</h5>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                    <div><label class="f-label">Mission Hospitals</label><input type="number" name="m[h_hospitals]" value="<?php echo $m['h_hospitals']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Health Centres</label><input type="number" name="m[h_clinics]" value="<?php echo $m['h_clinics']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Maternity Units</label><input type="number" name="m[h_maternity_units]" value="<?php echo $m['h_maternity_units']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Leprosaria</label><input type="number" name="m[h_leprosaria]" value="<?php echo $m['h_leprosaria']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Nurseries</label><input type="number" name="m[h_nurseries]" value="<?php echo $m['h_nurseries']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Social Centres</label><input type="number" name="m[h_social_centres]" value="<?php echo $m['h_social_centres']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Total Capacity (Beds)</label><input type="number" name="m[h_beds_total]" value="<?php echo $m['h_beds_total']; ?>" class="f-input"></div>
                                </div>
                                
                                <p style="font-size:0.55rem; color:var(--accent); font-weight:900; margin:10px 0;">Patient Accountability (Faith and Gender)</p>
                                <table class="f-table">
                                    <tr><th>Category</th><th>Catholic M</th><th>Catholic F</th><th>Non-Cath M</th><th>Non-Cath F</th></tr>
                                    <tr><td class="row-lbl">In-Patients</td><td><input type="number" name="m[h_in_catholic_male]" value="<?php echo $m['h_in_catholic_male']; ?>"></td><td><input type="number" name="m[h_in_catholic_female]" value="<?php echo $m['h_in_catholic_female']; ?>"></td><td><input type="number" name="m[h_in_noncatholic_male]" value="<?php echo $m['h_in_noncatholic_male']; ?>"></td><td><input type="number" name="m[h_in_noncatholic_female]" value="<?php echo $m['h_in_noncatholic_female']; ?>"></td></tr>
                                    <tr><td class="row-lbl">Out-Patients</td><td><input type="number" name="m[h_out_catholic_male]" value="<?php echo $m['h_out_catholic_male']; ?>"></td><td><input type="number" name="m[h_out_catholic_female]" value="<?php echo $m['h_out_catholic_female']; ?>"></td><td><input type="number" name="m[h_out_noncatholic_male]" value="<?php echo $m['h_out_noncatholic_male']; ?>"></td><td><input type="number" name="m[h_out_noncatholic_female]" value="<?php echo $m['h_out_noncatholic_female']; ?>"></td></tr>
                                </table>

                                <p style="font-size:0.55rem; color:var(--accent); font-weight:900; margin:10px 0;">Deaths in Hospitals and Clinics (Residency and Gender)</p>
                                <table class="f-table">
                                    <tr><th>Category</th><th>Resident M</th><th>Resident F</th><th>Non-Res M</th><th>Non-Res F</th></tr>
                                    <tr><td class="row-lbl">Deaths</td><td><input type="number" name="m[h_death_resident_male]" value="<?php echo $m['h_death_resident_male']; ?>"></td><td><input type="number" name="m[h_death_resident_female]" value="<?php echo $m['h_death_resident_female']; ?>"></td><td><input type="number" name="m[h_death_nonresident_male]" value="<?php echo $m['h_death_nonresident_male']; ?>"></td><td><input type="number" name="m[h_death_nonresident_female]" value="<?php echo $m['h_death_nonresident_female']; ?>"></td></tr>
                                </table>

                                <p style="font-size:0.55rem; color:var(--accent); font-weight:900; margin:10px 0;">Care Home Demographics (Elderly / Disabled / Orphans)</p>
                                <table class="f-table">
                                    <tr><th>Group</th><th>Res. Male</th><th>Res. Female</th><th>Non-Res M</th><th>Non-Res F</th></tr>
                                    <tr><td class="row-lbl">Elderly</td><td><input type="number" name="m[h_care_elderly_resident_male]" value="<?php echo $m['h_care_elderly_resident_male']; ?>"></td><td><input type="number" name="m[h_care_elderly_resident_female]" value="<?php echo $m['h_care_elderly_resident_female']; ?>"></td><td><input type="number" name="m[h_care_elderly_nonresident_male]" value="<?php echo $m['h_care_elderly_nonresident_male']; ?>"></td><td><input type="number" name="m[h_care_elderly_nonresident_female]" value="<?php echo $m['h_care_elderly_nonresident_female']; ?>"></td></tr>
                                    <tr><td class="row-lbl">Disabled</td><td><input type="number" name="m[h_care_disabled_resident_male]" value="<?php echo $m['h_care_disabled_resident_male']; ?>"></td><td><input type="number" name="m[h_care_disabled_resident_female]" value="<?php echo $m['h_care_disabled_resident_female']; ?>"></td><td><input type="number" name="m[h_care_disabled_nonresident_male]" value="<?php echo $m['h_care_disabled_nonresident_male']; ?>"></td><td><input type="number" name="m[h_care_disabled_nonresident_female]" value="<?php echo $m['h_care_disabled_nonresident_female']; ?>"></td></tr>
                                    <tr><td class="row-lbl">Orphans</td><td><input type="number" name="m[h_care_orphans_resident_male]" value="<?php echo $m['h_care_orphans_resident_male']; ?>"></td><td><input type="number" name="m[h_care_orphans_resident_female]" value="<?php echo $m['h_care_orphans_resident_female']; ?>"></td><td><input type="number" name="m[h_care_orphans_nonresident_male]" value="<?php echo $m['h_care_orphans_nonresident_male']; ?>"></td><td><input type="number" name="m[h_care_orphans_nonresident_female]" value="<?php echo $m['h_care_orphans_nonresident_female']; ?>"></td></tr>
                                </table>

                                <p style="font-size:0.55rem; color:var(--accent); font-weight:900; margin:10px 0;">Medical Personnel (Religious / Lay)</p>
                                <table class="f-table">
                                    <tr><th>Role</th><th>Religious</th><th>Lay</th></tr>
                                    <tr><td class="row-lbl">Doctors</td><td><input type="number" name="m[h_doc_religious]" value="<?php echo $m['h_doc_religious']; ?>"></td><td><input type="number" name="m[h_doc_lay]" value="<?php echo $m['h_doc_lay']; ?>"></td></tr>
                                    <tr><td class="row-lbl">Qualified Nurse</td><td><input type="number" name="m[h_nurse_qualified_religious]" value="<?php echo $m['h_nurse_qualified_religious']; ?>"></td><td><input type="number" name="m[h_nurse_qualified_lay]" value="<?php echo $m['h_nurse_qualified_lay']; ?>"></td></tr>
                                    <tr><td class="row-lbl">Paramedical</td><td><input type="number" name="m[h_paramedical_religious]" value="<?php echo $m['h_paramedical_religious']; ?>"></td><td><input type="number" name="m[h_paramedical_lay]" value="<?php echo $m['h_paramedical_lay']; ?>"></td></tr>
                                </table>

                                <p style="font-size:0.55rem; color:var(--accent); font-weight:900; margin:10px 0;">Maternity and Pediatrics</p>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                    <div><label class="f-label">Ante-Natal Mothers</label><input type="number" name="m[h_ante_natal]" value="<?php echo $m['h_ante_natal']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Baby Clinic (Boys/Girls)</label><input type="number" name="m[h_baby_clinic_boys]" value="<?php echo $m['h_baby_clinic_boys']; ?>" class="f-input"><input type="number" name="m[h_baby_clinic_girls]" value="<?php echo $m['h_baby_clinic_girls']; ?>" class="f-input"></div>
                                    <div><label class="f-label">Births (Male/Female)</label><input type="number" name="m[h_birth_male]" value="<?php echo $m['h_birth_male']; ?>" class="f-input"><input type="number" name="m[h_birth_female]" value="<?php echo $m['h_birth_female']; ?>" class="f-input"></div>
                                </div>

                                <p style="font-size:0.55rem; color:var(--accent); font-weight:900; margin:10px 0;">Hospital Pastoral Care</p>
                                <table class="f-table">
                                    <tr><th>Sacrament</th><th>Adult M</th><th>Adult F</th><th>Infant M</th><th>Infant F</th></tr>
                                    <tr>
                                        <td class="row-lbl">Baptisms</td>
                                        <td><input type="number" name="m[h_baptism_adult_male]" value="<?php echo $m['h_baptism_adult_male']; ?>"></td>
                                        <td><input type="number" name="m[h_baptism_adult_female]" value="<?php echo $m['h_baptism_adult_female']; ?>"></td>
                                        <td><input type="number" name="m[h_baptism_infant_male]" value="<?php echo $m['h_baptism_infant_male']; ?>"></td>
                                        <td><input type="number" name="m[h_baptism_infant_female]" value="<?php echo $m['h_baptism_infant_female']; ?>"></td>
                                    </tr>
                                </table>
                                <div style="display:grid; grid-template-columns:1fr; gap:10px;">
                                    <div><label class="f-label">Anointing of the Sick (Total)</label><input type="number" name="m[h_anointing]" value="<?php echo $m['h_anointing']; ?>" class="f-input"></div>
                                </div>
                            </div>

                            <!-- VII. FINANCE LEDGER -->
                            <div class="omega-card">
                                <h5>VII. Financial Ledger (Full Titles)</h5>
                                <label class="f-label">Reporting Currency</label>
                                <select name="m[f_currency]" class="f-input" style="margin-bottom: 20px; border-color: var(--accent);">
                                    <option value="USD" <?php echo (($m['f_currency'] ?? '') == 'USD') ? 'selected' : ''; ?>>United States Dollar (USD)</option>
                                    <option value="ZiG" <?php echo (($m['f_currency'] ?? '') == 'ZiG') ? 'selected' : ''; ?>>Zimbabwe Gold (ZiG)</option>
                                    <option value="ZAR" <?php echo (($m['f_currency'] ?? '') == 'ZAR') ? 'selected' : ''; ?>>South African Rand (ZAR)</option>
                                    <option value="BWP" <?php echo (($m['f_currency'] ?? '') == 'BWP') ? 'selected' : ''; ?>>Botswana Pula (BWP)</option>
                                </select>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                    <div><label class="f-label">Parish Tithe</label><input type="number" step="0.01" name="m[f_tithe]" value="<?php echo number_format((float)$m['f_tithe'], 2, '.', ''); ?>" class="f-input"></div>
                                    <div><label class="f-label">Offertory (Cash)</label><input type="number" step="0.01" name="m[f_offertory_cash]" value="<?php echo number_format((float)$m['f_offertory_cash'], 2, '.', ''); ?>" class="f-input"></div>
                                    <div><label class="f-label">Offertory (In-Kind)</label><input type="number" step="0.01" name="m[f_offertory_kind]" value="<?php echo number_format((float)$m['f_offertory_kind'], 2, '.', ''); ?>" class="f-input"></div>
                                    <div><label class="f-label">Missionary Childhood</label><input type="number" step="0.01" name="m[f_missionary_childhood]" value="<?php echo number_format((float)$m['f_missionary_childhood'], 2, '.', ''); ?>" class="f-input"></div>
                                    <div><label class="f-label">Lenten Sacrifice</label><input type="number" step="0.01" name="m[f_lenten_sacrifice]" value="<?php echo number_format((float)$m['f_lenten_sacrifice'], 2, '.', ''); ?>" class="f-input"></div>
                                    <div><label class="f-label">Holy Land Collection</label><input type="number" step="0.01" name="m[f_holy_land]" value="<?php echo number_format((float)$m['f_holy_land'], 2, '.', ''); ?>" class="f-input"></div>
                                    <div><label class="f-label">Seminary Fund</label><input type="number" step="0.01" name="m[f_seminary]" value="<?php echo number_format((float)$m['f_seminary'], 2, '.', ''); ?>" class="f-input"></div>
                                    <div><label class="f-label">Social Communications</label><input type="number" step="0.01" name="m[f_social_communications]" value="<?php echo number_format((float)$m['f_social_communications'], 2, '.', ''); ?>" class="f-input"></div>
                                    <div><label class="f-label">Mission Sunday (WMS)</label><input type="number" step="0.01" name="m[f_mission_sunday]" value="<?php echo number_format((float)$m['f_mission_sunday'], 2, '.', ''); ?>" class="f-input"></div>
                                    <div><label class="f-label">CARITAS</label><input type="number" step="0.01" name="m[f_caritas]" value="<?php echo number_format((float)$m['f_caritas'], 2, '.', ''); ?>" class="f-input"></div>
                                    <div><label class="f-label">Peter's Pence</label><input type="number" step="0.01" name="m[f_peters_pence]" value="<?php echo number_format((float)$m['f_peters_pence'], 2, '.', ''); ?>" class="f-input"></div>
                                    <div><label class="f-label">General Donations (Cash)</label><input type="number" step="0.01" name="m[f_donations_cash]" value="<?php echo number_format((float)$m['f_donations_cash'], 2, '.', ''); ?>" class="f-input"></div>
                                    <div><label class="f-label">General Donations (Kind)</label><input type="number" step="0.01" name="m[f_donations_kind]" value="<?php echo number_format((float)$m['f_donations_kind'], 2, '.', ''); ?>" class="f-input"></div>
                                </div>
                                <label class="f-label">Other Contribution (Specify Account)</label>
                                    <input type="text" name="m[f_other_specification]" value="<?php echo h($m['f_other_specification']); ?>" placeholder="Building Fund, etc." class="f-input">
                                    <label class="f-label">Other Amount</label>
                                    <input type="number" step="0.01" name="m[f_other_value]" value="<?php echo number_format((float)$m['f_other_value'], 2, '.', ''); ?>" class="f-input">
                                </div>

                                <!-- VIII. STATUS ANIMARUM -->
                                <div class="omega-card">
                                    <h5>VIII. Status Animarum (Population Dynamics)</h5>
                                    <label class="f-label">Total Catholics (Jan 1st / Previous Dec 31st)</label>
                                    <input type="number" name="m[pop_catholic_start]" value="<?php echo $m['pop_catholic_start']; ?>" class="f-input">
                                    
                                    <p style="font-size:0.55rem; color:var(--accent); font-weight:900; margin:10px 0;">Increases (Yearly)</p>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                        <div><label class="f-label">Baptisms</label><input type="number" name="m[pop_inc_baptisms]" value="<?php echo $m['pop_inc_baptisms']; ?>" class="f-input"></div>
                                        <div><label class="f-label">Immigrants</label><input type="number" name="m[pop_inc_immigrants]" value="<?php echo $m['pop_inc_immigrants']; ?>" class="f-input"></div>
                                        <div style="grid-column: span 2;"><label class="f-label">Other Increases</label><input type="number" name="m[pop_inc_others]" value="<?php echo $m['pop_inc_others']; ?>" class="f-input"></div>
                                    </div>

                                    <p style="font-size:0.55rem; color:var(--accent); font-weight:900; margin:10px 0;">Decreases (Yearly)</p>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                        <div><label class="f-label">Deaths</label><input type="number" name="m[pop_dec_deaths]" value="<?php echo $m['pop_dec_deaths']; ?>" class="f-input"></div>
                                        <div><label class="f-label">Emigrants</label><input type="number" name="m[pop_dec_emigrants]" value="<?php echo $m['pop_dec_emigrants']; ?>" class="f-input"></div>
                                        <div style="grid-column: span 2;"><label class="f-label">Other Decreases</label><input type="number" name="m[pop_dec_others]" value="<?php echo $m['pop_dec_others']; ?>" class="f-input"></div>
                                    </div>

                                    <div style="margin-top:15px; padding:10px; background:rgba(255,255,255,0.05); border-radius:8px;">
                                        <label class="f-label">Total Catholics (Dec 31st)</label>
                                        <input type="number" name="m[pop_catholic_end]" value="<?php echo $m['pop_catholic_end']; ?>" class="f-input" style="border-color:var(--accent);">
                                        <p style="font-size:0.6rem; color:var(--text-muted);">Balance: <?php echo ($m['pop_catholic_start'] + $m['pop_inc_baptisms'] + $m['pop_inc_immigrants'] + $m['pop_inc_others']) - ($m['pop_dec_deaths'] + $m['pop_dec_emigrants'] + $m['pop_dec_others']); ?> (Calculated)</p>
                                    </div>

                                    <label class="f-label" style="margin-top:15px;">Number of Catechumens for Baptism</label>
                                    <input type="number" name="m[pop_catechumens]" value="<?php echo $m['pop_catechumens']; ?>" class="f-input">
                                </div>

                                <!-- IX. MARRIAGE BREAKDOWN -->
                                <div class="omega-card">
                                    <h5>IX. Marriage Accountability (Canonical Breakdown)</h5>
                                    <div style="display:flex; flex-direction:column; gap:10px;">
                                        <div><label class="f-label">Between Catholics</label><input type="number" name="m[m_catholic_catholic]" value="<?php echo $m['m_catholic_catholic']; ?>" class="f-input"></div>
                                        <div><label class="f-label">Mixta Religio (Catholic & Non-Catholic Christian)</label><input type="number" name="m[m_mixed_religion]" value="<?php echo $m['m_mixed_religion']; ?>" class="f-input"></div>
                                        <div><label class="f-label">Disparitas Cultus (Catholic & Unbaptised Person)</label><input type="number" name="m[m_disparity_cult]" value="<?php echo $m['m_disparity_cult']; ?>" class="f-input"></div>
                                        <div style="padding-top:10px; border-top:1px solid rgba(255,255,255,0.1);">
                                            <label class="f-label">Total Number of Marriages</label>
                                            <input type="number" name="m[m_total_marriages]" value="<?php echo $m['m_total_marriages']; ?>" class="f-input" style="font-weight:900;">
                                        </div>
                                    </div>
                                </div>

                                <!-- X. OBSERVATIONS -->
                                <div class="omega-card">
                                    <h5>X. Observations or Additional Information</h5>
                                    <textarea name="m[report_observations]" class="f-area" style="height:150px;" placeholder="Enter any significant events, pastoral challenges, or clarifying notes for this reporting period..."><?php echo h($m['report_observations']); ?></textarea>
                                </div>

                            <button type="submit" class="btn btn-primary" style="width:100%; height:80px; border-radius:24px; font-weight:900; font-size:1.3rem;">SUBMIT TOTAL ARCHIVE</button>
                        </form>
                    </aside>

                    <!-- REPORT VIEW (TOTAL) -->
                    <div id="total-doc-preview">
                        <?php if ($report_data): ?>
                        <div class="doc-canvas" id="omega-total-doc">
                            <div class="doc-header">
                                <div style="width: 130px; height: 130px; border-radius: 50%; overflow: hidden; border: 4px solid #d4b34d; box-shadow: 0 0 0 2px #895c17, 0 10px 25px rgba(0,0,0,0.3), 0 0 40px rgba(212,179,77,0.2); margin: 0 auto 25px; display: flex; align-items: center; justify-content: center; background: #fff;">
                                    <img src="../assets/img/seal.png" style="width: 100%; height: 100%; object-fit: cover; transform: scale(1.20);" alt="Diocese Seal">
                                </div>
                                <div style="font-size: 0.8rem; letter-spacing: 2px; color: #64748b; margin-bottom: 5px;">ANNUAL STATISTICS</div>
                                <h1>Annua Statistica</h1>
                                <p>DIOCESE OF HWANGE • TOTAL ACCOUNTABILITY ARCHIVE</p>
                                <div style="margin-top: 10px; font-weight: 700; color: var(--accent); font-size: 1.2rem;"><?php echo h($selected_year); ?></div>
                                <div style="font-size: 0.9rem; margin-top: 5px;"><?php echo h($report_data['parish']['parish_name'] ?? ''); ?></div>
                            </div>

                            <!-- II. CHURCHES -->
                            <div class="doc-sec">I. Churches and Pastoral Centres</div>
                            <table class="doc-table">
                                <tr><td class="lbl">Churches with Blessed Sacrament</td><td><?php echo nl2br(h($m['inst_ch_bs_list'])); ?></td></tr>
                                <tr><td class="lbl">Number of Pastoral Centres</td><td><?php echo $m['inst_pastoral_centres_count']; ?></td></tr>
                                <tr><td class="lbl">Regular Mass Centres</td><td><?php echo $m['inst_mass_regular_count']; ?></td></tr>
                                <tr><td class="lbl">Pastoral Centre Attendees (Male)</td><td><?php echo $m['inst_pastoral_attendees_male']; ?></td></tr>
                                <tr><td class="lbl">Pastoral Centre Attendees (Female)</td><td><?php echo $m['inst_pastoral_attendees_female']; ?></td></tr>
                                <tr style="background:#f1f5f9; font-weight:700;"><td class="lbl">TOTAL ATTENDEES</td><td><?php echo $m['inst_pastoral_attendees_male'] + $m['inst_pastoral_attendees_female']; ?></td></tr>
                                <tr><td class="lbl">Pastoral Centre Locations</td><td><?php echo nl2br(h($m['inst_pastoral_centres_list'])); ?></td></tr>
                            </table>

                            <!-- III. PERSONNEL -->
                            <div class="doc-sec">II. Governance and Personnel</div>
                            <table class="doc-table">
                                <tr><td class="lbl">Ecclesiastical Governance</td><td style="font-weight: 800; color: #0f172a;"><?php echo h($m['gov_in_charge'] ?: 'NOT SPECIFIED'); ?></td></tr>
                            </table>
                            <table class="doc-table">
                                <thead><tr><th>Category</th><th>Diocesan</th><th>Religious</th><th>Total</th></tr></thead>
                                <tbody>
                                    <tr><td>Priests</td><td><?php echo $m['p_priest_diocesan']; ?></td><td><?php echo $m['p_priest_religious']; ?></td><td><?php echo $m['p_priest_diocesan']+$m['p_priest_religious']; ?></td></tr>
                                    <tr><td>Deacons</td><td><?php echo $m['p_deacon_diocesan']; ?></td><td><?php echo $m['p_deacon_religious']; ?></td><td><?php echo $m['p_deacon_diocesan']+$m['p_deacon_religious']; ?></td></tr>
                                    <tr><td>Brothers</td><td><?php echo $m['p_brother_diocesan']; ?></td><td><?php echo $m['p_brother_religious']; ?></td><td><?php echo $m['p_brother_diocesan']+$m['p_brother_religious']; ?></td></tr>
                                    <tr><td>Sisters</td><td><?php echo $m['p_sister_diocesan']; ?></td><td><?php echo $m['p_sister_religious']; ?></td><td><?php echo $m['p_sister_diocesan']+$m['p_sister_religious']; ?></td></tr>
                                    <tr><td>Catechists (Full/Part Time)</td><td><?php echo $m['p_catechist_fulltime']; ?></td><td><?php echo $m['p_catechist_parttime']; ?></td><td><?php echo $m['p_catechist_fulltime']+$m['p_catechist_parttime']; ?></td></tr>
                                </tbody>
                            </table>

                            <!-- IV. VOCATIONS -->
                            <div class="doc-sec">III. Vocations Pipeline</div>
                            <table class="doc-table">
                                <tr><td class="lbl">Major Seminarians (Philosophy)</td><td><?php echo $m['voc_seminarian_philosophy']; ?></td></tr>
                                <tr><td class="lbl">Major Seminarians (Theology)</td><td><?php echo $m['voc_seminarian_theology']; ?></td></tr>
                                <tr style="background:#f1f5f9; font-weight:700;"><td class="lbl">TOTAL MAJOR SEMINARIANS</td><td><?php echo $m['voc_seminarian_philosophy'] + $m['voc_seminarian_theology']; ?></td></tr>
                                <tr><td class="lbl">Minor Seminarians</td><td><?php echo $m['voc_seminarian_minor']; ?></td></tr>
                            </table>
                            <table class="doc-table">
                                <thead><tr><th>Training Stage</th><th>Brothers</th><th>Sisters</th></tr></thead>
                                <tbody>
                                    <tr><td>Professed</td><td><?php echo $m['voc_professed_brother']; ?></td><td><?php echo $m['voc_professed_sister']; ?></td></tr>
                                    <tr><td>Novices</td><td><?php echo $m['voc_novice_brother']; ?></td><td><?php echo $m['voc_novice_sister']; ?></td></tr>
                                    <tr><td>Postulants</td><td><?php echo $m['voc_postulant_brother']; ?></td><td><?php echo $m['voc_postulant_sister']; ?></td></tr>
                                    <tr><td>Candidates</td><td><?php echo $m['voc_candidate_brother']; ?></td><td><?php echo $m['voc_candidate_sister']; ?></td></tr>
                                </tbody>
                            </table>
                            <table class="doc-table">
                                <tr><td class="lbl">Priests in Training (Diocesan)</td><td><?php echo $m['voc_priest_diocesan']; ?></td></tr>
                                <tr><td class="lbl">Priests in Training (Religious)</td><td><?php echo $m['voc_priest_religious']; ?></td></tr>
                            </table>

                            <!-- IV. GUILDS & SACRAMENTS -->
                            <div class="doc-sec">IV. Parish Guilds and Sacramental Lifecycle</div>
                            <table class="doc-table">
                                <thead><tr><th>Guild / Association / Sacrament</th><th>Total Count / Celebrations</th></tr></thead>
                                <tbody>
                                    <tr><td>Legion of Mary</td><td><?php echo $m['g_legion_of_mary']; ?></td></tr>
                                    <tr><td>St. Anne</td><td><?php echo $m['g_st_anne']; ?></td></tr>
                                    <tr><td>St. Joseph</td><td><?php echo $m['g_st_joseph']; ?></td></tr>
                                    <tr><td>Catholic Youth Association (C.Y.A)</td><td><?php echo $m['g_catholic_youth_association']; ?></td></tr>
                                    <tr><td>Catholic Charismatic Renewal</td><td><?php echo $m['g_catholic_charismatic_renewal']; ?></td></tr>
                                    <tr><td>Sacred Heart</td><td><?php echo $m['g_sacred_heart']; ?></td></tr>
                                    <tr><td>JUFRAS</td><td><?php echo $m['g_jufras']; ?></td></tr>
                                    <tr><td>Parish Choir</td><td><?php echo $m['g_choir']; ?></td></tr>
                                    <tr><td>Altar Servers</td><td><?php echo $m['g_altar_servers']; ?></td></tr>
                                    <tr><td>Missionary Childhood Association</td><td><?php echo $m['g_missionary_childhood']; ?></td></tr>
                                    <tr style="background:#f8fafc;"><td colspan="2" style="height:10px;"></td></tr>
                                    <tr><td>First Holy Communion</td><td><?php echo $m['sac_first_communion']; ?></td></tr>
                                    <tr><td>Anointing of the Sick (Parish)</td><td><?php echo $m['sac_anointing_total']; ?></td></tr>
                                    <tr><td>RCIA Candidates</td><td><?php echo $m['sac_rcia_candidates']; ?></td></tr>
                                </tbody>
                            </table>

                            <!-- V. EDUCATION MATRIX -->
                            <div class="doc-sec page-break">V. Education Matrix</div>
                            <?php foreach ($edu_levels as $key => $name): ?>
                                <p style="font-size:0.8rem; font-weight:800; margin-top: 1.5rem; text-decoration: underline;"><?php echo $name; ?></p>
                                <table class="doc-table" style="margin-top: 0.5rem;">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Cath M</th>
                                            <th>Cath F</th>
                                            <th>Non-Cath M</th>
                                            <th>Non-Cath F</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Students</td>
                                            <td><?php echo $m["e_{$key}_catholic_male"]; ?></td>
                                            <td><?php echo $m["e_{$key}_catholic_female"]; ?></td>
                                            <td><?php echo $m["e_{$key}_noncatholic_male"]; ?></td>
                                            <td><?php echo $m["e_{$key}_noncatholic_female"]; ?></td>
                                            <td style="font-weight: 700;"><?php echo $m["e_{$key}_catholic_male"] + $m["e_{$key}_catholic_female"] + $m["e_{$key}_noncatholic_male"] + $m["e_{$key}_noncatholic_female"]; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <table class="doc-table">
                                    <thead>
                                        <tr>
                                            <th>Teaching Staff</th>
                                            <th>Cath M</th>
                                            <th>Cath F</th>
                                            <th>Non-Cath M</th>
                                            <th>Non-Cath F</th>
                                            <th>Religious</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Staff</td>
                                            <td><?php echo $m["e_{$key}_teacher_catholic_male"]; ?></td>
                                            <td><?php echo $m["e_{$key}_teacher_catholic_female"]; ?></td>
                                            <td><?php echo $m["e_{$key}_teacher_noncatholic_male"]; ?></td>
                                            <td><?php echo $m["e_{$key}_teacher_noncatholic_female"]; ?></td>
                                            <td><?php echo $m["e_{$key}_teacher_religious"]; ?></td>
                                            <td style="font-weight: 700;"><?php echo $m["e_{$key}_teacher_catholic_male"] + $m["e_{$key}_teacher_catholic_female"] + $m["e_{$key}_teacher_noncatholic_male"] + $m["e_{$key}_teacher_noncatholic_female"] + $m["e_{$key}_teacher_religious"]; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            <?php endforeach; ?>

                            <!-- VI. HEALTH MATRIX -->
                            <div class="doc-sec page-break">VI. Exhaustive Health and Care Matrix</div>
                            <table class="doc-table">
                                <tr><td class="lbl">Mission Hospitals</td><td><?php echo $m['h_hospitals']; ?></td></tr>
                                <tr><td class="lbl">Health Centres / Clinics</td><td><?php echo $m['h_clinics']; ?></td></tr>
                                <tr><td class="lbl">Maternity Units</td><td><?php echo $m['h_maternity_units']; ?></td></tr>
                                <tr><td class="lbl">Leprosaria</td><td><?php echo $m['h_leprosaria']; ?></td></tr>
                                <tr><td class="lbl">Nurseries</td><td><?php echo $m['h_nurseries']; ?></td></tr>
                                <tr><td class="lbl">Social Centres</td><td><?php echo $m['h_social_centres']; ?></td></tr>
                                <tr><td class="lbl">Total Capacity (Beds)</td><td><?php echo $m['h_beds_total']; ?></td></tr>
                            </table>

                            <p style="font-size:0.75rem; font-weight:700; margin-bottom:5px;">Patient Statistics (Cath / Non-Cath)</p>
                            <table class="doc-table">
                                <thead><tr><th>Category</th><th>Male</th><th>Female</th><th>Total</th></tr></thead>
                                <tbody>
                                    <tr><td>In-Patients (Catholic)</td><td><?php echo $m['h_in_catholic_male']; ?></td><td><?php echo $m['h_in_catholic_female']; ?></td><td><?php echo $m['h_in_catholic_male']+$m['h_in_catholic_female']; ?></td></tr>
                                    <tr><td>In-Patients (Non-Catholic)</td><td><?php echo $m['h_in_noncatholic_male']; ?></td><td><?php echo $m['h_in_noncatholic_female']; ?></td><td><?php echo $m['h_in_noncatholic_male']+$m['h_in_noncatholic_female']; ?></td></tr>
                                    <tr><td>Out-Patients (Catholic)</td><td><?php echo $m['h_out_catholic_male']; ?></td><td><?php echo $m['h_out_catholic_female']; ?></td><td><?php echo $m['h_out_catholic_male']+$m['h_out_catholic_female']; ?></td></tr>
                                    <tr><td>Out-Patients (Non-Catholic)</td><td><?php echo $m['h_out_noncatholic_male']; ?></td><td><?php echo $m['h_out_noncatholic_female']; ?></td><td><?php echo $m['h_out_noncatholic_male']+$m['h_out_noncatholic_female']; ?></td></tr>
                                </tbody>
                            </table>

                            <p style="font-size:0.75rem; font-weight:700; margin-bottom:5px;">Mortality in Facilities</p>
                            <table class="doc-table">
                                <thead><tr><th>Category</th><th>Male</th><th>Female</th><th>Total</th></tr></thead>
                                <tbody>
                                    <tr><td>Deaths (Residents)</td><td><?php echo $m['h_death_resident_male']; ?></td><td><?php echo $m['h_death_resident_female']; ?></td><td><?php echo $m['h_death_resident_male']+$m['h_death_resident_female']; ?></td></tr>
                                    <tr><td>Deaths (Non-Residents)</td><td><?php echo $m['h_death_nonresident_male']; ?></td><td><?php echo $m['h_death_nonresident_female']; ?></td><td><?php echo $m['h_death_nonresident_male']+$m['h_death_nonresident_female']; ?></td></tr>
                                </tbody>
                            </table>

                            <p style="font-size:0.75rem; font-weight:700; margin-bottom:5px;">Care Home Demographics</p>
                            <table class="doc-table">
                                <thead><tr><th>Group</th><th>Res. M</th><th>Res. F</th><th>Non-Res M</th><th>Non-Res F</th></tr></thead>
                                <tbody>
                                    <tr><td>Elderly</td><td><?php echo $m['h_care_elderly_resident_male']; ?></td><td><?php echo $m['h_care_elderly_resident_female']; ?></td><td><?php echo $m['h_care_elderly_nonresident_male']; ?></td><td><?php echo $m['h_care_elderly_nonresident_female']; ?></td></tr>
                                    <tr><td>Disabled</td><td><?php echo $m['h_care_disabled_resident_male']; ?></td><td><?php echo $m['h_care_disabled_resident_female']; ?></td><td><?php echo $m['h_care_disabled_nonresident_male']; ?></td><td><?php echo $m['h_care_disabled_nonresident_female']; ?></td></tr>
                                    <tr><td>Orphans</td><td><?php echo $m['h_care_orphans_resident_male']; ?></td><td><?php echo $m['h_care_orphans_resident_female']; ?></td><td><?php echo $m['h_care_orphans_nonresident_male']; ?></td><td><?php echo $m['h_care_orphans_nonresident_female']; ?></td></tr>
                                </tbody>
                            </table>

                            <p style="font-size:0.75rem; font-weight:700; margin-bottom:5px;">Medical Personnel and Pediatrics</p>
                            <table class="doc-table">
                                <tr><td class="lbl">Medical Doctors (Religious)</td><td><?php echo $m['h_doc_religious']; ?></td></tr>
                                <tr><td class="lbl">Medical Doctors (Lay)</td><td><?php echo $m['h_doc_lay']; ?></td></tr>
                                <tr><td class="lbl">Qualified Nurses (Religious)</td><td><?php echo $m['h_nurse_qualified_religious']; ?></td></tr>
                                <tr><td class="lbl">Qualified Nurses (Lay)</td><td><?php echo $m['h_nurse_qualified_lay']; ?></td></tr>
                                <tr><td class="lbl">Paramedical Staff (Religious)</td><td><?php echo $m['h_paramedical_religious']; ?></td></tr>
                                <tr><td class="lbl">Paramedical Staff (Lay)</td><td><?php echo $m['h_paramedical_lay']; ?></td></tr>
                                <tr><td class="lbl">Ante-Natal Mothers</td><td><?php echo $m['h_ante_natal']; ?></td></tr>
                                <tr><td class="lbl">Baby Clinic (Boys & Girls)</td><td><?php echo $m['h_baby_clinic_boys'] + $m['h_baby_clinic_girls']; ?></td></tr>
                                <tr><td class="lbl">Births (Male)</td><td><?php echo $m['h_birth_male']; ?></td></tr>
                                <tr><td class="lbl">Births (Female)</td><td><?php echo $m['h_birth_female']; ?></td></tr>
                            </table>

                            <p style="font-size:0.75rem; font-weight:700; margin-bottom:5px; margin-top:1.5rem;">Hospital Pastoral Care</p>
                            <table class="doc-table">
                                <thead><tr><th>Sacrament</th><th>Adult M/F</th><th>Infant M/F</th><th>Total</th></tr></thead>
                                <tbody>
                                    <tr>
                                        <td>Baptisms</td>
                                        <td><?php echo $m['h_baptism_adult_male']; ?> / <?php echo $m['h_baptism_adult_female']; ?></td>
                                        <td><?php echo $m['h_baptism_infant_male']; ?> / <?php echo $m['h_baptism_infant_female']; ?></td>
                                        <td><?php echo $m['h_baptism_adult_male'] + $m['h_baptism_adult_female'] + $m['h_baptism_infant_male'] + $m['h_baptism_infant_female']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Anointing of the Sick</td>
                                        <td colspan="2">Total Celebrations</td>
                                        <td><?php echo $m['h_anointing']; ?></td>
                                    </tr>
                                </tbody>
                            </table>

                             <!-- VII. FINANCE -->
                             <div class="doc-sec page-break">VII. Financial Ledger (Full Titles)</div>
                             <p style="font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 10px;">Currency: <?php echo h($m['f_currency']); ?></p>
                             <table class="doc-table">
                                 <tr><td class="lbl">Parish Tithe</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_tithe'], 2); ?></td></tr>
                                 <tr><td class="lbl">Offertory (Cash)</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_offertory_cash'], 2); ?></td></tr>
                                 <tr><td class="lbl">Offertory (In-Kind)</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_offertory_kind'], 2); ?></td></tr>
                                 <tr><td class="lbl">Missionary Childhood</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_missionary_childhood'], 2); ?></td></tr>
                                 <tr><td class="lbl">Lenten Sacrifice</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_lenten_sacrifice'], 2); ?></td></tr>
                                 <tr><td class="lbl">Holy Land Collection</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_holy_land'], 2); ?></td></tr>
                                 <tr><td class="lbl">Seminary Fund</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_seminary'], 2); ?></td></tr>
                                 <tr><td class="lbl">Social Communications</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_social_communications'], 2); ?></td></tr>
                                 <tr><td class="lbl">Mission Sunday (WMS)</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_mission_sunday'], 2); ?></td></tr>
                                 <tr><td class="lbl">CARITAS</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_caritas'], 2); ?></td></tr>
                                 <tr><td class="lbl">Peter's Pence</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_peters_pence'], 2); ?></td></tr>
                                 <tr><td class="lbl">General Donations (Cash)</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_donations_cash'], 2); ?></td></tr>
                                 <tr><td class="lbl">General Donations (Kind)</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_donations_kind'], 2); ?></td></tr>
                                 <tr><td class="lbl">Other (<?php echo h($m['f_other_specification'] ?: 'Building/Choir/etc'); ?>)</td><td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_other_value'], 2); ?></td></tr>
                                 <tr style="background:#f1f5f9; font-weight:900;">
                                     <td class="lbl">GRAND TOTAL CONTRIBUTION</td>
                                     <td><?php echo $m['f_currency']; ?> <?php echo number_format($m['f_tithe']+$m['f_offertory_cash']+$m['f_offertory_kind']+$m['f_missionary_childhood']+$m['f_lenten_sacrifice']+$m['f_holy_land']+$m['f_seminary']+$m['f_social_communications']+$m['f_mission_sunday']+$m['f_caritas']+$m['f_peters_pence']+$m['f_donations_cash']+$m['f_donations_kind']+$m['f_other_value'], 2); ?></td>
                                 </tr>
                             </table>

                             <!-- VIII. STATUS ANIMARUM -->
                             <div class="doc-sec page-break">VIII. Status Animarum (Population Dynamics)</div>
                             <table class="doc-table">
                                 <tr><td class="lbl">Catholics on 1st January (Start of Year)</td><td><?php echo number_format($m['pop_catholic_start']); ?></td></tr>
                                 <tr><td class="lbl">Increase: Baptisms</td><td><?php echo $m['pop_inc_baptisms']; ?></td></tr>
                                 <tr><td class="lbl">Increase: Immigrants</td><td><?php echo $m['pop_inc_immigrants']; ?></td></tr>
                                 <tr><td class="lbl">Increase: Others</td><td><?php echo $m['pop_inc_others']; ?></td></tr>
                                 <tr style="background:#f1f5f9; font-weight:700;"><td class="lbl">TOTAL INCREASE</td><td>+<?php echo $m['pop_inc_baptisms'] + $m['pop_inc_immigrants'] + $m['pop_inc_others']; ?></td></tr>
                                 <tr><td class="lbl">Decrease: Deaths</td><td><?php echo $m['pop_dec_deaths']; ?></td></tr>
                                 <tr><td class="lbl">Decrease: Emigrants</td><td><?php echo $m['pop_dec_emigrants']; ?></td></tr>
                                 <tr><td class="lbl">Decrease: Others</td><td><?php echo $m['pop_dec_others']; ?></td></tr>
                                 <tr style="background:#f1f5f9; font-weight:700;"><td class="lbl">TOTAL DECREASE</td><td>-<?php echo $m['pop_dec_deaths'] + $m['pop_dec_emigrants'] + $m['pop_dec_others']; ?></td></tr>
                                 <tr style="background:#f1f5f9; font-weight:900;">
                                     <td class="lbl">TOTAL CATHOLICS ON 31st DECEMBER</td>
                                     <td><?php echo number_format($m['pop_catholic_end']); ?></td>
                                 </tr>
                                 <tr><td class="lbl">Number of Catechumens for Baptism</td><td><?php echo number_format($m['pop_catechumens']); ?></td></tr>
                             </table>

                             <!-- IX. MARRIAGES -->
                             <div class="doc-sec page-break">IX. Sacramental Accountability (Marriages)</div>
                             <table class="doc-table">
                                 <tr><td class="lbl">Marriages Between Catholics</td><td><?php echo $m['m_catholic_catholic']; ?></td></tr>
                                 <tr><td class="lbl">Mixed Marriage (Mixta Religio)</td><td><?php echo $m['m_mixed_religion']; ?></td></tr>
                                 <tr><td class="lbl">Disparity of Cult (Disparitas Cultus)</td><td><?php echo $m['m_disparity_cult']; ?></td></tr>
                                 <tr style="background:#f1f5f9; font-weight:900;">
                                     <td class="lbl">TOTAL NUMBER OF MARRIAGES</td>
                                     <td><?php echo number_format($m['m_total_marriages']); ?></td>
                                 </tr>
                             </table>

                             <!-- X. OBSERVATIONS -->
                             <div class="doc-sec">X. Observations and Additional Information</div>
                             <div style="font-size: 0.85rem; line-height: 1.6; color: #0f172a; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; white-space: pre-wrap;"><?php echo !empty($m['report_observations']) ? h($m['report_observations']) : '<span style="color:#94a3b8; font-style:italic;">No additional observations recorded for this period.</span>'; ?></div>

                            <div style="margin-top: 4rem; display: flex; justify-content: space-between;">
                                <div style="text-align: center; border-top: 1px solid #000; width: 200px; padding-top: 10px; font-size: 0.7rem;">Parish Priest / In-Charge</div>
                                <div style="text-align: center; border-top: 1px solid #000; width: 200px; padding-top: 10px; font-size: 0.7rem;">Diocesan Chancellor / Date</div>
                            </div>
                        </div>
                        <button onclick="exportTotalPDF()" class="btn btn-primary" style="width:100%; margin-top:2rem; height: 60px; font-weight: 800;">Export Final Canonical PDF</button>
                        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js?v=1.6.2"></script>
    <script>
        function exportTotalPDF() {
            const element = document.getElementById('omega-total-doc');
            // Ensure we are at the top to avoid offset issues with html2canvas
            window.scrollTo(0, 0);
            
            const opt = {
                margin: 10,
                filename: 'Annua_Statistica_OMEGA_<?php echo $selected_year; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 4, // High-resolution for crisp seal printing
                    useCORS: true, 
                    logging: false,
                    scrollY: 0,
                    windowWidth: document.documentElement.offsetWidth
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'], avoid: ['tr', '.doc-sec', '.doc-table'] } // Prevent awkward table splitting
            };
            
            // Using worker to handle potential large document issues better
            html2pdf().set(opt).from(element).toPdf().get('pdf').then(function (pdf) {
                const totalPages = pdf.internal.getNumberOfPages();
                for (let i = 1; i <= totalPages; i++) {
                    pdf.setPage(i);
                    pdf.setFontSize(8);
                    pdf.setTextColor(150);
                    pdf.text('Page ' + i + ' of ' + totalPages, pdf.internal.pageSize.getWidth() - 25, pdf.internal.pageSize.getHeight() - 10);
                }
            }).save();
        }
    </script>
</body>
</html>
