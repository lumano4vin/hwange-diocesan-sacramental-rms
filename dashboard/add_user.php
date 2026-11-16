<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Staff Management - Add User Account
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page (Admin Only)
require_role('admin');

// Fetch parishes for dropdown
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Staff Account - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <style>
        /* Force-fix for Back Button */
        .header-actions a.btn-secondary {
            background: #334155 !important;
            color: #ffffff !important;
            border: 2px solid #38bdf8 !important;
            font-weight: 800 !important;
            opacity: 1 !important;
            visibility: visible !important;
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
            
            <header class="content-header">
                <div class="welcome-text">
                    <h2>Create Staff Account</h2>
                    <p>Provision access credentials and define security roles.</p>
                </div>
                <div class="header-actions">
                    <a href="../admin/users_list.php" class="btn btn-secondary" style="background: #334155 !important; color: #ffffff !important; border: 1px solid #475569 !important; font-weight: 700 !important; text-decoration: none !important;">
                        <ion-icon name="arrow-back-outline" style="color: #ffffff !important;"></ion-icon>
                        Return to Staff List
                    </a>
                </div>
            </header>

            <form action="../actions/save_user.php" method="POST" class="entry-form">
                
                <div class="form-grid">
                    
                    <!-- Section 1: Credentials -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="key-outline"></ion-icon> Login Credentials</h3>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" required placeholder="e.g., jdoe_vicfalls">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-input-group">
                                <input type="password" name="password" id="password" required placeholder="Min 8 characters">
                                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('password', this)" tabindex="-1">
                                    <ion-icon name="eye-outline"></ion-icon>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" placeholder="staff@hwangediocese.org">
                        </div>
                    </div>

                    <!-- Section 2: Profile & Role -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="shield-checkmark-outline"></ion-icon> Rights & Role</h3>
                        </div>
                        <div class="form-group">
                            <label for="full_name">Staff Full Name</label>
                            <input type="text" name="full_name" id="full_name" required placeholder="e.g., Rev. Fr. John Peter">
                        </div>
                        <div class="form-group">
                            <label for="role">Security Role</label>
                            <select name="role" id="role" required>
                                <option value="">Select Role...</option>
                                <option value="admin">Diocesan Admin (Full Rights)</option>
                                <option value="chancellor">Chancellor (Full Rights)</option>
                                <option value="priest">Priest-in-Charge (Parish Rights)</option>
                                <option value="deacon">Deacon (Parish Entry Rights)</option>
                                <option value="secretary">Parish Secretary (Parish Entry Rights)</option>
                                <option value="bookkeeper">Parish Bookkeeper / Treasurer (Financial & Daily Ledger)</option>
                            </select>
                        </div>
                        <?php
                        $parish_options = array_map(function($p) {
                            return ['value' => (string)$p['parish_id'], 'text' => $p['parish_name']];
                        }, $parishes);
                        array_unshift($parish_options, ['value' => '', 'text' => 'Chancery (All Parishes / Missions)']);
                        $options_json = htmlspecialchars(json_encode($parish_options), ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <label style="margin-bottom: 0;">Assigned Parish / Mission</label>
                                <button type="button" onclick="openQuickParishModal()" style="background: none; border: none; color: var(--accent); font-size: 0.8rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 2px 6px; border-radius: 4px;">
                                    <ion-icon name="add-circle-outline"></ion-icon> + Add New Mission
                                </button>
                            </div>
                            <div class="searchable-select-container">
                                <input type="text" class="searchable-input" placeholder="Type to search Parish or Mission..." 
                                       data-options='<?php echo $options_json; ?>'
                                       autocomplete="off" value="Chancery (All Parishes / Missions)">
                                <input type="hidden" name="parish_id" id="parish_id" value="">
                                <div class="search-select-results"></div>
                            </div>
                            <small style="color: var(--text-muted);">Non-admin users will be limited to this parish only.</small>
                        </div>
                        
                        <div class="form-action-area" style="margin-top: auto;">
                            <button type="submit" class="btn btn-primary btn-large">
                                <ion-icon name="person-add-outline"></ion-icon>
                                Create Staff Account
                            </button>
                        </div>
                    </div>

                </div>
            </form>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <?php if (isset($_SESSION['flash'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showToast("<?php echo $_SESSION['flash']['message']; ?>", "<?php echo $_SESSION['flash']['type']; ?>");
        });
    </script>
    <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
    <style>
        .entry-form { margin-top: 1rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; }
        .section-card { height: 100%; display: flex; flex-direction: column; }
        .card-header h3 { display: flex; align-items: center; gap: 0.75rem; color: var(--accent); font-size: 1.1rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.8rem; background: #0f172a; border: 1px solid #334155; border-radius: 0.5rem; color: white; font-size: 0.95rem; }
        .btn-large { width: 100%; padding: 1.25rem; font-size: 1.1rem; }

        /* Quick Add Modal */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: center; z-index: 9999;
            opacity: 0; pointer-events: none; transition: opacity 0.25s ease;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-box {
            background: #0f172a; border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 1.25rem;
            width: 90%; max-width: 480px; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7);
            transform: scale(0.95); transition: transform 0.25s ease;
        }
        .modal-overlay.active .modal-box { transform: scale(1); }
    </style>

    <!-- Quick Add Parish Modal -->
    <div id="quickParishModal" class="modal-overlay">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h3 style="color: var(--accent); font-family: 'Outfit'; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                    <ion-icon name="business-outline"></ion-icon> Register New Parish / Mission
                </h3>
                <button type="button" onclick="closeQuickParishModal()" style="background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form id="quickParishForm" onsubmit="submitQuickParish(event)">
                <div class="form-group">
                    <label>Parish / Mission Name *</label>
                    <input type="text" id="qp_name" required placeholder="e.g. Sacred Heart Mission, Lupane">
                </div>
                <div class="form-group">
                    <label>Deanery *</label>
                    <select id="qp_deanery" required>
                        <option value="Hwange">Hwange Deanery</option>
                        <option value="Victoria Falls">Victoria Falls Deanery</option>
                        <option value="Binga">Binga Deanery</option>
                        <option value="Empumalanga">Empumalanga Deanery</option>
                        <option value="Dete">Dete Deanery</option>
                        <option value="Lupane">Lupane Deanery</option>
                        <option value="Jambezi">Jambezi Deanery</option>
                        <option value="Makwa">Makwa Deanery</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Physical Location / Area</label>
                    <input type="text" id="qp_location" placeholder="e.g. Lupane Center">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                    <button type="button" onclick="closeQuickParishModal()" class="btn btn-secondary" style="flex: 1; padding: 0.8rem;">Cancel</button>
                    <button type="submit" id="qp_submit_btn" class="btn btn-primary" style="flex: 1; padding: 0.8rem; font-weight: 700;">Save & Select</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openQuickParishModal() {
            document.getElementById('quickParishModal').classList.add('active');
            document.getElementById('qp_name').focus();
        }
        function closeQuickParishModal() {
            document.getElementById('quickParishModal').classList.remove('active');
            document.getElementById('quickParishForm').reset();
        }

        async function submitQuickParish(e) {
            e.preventDefault();
            const btn = document.getElementById('qp_submit_btn');
            const name = document.getElementById('qp_name').value.trim();
            const deanery = document.getElementById('qp_deanery').value;
            const location = document.getElementById('qp_location').value.trim();

            if (!name) return;

            btn.disabled = true;
            btn.innerText = 'Registering...';

            try {
                const formData = new FormData();
                formData.append('parish_name', name);
                formData.append('deanery', deanery);
                formData.append('location', location || name);

                const res = await fetch('../api/quick_add_parish.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    // Update the search options in the searchable select input
                    const sInput = document.querySelector('.searchable-input');
                    const hiddenId = document.getElementById('parish_id');
                    let opts = JSON.parse(sInput.getAttribute('data-options') || '[]');
                    
                    // Add new option and select it
                    opts.push({ value: String(data.parish_id), text: data.parish_name });
                    sInput.setAttribute('data-options', JSON.stringify(opts));
                    sInput.value = data.parish_name;
                    hiddenId.value = String(data.parish_id);

                    closeQuickParishModal();
                    if (typeof showToast === 'function') {
                        showToast(data.message, 'success');
                    } else {
                        alert(data.message);
                    }
                } else {
                    alert(data.message || 'Error registering parish.');
                }
            } catch (err) {
                alert('Connection error: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerText = 'Save & Select';
            }
        }
    </script>
</body>
</html>
