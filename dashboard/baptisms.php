<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Baptismal Registry - List & Search
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Handle Search & Filter
$search = $_GET['q'] ?? '';
$year_filter = $_GET['year'] ?? '';
$parish_filter = $_GET['parish'] ?? '';

// Enforce Parish Isolation for non-admins
$where_parts = [];
$params = [];

// 1. Mandatory Parish Filter (Isolation)
$parish_filter_sql = get_parish_filter($params, 'b');
if (!empty($parish_filter_sql)) {
    $where_parts[] = ltrim($parish_filter_sql, ' AND ');
}

// 2. Search Filter
if (!empty($search)) {
    $where_parts[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR b.register_book_number = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
}

// 3. Year Filter
if (!empty($year_filter)) {
    $where_parts[] = "strftime('%Y', b.date_of_baptism) = ?";
    $params[] = $year_filter;
}

// 4. Manual Parish Filter (for Admins)
if (!empty($parish_filter) && is_admin()) {
    $where_parts[] = "b.parish_id = ?";
    $params[] = $parish_filter;
}

$where = !empty($where_parts) ? " WHERE " . implode(" AND ", $where_parts) : "";

// Fetch parishes for filter dropdown
$parishes_list = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC");

// Fetch Baptisms joined with Parishioners and Parishes
$sql = "SELECT b.*, p.first_name, p.last_name, p.dob, pr.parish_name 
        FROM baptisms b
        JOIN parishioners p ON b.person_id = p.person_id
        JOIN parishes pr ON b.parish_id = pr.parish_id
        $where
        ORDER BY b.date_of_baptism DESC";

$baptisms = db_fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baptismal Registry - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <!-- Sidebar (Reused from Dashboard) -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <?php 
                $header_title = "Baptismal Registry";
                $header_subtitle = "Search and manage sacramental records for the diocese.";
                ob_start(); ?>
                <a href="add_baptism.php" class="btn btn-primary">
                    <ion-icon name="add-circle-outline"></ion-icon>
                    New Baptismal Entry
                </a>
                <?php 
                $additional_header_actions = ob_get_clean();
                include '../includes/header.php'; 
            ?>

            <!-- Search & Filter Section -->
            <div class="filter-bar">
                <form action="baptisms.php" method="GET" class="filter-group" id="filter-form">
                    <div class="search-input-group" style="min-width: 300px;">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" name="q" value="<?php echo h($search); ?>" placeholder="Search name or book...">
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
                        <a href="baptisms.php" class="btn btn-secondary btn-sm" title="Clear Filters" style="padding: 0.5rem;">
                            <ion-icon name="close-circle-outline" style="margin: 0;"></ion-icon>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Records Table -->
            <div class="card bg-card table-card">
                <div class="card-header">
                    <h3>Recent Records</h3>
                </div>
                <div class="card-body no-padding">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>DOB</th>
                                <th>Date of Baptism</th>
                                <th>Minister</th>
                                <th>Parish</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($baptisms)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No records found.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($baptisms as $row): ?>
                                <tr>
                                    <td><strong><?php echo h($row['first_name'] . ' ' . $row['last_name']); ?></strong></td>
                                    <td><?php echo h($row['dob']); ?></td>
                                    <td><?php echo h($row['date_of_baptism']); ?></td>
                                    <td><?php echo h($row['minister']); ?></td>
                                    <td><?php echo h($row['parish_name']); ?></td>
                                    <td>
                                        <?php if (($row['status'] ?? 'Verified') === 'Verified'): ?>
                                            <span class="status-badge verified" title="Verified by clergy"><ion-icon name="checkmark-circle"></ion-icon> Verified</span>
                                        <?php else: ?>
                                            <span class="status-badge draft" title="Pending verification"><ion-icon name="shield-outline"></ion-icon> Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table-actions">
                                        <a href="view_baptism.php?id=<?php echo $row['baptism_id']; ?>" class="action-btn view" title="View Canonical History">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </a>
                                        
                                        <?php if (has_record_permission($row, 'print')): ?>
                                        <a href="certificate.php?id=<?php echo $row['baptism_id']; ?>" class="action-btn cert" title="Generate Certificate">
                                            <ion-icon name="document-text-outline"></ion-icon>
                                        </a>
                                        <a href="certificate.php?id=<?php echo $row['baptism_id']; ?>&type=baptism" class="action-btn" title="Print Certificate" target="_blank">
                                            <ion-icon name="print-outline"></ion-icon>
                                        </a>
                                        <?php endif; ?>

                                        <?php if (has_record_permission($row, 'edit')): ?>
                                        <a href="edit_baptism.php?id=<?php echo $row['baptism_id']; ?>" class="action-btn edit" title="Edit">
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
    <style>
        .search-card { margin-bottom: 2rem; padding: 1rem; }
        .search-form { display: flex; gap: 1rem; align-items: center; }
        .search-input-group { 
            flex: 1; 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            background: #0f172a; 
            padding: 0.75rem 1rem; 
            border-radius: 0.75rem; 
            border: 1px solid var(--border);
        }
        .search-input-group input { background: transparent; border: none; color: white; width: 100%; outline: none; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .data-table th { text-align: left; padding: 1rem; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        .data-table td { padding: 1.25rem 1rem; border-bottom: 1px solid var(--border); font-size: 0.95rem; }
        
        .table-actions { display: flex; gap: 0.75rem; }
        .action-btn { font-size: 1.25rem; color: var(--text-muted); transition: color 0.2s; }
        .action-btn:hover { color: var(--accent); }
        .action-btn.cert:hover { color: var(--secondary); }
    </style>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
