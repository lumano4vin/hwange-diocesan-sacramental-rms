<?php
/**
 * Hwange Diocese Records Management System
 * RCIA / Catechumen List
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

// Base SQL
$sql = "SELECT c.*, p.parish_name 
        FROM catechumens c 
        JOIN parishes p ON c.parish_id = p.parish_id";

$where_parts = [];
$params = [];

// Parish Isolation
$parish_filter = get_parish_filter($params, 'c');
if ($parish_filter) {
    $where_parts[] = ltrim($parish_filter, ' AND ');
}

// Search
$search = $_GET['q'] ?? '';
if (!empty($search)) {
    $where_parts[] = "(c.first_name LIKE ? OR c.last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where_parts)) {
    $sql .= " WHERE " . implode(" AND ", $where_parts);
}

$sql .= " ORDER BY c.enrollment_date DESC";
$catechumens = db_fetchAll($sql, $params);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCIA Tracking - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .status-inquirer { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.2); }
        .status-catechumen { background: rgba(251, 191, 36, 0.1); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.2); }
        .status-elect { background: rgba(167, 139, 250, 0.1); color: #a78bfa; border: 1px solid rgba(167, 139, 250, 0.2); }
        .status-neophyte { background: rgba(52, 211, 153, 0.1); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.2); }
        .status-dropped { background: rgba(248, 113, 113, 0.1); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.2); }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <?php 
                $header_title = "RCIA & Catechumens";
                $header_subtitle = "Track individuals preparing for the Sacraments of Initiation.";
                ob_start(); ?>
                <a href="add_catechumen.php" class="btn btn-primary">
                    <ion-icon name="person-add-outline"></ion-icon>
                    Enroll Catechumen
                </a>
                <?php
                $additional_header_actions = ob_get_clean();
                include '../includes/header.php'; 
            ?>

            <div class="filter-bar">
                <form method="GET" action="" style="display: flex; gap: 1rem; width: 100%; max-width: 600px;">
                    <div class="search-box" style="flex: 1;">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" name="q" placeholder="Search RCIA candidates..." value="<?php echo h($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Parish</th>
                            <th>Enrolled</th>
                            <th>Expected Baptism</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($catechumens)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">No catechumens found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($catechumens as $c): ?>
                            <tr>
                                <td style="font-weight: 600; color: white;">
                                    <?php echo h($c['first_name'] . ' ' . $c['last_name']); ?>
                                </td>
                                <td><?php echo h($c['parish_name']); ?></td>
                                <td><?php echo $c['enrollment_date'] ? date('d M Y', strtotime($c['enrollment_date'])) : '-'; ?></td>
                                <td><?php echo $c['expected_baptism_date'] ? date('d M Y', strtotime($c['expected_baptism_date'])) : '-'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($c['status']); ?>">
                                        <?php echo h($c['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
