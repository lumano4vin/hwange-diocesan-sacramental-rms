<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Prenuptial Investigation (PNI) List
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Marriage Preparation Hub";
$header_subtitle = "Management of Prenuptial Investigations (PNI) and Freedom to Marry declarations.";

$params = [];
$filter = get_parish_filter($params, 'pi');

$investigations = db_fetchAll("
    SELECT pi.*, 
           g.first_name as g_first, g.last_name as g_last,
           b.first_name as b_first, b.last_name as b_last,
           pa.parish_name, u.full_name as priest_name
    FROM prenuptial_investigations pi
    JOIN parishioners g ON pi.groom_id = g.person_id
    JOIN parishioners b ON pi.bride_id = b.person_id
    JOIN parishes pa ON pi.parish_id = pa.parish_id
    LEFT JOIN users u ON pi.priest_user_id = u.user_id
    WHERE 1=1 $filter
    ORDER BY pi.investigation_date DESC
", $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marriage Preparation - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Header -->
            <?php include '../includes/header.php'; ?>
            
            <div class="content-body" style="padding: 2rem 0;">
                
                <div class="action-bar" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="font-family: 'Outfit'; color: white;">Prenuptial Registry</h2>
                    <?php if (is_cleric()): ?>
                    <a href="marriage_pni_add.php" class="btn btn-primary" style="background: var(--accent); color: #000; border: none; display: flex; align-items: center; gap: 8px;">
                        <ion-icon name="add-outline"></ion-icon> New Investigation
                    </a>
                    <?php endif; ?>
                </div>

                <div class="card bg-card no-padding overflow-hidden">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Groom & Bride</th>
                                <th>Parish</th>
                                <th>Investigation Date</th>
                                <th>Priest / Minister</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($investigations as $row): ?>
                            <tr>
                                <td>
                                    <strong><?php echo h($row['g_first'] . ' ' . $row['g_last']); ?></strong><br>
                                    <span style="font-size: 0.8rem; color: var(--accent);">and</span><br>
                                    <strong><?php echo h($row['b_first'] . ' ' . $row['b_last']); ?></strong>
                                </td>
                                <td><?php echo h($row['parish_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['investigation_date'])); ?></td>
                                <td><?php echo h($row['priest_name'] ?: 'External'); ?></td>
                                <td>
                                    <?php if ($row['status'] == 'Completed'): ?>
                                        <span class="status-badge verified">Completed</span>
                                    <?php else: ?>
                                        <span class="status-badge draft">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <div style="display: flex; gap: 8px;">
                                        <a href="marriage_pni_print.php?id=<?php echo $row['pni_id']; ?>" class="btn btn-sm" style="background: rgba(255,255,255,0.05); color: white;" title="Print Form A">
                                            <ion-icon name="print-outline"></ion-icon> PNI
                                        </a>
                                        <a href="marriage_banns_print.php?id=<?php echo $row['pni_id']; ?>" class="btn btn-sm" style="background: rgba(251, 191, 36, 0.1); color: #fbbf24;" title="Print Banns">
                                            <ion-icon name="megaphone-outline"></ion-icon> Banns
                                        </a>
                                        <?php if (is_admin()): ?>
                                            <a href="../actions/delete_record.php?type=pni&id=<?php echo $row['pni_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Permanently delete this investigation record?')" title="Delete">
                                                <ion-icon name="trash-outline"></ion-icon>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($investigations)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">No prenuptial investigations found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php include '../includes/privacy_footer.php'; ?>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
