<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parish Directory - Management
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page (Admin Only)
require_role('admin');

// Handle Search
$search = $_GET['q'] ?? '';
$where = "";
$params = [];

if (!empty($search)) {
    $where = " WHERE parish_name LIKE ? OR deanery LIKE ? OR location LIKE ? ";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Fetch Parishes
$sql = "SELECT p.*, u.full_name as priest_name 
        FROM parishes p
        LEFT JOIN users u ON p.priest_in_charge_id = u.user_id
        $where
        ORDER BY p.deanery ASC, p.parish_name ASC";

$parishes = db_fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parish Directory - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                $header_title = "Parish Directory";
                $header_subtitle = "Manage the canonical structure of the Hwange Diocese.";
                ob_start(); ?>
                <a href="add_parish.php" class="btn btn-primary">
                    <ion-icon name="add-circle-outline"></ion-icon>
                    Register New Parish
                </a>
                <?php 
                $additional_header_actions = ob_get_clean();
                include '../includes/header.php'; 
            ?>

            <!-- Search Section -->
            <div class="card bg-card search-card">
                <form action="parishes.php" method="GET" class="search-form">
                    <div class="search-input-group">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" name="q" value="<?php echo h($search); ?>" placeholder="Search by name, deanery or location...">
                    </div>
                    <button type="submit" class="btn btn-secondary">Filter List</button>
                </form>
            </div>

            <!-- Records Table -->
            <div class="card bg-card table-card">
                <div class="card-header">
                    <h3>Diocesan Parishes</h3>
                </div>
                <div class="card-body no-padding">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Parish Name</th>
                                <th>Deanery</th>
                                <th>Location</th>
                                <th>Priest in Charge</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($parishes)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No records found.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($parishes as $row): ?>
                                <tr>
                                    <td><strong><?php echo h($row['parish_name']); ?></strong></td>
                                    <td><span class="badge badge-outline"><?php echo h($row['deanery']); ?></span></td>
                                    <td><?php echo h($row['location']); ?></td>
                                    <td><?php echo h($row['priest_name'] ?: 'Not Assigned'); ?></td>
                                    <td><?php echo h($row['contact_number'] ?: '--'); ?></td>
                                    <td class="table-actions">
                                        <a href="edit_parish.php?id=<?php echo $row['parish_id']; ?>" class="action-btn edit" title="Edit Parish">
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
        .badge-outline { border: 1px solid var(--accent); color: var(--accent); padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .data-table td { font-size: 0.9rem; }
    </style>
</body>
</html>
