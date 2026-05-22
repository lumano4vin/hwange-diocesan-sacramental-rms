<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Clergy Registry - Chancery Module
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_admin();

$clergy = db_fetchAll("
    SELECT u.user_id, u.full_name, u.role, u.parish_id, p.parish_name,
           cp.title, cp.faculties_status, cp.incardination_status, cp.date_of_ordination
    FROM users u
    LEFT JOIN parishes p ON u.parish_id = p.parish_id
    LEFT JOIN clergy_profiles cp ON u.user_id = cp.user_id
    WHERE u.role IN ('Priest', 'Deacon', 'Admin', 'Chancellor')
    ORDER BY u.full_name ASC
");

$header_title = "Clergy Registry";
$header_subtitle = "Canonical personnel files for the Diocese of Hwange.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clergy Registry - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0;">
                <div class="card bg-card no-padding overflow-hidden" style="border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                    <div class="card-header" style="padding: 1.5rem 2rem; background: rgba(255,255,255,0.02);">
                        <h3 style="font-family: 'Outfit'; color: var(--accent);">Personnel Oversight</h3>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.02); text-align: left;">
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Name & Title</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Current Appointment</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Canonical Status</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Faculties</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clergy as $c): ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                        <td style="padding: 1.25rem 2rem;">
                                            <div style="font-weight: 700; color: white;">
                                                <?php echo h(($c['title'] ?: 'Rev. ') . $c['full_name']); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo h($c['role']); ?></div>
                                        </td>
                                        <td style="padding: 1.25rem 2rem; font-size: 0.9rem;">
                                            <?php echo h($c['parish_name'] ?: 'Chancery / Unassigned'); ?>
                                        </td>
                                        <td style="padding: 1.25rem 2rem;">
                                            <span class="status-badge" style="background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.2);">
                                                <?php echo h($c['incardination_status'] ?: 'Unknown'); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1.25rem 2rem;">
                                            <span class="status-badge <?php echo strtolower($c['faculties_status']) === 'active' ? 'verified' : 'draft'; ?>">
                                                <?php echo h($c['faculties_status'] ?: 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1.25rem 2rem;">
                                            <a href="clergy_dossier.php?id=<?php echo $c['user_id']; ?>" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.5rem 1rem;">View Dossier</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
