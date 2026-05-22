<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Marriage Records List - Modernized
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Matrimonial Registry";
$header_subtitle = "Viewing all canonical marriage records in the Diocese.";

// Search logic
$search = $_GET['search'] ?? '';
$params = [];
$where = "";

if ($search) {
    $clean_search = trim($search);
    $terms = array_filter(explode(' ', $clean_search));
    $groom_term_conds = [];
    $bride_term_conds = [];
    
    foreach ($terms as $term) {
        $term = trim($term);
        if (empty($term)) continue;
        
        $groom_term_conds[] = "(p1.first_name || ' ' || IFNULL(p1.other_names, '') || ' ' || p1.last_name) LIKE ?";
        $bride_term_conds[] = "(p2.first_name || ' ' || IFNULL(p2.other_names, '') || ' ' || p2.last_name) LIKE ?";
        
        $params[] = "%$term%";
    }
    
    if (!empty($groom_term_conds)) {
        $groom_match = "(" . implode(" AND ", $groom_term_conds) . ")";
        $bride_match = "(" . implode(" AND ", $bride_term_conds) . ")";
        
        // Double up parameters because we have groom_match and bride_match
        foreach ($terms as $term) {
            $params[] = "%$term%";
        }
        
        $where = " WHERE ($groom_match OR $bride_match OR pa.parish_name LIKE ?)";
        $params[] = "%$search%";
    }
}

