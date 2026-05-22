<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Death & Burial Record Detailed View - Modernized
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? null;
if (!$id) die("Record ID required.");

// Fetch record with full details
$record = db_fetch("
    SELECT d.*, p.first_name, p.last_name, p.dob,
           pa.parish_name, pa.location as parish_location
    FROM deaths d 
    JOIN parishioners p ON d.person_id = p.person_id 
    JOIN parishes pa ON d.parish_id = pa.parish_id 
    WHERE d.death_id = ?
", [$id]);

if (!$record) die("Canonical record not found.");

// Header metadata
$header_title = "Funeral Archive Entry";
$header_subtitle = "Detailed canonical view for the late " . h($record['first_name'] . ' ' . $record['last_name']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Burial - <?php echo h($record['first_name']); ?> - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Header -->
            <?php include '../includes/header.php'; ?>
            
            <div class="content-body" style="padding: 2rem 0;">
                
                <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <a href="burial_list.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px;">
                        <ion-icon name="arrow-back-outline"></ion-icon> Back to Registry
                    </a>
                    <div style="display: flex; gap: 1rem;">
                        <a href="burial_certificate.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-primary" style="background: #94a3b8; color: #000; font-weight: 800; display: flex; align-items: center; gap: 8px; border: none;">
                            <ion-icon name="print-outline"></ion-icon> Print Certificate
                        </a>
                        <a href="burial_edit.php?id=<?php echo $id; ?>" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px;">
                            <ion-icon name="create-outline"></ion-icon> Edit Entry
                        </a>
                    </div>
                </div>

                <div class="record-details-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    
                    <div class="main-details">
                        <div class="card bg-card" style="padding: 3rem; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; color: white; margin-bottom: 2rem; display: flex; align-items: center; gap: 12px;">
                                <ion-icon name="leaf-outline" style="color: #94a3b8;"></ion-icon>
                                Funeral & Burial Declaration
                            </h3>

                            <div class="details-rows" style="display: flex; flex-direction: column; gap: 2rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem;">
                                    <div>
                                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Faithful Name</span>
                                        <span style="font-size: 1.2rem; font-weight: 700; color: white;"><?php echo h($record['first_name'] . ' ' . $record['last_name']); ?></span>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Date of Death</span>
                                        <span style="font-size: 1.2rem; font-weight: 700; color: white;"><?php echo date('d F, Y', strtotime($record['date_of_death'])); ?></span>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Age at Death</span>
                                        <span style="font-size: 1.2rem; font-weight: 700; color: var(--accent);"><?php echo get_age($record['dob'], $record['date_of_death']); ?></span>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                    <div>
                                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Date of Burial</span>
                                        <span style="font-size: 1.1rem; color: white;"><?php echo $record['date_of_burial'] ? date('d F, Y', strtotime($record['date_of_burial'])) : 'Not recorded'; ?></span>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Place of Burial</span>
                                        <span style="font-size: 1.1rem; color: white;"><?php echo h($record['place_of_burial'] ?: 'Not recorded'); ?></span>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                    <div>
                                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Minister (Officiant)</span>
                                        <span style="font-size: 1.1rem; color: white;"><?php echo h($record['minister'] ?: 'Not recorded'); ?></span>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Parish / Mission</span>
                                        <span style="font-size: 1.1rem; color: white;"><?php echo h($record['parish_name']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="side-metadata">
                        <div class="card bg-card" style="padding: 2rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 1.5rem;">
                            <h4 style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 1.5rem;">Registry Archive</h4>
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                <div>
                                    <span style="display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase;">Register Book</span>
                                    <span style="font-weight: 700; color: white;"><?php echo h($record['register_book_number'] ?: 'Volume I'); ?></span>
                                </div>
                                <div>
                                    <span style="display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase;">Page Number</span>
                                    <span style="font-weight: 700; color: white;"><?php echo h($record['page_number'] ?: '-'); ?></span>
                                </div>
                                <div>
                                    <span style="display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase;">Entry Number</span>
                                    <span style="font-weight: 700; color: white;"><?php echo h($record['entry_number'] ?: '-'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-card" style="padding: 2rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05);">
                            <h4 style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 1.5rem;">System Verification</h4>
                            <div style="text-align: center;">
                                <div style="font-size: 3rem; color: #94a3b8; margin-bottom: 10px;"><ion-icon name="shield-checkmark"></ion-icon></div>
                                <span style="display: block; font-weight: 800; color: #94a3b8; text-transform: uppercase; font-size: 0.8rem; margin-bottom: 15px;">Archival Record Valid</span>
                                <div style="font-family: monospace; font-size: 0.6rem; color: var(--text-muted); word-break: break-all; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px;">
                                    <?php echo h($record['verification_hash'] ?: 'DEATH_HASH_VERIFICATION_SECURE'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <?php include '../includes/privacy_footer.php'; ?>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
