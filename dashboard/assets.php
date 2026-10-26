<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parish & Mission Temporal Goods & Asset Register (Canon 1283)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user_role = strtolower($_SESSION['role'] ?? '');
$is_admin = ($user_role === 'admin' || $user_role === 'chancellor');

$parish_id = (int)($_SESSION['parish_id'] ?? 0);
if ($is_admin) {
    if (!empty($_GET['parish_id'])) {
        $parish_id = (int)$_GET['parish_id'];
    } elseif ($parish_id <= 0) {
        $first_parish = db_fetch("SELECT parish_id FROM parishes ORDER BY parish_name ASC LIMIT 1");
        $parish_id = (int)($first_parish['parish_id'] ?? 1);
    }
}

if ($parish_id <= 0) {
    die("You must be assigned to a parish to view the Asset Register.");
}

$parish = db_fetch("SELECT * FROM parishes WHERE parish_id = ?", [$parish_id]);
$all_parishes = $is_admin ? db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC") : [];

$filter_category = trim($_GET['category'] ?? 'all');
$filter_status = trim($_GET['status'] ?? 'all');
$filter_sacred = trim($_GET['sacred'] ?? 'all');
$search_q = trim($_GET['q'] ?? '');

// Build query
$where = "WHERE a.parish_id = ?";
$params = [$parish_id];

if ($filter_category !== 'all' && !empty($filter_category)) {
    $where .= " AND a.category = ?";
    $params[] = $filter_category;
}

if ($filter_status !== 'all' && !empty($filter_status)) {
    $where .= " AND a.current_status = ?";
    $params[] = $filter_status;
}

if ($filter_sacred === '1') {
    $where .= " AND a.is_sacred = 1";
} elseif ($filter_sacred === '0') {
    $where .= " AND a.is_sacred = 0";
}

