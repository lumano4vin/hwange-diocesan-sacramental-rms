<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Death Registry - Add Record
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Fetch parishes for dropdown
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC");

// Smart Reference: Fetch last entry for this parish to suggest numbers
$parish_id = $_SESSION['parish_id'] ?? 1;
$suggested_book = 'Book 01';
$suggested_page = '1';
$suggested_entry = '1';

try {
    $last_ref = db_fetch("SELECT register_book_number, page_number, entry_number FROM deaths WHERE parish_id = ? ORDER BY death_id DESC LIMIT 1", [$parish_id]);
    
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
    <title>Add Death Record - Hwange Diocese RMS</title>
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

        <!-- Main Content -->
        <main class="main-content">
            
            <?php 
                $header_title = "Record New Death";
                $header_subtitle = "Enter the details for the official death register.";
                ob_start(); ?>
                <a href="deaths.php" class="btn btn-secondary" style="background: #334155 !important; color: #ffffff !important; border: 1px solid #475569 !important; font-weight: 700 !important; text-decoration: none !important;">
                    <ion-icon name="arrow-back-outline" style="color: #ffffff !important;"></ion-icon>
                    Back to List
                </a>
                <?php 
                $additional_header_actions = ob_get_clean();
                include '../includes/header.php'; 
            ?>

            <form action="../actions/save_death.php" method="POST" class="entry-form">
                
                <div class="form-grid">
                    
                    <!-- Section 1: Person -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="person-outline"></ion-icon> Deceased Details</h3>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name(s)</label>
                                <input type="text" name="first_name" id="first_name" required placeholder="Search name...">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" name="last_name" id="last_name" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" name="dob" id="dob" required>
                        </div>
                        <div class="form-group">
                            <label for="baptism_link">Baptismal Reference (if known)</label>
                            <input type="text" name="baptism_link" id="baptism_link" placeholder="Internal link for final notation">
                        </div>
                    </div>

                    <!-- Section 2: Death & Burial -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="skull-outline"></ion-icon> Event Details</h3>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_death">Date of Death</label>
                                <input type="date" name="date_of_death" id="date_of_death" required>
                            </div>
                            <div class="form-group">
                                <label for="date_of_burial">Date of Burial</label>
                                <input type="date" name="date_of_burial" id="date_of_burial">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="place_of_burial">Place of Burial / Cemetery</label>
                            <input type="text" name="place_of_burial" id="place_of_burial" required placeholder="e.g., Hwange Cemetery">
                        </div>
                        <div class="form-group">
                            <label for="minister">Officiating Minister</label>
                            <input type="text" name="minister" id="minister" required placeholder="Rev. Fr. Example">
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
                        <div class="form-group" style="margin-top: 1.25rem;">
                            <label>Parish of Origin / Funeral</label>
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

                    <!-- Section 3: Registry Ref -->
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="book-outline"></ion-icon> Register Reference</h3>
                        </div>
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
                        
                        <div class="form-action-area" style="margin-top: auto;">
                            <button type="submit" class="btn btn-primary btn-large">
                                <ion-icon name="save-outline"></ion-icon>
                                Save Death Record
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
        .btn-large { width: 100%; padding: 1.25rem; font-size: 1.1rem; }
        .form-action-area { padding-top: 1rem; }
    </style>
</body>
</html>
