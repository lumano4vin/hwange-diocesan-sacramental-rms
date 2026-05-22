<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Baptismal Registry - Add Record
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Fetch parishes for dropdown
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC");

// Smart Reference: Fetch last entry for this parish to suggest numbers
$parish_id = $_SESSION['parish_id'] ?? 1;
$last_ref = db_fetch("SELECT register_book_number, page_number, entry_number FROM baptisms WHERE parish_id = ? ORDER BY baptism_id DESC LIMIT 1", [$parish_id]);

$suggested_book = $last_ref['register_book_number'] ?? 'Book 01';
$suggested_page = $last_ref['page_number'] ?? '1';
$last_entry = $last_ref['entry_number'] ?? '0';

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
    <title>Add Baptismal Record - Hwange Diocese RMS</title>
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
        
        <!-- Sidebar (Reused) -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <?php 
                $header_title = "Record New Baptism";
                $header_subtitle = "Enter the sacramental details for the baptismal register.";
                ob_start(); ?>
                <a href="baptisms.php" class="btn btn-secondary" style="background: #334155 !important; color: #ffffff !important; border: 1px solid #475569 !important; font-weight: 700 !important; text-decoration: none !important;">
                    <ion-icon name="arrow-back-outline" style="color: #ffffff !important;"></ion-icon>
                    Return to Baptismal List
                </a>
                <?php 
                $additional_header_actions = ob_get_clean();
                include '../includes/header.php'; 
            ?>

            <form action="../actions/save_baptism.php" method="POST" class="entry-form">
                
                <div class="form-grid">
                    
                    <!-- Section 1: Candidate -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="person-outline"></ion-icon> Candidate Details</h3>
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
                                <label for="dob">Date of Birth</label>
                                <input type="date" name="dob" id="dob" required>
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select name="gender" id="gender" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="place_of_birth">Place of Birth</label>
                            <input type="text" name="place_of_birth" id="place_of_birth" placeholder="Hwange St. Patrick's Hospital">
                        </div>
                    </div>

                    <!-- Section 2: Lineage -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="people-outline"></ion-icon> Parents & Godparents</h3>
                        </div>
                        <div class="form-group">
                            <label for="father_name">Father's Full Name</label>
                            <input type="text" name="father_name" id="father_name" placeholder="Robert Doe">
                        </div>
                        <div class="form-group">
                            <label for="mother_name">Mother's First Name</label>
                            <input type="text" name="mother_name" id="mother_name" placeholder="Mary">
                        </div>
                        <div class="form-group">
                            <label for="mother_maiden_name">Mother's Maiden Name (Surname)</label>
                            <input type="text" name="mother_maiden_name" id="mother_maiden_name" placeholder="Smith" required>
                        </div>
                        <div class="form-group">
                            <label for="godparents">Godparents / Sponsors (Names)</label>
                            <input type="text" name="godparents" id="godparents" placeholder="Simon Peter and Elizabeth Ann">
                        </div>
                    </div>

                    <!-- Section 3: Sacrament Details -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="water-outline"></ion-icon> Sacrament Details</h3>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_baptism">Date of Baptism</label>
                                <input type="date" name="date_of_baptism" id="date_of_baptism" required>
                            </div>
                            <?php
                            $parish_options = array_map(function($p) {
                                return ['value' => (string)$p['parish_id'], 'text' => $p['parish_name']];
                            }, $parishes);
                            $options_json = htmlspecialchars(json_encode($parish_options), ENT_QUOTES, 'UTF-8');
                            ?>
                            <div class="form-group">
                                <label>Parish of Baptism</label>
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
                            <label for="minister">Minister (Celebrant)</label>
                            <input type="text" name="minister" id="minister" placeholder="Rev. Fr. Example" required>
                        </div>
                        <div class="form-group">
                            <label for="witnesses">Official Witnesses (if any)</label>
                            <input type="text" name="witnesses" id="witnesses">
                        </div>
                    </div>

                    <!-- Section 4: Registry Ref -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="book-outline"></ion-icon> Register Reference</h3>
                        </div>
                        <div class="form-group">
                            <label for="register_book_number">Register Book #</label>
                            <input type="text" name="register_book_number" id="register_book_number" value="<?php echo h($suggested_book); ?>" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="page_number">Page #</label>
                                <input type="text" name="page_number" id="page_number" value="<?php echo h($suggested_page); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="entry_number">Entry #</label>
                                <input type="text" name="entry_number" id="entry_number" value="<?php echo h($suggested_entry); ?>" required>
                            </div>
                        </div>
                        <div class="form-action-area">
                            <button type="submit" class="btn btn-primary btn-large">
                                <ion-icon name="save-outline"></ion-icon>
                                Save Baptismal Record
                            </button>
                        </div>
                    </div>

                </div>
            </form>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <style>
        .entry-form { margin-top: 1rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; }
        .section-card { height: 100%; display: flex; flex-direction: column; }
        .card-header h3 { display: flex; align-items: center; gap: 0.75rem; color: var(--accent); font-size: 1.1rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.8rem; background: #0f172a; border: 1px solid #334155; border-radius: 0.5rem; color: white; font-size: 0.95rem; }
        .btn-large { width: 100%; padding: 1.25rem; font-size: 1.1rem; margin-top: auto; }
        .form-action-area { margin-top: auto; padding-top: 1rem; }
    </style>
</body>
</html>
