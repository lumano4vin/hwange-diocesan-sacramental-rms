<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parishioners Registry - Modernized
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Parishioners Registry";
$header_subtitle = "Canonical list of the faithful across the Diocese.";

// Search logic
$search = $_GET['search'] ?? '';

// 1. Mandatory Parish Filter (Isolation)
$where_parts = [];
$params = [];
$parish_filter_sql = get_parish_filter($params, 'p');
if (!empty($parish_filter_sql)) {
    $where_parts[] = ltrim($parish_filter_sql, ' AND ');
}

// 2. Smart Search Engine Upgrade (Multi-term & Fuzzy Wildcard)
if ($search) {
    $clean_search = trim($search);
    $terms = array_filter(explode(' ', $clean_search));
    $term_conditions = [];
    
    foreach ($terms as $term) {
        $term = trim($term);
        if (empty($term)) continue;
        
        // 1. Standard precise match across the concatenated full name
        $cond = "(first_name || ' ' || IFNULL(other_names, '') || ' ' || last_name) LIKE ?";
        $params[] = "%$term%";
        
        // 2. Fuzzy wildcard match for typos (only applied to words >= 4 chars to prevent overly broad results)
        if (strlen($term) >= 4) {
            $cond .= " OR (first_name || ' ' || IFNULL(other_names, '') || ' ' || last_name) LIKE ?";
            // Converts "Moyo" into "%M%o%y%o%"
            $fuzzy_pattern = "%" . implode("%", str_split($term)) . "%";
            $params[] = $fuzzy_pattern;
        }
        
        $term_conditions[] = "($cond)";
    }
    
    if (!empty($term_conditions)) {
        // All terms must match somewhere in the name (AND logic for multi-term, OR logic for exact vs fuzzy)
        $where_parts[] = "(" . implode(" AND ", $term_conditions) . ")";
    }
}

$where = !empty($where_parts) ? implode(" AND ", $where_parts) : "1=1";

