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
                            </select>
                        </div>
                        <?php
                        $parish_options = array_map(function($p) {
                            return ['value' => (string)$p['parish_id'], 'text' => $p['parish_name']];
                        }, $parishes);
                        array_unshift($parish_options, ['value' => '', 'text' => 'Chancery (All Parishes)']);
                        $options_json = htmlspecialchars(json_encode($parish_options), ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="form-group">
                            <label>Assigned Parish / Mission</label>
                            <div class="searchable-select-container">
                                <input type="text" class="searchable-input" placeholder="Type to search Parish or Mission..." 
                                       data-options='<?php echo $options_json; ?>'
                                       autocomplete="off" value="Chancery (All Parishes)">
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
    </style>
</body>
</html>