if (!empty($search_q)) {
    $where .= " AND (a.item_name LIKE ? OR a.serial_or_reg_number LIKE ? OR a.location_stored LIKE ? OR a.description LIKE ?)";
    $like = "%$search_q%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$assets = db_fetchAll("
    SELECT a.*, u.full_name as recorded_by_name
    FROM parish_assets a
    LEFT JOIN users u ON a.recorded_by = u.user_id
    $where
    ORDER BY a.is_sacred DESC, a.category ASC, a.item_name ASC
", $params);

// Calculate KPI Stats for this Parish
$total_items_count = (int)(db_fetch("SELECT SUM(quantity) as s FROM parish_assets WHERE parish_id = ?", [$parish_id])['s'] ?? 0);
$total_value_usd = (float)(db_fetch("SELECT SUM(estimated_value * quantity) as s FROM parish_assets WHERE parish_id = ?", [$parish_id])['s'] ?? 0);
$sacred_items_count = (int)(db_fetch("SELECT COUNT(*) as c FROM parish_assets WHERE parish_id = ? AND is_sacred = 1", [$parish_id])['c'] ?? 0);
$infrastructure_count = (int)(db_fetch("SELECT COUNT(*) as c FROM parish_assets WHERE parish_id = ? AND category IN ('Power & Solar Utilities', 'Vehicles & Machinery')", [$parish_id])['c'] ?? 0);

$header_title = h($parish['parish_name']) . " — Asset Register";
$header_subtitle = "Canonical Inventory of Temporal Goods (Canon 1283 §2)";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($parish['parish_name']); ?> - Asset Register - Hwange RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=1.1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .stats-grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .card-stat {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            border: 1px solid rgba(255,255,255,0.06);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card-stat .label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            font-weight: 700;
        }
        .card-stat .val {
            font-size: 1.65rem;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            margin: 6px 0 2px 0;
        }
        .filter-panel {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.25rem;
            border: 1px solid rgba(255,255,255,0.06);
            margin-bottom: 1.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
        }
        .table-card {
            background: var(--card-bg);
            border-radius: 18px;
            padding: 1.5rem;
            border: 1px solid rgba(255,255,255,0.06);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .asset-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.85rem;
        }
        .asset-table th {
            text-align: left;
            padding: 12px 14px;
            color: var(--text-muted);
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .asset-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            vertical-align: middle;
        }
        .asset-table tr:hover td {
            background: rgba(255,255,255,0.02);
        }
        .badge-status {
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-block;
        }
        .status-excellent { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-good { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .status-fair { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
        .status-repair { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        .badge-sacred {
            background: rgba(251, 191, 36, 0.15);
            border: 1px solid rgba(251, 191, 36, 0.4);
            color: #fbbf24;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(5px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            width: 90%;
            max-width: 620px;
            padding: 2rem;
            max-height: 90vh;
            overflow-y: auto;
        }
        @media print {
            .no-print, .sidebar, .header-actions, .filter-panel, .btn { display: none !important; }
            body, .main-content { background: white !important; color: black !important; padding: 0 !important; }
            .card-stat, .table-card { border: 1px solid #ccc !important; box-shadow: none !important; background: white !important; color: black !important; }
            .asset-table th, .asset-table td { color: black !important; border-bottom: 1px solid #ddd !important; }
        }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="container-fluid" style="padding: 1.5rem 0;">
                
                <!-- Top Actions Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 style="font-family: 'Outfit'; font-weight: 800; margin: 0; font-size: 1.5rem; display: flex; align-items: center; gap: 8px;">
                            <ion-icon name="cube-outline" style="color: var(--accent);"></ion-icon>
                            <span>Temporal Goods & Property Register</span>
                        </h2>
                        <p style="color: var(--text-muted); font-size: 0.8rem; margin: 3px 0 0 0;">
                            Official diocesan asset inventory prescribed under Canon 1283 §2.
                        </p>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px;" class="no-print">
                        <?php if ($is_admin && !empty($all_parishes)): ?>
                            <form method="GET" style="display: flex; align-items: center; gap: 6px;">
                                <select name="parish_id" class="input-field" onchange="this.form.submit()" style="padding: 6px 12px; font-size: 0.82rem; font-weight: 700;">
                                    <?php foreach ($all_parishes as $p_item): ?>
                                        <option value="<?php echo $p_item['parish_id']; ?>" <?php echo $p_item['parish_id'] == $parish_id ? 'selected' : ''; ?>>
                                            <?php echo h($p_item['parish_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        <?php endif; ?>

                        <button onclick="window.print()" class="btn btn-secondary" style="font-size: 0.82rem; padding: 0.6rem 1rem;">
                            <ion-icon name="print-outline"></ion-icon> Print Inventory
                        </button>

                        <button onclick="openAssetModal()" class="btn btn-primary" style="font-weight: 700; font-size: 0.85rem; padding: 0.6rem 1.25rem;">
                            <ion-icon name="add-circle-outline"></ion-icon> + Add Asset
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="stats-grid-4">
                    <div class="card-stat">
                        <div class="label">Total Tracked Units</div>
                        <div class="val" style="color: #38bdf8;"><?php echo number_format($total_items_count); ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Across all physical categories</div>
                    </div>
                    <div class="card-stat">
                        <div class="label">Estimated Total Value</div>
                        <div class="val" style="color: #10b981;">$<?php echo number_format($total_value_usd, 2); ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Declared parish replacement value</div>
                    </div>
                    <div class="card-stat">
                        <div class="label">Sacred Liturgical Items</div>
                        <div class="val" style="color: #fbbf24;"><?php echo number_format($sacred_items_count); ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Vessels, vestments & tabernacles</div>
                    </div>
                    <div class="card-stat">
                        <div class="label">Infrastructure & Vehicles</div>
                        <div class="val" style="color: #a78bfa;"><?php echo number_format($infrastructure_count); ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Solar, boreholes, generators, transport</div>
                    </div>
                </div>

                <!-- Filters & Search Bar -->
                <div class="filter-panel no-print">
                    <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; width: 100%;">
                        <?php if ($is_admin): ?>
                            <input type="hidden" name="parish_id" value="<?php echo $parish_id; ?>">
                        <?php endif; ?>

                        <div style="flex: 1; min-width: 220px;">
                            <input type="text" name="q" value="<?php echo h($search_q); ?>" class="input-field" placeholder="Search asset name, serial #, location..." style="padding: 7px 12px; font-size: 0.82rem;">
                        </div>

                        <div>
                            <select name="category" class="input-field" style="padding: 7px 10px; font-size: 0.82rem;">
                                <option value="all">All Categories</option>
                                <option value="Sacred Vessels & Vestments" <?php echo $filter_category === 'Sacred Vessels & Vestments' ? 'selected' : ''; ?>>Sacred Vessels & Vestments</option>
                                <option value="Church Land & Buildings" <?php echo $filter_category === 'Church Land & Buildings' ? 'selected' : ''; ?>>Church Land & Buildings</option>
                                <option value="Power & Solar Utilities" <?php echo $filter_category === 'Power & Solar Utilities' ? 'selected' : ''; ?>>Power & Solar Utilities</option>
                                <option value="Vehicles & Machinery" <?php echo $filter_category === 'Vehicles & Machinery' ? 'selected' : ''; ?>>Vehicles & Machinery</option>
                                <option value="Sound & Office Equipment" <?php echo $filter_category === 'Sound & Office Equipment' ? 'selected' : ''; ?>>Sound & Office Equipment</option>
                                <option value="Furniture & Equipment" <?php echo $filter_category === 'Furniture & Equipment' ? 'selected' : ''; ?>>Furniture & Equipment</option>
                            </select>
                        </div>

                        <div>
                            <select name="status" class="input-field" style="padding: 7px 10px; font-size: 0.82rem;">
                                <option value="all">All Conditions</option>
                                <option value="Excellent" <?php echo $filter_status === 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                                <option value="Good" <?php echo $filter_status === 'Good' ? 'selected' : ''; ?>>Good</option>
                                <option value="Fair" <?php echo $filter_status === 'Fair' ? 'selected' : ''; ?>>Fair</option>
                                <option value="Needs Repair" <?php echo $filter_status === 'Needs Repair' ? 'selected' : ''; ?>>Needs Repair</option>
                                <option value="Decommissioned" <?php echo $filter_status === 'Decommissioned' ? 'selected' : ''; ?>>Decommissioned</option>
                            </select>
                        </div>

                        <div>
                            <select name="sacred" class="input-field" style="padding: 7px 10px; font-size: 0.82rem;">
                                <option value="all">Sacred & Temporal</option>
                                <option value="1" <?php echo $filter_sacred === '1' ? 'selected' : ''; ?>>Sacred Only</option>
                                <option value="0" <?php echo $filter_sacred === '0' ? 'selected' : ''; ?>>Temporal / Utilities Only</option>
                            </select>
                        </div>

                        <div>
                            <button type="submit" class="btn btn-secondary" style="padding: 7px 14px; font-size: 0.82rem;">Filter</button>
                            <?php if ($filter_category !== 'all' || $filter_status !== 'all' || $filter_sacred !== 'all' || !empty($search_q)): ?>
                                <a href="assets.php<?php echo $is_admin ? '?parish_id=' . $parish_id : ''; ?>" class="btn btn-secondary" style="padding: 7px 12px; font-size: 0.78rem; color: var(--text-muted);">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Asset Table Card -->
                <div class="table-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 0.75rem;">
                        <div>
                            <h3 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1.15rem;">Asset Inventory List</h3>
                            <p style="font-size: 0.72rem; color: var(--text-muted); margin: 2px 0 0 0;">
                                Showing <?php echo count($assets); ?> items for <?php echo h($parish['parish_name']); ?>.
                            </p>
                        </div>
                    </div>

                    <?php if (empty($assets)): ?>
                        <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                            <ion-icon name="cube-outline" style="font-size: 3rem; opacity: 0.3; margin-bottom: 8px;"></ion-icon>
                            <h4 style="color: white; margin: 0 0 6px 0;">No assets registered yet</h4>
                            <p style="font-size: 0.8rem; max-width: 450px; margin: 0 auto 1.5rem auto;">
                                Maintain institutional memory and canonical accountability by logging church land, sacred vessels, vehicles, solar systems, and mission equipment.
                            </p>
                            <button onclick="openAssetModal()" class="btn btn-primary" style="font-weight: 700;">+ Record First Asset</button>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="asset-table">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Serial / Reg #</th>
                                        <th>Location / Storage</th>
                                        <th>Qty</th>
                                        <th>Condition</th>
                                        <th>Est. Value</th>
                                        <th>Photo</th>
                                        <th class="no-print">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assets as $a): ?>
                                        <?php 
                                            $cond_class = 'status-good';
                                            $st_low = strtolower($a['current_status'] ?? '');
                                            if ($st_low === 'excellent') $cond_class = 'status-excellent';
                                            elseif ($st_low === 'fair') $cond_class = 'status-fair';
                                            elseif (strpos($st_low, 'repair') !== false || strpos($st_low, 'decommission') !== false) $cond_class = 'status-repair';
                                        ?>
                                        <tr>
                                            <td style="font-weight: 700; color: #fff;">
                                                <?php echo h($a['item_name']); ?>
                                                <?php if (!empty($a['is_sacred'])): ?>
                                                    <span class="badge-sacred"><ion-icon name="sparkles"></ion-icon> Sacred</span>
                                                <?php endif; ?>
                                                <?php if (!empty($a['description'])): ?>
                                                    <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: normal; margin-top: 2px;">
                                                        <?php echo h(mb_strimwidth($a['description'], 0, 50, '...')); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-size: 0.78rem; color: #cbd5e1;">
                                                <?php echo h($a['category']); ?>
                                            </td>
                                            <td style="font-family: monospace; font-size: 0.75rem; color: var(--accent);">
                                                <?php echo h($a['serial_or_reg_number'] ?: '—'); ?>
                                            </td>
                                            <td style="font-size: 0.78rem; color: var(--text-muted);">
                                                <?php echo h($a['location_stored'] ?: 'Main Mission'); ?>
                                            </td>
                                            <td style="font-weight: 700;">
                                                <?php echo (int)$a['quantity']; ?>
                                            </td>
                                            <td>
                                                <span class="badge-status <?php echo $cond_class; ?>">
                                                    <?php echo h($a['current_status']); ?>
                                                </span>
                                            </td>
                                            <td style="font-weight: 800; color: #10b981; white-space: nowrap;">
                                                <?php if ((float)$a['estimated_value'] > 0): ?>
                                                    $<?php echo number_format((float)$a['estimated_value'], 2); ?>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted); font-weight: normal;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($a['photo_path'])): ?>
                                                    <a href="../<?php echo h($a['photo_path']); ?>" target="_blank" style="color: #38bdf8; display: inline-flex; align-items: center; gap: 3px; font-size: 0.72rem;">
                                                        <ion-icon name="image-outline"></ion-icon> Photo
                                                    </a>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted); font-size: 0.7rem;">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="no-print" style="white-space: nowrap;">
                                                <div style="display: inline-flex; gap: 6px;">
                                                    <button onclick='editAsset(<?php echo json_encode($a); ?>)' class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.7rem;" title="Edit Asset">
                                                        <ion-icon name="create-outline"></ion-icon>
                                                    </button>
                                                    <form action="../actions/delete_asset.php" method="POST" onsubmit="return confirm('Remove this asset from parish inventory?');" style="display: inline;">
                                                        <input type="hidden" name="asset_id" value="<?php echo $a['id']; ?>">
                                                        <input type="hidden" name="return_to" value="dashboard/assets.php?parish_id=<?php echo $parish_id; ?>">
                                                        <button type="submit" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.7rem; color: #ef4444;" title="Delete Asset">
                                                            <ion-icon name="trash-outline"></ion-icon>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <!-- Modal: Add / Edit Asset -->
    <div id="assetModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.75rem;">
                <h3 id="assetModalTitle" style="font-family: 'Outfit'; color: var(--accent); margin: 0; font-size: 1.25rem;">Record Parish Asset</h3>
                <span onclick="closeAssetModal()" style="font-size: 1.5rem; color: var(--text-muted); cursor: pointer;">&times;</span>
            </div>

            <form action="../actions/save_asset.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="asset_id" id="ast_id" value="0">
                <input type="hidden" name="parish_id" value="<?php echo $parish_id; ?>">
                <input type="hidden" name="return_to" value="dashboard/assets.php?parish_id=<?php echo $parish_id; ?>">

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Item Name *</label>
                    <input type="text" name="item_name" id="ast_item_name" class="input-field" required placeholder="e.g. Toyota Land Cruiser Pickup, Solar 5kVA Inverter, Gold Ciborium...">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Asset Category *</label>
                        <select name="category" id="ast_category" class="input-field" required>
                            <option value="Sacred Vessels & Vestments">Sacred Vessels & Vestments</option>
                            <option value="Church Land & Buildings">Church Land & Buildings</option>
                            <option value="Power & Solar Utilities">Power & Solar Utilities (Borehole/Solar)</option>
                            <option value="Vehicles & Machinery">Vehicles & Machinery</option>
                            <option value="Sound & Office Equipment">Sound & Office Equipment</option>
                            <option value="Furniture & Equipment">Furniture & Equipment</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Serial / Registration / Engine #</label>
                        <input type="text" name="serial_or_reg_number" id="ast_serial" class="input-field" placeholder="e.g. AFG-1029 or S/N 84920">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Location / Storage Place</label>
                        <input type="text" name="location_stored" id="ast_location" class="input-field" placeholder="e.g. Main Church Sacristy, St. Peter Outstation...">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Quantity *</label>
                        <input type="number" name="quantity" id="ast_quantity" class="input-field" value="1" min="1" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Estimated Unit Value ($ USD)</label>
                        <input type="number" step="0.01" name="estimated_value" id="ast_value" class="input-field" placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Current Condition *</label>
                        <select name="current_status" id="ast_status" class="input-field" required>
                            <option value="Excellent">Excellent</option>
                            <option value="Good" selected>Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Needs Repair">Needs Repair</option>
                            <option value="Decommissioned">Decommissioned</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Acquisition Date</label>
                        <input type="date" name="acquisition_date" id="ast_date" class="input-field">
                    </div>

                    <div class="form-group" style="display: flex; flex-direction: column; justify-content: center;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 15px;">
                            <input type="checkbox" name="is_sacred" id="ast_is_sacred" value="1" style="width: 18px; height: 18px;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: #fbbf24;">Is Sacred Liturgical Item (Canon 1171)</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Photo / Documentation (Optional)</label>
                    <input type="file" name="asset_photo" id="ast_photo" class="input-field" accept="image/*" style="padding: 6px;">
                    <div id="ast_photo_prev" style="font-size: 0.72rem; margin-top: 3px; display: none;"></div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Description / Notes</label>
                    <textarea name="description" id="ast_desc" class="input-field" rows="2" placeholder="e.g. Donated by Catholic Women's League, insured under diocesan policy..."></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeAssetModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 800; padding: 0.7rem 1.5rem;">Save Asset</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAssetModal() {
            document.getElementById('ast_id').value = '0';
            document.getElementById('assetModalTitle').textContent = 'Record Parish Asset';
            document.getElementById('ast_item_name').value = '';
            document.getElementById('ast_serial').value = '';
            document.getElementById('ast_location').value = '';
            document.getElementById('ast_quantity').value = '1';
            document.getElementById('ast_value').value = '';
            document.getElementById('ast_date').value = '';
            document.getElementById('ast_desc').value = '';
            document.getElementById('ast_status').value = 'Good';
            document.getElementById('ast_category').value = 'Furniture & Equipment';
            document.getElementById('ast_is_sacred').checked = false;
            document.getElementById('ast_photo').value = '';
            document.getElementById('ast_photo_prev').style.display = 'none';

            document.getElementById('assetModal').classList.add('active');
        }

        function closeAssetModal() {
            document.getElementById('assetModal').classList.remove('active');
        }

        function editAsset(row) {
            document.getElementById('ast_id').value = row.id;
            document.getElementById('assetModalTitle').textContent = 'Edit Asset: ' + row.item_name;
            document.getElementById('ast_item_name').value = row.item_name;
            document.getElementById('ast_category').value = row.category || 'Furniture & Equipment';
            document.getElementById('ast_serial').value = row.serial_or_reg_number || '';
            document.getElementById('ast_location').value = row.location_stored || '';
            document.getElementById('ast_quantity').value = row.quantity || '1';
            document.getElementById('ast_value').value = row.estimated_value || '';
            document.getElementById('ast_status').value = row.current_status || 'Good';
            document.getElementById('ast_date').value = row.acquisition_date || '';
            document.getElementById('ast_is_sacred').checked = (row.is_sacred == 1);
            document.getElementById('ast_desc').value = row.description || '';
            document.getElementById('ast_photo').value = '';

            const prevBox = document.getElementById('ast_photo_prev');
            if (row.photo_path) {
                prevBox.style.display = 'block';
                prevBox.innerHTML = '<span style="color: var(--text-muted);">Current Photo: </span><a href="../' + encodeURI(row.photo_path) + '" target="_blank" style="color: #38bdf8; text-decoration: underline;">View Image</a>';
            } else {
                prevBox.style.display = 'none';
            }

            document.getElementById('assetModal').classList.add('active');
        }
    </script>
</body>
</html>
