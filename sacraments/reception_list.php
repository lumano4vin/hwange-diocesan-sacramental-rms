<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Profession of Faith / Reception Registry List
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Profession of Faith Registry";
$header_subtitle = "Canonical records of converts received into full communion.";

$params = [];
$filter = get_parish_filter($params, 'r');

$receptions = db_fetchAll("
    SELECT r.*, p.first_name, p.last_name, pa.parish_name 
    FROM receptions r 
    JOIN parishioners p ON r.person_id = p.person_id 
    JOIN parishes pa ON r.parish_id = pa.parish_id 
    WHERE 1=1 $filter 
    ORDER BY r.date_of_reception DESC
", $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reception Registry - Hwange Diocesan RMS</title>
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
                    <h2 style="font-family: 'Outfit'; color: white;">Registry Entries</h2>
                    <a href="reception_add.php" class="btn btn-primary" style="background: #8b5cf6; border: none; display: flex; align-items: center; gap: 8px;">
                        <ion-icon name="add-outline"></ion-icon> New Entry
                    </a>
                </div>

                <div class="card bg-card no-padding overflow-hidden">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Previous Denomination</th>
                                <th>Date of Reception</th>
                                <th>Parish</th>
                                <th>Minister</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($receptions as $r): ?>
                            <tr>
                                <td><strong><?php echo h($r['first_name'] . ' ' . $r['last_name']); ?></strong></td>
                                <td>
                                    <?php echo h($r['previous_denomination'] ?: 'N/A'); ?>
                                    <?php if ($r['previous_baptism_church']): ?>
                                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;">
                                            Baptized at: <?php echo h($r['previous_baptism_church']); ?> 
                                            <?php if ($r['previous_baptism_date']): ?>
                                                on <?php echo date('d/m/Y', strtotime($r['previous_baptism_date'])); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($r['date_of_reception'])); ?></td>
                                <td><?php echo h($r['parish_name']); ?></td>
                                <td><?php echo h($r['minister']); ?></td>
                                <td class="actions">
                                    <a href="reception_certificate.php?id=<?php echo $r['reception_id']; ?>" class="btn btn-sm btn-secondary" title="Print Certificate">
                                        <ion-icon name="print-outline"></ion-icon>
                                    </a>
                                    <?php if (is_admin()): ?>
                                        <a href="../actions/delete_record.php?type=reception&id=<?php echo $r['reception_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('WARNING: You are about to permanently delete this canonical record. This action is logged and IRREVERSIBLE. Proceed?')" 
                                           title="Delete Record">
                                            <ion-icon name="trash-outline"></ion-icon>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($receptions)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">No records found in this registry.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
