<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Ordination & profession Registry - List & Search
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Handle Search & Filter
$search = $_GET['q'] ?? '';
$year_filter = $_GET['year'] ?? '';
$parish_filter = $_GET['parish'] ?? '';

$where = "";
$params = [];

if (!empty($search)) {
    $where = " WHERE (p.first_name LIKE ? OR p.last_name LIKE ? OR op.record_type = ?) ";
    $params = ["%$search%", "%$search%", $search];
}

if (!empty($year_filter)) {
    $where .= ($where ? " AND " : " WHERE ") . "strftime('%Y', op.event_date) = ? ";
    $params[] = $year_filter;
}
if (!empty($parish_filter)) {
    $where .= ($where ? " AND " : " WHERE ") . "op.parish_id = ? ";
    $params[] = $parish_filter;
}

// Fetch parishes for filter dropdown
$parishes_list = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC");

// Fetch Ordinations/Professions joined with Parishioners and Parishes
$sql = "SELECT op.*, p.first_name, p.last_name, p.dob, pr.parish_name 
        FROM ordinations_professions op
        JOIN parishioners p ON op.person_id = p.person_id
        JOIN parishes pr ON op.parish_id = pr.parish_id
        $where
        ORDER BY op.event_date DESC";

$records = db_fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordination & Profession Registry - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.2">
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
            
            <?php 
                $header_title = "Ordination & Religious Profession Registry";
                $header_subtitle = "Manage and record Holy Orders and Perpetual Vows.";
                ob_start(); ?>
                <a href="add_ordination.php" class="btn btn-primary">
                    <ion-icon name="add-circle-outline"></ion-icon>
                    New Record
                </a>
                <?php 
                $additional_header_actions = ob_get_clean();
                include '../includes/header.php'; 
            ?>

            <!-- Search & Filter Section -->
            <div class="filter-bar">
                <form action="ordinations.php" method="GET" class="filter-group" id="filter-form">
                    <div class="search-input-group" style="min-width: 300px;">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" name="q" value="<?php echo h($search); ?>" placeholder="Search name or type...">
                    </div>
                    
                    <div class="filter-group">
                        <label>Year</label>
                        <select name="year" onchange="this.form.submit()">
                            <option value="">All Years</option>
                            <?php for($y = date('Y'); $y >= 1950; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $year_filter == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Parish</label>
                        <select name="parish" onchange="this.form.submit()">
                            <option value="">All Parishes</option>
                            <?php foreach($parishes_list as $p): ?>
                                <option value="<?php echo $p['parish_id']; ?>" <?php echo $parish_filter == $p['parish_id'] ? 'selected' : ''; ?>>
                                    <?php echo h($p['parish_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if(!empty($search) || !empty($year_filter) || !empty($parish_filter)): ?>
                        <a href="ordinations.php" class="btn btn-secondary btn-sm" title="Clear Filters" style="padding: 0.5rem;">
                            <ion-icon name="close-circle-outline" style="margin: 0;"></ion-icon>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Records Table -->
            <div class="card bg-card table-card">
                <div class="card-header">
                    <h3>Clergy & Religious Records</h3>
                </div>
                <div class="card-body no-padding">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Event Date</th>
                                <th>Place / Parish</th>
                                <th>Minister / Superior</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No records found.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($records as $row): ?>
                                <tr>
                                    <td><strong><?php echo h($row['first_name'] . ' ' . $row['last_name']); ?></strong></td>
                                    <td><?php echo h($row['record_type']); ?></td>
                                    <td><?php echo h($row['event_date']); ?></td>
                                    <td><?php echo h($row['parish_name']); ?></td>
                                    <td><?php echo h($row['celebrant_superior']); ?></td>
                                    <td>
                                        <?php if (($row['status'] ?? 'Verified') === 'Verified'): ?>
                                            <span class="status-badge verified" title="Verified by clergy"><ion-icon name="checkmark-circle"></ion-icon> Verified</span>
                                        <?php else: ?>
                                            <span class="status-badge draft" title="Pending verification"><ion-icon name="shield-outline"></ion-icon> Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table-actions">
                                        <a href="view_ordination.php?id=<?php echo $row['record_id']; ?>" class="action-btn view" title="View Details">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </a>

                                        <?php if (has_record_permission($row, 'print')): ?>
                                        <a href="ordination_certificate.php?id=<?php echo $row['record_id']; ?>" class="action-btn cert" title="View Certificate">
                                            <ion-icon name="document-text-outline"></ion-icon>
                                        </a>
                                        <a href="ordination_certificate.php?id=<?php echo $row['record_id']; ?>&print=true" class="action-btn" title="Print Certificate" target="_blank">
                                            <ion-icon name="print-outline"></ion-icon>
                                        </a>
                                        <?php endif; ?>

                                        <?php if (has_record_permission($row, 'edit')): ?>
                                        <a href="edit_ordination.php?id=<?php echo $row['record_id']; ?>" class="action-btn edit" title="Edit">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </a>
                                        <?php else: ?>
                                        <span class="action-btn locked" title="Record locked for your role"><ion-icon name="lock-closed-outline"></ion-icon></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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