// Fetch parishioners with parish details
$parishioners = db_fetchAll("
    SELECT p.*, pa.parish_name 
    FROM parishioners p 
    LEFT JOIN parishes pa ON p.current_parish_id = pa.parish_id 
    WHERE $where 
    ORDER BY p.last_name, p.first_name
", $params);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parishioners Registry - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Header -->
            <?php include 'includes/header.php'; ?>
            
            <div class="content-body" style="padding: 2rem 0;">
                
                <!-- Search and Stats Bar -->
                <div class="stats-search-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="card bg-card" style="padding: 1.5rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05);">
                        <form method="GET" action="parishioners.php" style="display: flex; gap: 1rem;">
                            <div style="flex: 1; position: relative;">
                                <ion-icon name="search-outline" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></ion-icon>
                                <input type="text" name="search" placeholder="Search by name, baptismal name..." value="<?php echo h($search); ?>" style="width: 100%; padding: 1rem 1rem 1rem 3rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; color: white;">
                            </div>
                            <button type="submit" class="btn btn-primary" style="padding: 0 2rem; border-radius: 1rem; font-weight: 800;">Search</button>
                            <?php if ($search): ?>
                                <a href="parishioners.php" class="btn" style="display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); color: white; padding: 0 1.5rem; border-radius: 1rem; text-decoration: none;">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="card bg-card" style="padding: 1.5rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: space-around;">
                        <div style="text-align: center;">
                            <span style="display: block; font-size: 1.5rem; font-weight: 900; color: var(--accent);"><?php echo count($parishioners); ?></span>
                            <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Found</span>
                        </div>
                        <div style="width: 1px; height: 30px; background: rgba(255,255,255,0.1);"></div>
                        <?php if (can_edit()): ?>
                        <a href="parishioners/add.php" style="text-align: center; text-decoration: none;">
                            <ion-icon name="person-add-outline" style="font-size: 1.5rem; color: #10b981;"></ion-icon>
                            <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">Register</span>
                        </a>
                        <?php else: ?>
                        <div style="text-align: center; opacity: 0.3;">
                            <ion-icon name="lock-closed-outline" style="font-size: 1.5rem; color: var(--text-muted);"></ion-icon>
                            <span style="display: block; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">Read Only</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Data Table -->
                <div class="card bg-card no-padding overflow-hidden" style="border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);">
                    <div class="table-responsive">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.02); text-align: left;">
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Faithful Name</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Parish / Mission</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Gender</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Date of Birth</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Status</th>
                                    <th style="padding: 1.25rem 2rem; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($parishioners)): ?>
                                    <tr>
                                        <td colspan="6" style="padding: 4rem; text-align: center; color: var(--text-muted);">
                                            <ion-icon name="people-outline" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.2;"></ion-icon>
                                            <p>No parishioners found in the registry.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($parishioners as $p): ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.03); transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                            <td style="padding: 1.25rem 2rem;">
                                                <div style="font-weight: 700; color: white;"><?php echo h($p['last_name'] . ', ' . $p['first_name']); ?></div>
                                                <?php if($p['other_names']): ?>
                                                    <div style="font-size: 0.75rem; color: var(--accent);"><?php echo h($p['other_names']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 1.25rem 2rem; font-size: 0.9rem; color: var(--text-muted);"><?php echo h($p['parish_name'] ?? 'Diocesan Registry'); ?></td>
                                            <td style="padding: 1.25rem 2rem; font-size: 0.9rem;"><?php echo h($p['gender']); ?></td>
                                            <td style="padding: 1.25rem 2rem; font-size: 0.9rem;"><?php echo date('d M Y', strtotime($p['dob'])); ?></td>
                                            <td style="padding: 1.25rem 2rem;">
                                                <?php 
                                                    $display_status = $p['status'] == 'Moved' ? 'Transferred Out' : ($p['status'] ?: 'Active');
                                                    $bg = 'rgba(16, 185, 129, 0.1)'; $color = '#10b981';
                                                    if ($display_status == 'Transferred In') { $bg = 'rgba(56, 189, 248, 0.1)'; $color = '#38bdf8'; $display_status = 'Immigrant'; }
                                                    elseif ($display_status == 'Transferred Out') { $bg = 'rgba(245, 158, 11, 0.1)'; $color = '#f59e0b'; $display_status = 'Migrant'; }
                                                    elseif ($display_status == 'Deceased') { $bg = 'rgba(148, 163, 184, 0.1)'; $color = '#94a3b8'; }
                                                ?>
                                                <span class="status-badge" style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;"><?php echo h($display_status); ?></span>
                                            </td>
                                            <td style="padding: 1.25rem 2rem;">
                                                <div style="display: flex; gap: 0.75rem;">
                                                    <a href="parishioners/profile.php?id=<?php echo $p['person_id']; ?>" title="View Faithful Profile" style="color: var(--accent); font-size: 1.25rem;"><ion-icon name="person-circle-outline"></ion-icon></a>
                                                    <?php if (can_edit()): ?>
                                                        <a href="parishioners/edit.php?id=<?php echo $p['person_id']; ?>" title="Edit Record" style="color: white; font-size: 1.25rem;"><ion-icon name="create-outline"></ion-icon></a>
                                                    <?php endif; ?>
                                                    <?php if (is_admin()): ?>
                                                        <a href="actions/delete_record.php?type=parishioner&id=<?php echo $p['person_id']; ?>" 
                                                           onclick="return confirm('CRITICAL WARNING: Deleting a parishioner will NOT remove their sacramental records but will break canonical links. Only proceed if this is a duplicate entry. Proceed?')" 
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

                <?php include 'includes/privacy_footer.php'; ?>

            </div>

        </main>
    </div>

    <script src="assets/js/main.js?v=1.6.2"></script>
</body>
</html>
