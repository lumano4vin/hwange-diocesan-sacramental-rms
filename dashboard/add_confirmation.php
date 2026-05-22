<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Confirmation Registry - Add Record (with Smart Candidate Lookup)
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

// Fetch parishes for dropdown
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC");

// Smart Reference: Fetch last entry for this parish to suggest numbers
$parish_id = $_SESSION['parish_id'] ?? 1;
$suggested_book = 'Book 01';
$suggested_page = '1';
$suggested_entry = '1';

try {
    $last_ref = db_fetch("SELECT register_book_number, page_number, entry_number FROM confirmations WHERE parish_id = ? ORDER BY confirmation_id DESC LIMIT 1", [$parish_id]);
    
    if ($last_ref) {
        $suggested_book = $last_ref['register_book_number'] ?? 'Book 01';
        $suggested_page = $last_ref['page_number'] ?? '1';
        $last_entry = $last_ref['entry_number'] ?? '0';

        // Increment entry number
        if (preg_match('/(.*?)(\d+)$/', (string)$last_entry, $matches)) {
            $prefix = $matches[1];
            $number = (int)$matches[2] + 1;
            $suggested_entry = $prefix . str_pad($number, strlen($matches[2]), '0', STR_PAD_LEFT);
        } else if (is_numeric($last_entry)) {
            $suggested_entry = (int)$last_entry + 1;
        }
    }
} catch (Exception $e) {
    // Silence error to allow page to load with default values
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Confirmation Record - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <?php 
                $header_title = "Record New Confirmation";
                $header_subtitle = "Enter the sacramental details for the confirmation register.";
                ob_start(); ?>
                <a href="confirmations.php" class="btn btn-secondary" style="background: #334155 !important; color: #ffffff !important; border: 1px solid #475569 !important; font-weight: 700 !important; text-decoration: none !important;">
                    <ion-icon name="arrow-back-outline" style="color: #ffffff !important;"></ion-icon>
                    Back to List
                </a>
                <?php 
                $additional_header_actions = ob_get_clean();
                include '../includes/header.php'; 
            ?>

            <!-- Mismatch / Warning Banners -->
            <div id="banner-no-baptism" class="alert-banner" style="display:none; background: rgba(245,158,11,0.1); border: 1px solid #f59e0b; color: #f59e0b; border-radius: 0.75rem; padding: 1rem 1.5rem; margin-bottom: 1.5rem; display: none;">
                <ion-icon name="warning-outline" style="font-size: 1.3rem; vertical-align: middle;"></ion-icon>
                <strong>No Baptism Record Found</strong> — This candidate has no linked baptism in the diocesan registry. The confirmation will still be saved, but no canonical baptismal notation will be generated. Please verify with the candidate's parish of baptism.
            </div>

            <div id="banner-new-person" class="alert-banner" style="display:none; background: rgba(56,189,248,0.1); border: 1px solid #38bdf8; color: #38bdf8; border-radius: 0.75rem; padding: 1rem 1.5rem; margin-bottom: 1.5rem; display: none;">
                <ion-icon name="information-circle-outline" style="font-size: 1.3rem; vertical-align: middle;"></ion-icon>
                <strong>New Candidate</strong> — This person was not found in the registry. A new parishioner record will be created automatically upon saving.
            </div>

            <form action="../actions/save_confirmation.php" method="POST" class="entry-form" id="confirmation-form">
                
                <!-- Hidden: populated by autocomplete selection -->
                <input type="hidden" name="person_id" id="person_id" value="">

                <div class="form-grid">
                    
                    <!-- Section 1: Candidate -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="person-outline"></ion-icon> Candidate Details</h3>
                        </div>
                        <div class="card-body">

                            <!-- Live Search Field -->
                            <div class="form-group" style="position: relative;">
                                <label for="candidate_search">Search Existing Parishioner</label>
                                <div style="position: relative;">
                                    <ion-icon name="search-outline" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 1.1rem;"></ion-icon>
                                    <input type="text" id="candidate_search" placeholder="Type name to search registry..." autocomplete="off"
                                        style="padding-left: 2.5rem; background: #0f172a; border: 1px solid #334155; border-radius: 0.75rem; color: white; width: 100%; padding-top: 0.8rem; padding-bottom: 0.8rem;">
                                </div>
                                <!-- Dropdown Results -->
                                <div id="search-results" style="display:none; position: absolute; z-index: 100; width: 100%; background: #1e293b; border: 1px solid #334155; border-radius: 0.75rem; margin-top: 4px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); overflow: hidden; max-height: 300px; overflow-y: auto;"></div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 1rem; margin: 1rem 0;">
                                <div style="flex: 1; height: 1px; background: #334155;"></div>
                                <span style="color: #475569; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">or enter manually</span>
                                <div style="flex: 1; height: 1px; background: #334155;"></div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name(s)</label>
                                    <input type="text" name="first_name" id="first_name" required placeholder="e.g. Mary Anne">
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" name="last_name" id="last_name" required placeholder="e.g. Sibanda">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="date" name="dob" id="dob" required>
                                <small style="color: #64748b; margin-top: 4px; display: block;">Must match baptism record exactly to link automatically.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Sacrament Details -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="flame-outline"></ion-icon> Sacrament Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="minister">Minister (Celebrant)</label>
                                <input type="text" name="minister" id="minister" placeholder="Bishop R.M.M. Ncube" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="date_of_confirmation">Date of Confirmation</label>
                                    <input type="date" name="date_of_confirmation" id="date_of_confirmation" required>
                                </div>
                                <?php
                                $user_parish_id = $_SESSION['parish_id'] ?? null;
                                $user_parish_name = '';
                                if ($user_parish_id) {
                                    foreach ($parishes as $p) {
                                        if ($p['parish_id'] == $user_parish_id) {
                                            $user_parish_name = $p['parish_name'];
                                            break;
                                        }
                                    }
                                }
                                
                                $is_admin = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chancellor');
                                ?>
                                <div class="form-group">
                                    <label>Parish of Celebration</label>
                                    <?php if ($is_admin): 
                                        $parish_options = array_map(function($p) {
                                            return ['value' => (string)$p['parish_id'], 'text' => $p['parish_name']];
                                        }, $parishes);
                                        $options_json = htmlspecialchars(json_encode($parish_options), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <div class="searchable-select-container">
                                        <input type="text" class="searchable-input" placeholder="Type to search Parish..." 
                                               data-options='<?php echo $options_json; ?>'
                                               autocomplete="off" required>
                                        <input type="hidden" name="parish_id" id="parish_id" value="" required>
                                        <div class="search-select-results"></div>
                                    </div>
                                    <?php else: ?>
                                    <input type="text" value="<?php echo h($user_parish_name); ?>" readonly style="background: #1e293b; color: #94a3b8; cursor: not-allowed; border-color: #334155;">
                                    <input type="hidden" name="parish_id" value="<?php echo $user_parish_id; ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="sponsor">Confirmation Sponsor (Name)</label>
                                <input type="text" name="sponsor" id="sponsor" required placeholder="Simon Peter">
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Registry Ref -->
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
                            
                            <div class="form-action-area" style="margin-top: 1.5rem;">
                                <button type="submit" class="btn btn-primary btn-large">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Save Confirmation Record
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
        const searchInput = document.getElementById('candidate_search');
        const resultsBox  = document.getElementById('search-results');
        const personIdField = document.getElementById('person_id');
        const firstNameField = document.getElementById('first_name');
        const lastNameField  = document.getElementById('last_name');
        const dobField       = document.getElementById('dob');
        const bannerNoBaptism = document.getElementById('banner-no-baptism');
        const bannerNewPerson = document.getElementById('banner-new-person');

        let searchTimeout = null;

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            const q = this.value.trim();

            if (q.length < 2) {
                resultsBox.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`../actions/search_parishioner.php?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(data => {
                        resultsBox.innerHTML = '';

                        if (data.length === 0) {
                            resultsBox.innerHTML = `<div style="padding: 1rem 1.5rem; color: #64748b; font-size: 0.9rem;">
                                <ion-icon name="alert-circle-outline" style="vertical-align: middle;"></ion-icon>
                                No matching parishioner found. A new record will be created.
                            </div>`;
                            // Show new person banner
                            bannerNewPerson.style.display = 'flex';
                            bannerNoBaptism.style.display = 'none';
                        } else {
                            bannerNewPerson.style.display = 'none';
                            data.forEach(person => {
                                const hasBaptism = parseInt(person.has_baptism) > 0;
                                const item = document.createElement('div');
                                item.style.cssText = 'padding: 0.9rem 1.2rem; cursor: pointer; border-bottom: 1px solid #1e293b; display: flex; align-items: center; justify-content: space-between; transition: background 0.15s;';
                                item.innerHTML = `
                                    <div>
                                        <div style="font-weight: 700; color: white;">${person.first_name} ${person.last_name}</div>
                                        <div style="font-size: 0.8rem; color: #64748b;">DOB: ${person.dob || 'N/A'}</div>
                                    </div>
                                    <div>
                                        ${hasBaptism 
                                            ? '<span style="background: rgba(16,185,129,0.15); color: #10b981; padding: 3px 10px; border-radius: 50px; font-size: 0.7rem; font-weight: 800;">✓ BAPTISM LINKED</span>'
                                            : '<span style="background: rgba(245,158,11,0.15); color: #f59e0b; padding: 3px 10px; border-radius: 50px; font-size: 0.7rem; font-weight: 800;">⚠ NO BAPTISM</span>'
                                        }
                                    </div>`;

                                item.addEventListener('mouseenter', () => item.style.background = '#1e293b');
                                item.addEventListener('mouseleave', () => item.style.background = 'transparent');

                                item.addEventListener('click', () => {
                                    // Auto-fill form fields
                                    firstNameField.value = person.first_name;
                                    lastNameField.value  = person.last_name;
                                    dobField.value       = person.dob || '';
                                    personIdField.value  = person.person_id;
                                    searchInput.value    = `${person.first_name} ${person.last_name}`;
                                    resultsBox.style.display = 'none';

                                    // Show appropriate warning
                                    if (!hasBaptism) {
                                        bannerNoBaptism.style.display = 'flex';
                                        bannerNewPerson.style.display = 'none';
                                    } else {
                                        bannerNoBaptism.style.display = 'none';
                                        bannerNewPerson.style.display = 'none';
                                    }
                                });

                                resultsBox.appendChild(item);
                            });
                        }

                        resultsBox.style.display = 'block';
                    });
            }, 300); // 300ms debounce
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#search-results') && e.target !== searchInput) {
                resultsBox.style.display = 'none';
            }
        });
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
        .form-group input, .form-group select { width: 100%; padding: 0.8rem; background: #0f172a; border: 1px solid #334155; border-radius: 0.5rem; color: white; font-size: 0.95rem; }
        .btn-large { width: 100%; padding: 1.25rem; font-size: 1.1rem; }
        .alert-banner { display: flex; align-items: flex-start; gap: 0.75rem; }
    </style>
</body>
</html>
