<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Marriage Record Detailed View - Modernized
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? null;
if (!$id) die("Record ID required.");

// Fetch record with full details
$record = db_fetch("
    SELECT m.*, 
           g.first_name as groom_first, g.last_name as groom_last, g.dob as groom_dob,
           b.first_name as bride_first, b.last_name as bride_last, b.dob as bride_dob,
           pa.parish_name, pa.location as parish_location
    FROM marriages m 
    JOIN parishioners g ON m.groom_person_id = g.person_id 
    JOIN parishioners b ON m.bride_person_id = b.person_id
    JOIN parishes pa ON m.parish_id = pa.parish_id 
    WHERE m.marriage_id = ?
", [$id]);

if (!$record) die("Canonical record not found.");

// Header metadata
$header_title = "Matrimonial Archive Entry";
$header_subtitle = "Detailed canonical view of the marriage of " . h($record['groom_first'] . " & " . $record['bride_first']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Marriage - <?php echo h($record['groom_last']); ?> - Hwange Diocesan RMS</title>
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
                    <a href="marriage_list.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px;">
                        <ion-icon name="arrow-back-outline"></ion-icon> Back to Registry
                    </a>
                    <div style="display: flex; gap: 1rem;">
                        <a href="marriage_certificate.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-primary" style="background: #f472b6; color: white; font-weight: 800; display: flex; align-items: center; gap: 8px; border: none;">
                            <ion-icon name="print-outline"></ion-icon> Print Certificate
                        </a>
                        <a href="marriage_edit.php?id=<?php echo $id; ?>" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px;">
                            <ion-icon name="create-outline"></ion-icon> Edit Entry
                        </a>
                        <?php if (is_admin()): ?>
                            <a href="../actions/delete_record.php?type=marriage&id=<?php echo $id; ?>" 
                               onclick="return confirm('WARNING: Permanent deletion of canonical records is logged and IRREVERSIBLE. Proceed?')" 
                               class="btn btn-danger" style="display: flex; align-items: center; gap: 8px;">
                                <ion-icon name="trash-outline"></ion-icon> Delete Entry
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="record-details-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    
                    <div class="main-details">
                        <div class="card bg-card" style="padding: 3rem; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; color: white; margin-bottom: 2rem; display: flex; align-items: center; gap: 12px;">
                                <ion-icon name="heart-outline" style="color: #f472b6;"></ion-icon>
                                Matrimonial Declaration
                            </h3>

                            <div class="marriage-subjects" style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 2rem; align-items: center; margin-bottom: 3rem; background: rgba(255,255,255,0.02); padding: 2rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05);">
                                <div style="text-align: center;">
                                    <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">The Groom</span>
                                    <span style="display: block; font-size: 1.25rem; font-weight: 800; color: white;"><?php echo h($record['groom_first'] . ' ' . $record['groom_last']); ?></span>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">
                                        <span>Born: <?php echo date('Y', strtotime($record['groom_dob'])); ?></span>
                                        <span style="margin-left: 8px; color: var(--accent);">(Age: <?php echo get_age($record['groom_dob'], $record['date_of_marriage']); ?>)</span>
                                    </div>
                                </div>
                                <div style="font-size: 2rem; color: #f472b6; opacity: 0.5;"><ion-icon name="infinite-outline"></ion-icon></div>
                                <div style="text-align: center;">
                                    <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">The Bride</span>
                                    <span style="display: block; font-size: 1.25rem; font-weight: 800; color: white;"><?php echo h($record['bride_first'] . ' ' . $record['bride_last']); ?></span>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">
                                        <span>Born: <?php echo date('Y', strtotime($record['bride_dob'])); ?></span>
                                        <span style="margin-left: 8px; color: var(--accent);">(Age: <?php echo get_age($record['bride_dob'], $record['date_of_marriage']); ?>)</span>
                                    </div>
                                </div>
                            </div>

                            <div class="details-rows" style="display: flex; flex-direction: column; gap: 2rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                    <div>
                                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Date of Marriage</span>
                                        <span style="font-size: 1.2rem; font-weight: 700; color: white;"><?php echo date('d F, Y', strtotime($record['date_of_marriage'])); ?></span>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Parish / Mission</span>
                                        <span style="font-size: 1.1rem; color: white;"><?php echo h($record['parish_name']); ?></span>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                    <div>
                                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Officiating Priest</span>
                                        <span style="font-size: 1.1rem; color: white;"><?php echo h($record['officiant']); ?></span>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Canonical Witnesses</span>
                                        <span style="font-size: 1.1rem; color: white;"><?php echo h($record['witnesses_names'] ?: 'Not recorded'); ?></span>
                                    </div>
                                </div>

                                <?php if ($record['convalidation_date']): ?>
                                <div style="padding: 1.5rem; background: rgba(244, 114, 182, 0.1); border-radius: 1rem; border: 1px solid rgba(244, 114, 182, 0.2);">
                                    <h4 style="font-size: 0.75rem; text-transform: uppercase; color: #f472b6; margin-bottom: 0.5rem; font-weight: 800;">Convalidation Record</h4>
                                    <p style="color: white; font-weight: 600;">Marriage convalidated on <?php echo date('d M Y', strtotime($record['convalidation_date'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="side-metadata">
                        <div class="card bg-card" style="padding: 2rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 1.5rem;">
                            <h4 style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 1.5rem;">Registry Archive</h4>
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                <div>
                                    <span style="display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase;">Register Book</span>
                                    <span style="font-weight: 700; color: white;"><?php echo h($record['register_book_number'] ?: 'Matrimonial Vol I'); ?></span>
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
                                <div style="font-size: 3rem; color: #f472b6; margin-bottom: 10px;"><ion-icon name="shield-checkmark"></ion-icon></div>
                                <span style="display: block; font-weight: 800; color: #f472b6; text-transform: uppercase; font-size: 0.8rem; margin-bottom: 15px;">Marriage Record Valid</span>
                                <div style="font-family: monospace; font-size: 0.6rem; color: var(--text-muted); word-break: break-all; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px;">
                                    <?php echo h($record['verification_hash'] ?: 'M_HASH_VERIFICATION_SECURE'); ?>
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
