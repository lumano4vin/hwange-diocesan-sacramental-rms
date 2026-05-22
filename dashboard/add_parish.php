<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parish Directory - Add New Parish/Mission
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page (Admin Only)
require_role('admin');

// Fetch potential priests for the dropdown
$priests = db_fetchAll("SELECT user_id, full_name FROM users WHERE role IN ('admin', 'priest') ORDER BY full_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Parish - Hwange Diocese RMS</title>
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
                    <h2>Register New Parish</h2>
                    <p>Add a new canonical mission or parish to the diocesan registry.</p>
                </div>
                <div class="header-actions">
                    <a href="parishes.php" class="btn btn-secondary">
                        <ion-icon name="arrow-back-outline"></ion-icon>
                        Back to Directory
                    </a>
                </div>
            </header>

            <form action="../actions/save_parish.php" method="POST" class="entry-form">
                <div class="form-grid single-column">
                    
                    <div class="card bg-card section-card">
                        <div class="card-header">
                            <h3><ion-icon name="business-outline"></ion-icon> Parish Information</h3>
                        </div>
                        
                        <div class="form-group">
                            <label for="parish_name">Official Parish Name</label>
                            <input type="text" name="parish_name" id="parish_name" placeholder="e.g., St. Mulumba Mission" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="deanery">Deanery</label>
                                <select name="deanery" id="deanery" required>
                                    <option value="">Select Deanery...</option>
                                    <option value="Binga">Binga</option>
                                    <option value="Dete">Dete</option>
                                    <option value="Hwange Urban">Hwange Urban</option>
                                    <option value="Empumalanga">Empumalanga</option>
                                    <option value="Lupane">Lupane</option>
                                    <option value="Jambezi">Jambezi</option>
                                    <option value="Makwa">Makwa</option>
                                    <option value="Victoria Falls">Victoria Falls</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="location">Physical Location</label>
                                <input type="text" name="location" id="location" placeholder="e.g., Baobab Hill, Hwange" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="priest_in_charge_id">Priest in Charge (Resident Priest)</label>
                            <select name="priest_in_charge_id" id="priest_in_charge_id">
                                <option value="">Select Priest...</option>
                                <?php foreach ($priests as $p): ?>
                                <option value="<?php echo $p['user_id']; ?>"><?php echo h($p['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="contact_number">Official Contact Number</label>
                            <input type="text" name="contact_number" id="contact_number" placeholder="+263 77...">
                        </div>

                        <div class="form-action-area">
                            <button type="submit" class="btn btn-primary btn-large">
                                <ion-icon name="save-outline"></ion-icon>
                                Save Parish Record
                            </button>
                        </div>
                    </div>

                </div>
            </form>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <style>
        .single-column { max-width: 800px; margin: 0 auto; }
        .entry-form { margin-top: 2rem; }
        .card-header h3 { display: flex; align-items: center; gap: 0.75rem; color: var(--accent); font-size: 1.1rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.9rem; background: #0f172a; border: 1px solid #334155; border-radius: 0.6rem; color: white; font-size: 0.95rem; }
        .btn-large { width: 100%; padding: 1.1rem; font-size: 1rem; border-radius: 0.6rem; margin-top: 1rem; }
    </style>
</body>
</html>
