<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parishioner Directory - Register New Member
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Fetch parishes for dropdown
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Parishioner - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                    <h2>Register New Parishioner</h2>
                    <p>Enter the core personal and canonical details for a new member of the faithful.</p>
                </div>
                <div class="header-actions">
                    <a href="parishioners.php" class="btn btn-secondary" style="background: #334155 !important; color: #ffffff !important; border: 1px solid #475569 !important; font-weight: 700 !important; text-decoration: none !important;">
                        <ion-icon name="arrow-back-outline" style="color: #ffffff !important;"></ion-icon>
                        Back to List
                    </a>
                </div>
            </header>

            <form action="../actions/save_parishioner.php" method="POST" class="entry-form">
                <div class="form-grid">
                    
                    <!-- Section 1: Personal Details -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="person-outline"></ion-icon> Personal Details</h3>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name(s)</label>
                                <input type="text" name="first_name" id="first_name" placeholder="John" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name / Surname</label>
                                <input type="text" name="last_name" id="last_name" placeholder="Doe" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select name="gender" id="gender" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="date" name="dob" id="dob" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="place_of_birth">Place of Birth</label>
                            <input type="text" name="place_of_birth" id="place_of_birth" placeholder="e.g., St. Patrick Hospital, Hwange">
                        </div>
                    </div>

                    <!-- Section 2: Canonical Lineage -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="people-outline"></ion-icon> Parentage (Canon 877)</h3>
                        </div>
                        <div class="form-group">
                            <label for="father_name">Father's Full Name</label>
                            <input type="text" name="father_name" id="father_name" placeholder="Full name of father">
                        </div>
                        <div class="form-group">
                            <label for="mother_name">Mother's First Name</label>
                            <input type="text" name="mother_name" id="mother_name" placeholder="Mary">
                        </div>
                        <div class="form-group">
                            <label for="mother_maiden_name">Mother's Maiden Name (Surname)</label>
                            <input type="text" name="mother_maiden_name" id="mother_maiden_name" placeholder="Required for canonical verification" required>
                        </div>
                    </div>

                    <!-- Section 3: Administrative Info -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="shield-outline"></ion-icon> Diocesan Status</h3>
                        </div>
                        <?php
                        $parish_options = array_map(function($p) {
                            return ['value' => (string)$p['parish_id'], 'text' => $p['parish_name']];
                        }, $parishes);
                        $options_json = htmlspecialchars(json_encode($parish_options), ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="form-group">
                            <label>Home Parish / Mission</label>
                            <div class="searchable-select-container">
                                <input type="text" class="searchable-input" placeholder="Type to search Parish..." 
                                       data-options='<?php echo $options_json; ?>'
                                       autocomplete="off" required>
                                <input type="hidden" name="current_parish_id" id="current_parish_id" value="" required>
                                <div class="search-select-results"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="status">Membership Status</label>
                            <select name="status" id="status" required>
                                <option value="Active">Active (Local Member)</option>
                                <option value="Transferred In">Immigrant (Transferred In)</option>
                                <option value="Transferred Out">Migrant (Transferred Out)</option>
                                <option value="Deceased">Deceased</option>
                            </select>
                        </div>
                        
                        <div class="form-action-area">
                            <button type="submit" class="btn btn-primary btn-large">
                                <ion-icon name="save-outline"></ion-icon>
                                Register Member
                            </button>
                        </div>
                    </div>

                </div>
            </form>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <style>
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.8rem; background: #0f172a; border: 1px solid #334155; border-radius: 0.5rem; color: white; font-size: 0.95rem; }
        .btn-large { width: 100%; padding: 1.25rem; font-size: 1.1rem; margin-top: 1rem; }
    </style>
</body>
</html>
