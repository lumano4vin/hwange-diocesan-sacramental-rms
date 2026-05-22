<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Bulk Legacy Data Importer (CSV)
 * 
 * Allows the Chancery to quickly digitize thousands of legacy records from old spreadsheets.
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page - ADMIN ONLY
require_role('admin');

$parishes = db_fetchAll("SELECT * FROM parishes ORDER BY parish_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Import - Hwange Diocese RMS</title>
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
                $header_title = "Bulk Legacy Data Importer";
                $header_subtitle = "Efficiently digitize centuries of Hwange Diocesan records via secure CSV upload.";
                include '../includes/header.php'; 
            ?>


            <div class="dashboard-grid single-column">
                
                <div class="card bg-card">
                    <div class="card-header">
                        <h3 style="color: #10b981;"><ion-icon name="cloud-upload-outline"></ion-icon> Data Upload</h3>
                    </div>
                    <div class="card-body">
                        
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success" style="margin-bottom: 2rem;">
                                <b>Import Successful!</b> <?php echo h($_GET['count']); ?> records have been securely added.
                            </div>
                        <?php endif; ?>

                        <form action="../actions/process_import.php" method="POST" enctype="multipart/form-data" class="import-form">
                            
                            <div class="instruction-box" style="background: rgba(16, 185, 129, 0.05); padding: 1.5rem; border: 1px dashed rgba(16, 185, 129, 0.3); border-radius: 1rem; margin-bottom: 2rem;">
                                <h4 style="color: #10b981; margin-bottom: 0.5rem;">Legacy Data Security</h4>
                                <p class="text-muted" style="font-size: 0.9rem;">Please ensure your CSV file follows the standard Hwange Diocesan template. Every imported record will be assigned a unique verification hash automatically.</p>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Parish / Mission of Registry</label>
                                    <select name="parish_id" required>
                                        <option value="">Select the primary parish for this batch...</option>
                                        <?php foreach ($parishes as $p): ?>
                                            <option value="<?php echo $p['parish_id']; ?>"><?php echo h($p['parish_name']); ?> (<?php echo h($p['deanery']); ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Select CSV File (Legacy Records)</label>
                                    <div class="file-drop-zone" id="drop-zone" style="height: 150px; border: 2px dashed #334155; border-radius: 1rem; display: flex; align-items: center; justify-content: center; flex-direction: column; cursor: pointer; transition: 0.3s; background: rgba(30, 41, 59, 0.4);">
                                        <ion-icon name="document-text-outline" style="font-size: 3rem; color: #475569; margin-bottom: 0.5rem;"></ion-icon>
                                        <p style="color: #94a3b8; font-weight: 500;">Click or drag your archival CSV here</p>
                                        <input type="file" name="csv_file" id="csv-input" accept=".csv" required style="display: none;">
                                    </div>
                                    <p id="file-name" style="margin-top: 1rem; color: #10b981; font-weight: 600; font-size: 0.9rem; text-align: center; display: none;"></p>
                                </div>
                            </div>

                            <div class="form-actions" style="margin-top: 2rem;">
                                <button type="submit" class="btn btn-primary" style="background: #10b981; border: none; width: 100%; padding: 1.2rem; font-size: 1.1rem; border-radius: 1rem; box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.2);">
                                    <ion-icon name="cloud-upload-outline"></ion-icon>
                                    Execute Batch Archival Import
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <div class="card bg-card" style="margin-top: 1.5rem;">
                    <div class="card-header">
                        <h3><ion-icon name="help-circle-outline"></ion-icon> Archival CSV Guidelines</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted" style="margin-bottom: 1.5rem;">For successful digitization, your spreadsheet must contain these exact headers in row 1:</p>
                        <div class="template-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                            <div class="template-item" style="background: #1e293b; padding: 1rem; border-radius: 0.75rem; border: 1px solid #334155;">
                                <code style="color: #60a5fa; font-weight: 700;">first_name</code>
                                <div style="font-size: 0.8rem; color: #94a3b8;">Candidate's baptismal name</div>
                            </div>
                            <div class="template-item" style="background: #1e293b; padding: 1rem; border-radius: 0.75rem; border: 1px solid #334155;">
                                <code style="color: #60a5fa; font-weight: 700;">last_name</code>
                                <div style="font-size: 0.8rem; color: #94a3b8;">Family / Surname</div>
                            </div>
                            <div class="template-item" style="background: #1e293b; padding: 1rem; border-radius: 0.75rem; border: 1px solid #334155;">
                                <code style="color: #60a5fa; font-weight: 700;">dob</code>
                                <div style="font-size: 0.8rem; color: #94a3b8;">Date of Birth (YYYY-MM-DD)</div>
                            </div>
                            <div class="template-item" style="background: #1e293b; padding: 1rem; border-radius: 0.75rem; border: 1px solid #334155;">
                                <code style="color: #60a5fa; font-weight: 700;">baptism_date</code>
                                <div style="font-size: 0.8rem; color: #94a3b8;">Date of Sacrament (YYYY-MM-DD)</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <script>
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('csv-input');
        const fileNameUI = document.getElementById('file-name');

        dropZone.onclick = () => fileInput.click();

        fileInput.onchange = () => {
            const file = fileInput.files[0];
            if (file) {
                fileNameUI.textContent = 'Ready to import: ' + file.name;
                fileNameUI.style.display = 'block';
                dropZone.style.borderColor = '#10b981';
                dropZone.style.background = 'rgba(16, 185, 129, 0.1)';
            }
        };

        dropZone.ondragover = (e) => { e.preventDefault(); dropZone.style.borderColor = '#10b981'; };
        dropZone.ondragleave = () => { dropZone.style.borderColor = '#334155'; };
        dropZone.ondrop = (e) => {
            e.preventDefault();
            fileInput.files = e.dataTransfer.files;
            fileInput.onchange();
        };
    </script>
    <style>
        .single-column { max-width: 800px; margin: 0 auto; }
        .template-grid { margin-top: 1rem; }
        .instruction-box h4 { font-family: 'Outfit', sans-serif; font-size: 1.1rem; }
        .instruction-box p { line-height: 1.5; }
    </style>
</body>
</html>
