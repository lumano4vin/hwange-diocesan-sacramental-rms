<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parish Outstations & Rural Mass Centres Directory
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
    die("You must be assigned to a parish to view Outstations.");
}

$parish = db_fetch("SELECT * FROM parishes WHERE parish_id = ?", [$parish_id]);
$all_parishes = $is_admin ? db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name ASC") : [];

$search_q = trim($_GET['q'] ?? '');

$where = "WHERE parish_id = ?";
$params = [$parish_id];

if (!empty($search_q)) {
    $where .= " AND (outstation_name LIKE ? OR patron_saint LIKE ? OR catechist_name LIKE ? OR chairperson_name LIKE ?)";
    $like = "%$search_q%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$outstations = db_fetchAll("
    SELECT * FROM parish_outstations
    $where
    ORDER BY distance_km ASC, outstation_name ASC
", $params);

// Stats
$total_outstations = count($outstations);
$total_families = (int)(db_fetch("SELECT SUM(active_families_count) as s FROM parish_outstations WHERE parish_id = ?", [$parish_id])['s'] ?? 0);
$church_buildings = (int)(db_fetch("SELECT COUNT(*) as c FROM parish_outstations WHERE parish_id = ? AND has_church_building = 1", [$parish_id])['c'] ?? 0);
$avg_distance = (float)(db_fetch("SELECT AVG(distance_km) as a FROM parish_outstations WHERE parish_id = ? AND distance_km > 0", [$parish_id])['a'] ?? 0);

$header_title = h($parish['parish_name']) . " — Outstations";
$header_subtitle = "Rural Mass Centres & Outreach Stations";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($parish['parish_name']); ?> - Outstations - Hwange RMS</title>
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
        .outstation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 1.5rem;
        }
        .outstation-card {
            background: var(--card-bg);
            border-radius: 18px;
            padding: 1.5rem;
            border: 1px solid rgba(255,255,255,0.06);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, border-color 0.2s;
        }
        .outstation-card:hover {
            transform: translateY(-3px);
            border-color: rgba(56, 189, 248, 0.4);
        }
        .badge-chapel {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
            font-size: 0.68rem;
            padding: 2px 7px;
            border-radius: 6px;
            font-weight: 700;
        }
        .badge-openair {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
            font-size: 0.68rem;
            padding: 2px 7px;
            border-radius: 6px;
            font-weight: 700;
        }
        .contact-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #38bdf8;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.72rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .contact-btn:hover {
            background: rgba(56, 189, 248, 0.2);
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
            max-width: 600px;
            padding: 2rem;
            max-height: 90vh;
            overflow-y: auto;
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
                            <ion-icon name="location-outline" style="color: #38bdf8;"></ion-icon>
                            <span>Outstations & Mass Centres</span>
                        </h2>
                        <p style="color: var(--text-muted); font-size: 0.8rem; margin: 3px 0 0 0;">
                            Centres of worship, rural chapels, and outreach communities under <?php echo h($parish['parish_name']); ?>.
                        </p>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px;">
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

                        <button onclick="openOutstationModal()" class="btn btn-primary" style="font-weight: 700; font-size: 0.85rem; padding: 0.6rem 1.25rem;">
                            <ion-icon name="add-circle-outline"></ion-icon> + Add Outstation
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="stats-grid-4">
                    <div class="card-stat">
                        <div class="label">Total Outstations</div>
                        <div class="val" style="color: #38bdf8;"><?php echo $total_outstations; ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Centres of regular pastoral outreach</div>
                    </div>
                    <div class="card-stat">
                        <div class="label">Dedicated Church Buildings</div>
                        <div class="val" style="color: #10b981;"><?php echo $church_buildings; ?> / <?php echo $total_outstations; ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Permanent chapels vs. open-air/schools</div>
                    </div>
                    <div class="card-stat">
                        <div class="label">Active Families</div>
                        <div class="val" style="color: #fbbf24;"><?php echo number_format($total_families); ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Registered Catholic households</div>
                    </div>
                    <div class="card-stat">
                        <div class="label">Avg. Distance from Mission</div>
                        <div class="val" style="color: #a78bfa;"><?php echo number_format($avg_distance, 1); ?> km</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Pastoral travel radius</div>
                    </div>
                </div>

                <!-- Search Filter -->
                <div style="margin-bottom: 1.5rem;">
                    <form method="GET" style="display: flex; gap: 10px; max-width: 450px;">
                        <?php if ($is_admin): ?>
                            <input type="hidden" name="parish_id" value="<?php echo $parish_id; ?>">
                        <?php endif; ?>
                        <input type="text" name="q" value="<?php echo h($search_q); ?>" class="input-field" placeholder="Search by name, saint, catechist..." style="padding: 8px 12px; font-size: 0.82rem;">
                        <button type="submit" class="btn btn-secondary" style="padding: 8px 14px; font-size: 0.82rem;">Search</button>
                        <?php if (!empty($search_q)): ?>
                            <a href="outstations.php<?php echo $is_admin ? '?parish_id=' . $parish_id : ''; ?>" class="btn btn-secondary" style="padding: 8px 12px; font-size: 0.78rem; color: var(--text-muted);">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Outstations Grid -->
                <?php if (empty($outstations)): ?>
                    <div class="card-stat" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        <ion-icon name="map-outline" style="font-size: 3rem; opacity: 0.3; margin-bottom: 8px;"></ion-icon>
                        <h4 style="color: white; margin: 0 0 6px 0;">No outstations logged yet</h4>
                        <p style="font-size: 0.8rem; max-width: 450px; margin: 0 auto 1.5rem auto;">
                            Rural mission stations in Hwange Diocese coordinate pastoral ministry across multiple centres. Register your outstations to manage Mass schedules, catechists, and local Sunday collections.
                        </p>
                        <button onclick="openOutstationModal()" class="btn btn-primary" style="font-weight: 700;">+ Register First Outstation</button>
                    </div>
                <?php else: ?>
                    <div class="outstation-grid">
                        <?php foreach ($outstations as $ost): ?>
                            <div class="outstation-card">
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                        <div>
                                            <h3 style="font-family: 'Outfit'; color: #fff; margin: 0; font-size: 1.15rem;">
                                                <?php echo h($ost['outstation_name']); ?>
                                            </h3>
                                            <?php if (!empty($ost['patron_saint'])): ?>
                                                <div style="font-size: 0.75rem; color: #38bdf8; font-weight: 600;">
                                                    Patron: <?php echo h($ost['patron_saint']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($ost['has_church_building'])): ?>
                                            <span class="badge-chapel"><ion-icon name="home"></ion-icon> Chapel</span>
                                        <?php else: ?>
                                            <span class="badge-openair"><ion-icon name="leaf-outline"></ion-icon> Open-Air / School</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Distance & Families -->
                                    <div style="display: flex; gap: 14px; margin: 10px 0; padding: 8px 10px; background: rgba(255,255,255,0.02); border-radius: 8px; font-size: 0.75rem;">
                                        <div>
                                            <span style="color: var(--text-muted);">Distance: </span>
                                            <strong style="color: #fff;"><?php echo (float)$ost['distance_km'] > 0 ? (float)$ost['distance_km'] . ' km' : '—'; ?></strong>
                                        </div>
                                        <div>
                                            <span style="color: var(--text-muted);">Households: </span>
                                            <strong style="color: #fbbf24;"><?php echo (int)$ost['active_families_count']; ?></strong>
                                        </div>
                                    </div>

                                    <!-- Mass Schedule -->
                                    <div style="font-size: 0.75rem; margin-bottom: 8px;">
                                        <span style="color: var(--text-muted);">Mass Schedule: </span>
                                        <span style="color: #e2e8f0; font-weight: 600;"><?php echo h($ost['mass_schedule'] ?: 'As announced by Parish Priest'); ?></span>
                                    </div>

                                    <!-- Key Leaders -->
                                    <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 8px; margin-top: 8px; font-size: 0.75rem; display: flex; flex-direction: column; gap: 4px;">
                                        <?php if (!empty($ost['catechist_name'])): ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <span style="color: var(--text-muted);">Catechist: <strong><?php echo h($ost['catechist_name']); ?></strong></span>
                                                <?php if (!empty($ost['catechist_phone'])): ?>
                                                    <a href="tel:<?php echo h($ost['catechist_phone']); ?>" class="contact-btn">
                                                        <ion-icon name="call-outline"></ion-icon> Call
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($ost['chairperson_name'])): ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <span style="color: var(--text-muted);">Chairperson: <strong><?php echo h($ost['chairperson_name']); ?></strong></span>
                                                <?php if (!empty($ost['chairperson_phone'])): ?>
                                                    <a href="tel:<?php echo h($ost['chairperson_phone']); ?>" class="contact-btn">
                                                        <ion-icon name="call-outline"></ion-icon> Call
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($ost['notes'])): ?>
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 8px; font-style: italic;">
                                            <?php echo h($ost['notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Card Footer Actions -->
                                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 8px;">
                                    <button onclick='editOutstation(<?php echo json_encode($ost); ?>)' class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.72rem;">
                                        <ion-icon name="create-outline"></ion-icon> Edit
                                    </button>
                                    <form action="../actions/delete_outstation.php" method="POST" onsubmit="return confirm('Delete this outstation?');" style="display: inline;">
                                        <input type="hidden" name="outstation_id" value="<?php echo $ost['outstation_id']; ?>">
                                        <input type="hidden" name="return_to" value="dashboard/outstations.php?parish_id=<?php echo $parish_id; ?>">
                                        <button type="submit" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.72rem; color: #ef4444;">
                                            <ion-icon name="trash-outline"></ion-icon>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- Modal: Add / Edit Outstation -->
    <div id="outstationModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.75rem;">
                <h3 id="ostModalTitle" style="font-family: 'Outfit'; color: #38bdf8; margin: 0; font-size: 1.25rem;">Register Outstation</h3>
                <span onclick="closeOutstationModal()" style="font-size: 1.5rem; color: var(--text-muted); cursor: pointer;">&times;</span>
            </div>

            <form action="../actions/save_outstation.php" method="POST">
                <input type="hidden" name="outstation_id" id="ost_id" value="0">
                <input type="hidden" name="parish_id" value="<?php echo $parish_id; ?>">
                <input type="hidden" name="return_to" value="dashboard/outstations.php?parish_id=<?php echo $parish_id; ?>">

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Outstation / Mass Centre Name *</label>
                    <input type="text" name="outstation_name" id="ost_name" class="input-field" required placeholder="e.g. St. Peter - Dinde, Our Lady of Peace - Kasibo...">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Patron Saint / Dedication</label>
                        <input type="text" name="patron_saint" id="ost_saint" class="input-field" placeholder="e.g. St. Joseph, St. Theresa...">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Distance from Main Mission (km)</label>
                        <input type="number" step="0.1" name="distance_km" id="ost_distance" class="input-field" placeholder="e.g. 18.5">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Local Catechist Name</label>
                        <input type="text" name="catechist_name" id="ost_cat_name" class="input-field" placeholder="Full name">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Catechist Phone (Econet/NetOne)</label>
                        <input type="text" name="catechist_phone" id="ost_cat_phone" class="input-field" placeholder="077... / 071...">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Council Chairperson Name</label>
                        <input type="text" name="chairperson_name" id="ost_chair_name" class="input-field" placeholder="Full name">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Chairperson Phone</label>
                        <input type="text" name="chairperson_phone" id="ost_chair_phone" class="input-field" placeholder="077... / 071...">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Mass / Service Schedule</label>
                        <input type="text" name="mass_schedule" id="ost_schedule" class="input-field" placeholder="e.g. 1st & 3rd Sundays 09:00">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Catholic Households</label>
                        <input type="number" name="active_families_count" id="ost_families" class="input-field" placeholder="e.g. 45">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Church Structure Type</label>
                    <select name="has_church_building" id="ost_building" class="input-field">
                        <option value="1">Dedicated Church / Chapel Building</option>
                        <option value="0">Open-Air / Under Tree / School Classroom</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Pastoral Notes / Directions</label>
                    <textarea name="notes" id="ost_notes" class="input-field" rows="2" placeholder="e.g. River crossing impassable during heavy rains; borehole drilled 2024..."></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeOutstationModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 800; padding: 0.7rem 1.5rem;">Save Outstation</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openOutstationModal() {
            document.getElementById('ost_id').value = '0';
            document.getElementById('ostModalTitle').textContent = 'Register Outstation';
            document.getElementById('ost_name').value = '';
            document.getElementById('ost_saint').value = '';
            document.getElementById('ost_distance').value = '';
            document.getElementById('ost_cat_name').value = '';
            document.getElementById('ost_cat_phone').value = '';
            document.getElementById('ost_chair_name').value = '';
            document.getElementById('ost_chair_phone').value = '';
            document.getElementById('ost_schedule').value = '';
            document.getElementById('ost_families').value = '';
            document.getElementById('ost_building').value = '1';
            document.getElementById('ost_notes').value = '';

            document.getElementById('outstationModal').classList.add('active');
        }

        function closeOutstationModal() {
            document.getElementById('outstationModal').classList.remove('active');
        }

        function editOutstation(row) {
            document.getElementById('ost_id').value = row.outstation_id;
            document.getElementById('ostModalTitle').textContent = 'Edit Outstation: ' + row.outstation_name;
            document.getElementById('ost_name').value = row.outstation_name;
            document.getElementById('ost_saint').value = row.patron_saint || '';
            document.getElementById('ost_distance').value = row.distance_km || '';
            document.getElementById('ost_cat_name').value = row.catechist_name || '';
            document.getElementById('ost_cat_phone').value = row.catechist_phone || '';
            document.getElementById('ost_chair_name').value = row.chairperson_name || '';
            document.getElementById('ost_chair_phone').value = row.chairperson_phone || '';
            document.getElementById('ost_schedule').value = row.mass_schedule || '';
            document.getElementById('ost_families').value = row.active_families_count || '';
            document.getElementById('ost_building').value = row.has_church_building ? '1' : '0';
            document.getElementById('ost_notes').value = row.notes || '';

            document.getElementById('outstationModal').classList.add('active');
        }
    </script>
</body>
</html>
