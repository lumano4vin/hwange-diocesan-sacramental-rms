<?php
/**
 * Hwange Diocese Records Management System
 * Enroll New RCIA Catechumen
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

// Fetch parishes for dropdown
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC");
$user_parish = $_SESSION['parish_id'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Catechumen - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <?php 
                $header_title = "Enroll Catechumen";
                $header_subtitle = "Add a new candidate to the RCIA tracking registry.";
                include '../includes/header.php'; 
            ?>

            <div class="card bg-card" style="max-width: 800px; margin: 0 auto;">
                <div class="card-body">
                    <form action="../actions/save_catechumen.php" method="POST" class="standard-form">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name(s) *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Parish / Mission *</label>
                                <select name="parish_id" class="form-control" required <?php echo $user_parish ? 'disabled' : ''; ?>>
                                    <option value="">Select Parish / Mission...</option>
                                    <?php foreach ($parishes as $p): ?>
                                        <option value="<?php echo $p['parish_id']; ?>" <?php echo ($user_parish == $p['parish_id']) ? 'selected' : ''; ?>>
                                            <?php echo h($p['parish_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($user_parish): ?>
                                    <input type="hidden" name="parish_id" value="<?php echo $user_parish; ?>">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Date of Enrollment in RCIA</label>
                                <input type="date" name="enrollment_date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Expected Baptism Date (Easter Vigil)</label>
                                <input type="date" name="expected_baptism_date" class="form-control">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Status / Canonical Stage</label>
                                <select name="status" class="form-control" required>
                                    <option value="Inquirer">Inquirer (Pre-Catechumenate)</option>
                                    <option value="Catechumen">Catechumen (Accepted)</option>
                                    <option value="Elect">Elect (Post Rite of Election)</option>
                                    <option value="Neophyte">Neophyte (Baptized)</option>
                                    <option value="Dropped">Dropped / Inactive</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Sponsor Name</label>
                                <input type="text" name="sponsor_name" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Additional Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-actions" style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                            <a href="rcia.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Enroll Catechumen</button>
                        </div>

                    </form>
                </div>
            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