// Fetch records with parish details and groom/bride baptism links
$records = db_fetchAll("
    SELECT m.*, p1.first_name as g_first, p1.last_name as g_last, 
           p2.first_name as b_first, p2.last_name as b_last, pa.parish_name,
           b1.parish_id as g_bap_parish_id, b2.parish_id as b_bap_parish_id
    FROM marriages m 
    JOIN parishioners p1 ON m.groom_person_id = p1.person_id 
    JOIN parishioners p2 ON m.bride_person_id = p2.person_id 
    JOIN parishes pa ON m.parish_id = pa.parish_id 
    LEFT JOIN baptisms b1 ON m.groom_person_id = b1.person_id
    LEFT JOIN baptisms b2 ON m.bride_person_id = b2.person_id
    $where
    ORDER BY m.date_of_marriage DESC
", $params);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marriage Registry - Hwange Diocesan RMS</title>
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
                            <form method="GET" action="marriage_list.php" style="flex: 1; max-width: 500px; position: relative; display: flex; gap: 10px;">
                                <div style="flex: 1; position: relative;">
                                    <ion-icon name="search-outline" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></ion-icon>
                                    <input type="text" name="search" placeholder="Search groom, bride, or parish..." value="<?php echo h($search); ?>" autocomplete="off" style="width: 100%; padding: 0.75rem 1rem 0.75rem 3rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.75rem; color: white; font-size: 0.85rem;">
                                </div>
                                <?php if ($search): ?>
                                    <a href="marriage_list.php" class="btn btn-secondary" style="padding: 0 1rem; border-radius: 0.75rem; display: flex; align-items: center; font-size: 0.75rem;">Clear</a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="table-actions">
                            <a href="marriage_add.php" class="btn btn-primary" style="background: #f472b6; color: white; font-weight: 800; display: flex; align-items: center; gap: 8px; border: none; box-shadow: 0 10px 20px rgba(244, 114, 182, 0.2);">
                                <ion-icon name="add-circle-outline"></ion-icon> New Record
                            </a>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.02); text-align: left;">
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Ref No.</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Groom & Bride</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Parish / Mission</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Marriage Date</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Baptismal Notice</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($records)): ?>
                                    <tr>
                                        <td colspan="6" style="padding: 4rem; text-align: center; color: var(--text-muted);">
                                            <ion-icon name="heart-outline" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.2;"></ion-icon>
                                            <p>No marriage records found in the registry.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($records as $row): ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.03); transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                            <td style="padding: 1.25rem 2rem; font-family: monospace; font-size: 0.85rem; color: var(--accent);"><?php echo h($row['entry_number'] ?: 'M-'.str_pad($row['marriage_id'], 4, '0', STR_PAD_LEFT)); ?></td>
                                            <td style="padding: 1.25rem 2rem;">
                                                <div style="font-weight: 700; color: white;"><?php echo h($row['g_first'] . ' ' . $row['g_last']); ?></div>
                                                <div style="font-size: 0.8rem; color: var(--accent); margin: 2px 0;">&</div>
                                                <div style="font-weight: 700; color: white;"><?php echo h($row['b_first'] . ' ' . $row['b_last']); ?></div>
                                            </td>
                                            <td style="padding: 1.25rem 2rem; font-size: 0.9rem; color: var(--text-muted);"><?php echo h($row['parish_name']); ?></td>
                                            <td style="padding: 1.25rem 2rem; font-size: 0.9rem;"><?php echo date('d M Y', strtotime($row['date_of_marriage'])); ?></td>
                                            <td style="padding: 1.25rem 2rem;">
                                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                                    <!-- Groom Notice Status -->
                                                    <?php if (!empty($row['g_bap_parish_id']) && $row['g_bap_parish_id'] == $row['parish_id']): ?>
                                                        <span class="status-badge verified" style="font-size: 0.65rem; padding: 4px 8px; background: rgba(16, 185, 129, 0.15) !important; color: #10b981 !important; border: 1px solid rgba(16, 185, 129, 0.3) !important; font-weight: 700; text-transform: uppercase; text-align: center;">
                                                            Groom: LOCAL (NOTED)
                                                        </span>
                                                    <?php else: ?>
                                                        <a href="marriage_notification_print.php?id=<?php echo $row['marriage_id']; ?>&party=groom" class="status-badge <?php echo $row['baptism_notified_groom'] ? 'verified' : 'draft'; ?>" style="font-size: 0.65rem; padding: 4px 8px; text-decoration: none; font-weight: 700; text-align: center; display: block;">
                                                            Groom: <?php echo $row['baptism_notified_groom'] ? 'NOTIFIED' : 'PENDING'; ?>
                                                        </a>
                                                    <?php endif; ?>

                                                    <!-- Bride Notice Status -->
                                                    <?php if (!empty($row['b_bap_parish_id']) && $row['b_bap_parish_id'] == $row['parish_id']): ?>
                                                        <span class="status-badge verified" style="font-size: 0.65rem; padding: 4px 8px; background: rgba(16, 185, 129, 0.15) !important; color: #10b981 !important; border: 1px solid rgba(16, 185, 129, 0.3) !important; font-weight: 700; text-transform: uppercase; text-align: center;">
                                                            Bride: LOCAL (NOTED)
                                                        </span>
                                                    <?php else: ?>
                                                        <a href="marriage_notification_print.php?id=<?php echo $row['marriage_id']; ?>&party=bride" class="status-badge <?php echo $row['baptism_notified_bride'] ? 'verified' : 'draft'; ?>" style="font-size: 0.65rem; padding: 4px 8px; text-decoration: none; font-weight: 700; text-align: center; display: block;">
                                                            Bride: <?php echo $row['baptism_notified_bride'] ? 'NOTIFIED' : 'PENDING'; ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td style="padding: 1.25rem 2rem;">
                                                <div style="display: flex; gap: 0.75rem;">
                                                    <a href="marriage_certificate.php?id=<?php echo $row['marriage_id']; ?>" target="_blank" title="Download Certificate" style="color: #f472b6; font-size: 1.25rem;"><ion-icon name="print-outline"></ion-icon></a>
                                                    <a href="marriage_edit.php?id=<?php echo $row['marriage_id']; ?>" title="Edit Record" style="color: var(--accent); font-size: 1.25rem;"><ion-icon name="create-outline"></ion-icon></a>
                                                    <a href="view_marriage.php?id=<?php echo $row['marriage_id']; ?>" title="View Full Details" style="color: white; font-size: 1.25rem;"><ion-icon name="eye-outline"></ion-icon></a>
                                                    <?php if (is_admin()): ?>
                                                        <a href="../actions/delete_record.php?type=marriage&id=<?php echo $row['marriage_id']; ?>" 
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
