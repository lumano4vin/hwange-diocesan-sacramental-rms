<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Vocations Registry (Orders & Professions) - Modernized & Fixed
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Vocations & Holy Orders";
$header_subtitle = "Canonical registry of Ordinations and Perpetual Religious Professions.";

// Fetch all vocation records from the unified table
$records = db_fetchAll("
    SELECT v.*, p.first_name, p.last_name 
    FROM ordinations_professions v 
    JOIN parishioners p ON v.person_id = p.person_id 
    ORDER BY v.event_date DESC
");

// Split into Orders and Professions for the UI
$orders = array_filter($records, function($r) {
    return in_array($r['record_type'], ['Diaconate', 'Priesthood', 'Episcopate']);
});

$professions = array_filter($records, function($r) {
    return in_array($r['record_type'], ['First Vows', 'Perpetual Profession']);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vocations Registry - Hwange Diocesan RMS</title>
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
                
                <!-- Section 1: Holy Orders -->
                <div class="section-container" style="margin-bottom: 4rem;">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 0 1rem;">
                        <h2 style="font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 12px; color: white;">
                            <ion-icon name="ribbon-outline" style="color: #34d399;"></ion-icon>
                            Holy Orders Registry
                        </h2>
                        <a href="orders_add.php" class="btn btn-primary" style="background: #34d399; color: #000; border: none; font-weight: 800;">Register Ordination</a>
                    </div>
                    
                    <div class="card bg-card no-padding overflow-hidden" style="border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                        <div class="table-responsive">
                            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: rgba(255,255,255,0.02); text-align: left;">
                                        <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Ordinand Name</th>
                                        <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Canonical Rank</th>
                                        <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Date</th>
                                        <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Celebrant</th>
                                        <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr><td colspan="5" style="padding: 3rem; text-align: center; color: var(--text-muted);">No ordination records found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $row): ?>
                                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                                <td style="padding: 1.25rem 2rem; font-weight: 700; color: white;"><?php echo h($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                                <td style="padding: 1.25rem 2rem;">
                                                    <span style="background: rgba(52, 211, 153, 0.1); color: #34d399; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;"><?php echo h($row['record_type']); ?></span>
                                                </td>
                                                <td style="padding: 1.25rem 2rem;"><?php echo date('d M Y', strtotime($row['event_date'])); ?></td>
                                                <td style="padding: 1.25rem 2rem; color: var(--text-muted);"><?php echo h($row['celebrant_superior']); ?></td>
                                                <td style="padding: 1.25rem 2rem;">
                                                    <div style="display: flex; gap: 0.75rem;">
                                                        <a href="../parishioners/profile.php?id=<?php echo $row['person_id']; ?>" title="View Profile" style="color: var(--accent); font-size: 1.25rem;"><ion-icon name="person-circle-outline"></ion-icon></a>
                                                        <?php if (is_admin()): ?>
                                                            <a href="../actions/delete_record.php?type=ordination&id=<?php echo $row['ordination_id']; ?>" 
                                                               onclick="return confirm('WARNING: Permanent deletion of ordination records is logged and IRREVERSIBLE. Proceed?')" 
                                                               title="Delete Record" style="color: var(--error); font-size: 1.25rem;">
                                                                <ion-icon name="trash-outline"></ion-icon>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Religious Professions -->
                <div class="section-container">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 0 1rem;">
                        <h2 style="font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 12px; color: white;">
                            <ion-icon name="flame-outline" style="color: #fbbf24;"></ion-icon>
                            Religious Professions
                        </h2>
                        <a href="profession_add.php" class="btn btn-primary" style="background: #fbbf24; color: #000; border: none; font-weight: 800;">Register Vows</a>
                    </div>
                    
                    <div class="card bg-card no-padding overflow-hidden" style="border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                        <div class="table-responsive">
                            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: rgba(255,255,255,0.02); text-align: left;">
                                        <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Religious / Full Name</th>
                                        <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Congregation</th>
                                        <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Vow Type</th>
                                        <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Date</th>
                                        <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($professions)): ?>
                                        <tr><td colspan="5" style="padding: 3rem; text-align: center; color: var(--text-muted);">No profession records found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($professions as $row): ?>
                                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                                <td style="padding: 1.25rem 2rem;">
                                                    <div style="font-weight: 700; color: white;"><?php echo h($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                                </td>
                                                <td style="padding: 1.25rem 2rem; color: var(--accent); font-weight: 600;"><?php echo h($row['congregation']); ?></td>
                                                <td style="padding: 1.25rem 2rem;">
                                                    <span style="background: rgba(251, 191, 36, 0.1); color: #fbbf24; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;"><?php echo h($row['record_type']); ?></span>
                                                </td>
                                                <td style="padding: 1.25rem 2rem;"><?php echo date('d M Y', strtotime($row['event_date'])); ?></td>
                                                <td style="padding: 1.25rem 2rem;">
                                                    <div style="display: flex; gap: 0.75rem;">
                                                        <a href="../parishioners/profile.php?id=<?php echo $row['person_id']; ?>" title="View Profile" style="color: white; font-size: 1.25rem;"><ion-icon name="person-circle-outline"></ion-icon></a>
                                                        <?php if (is_admin()): ?>
                                                            <a href="../actions/delete_record.php?type=ordination&id=<?php echo $row['ordination_id']; ?>" 
                                                               onclick="return confirm('WARNING: Permanent deletion of profession records is logged and IRREVERSIBLE. Proceed?')" 
                                                               title="Delete Record" style="color: var(--error); font-size: 1.25rem;">
                                                                <ion-icon name="trash-outline"></ion-icon>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php include '../includes/privacy_footer.php'; ?>
        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
