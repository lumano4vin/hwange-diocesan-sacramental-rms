<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Marriage Registry - Add Record
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Fetch parishes for dropdown
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC");

// Smart Reference: Fetch last entry for this parish to suggest numbers
$parish_id = $_SESSION['parish_id'] ?? 1;
$last_ref = db_fetch("SELECT register_book_number as reg_book, page_number as reg_page, entry_number as reg_entry FROM marriages WHERE parish_id = ? ORDER BY marriage_id DESC LIMIT 1", [$parish_id]);

$suggested_book = $last_ref['reg_book'] ?? 'M-01';
$suggested_page = $last_ref['reg_page'] ?? '1';
$last_entry = $last_ref['reg_entry'] ?? '0';

// Try to increment entry number if it's numeric/suffixed
$suggested_entry = $last_entry;
if (preg_match('/(.*?)(\d+)$/', $last_entry, $matches)) {
    $prefix = $matches[1];
    $number = (int)$matches[2] + 1;
    $suggested_entry = $prefix . str_pad($number, strlen($matches[2]), '0', STR_PAD_LEFT);
} else if (is_numeric($last_entry)) {
    $suggested_entry = (int)$last_entry + 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Marriage Record - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <style>
        .header-actions a.btn-secondary {
            background: #334155 !important;
            color: #ffffff !important;
            border: 2px solid #38bdf8 !important;
            font-weight: 800 !important;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <?php 
                $header_title = "Record New Marriage";
                $header_subtitle = "Enter the sacramental details for the marriage covenant.";
                ob_start(); ?>
                <a href="marriages.php" class="btn btn-secondary" style="background: #334155 !important; color: #ffffff !important; border: 1px solid #475569 !important; font-weight: 700 !important; text-decoration: none !important;">
                    <ion-icon name="arrow-back-outline" style="color: #ffffff !important;"></ion-icon>
                    Return to Marriage List
                </a>
                <?php 
                $additional_header_actions = ob_get_clean();
                include '../includes/header.php'; 
            ?>

            <form action="../actions/save_marriage.php" method="POST" class="entry-form">
                
                <div class="form-grid">
                    
                    <!-- Section 1: Groom Details -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="man-outline"></ion-icon> Groom Details</h3>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="groom_person_id" id="groom_person_id" value="">
                            <div class="form-group" style="position: relative;">
                                <label>Search Registry</label>
                                <div style="position: relative;">
                                    <ion-icon name="search-outline" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b;"></ion-icon>
                                    <input type="text" id="groom_search" placeholder="Type groom's name..." autocomplete="off" style="padding-left: 2.5rem; width: 100%; padding-top: 0.8rem; padding-bottom: 0.8rem; background: #0f172a; border: 1px solid #334155; border-radius: 0.5rem; color: white;">
                                </div>
                                <div id="groom-results" class="ac-dropdown" style="display:none;"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="g_first_name">First Name(s)</label>
                                    <input type="text" name="g_first_name" id="g_first_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="g_last_name">Last Name</label>
                                    <input type="text" name="g_last_name" id="g_last_name" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="g_dob">Date of Birth</label>
                                <input type="date" name="g_dob" id="g_dob" required>
                            </div>
                            <div id="groom-warning" style="display:none; color:#f59e0b; font-size:0.85rem; padding: 0.5rem; background: rgba(245,158,11,0.1); border-radius: 0.5rem;">
                                <ion-icon name="warning-outline" style="vertical-align:middle;"></ion-icon> No baptism record found for this groom.
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Bride Details -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="woman-outline"></ion-icon> Bride Details</h3>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="bride_person_id" id="bride_person_id" value="">
                            <div class="form-group" style="position: relative;">
                                <label>Search Registry</label>
                                <div style="position: relative;">
                                    <ion-icon name="search-outline" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b;"></ion-icon>
                                    <input type="text" id="bride_search" placeholder="Type bride's name..." autocomplete="off" style="padding-left: 2.5rem; width: 100%; padding-top: 0.8rem; padding-bottom: 0.8rem; background: #0f172a; border: 1px solid #334155; border-radius: 0.5rem; color: white;">
                                </div>
                                <div id="bride-results" class="ac-dropdown" style="display:none;"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="b_first_name">First Name(s)</label>
                                    <input type="text" name="b_first_name" id="b_first_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="b_last_name">Last Name / Maiden Name</label>
                                    <input type="text" name="b_last_name" id="b_last_name" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="b_dob">Date of Birth</label>
                                <input type="date" name="b_dob" id="b_dob" required>
                            </div>
                            <div id="bride-warning" style="display:none; color:#f59e0b; font-size:0.85rem; padding: 0.5rem; background: rgba(245,158,11,0.1); border-radius: 0.5rem;">
                                <ion-icon name="warning-outline" style="vertical-align:middle;"></ion-icon> No baptism record found for this bride.
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Marriage Details -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="heart-outline"></ion-icon> Sacrament Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="date_of_marriage">Date of Marriage</label>
                                    <input type="date" name="date_of_marriage" id="date_of_marriage" required>
                                </div>
                                    <?php
                                    $parish_options = array_map(function($p) {
                                        return ['value' => (string)$p['parish_id'], 'text' => $p['parish_name']];
                                    }, $parishes);
                                    $options_json = htmlspecialchars(json_encode($parish_options), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <div class="form-group">
                                        <label>Parish of Celebration</label>
                                        <div class="searchable-select-container">
                                            <?php 
                                            $user_parish_id = $_SESSION['parish_id'] ?? '';
                                            $user_parish_name = '';
                                            foreach($parishes as $p) {
                                                if($p['parish_id'] == $user_parish_id) {
                                                    $user_parish_name = $p['parish_name'];
                                                    break;
                                                }
                                            }
                                            $is_user_admin = is_admin();
                                            ?>
                                            <input type="text" class="searchable-input" 
                                                   placeholder="<?php echo $is_user_admin ? 'Type to search Parish...' : h($user_parish_name); ?>" 
                                                   value="<?php echo $is_user_admin ? '' : h($user_parish_name); ?>"
                                                   data-options='<?php echo $options_json; ?>'
                                                   autocomplete="off" required 
                                                   <?php echo $is_user_admin ? '' : 'readonly style="background: rgba(56,189,248,0.05); color: var(--accent); font-weight: 700; cursor: not-allowed;"'; ?>>
                                            <input type="hidden" name="parish_id" id="parish_id" value="<?php echo h($user_parish_id); ?>" required>
                                            <div class="search-select-results"></div>
                                        </div>
                                        <?php if(!$is_user_admin): ?>
                                            <small style="color: var(--text-muted); font-size: 0.7rem; margin-top: 4px; display: block;">Record is automatically linked to your assigned mission.</small>
                                        <?php endif; ?>
                                    </div>
                            </div>
                            <div class="form-group">
                                <label for="officiant">Officiating Minister</label>
                                <input type="text" name="officiant" id="officiant" required>
                            </div>
                            <div class="form-group">
                                <label for="witnesses">Official Witnesses (Names)</label>
                                <textarea name="witnesses" id="witnesses" rows="2" placeholder="e.g., Peter Mukonzi and Sarah Dube"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Registry Ref -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="book-outline"></ion-icon> Register Reference</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="reg_book">Register Book #</label>
                                <input type="text" name="reg_book" id="reg_book" value="<?php echo h($suggested_book); ?>" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="reg_page">Page #</label>
                                    <input type="text" name="reg_page" id="reg_page" value="<?php echo h($suggested_page); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="reg_entry">Entry #</label>
                                    <input type="text" name="reg_entry" id="reg_entry" value="<?php echo h($suggested_entry); ?>" required>
                                </div>
                            </div>
                            <div class="form-action-area">
                                <button type="submit" class="btn btn-primary btn-large">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Save Marriage Record
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </form>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <script>
        function initAutocomplete(searchId, resultsId, firstId, lastId, dobId, idFieldId, warningId) {
            const search  = document.getElementById(searchId);
            const results = document.getElementById(resultsId);
            let timer = null;

            search.addEventListener('input', function() {
                clearTimeout(timer);
                const q = this.value.trim();
                if (q.length < 2) { results.style.display = 'none'; return; }

                timer = setTimeout(() => {
                    fetch(`../actions/search_parishioner.php?q=${encodeURIComponent(q)}`)
                        .then(r => r.json())
                        .then(data => {
                            results.innerHTML = '';
                            if (!data.length) {
                                results.innerHTML = `<div style="padding:1rem; color:#64748b; font-size:0.85rem;">Not found — will create new parishioner.</div>`;
                            } else {
                                data.forEach(p => {
                                    const hasBaptism = parseInt(p.has_baptism) > 0;
                                    const item = document.createElement('div');
                                    item.className = 'ac-item';
                                    item.innerHTML = `
                                        <div style="font-weight:700; color:white;">${p.first_name} ${p.last_name}</div>
                                        <div style="font-size:0.75rem; color:#64748b;">${p.dob || 'DOB unknown'} &nbsp;
                                        ${hasBaptism ? '<span style="color:#10b981;">✓ Baptism linked</span>' : '<span style="color:#f59e0b;">⚠ No baptism</span>'}</div>`;
                                    item.addEventListener('click', () => {
                                        document.getElementById(firstId).value  = p.first_name;
                                        document.getElementById(lastId).value   = p.last_name;
                                        document.getElementById(dobId).value    = p.dob || '';
                                        document.getElementById(idFieldId).value = p.person_id;
                                        search.value = `${p.first_name} ${p.last_name}`;
                                        results.style.display = 'none';
                                        document.getElementById(warningId).style.display = hasBaptism ? 'none' : 'block';
                                    });
                                    results.appendChild(item);
                                });
                            }
                            results.style.display = 'block';
                        });
                }, 300);
            });

            document.addEventListener('click', e => {
                if (!e.target.closest('#' + resultsId) && e.target !== search) {
                    results.style.display = 'none';
                }
            });
        }

        initAutocomplete('groom_search', 'groom-results', 'g_first_name', 'g_last_name', 'g_dob', 'groom_person_id', 'groom-warning');
        initAutocomplete('bride_search', 'bride-results', 'b_first_name', 'b_last_name', 'b_dob', 'bride_person_id', 'bride-warning');
    </script>
    <style>
        .entry-form { margin-top: 1rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; }
        .section-card { height: 100%; display: flex; flex-direction: column; }
        .card-body { flex: 1; padding: 1.5rem; }
        .card-header h3 { display: flex; align-items: center; gap: 0.75rem; color: var(--accent); font-size: 1.1rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.8rem; background: #0f172a; border: 1px solid #334155; border-radius: 0.5rem; color: white; font-size: 0.95rem; }
        .btn-large { width: 100%; padding: 1.25rem; font-size: 1.1rem; margin-top: auto; }
        .form-action-area { margin-top: auto; padding-top: 1rem; }
        .ac-dropdown { position: absolute; z-index: 100; width: 100%; background: #1e293b; border: 1px solid #334155; border-radius: 0.75rem; margin-top: 4px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); overflow: hidden; max-height: 250px; overflow-y: auto; }
        .ac-item { padding: 0.9rem 1.2rem; cursor: pointer; border-bottom: 1px solid #1e293b; transition: background 0.15s; }
        .ac-item:hover { background: #0f172a; }
    </style>
</body>
</html>
