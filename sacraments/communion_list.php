<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * First Holy Communion Records List - Modernized
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "First Holy Communion Registry";
$header_subtitle = "Viewing all canonical first communion records in the Diocese.";

// Search logic
$search = $_GET['search'] ?? '';
$params = [];
$where = "";

if ($search) {
    $clean_search = trim($search);
    $terms = array_filter(explode(' ', $clean_search));
    $term_conditions = [];
    foreach ($terms as $term) {
        $term = trim($term);
        if (empty($term)) continue;
        $term_conditions[] = "(p.first_name || ' ' || IFNULL(p.other_names, '') || ' ' || p.last_name) LIKE ?";
        $params[] = "%$term%";
    }
    if (!empty($term_conditions)) {
        $name_cond = "(" . implode(" AND ", $term_conditions) . ")";
        $where = " WHERE ($name_cond OR pa.parish_name LIKE ? OR c.minister LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
}

// Fetch records with parish details
$records = db_fetchAll("
    SELECT c.*, p.first_name, p.last_name, pa.parish_name 
    FROM first_holy_communions c 
    JOIN parishioners p ON c.person_id = p.person_id 
    JOIN parishes pa ON c.parish_id = pa.parish_id 
    $where
    ORDER BY c.date_of_communion DESC
", $params);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First Communion Registry - Hwange Diocesan RMS</title>
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
                
                <div class="card bg-card no-padding overflow-hidden" style="border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                    <div class="card-header" style="padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                        <div style="display: flex; align-items: center; gap: 2rem; flex: 1;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; white-space: nowrap;">Canonical Registry</h3>
                            
                            <!-- Registry Search Bar -->
                            <form method="GET" action="communion_list.php" style="flex: 1; max-width: 500px; position: relative; display: flex; gap: 10px;">
                                <div style="flex: 1; position: relative;">
                                    <ion-icon name="search-outline" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></ion-icon>
                                    <input type="text" name="search" placeholder="Search by name, minister, or parish..." value="<?php echo h($search); ?>" autocomplete="off" style="width: 100%; padding: 0.75rem 1rem 0.75rem 3rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.75rem; color: white; font-size: 0.85rem;">
                                </div>
                                <?php if ($search): ?>
                                    <a href="communion_list.php" class="btn btn-secondary" style="padding: 0 1rem; border-radius: 0.75rem; display: flex; align-items: center; font-size: 0.75rem;">Clear</a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="table-actions">
                            <a href="communion_add.php" class="btn btn-primary" style="background: #fbbf24; color: #000; font-weight: 800; display: flex; align-items: center; gap: 8px; border: none; box-shadow: 0 10px 20px rgba(251, 191, 36, 0.2);">
                                <ion-icon name="add-circle-outline"></ion-icon> New Record
                            </a>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.02); text-align: left;">
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Ref No.</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Communicant (Full Name)</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Parish / Mission</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Communion Date</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Minister</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($records)): ?>
                                    <tr>
                                        <td colspan="6" style="padding: 4rem; text-align: center; color: var(--text-muted);">
                                            <ion-icon name="restaurant-outline" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.2;"></ion-icon>
                                            <p>No communion records found in the registry.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($records as $row): ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.03); transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                            <td style="padding: 1.25rem 2rem; font-family: monospace; font-size: 0.85rem; color: var(--accent);"><?php echo h($row['entry_number'] ?: 'FHC-'.str_pad($row['communion_id'], 4, '0', STR_PAD_LEFT)); ?></td>
                                            <td style="padding: 1.25rem 2rem;">
                                                <div style="font-weight: 700; color: white;"><?php echo h($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                            </td>
                                            <td style="padding: 1.25rem 2rem; font-size: 0.9rem; color: var(--text-muted);"><?php echo h($row['parish_name']); ?></td>
                                            <td style="padding: 1.25rem 2rem; font-size: 0.9rem;"><?php echo date('d M Y', strtotime($row['date_of_communion'])); ?></td>
                                            <td style="padding: 1.25rem 2rem; font-size: 0.9rem; color: var(--text-muted);"><?php echo h($row['minister_name'] ?: '-'); ?></td>
                                            <td style="padding: 1.25rem 2rem;">
                                                <div style="display: flex; gap: 0.75rem;">
                                                    <a href="communion_certificate.php?id=<?php echo $row['communion_id']; ?>" target="_blank" title="Download Certificate" style="color: #fbbf24; font-size: 1.25rem;"><ion-icon name="print-outline"></ion-icon></a>
                                                    <a href="view_communion.php?id=<?php echo $row['communion_id']; ?>" title="View Full Details" style="color: white; font-size: 1.25rem;"><ion-icon name="eye-outline"></ion-icon></a>
                                                    <?php if (is_admin()): ?>
                                                        <a href="../actions/delete_record.php?type=communion&id=<?php echo $row['communion_id']; ?>" 
                                                           onclick="return confirm('WARNING: Permanent deletion of canonical records is logged and IRREVERSIBLE. Proceed?')" 
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

            <?php include '../includes/privacy_footer.php'; ?>
        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
