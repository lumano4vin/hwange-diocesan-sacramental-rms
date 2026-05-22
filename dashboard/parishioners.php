<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parishioner Directory - Main List
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Handle Search & Filter
$search = $_GET['q'] ?? '';
$parish_filter = $_GET['parish_id'] ?? '';
$where = " WHERE 1=1 ";
$params = [];

if (!empty($search)) {
    $where .= " AND (p.first_name LIKE ? OR p.last_name LIKE ?) ";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($parish_filter)) {
    $where .= " AND p.current_parish_id = ? ";
    $params[] = $parish_filter;
}

// Fetch Parishioners with Parish names
$sql = "SELECT p.*, pr.parish_name 
        FROM parishioners p
        LEFT JOIN parishes pr ON p.current_parish_id = pr.parish_id
        $where
        ORDER BY p.last_name ASC, p.first_name ASC";

$parishioners = db_fetchAll($sql, $params);

// Fetch parishes for the filter dropdown
$parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parishioner Directory - Hwange Diocese RMS</title>
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
                $header_title = "Parishioner Directory";
                $header_subtitle = "Manage the central database of the faithful in the Hwange Diocese.";
                ob_start(); ?>
                <a href="add_parishioner.php" class="btn btn-primary">
                    <ion-icon name="person-add-outline"></ion-icon>
                    New Registration
                </a>
                <?php 
                $additional_header_actions = ob_get_clean();
                include '../includes/header.php'; 
            ?>

            <!-- Search & Filter Section -->
            <div class="filter-bar">
                <form action="parishioners.php" method="GET" class="filter-group" id="filter-form">
                    <div class="search-input-group" style="min-width: 300px;">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" name="q" value="<?php echo h($search); ?>" placeholder="Search parishioners...">
                    </div>
                    
                    <div class="filter-group">
                        <label>Parish</label>
                        <select name="parish_id" onchange="this.form.submit()">
                            <option value="">All Parishes</option>
                            <?php foreach($parishes as $p): ?>
                                <option value="<?php echo $p['parish_id']; ?>" <?php echo $parish_filter == $p['parish_id'] ? 'selected' : ''; ?>>
                                    <?php echo h($p['parish_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if(!empty($search) || !empty($parish_filter)): ?>
                        <a href="parishioners.php" class="btn btn-secondary btn-sm" title="Clear Filters" style="padding: 0.5rem;">
                            <ion-icon name="close-circle-outline" style="margin: 0;"></ion-icon>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Records Table -->
            <div class="card bg-card table-card">
                <div class="card-header">
                    <h3>Diocesan Members</h3>
                </div>
                <div class="card-body no-padding">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Gender</th>
                                <th>Date of Birth</th>
                                <th>Current Parish</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($parishioners)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No records found matching your criteria.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($parishioners as $row): ?>
                                <tr>
                                    <td>
                                        <span class="parishioner-name"><?php echo h($row['first_name'] . ' ' . $row['last_name']); ?></span>
                                        <span class="parishioner-meta">UID: <?php echo str_pad($row['person_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td><?php echo h($row['gender']); ?></td>
                                    <td><?php echo !empty($row['dob']) ? date('d M Y', strtotime($row['dob'])) : '<span class="text-muted">Not Recorded</span>'; ?></td>
                                    <td><?php echo h($row['parish_name'] ?: '--'); ?></td>
                                    <?php 
                                        $display_status = $row['status'] == 'Moved' ? 'Transferred Out' : ($row['status'] ?: 'Active');
                                        $status_class = strtolower(str_replace(' ', '-', $display_status));
                                        if ($display_status == 'Transferred In') $display_status = 'Immigrant';
                                        if ($display_status == 'Transferred Out') $display_status = 'Migrant';
                                    ?>
                                    <td><span class="badge-status badge-<?php echo $status_class; ?>"><?php echo h($display_status); ?></span></td>
                                    <td class="table-actions">
                                        <a href="baptisms.php?q=<?php echo urlencode($row['first_name']); ?>" class="action-btn view" title="View Sacraments">
                                            <ion-icon name="ribbon-outline"></ion-icon>
                                        </a>
                                        <a href="edit_parishioner.php?id=<?php echo $row['person_id']; ?>" class="action-btn edit" title="Edit Member">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </a>
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
    <style>
        /* Spread Out & Presentable Table Layout */
        .table-card { margin-top: 2rem; box-shadow: 0 20px 50px rgba(0,0,0,0.3); border-radius: 1.5rem; overflow: hidden; }
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .data-table th { 
            padding: 1.25rem 1.5rem; 
            text-align: left; 
            color: var(--accent); 
            font-size: 0.85rem; 
            text-transform: uppercase; 
            letter-spacing: 2px; 
            font-weight: 800;
            background: rgba(255,255,255,0.02);
            border-bottom: 2px solid rgba(56, 189, 248, 0.2);
        }
        .data-table td { 
            padding: 1.5rem; 
            border-bottom: 1px solid rgba(255,255,255,0.05); 
            font-size: 1rem; 
            vertical-align: middle;
            transition: all 0.2s;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: rgba(56, 189, 248, 0.03); color: white; }
        
        .parishioner-name { font-size: 1.1rem; font-weight: 700; color: white; display: block; margin-bottom: 4px; }
        .parishioner-meta { font-size: 0.8rem; color: var(--text-muted); }
        
        .badge-status { 
            padding: 0.5rem 1rem; 
            border-radius: 50px; 
            font-size: 0.75rem; 
            font-weight: 800; 
            text-transform: uppercase;
            display: inline-block;
        }
        .badge-active { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .badge-transferred-in { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.2); }
        .badge-transferred-out { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
        .badge-deceased { background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2); }

        .filter-bar { padding: 2rem; background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(10px); }
        .search-input-group input { padding: 1rem 1.5rem 1rem 3rem; font-size: 1rem; width: 100%; }
        .search-input-group ion-icon { left: 1.2rem; font-size: 1.4rem; }
    </style>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
