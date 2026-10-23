<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Clergy Management & Assignment Tracking
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page - Admin only
require_role('admin');

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch all clergy (users who are not just 'archivist' or 'guest')
$clergy = db_fetchAll("
    SELECT u.*, COALESCE(p.parish_name, p_direct.parish_name) as parish_name, 
           COALESCE(pa.role, CASE WHEN u.parish_id IS NOT NULL THEN 'Parish Priest' ELSE NULL END) as assignment_role, 
           pa.start_date, pa.status as assignment_status
    FROM users u 
    LEFT JOIN parish_assignments pa ON u.user_id = pa.user_id AND pa.status = 'Active'
    LEFT JOIN parishes p ON pa.parish_id = p.parish_id
    LEFT JOIN parishes p_direct ON u.parish_id = p_direct.parish_id
    WHERE LOWER(u.role) IN ('admin', 'priest', 'deacon', 'mission_admin')
    ORDER BY u.full_name ASC
");

// Fetch assignments for the timeline
$assignments = db_fetchAll("
    SELECT pa.*, p.parish_name, COALESCE(u.full_name, pa.priest_name_manual) as full_name
    FROM parish_assignments pa 
    JOIN parishes p ON pa.parish_id = p.parish_id
    LEFT JOIN users u ON pa.user_id = u.user_id
    ORDER BY pa.start_date DESC, pa.assignment_id DESC
    LIMIT 20
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clergy Management - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        /* Clergy Grid & Responsive Layout */
        .clergy-stats-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 1.5rem; 
            margin-top: 1rem; 
            margin-bottom: 2rem; 
        }

        .clergy-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 1rem 1.25rem;
            border-radius: 16px;
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            flex-wrap: wrap;
        }

        .clergy-search-box {
            position: relative;
            min-width: 260px;
            max-width: 420px;
            flex: 1;
        }

        .clergy-search-box ion-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.2rem;
            pointer-events: none;
        }

        .clergy-search-box input {
            width: 100%;
            padding: 11px 16px 11px 44px;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s ease;
        }

        .clergy-search-box input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        .view-toggle-group {
            display: flex;
            background: rgba(0, 0, 0, 0.25);
            padding: 4px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .view-toggle-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            padding: 7px 14px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            transition: all 0.2s;
        }

        .view-toggle-btn.active {
            background: var(--accent);
            color: #000;
        }

        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .clergy-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 1.5rem; 
        }

        @media (max-width: 640px) {
            .clergy-grid {
                grid-template-columns: 1fr !important;
            }
        }

        .cleric-card { 
            background: var(--card-bg); 
            border-radius: 20px; 
            padding: 1.5rem; 
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .cleric-card:hover { 
            transform: translateY(-5px); 
            border-color: var(--accent); 
            box-shadow: 0 20px 40px rgba(0,0,0,0.3); 
        }

        .cleric-card::before { 
            content: ''; 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 4px; 
            height: 100%; 
            background: var(--accent); 
            opacity: 0.5;
        }
        
        .avatar-ring { 
            width: 60px; 
            height: 60px; 
            border-radius: 50%; 
            background: linear-gradient(45deg, var(--accent), #fbbf24); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.5rem; 
            color: #000; 
            font-weight: 900;
            margin-bottom: 1rem;
            flex-shrink: 0;
        }
        
        .assignment-badge { 
            display: inline-block; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 0.7rem; 
            font-weight: 800; 
            text-transform: uppercase;
            background: rgba(56, 189, 248, 0.1); 
            color: var(--accent);
            border: 1px solid rgba(56, 189, 248, 0.2);
            white-space: nowrap;
        }

        .cleric-actions {
            margin-top: 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .cleric-actions .btn {
            font-size: 0.8rem;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* List View Styles */
        .clergy-grid.list-view {
            grid-template-columns: 1fr !important;
            gap: 1rem;
        }

        .clergy-grid.list-view .cleric-card {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 2rem;
            gap: 1.5rem;
        }

        .clergy-grid.list-view .cleric-header-group {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            min-width: 250px;
            flex: 1.2;
        }

        .clergy-grid.list-view .avatar-ring {
            margin-bottom: 0;
            width: 50px;
            height: 50px;
            font-size: 1.25rem;
        }

        .clergy-grid.list-view .cleric-info h3 {
            margin-bottom: 2px;
        }

        .clergy-grid.list-view .cleric-info p {
            margin-bottom: 0 !important;
        }

        .clergy-grid.list-view .mission-box {
            flex: 1.2;
            min-width: 200px;
        }

        .clergy-grid.list-view .cleric-actions {
            margin-top: 0 !important;
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1024px) {
            .clergy-stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .clergy-stats-grid {
                grid-template-columns: 1fr;
            }

            .clergy-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
                padding: 1rem;
            }

            .toolbar-left {
                flex-direction: column;
                align-items: stretch;
            }

            .clergy-search-box {
                max-width: 100%;
                min-width: 100%;
            }

            .view-toggle-group {
                justify-content: center;
                width: 100%;
            }

            .view-toggle-btn {
                flex: 1;
                justify-content: center;
            }

            .toolbar-right button {
                width: 100%;
            }

            .clergy-grid.list-view .cleric-card {
                flex-direction: column;
                align-items: stretch;
                padding: 1.5rem;
                gap: 1.25rem;
            }

            .clergy-grid.list-view .cleric-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php 
                $header_title = "Clergy Command Center";
                $header_subtitle = "Managing canonical leadership and mission assignments across the Diocese.";
                include '../includes/header.php'; 
            ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-top: 1rem;"><?php echo h($success); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-top: 1rem;"><?php echo h($error); ?></div>
            <?php endif; ?>

            <div class="clergy-stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Total Clergy</span>
                    <span class="stat-value"><?php echo count($clergy); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Active Missions</span>
                    <span class="stat-value"><?php echo db_fetch("SELECT COUNT(*) as count FROM parishes")['count']; ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Chancery Staff</span>
                    <span class="stat-value"><?php echo db_fetch("SELECT COUNT(*) as count FROM users WHERE role='admin'")['count']; ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Vacant Posts</span>
                    <span class="stat-value" style="color: #ef4444;"><?php echo db_fetch("SELECT COUNT(*) as count FROM parishes WHERE priest_in_charge_id IS NULL")['count']; ?></span>
                </div>
            </div>

            <!-- Command Center Controls -->
            <div class="clergy-toolbar">
                <div class="toolbar-left">
                    <div class="clergy-search-box">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="clergy-search" placeholder="Search clergy by name, mission, role..." oninput="filterClergy()">
                    </div>
                    <div class="view-toggle-group">
                        <button type="button" class="view-toggle-btn active" onclick="toggleView('grid')" id="grid-toggle-btn">
                            <ion-icon name="grid-outline"></ion-icon> Grid
                        </button>
                        <button type="button" class="view-toggle-btn" onclick="toggleView('list')" id="list-toggle-btn">
                            <ion-icon name="list-outline"></ion-icon> List
                        </button>
                    </div>
                </div>
                <div class="toolbar-right">
                    <button type="button" class="btn btn-primary" onclick="showAssignModal()">
                        <ion-icon name="person-add-outline"></ion-icon> New Assignment
                    </button>
                </div>
            </div>

            <!-- Clergy Cards Grid -->
            <div class="clergy-grid">
                <?php foreach ($clergy as $cleric): 
                    $clean_name = str_ireplace(['Mnkmba', 'Mnkamba'], 'Nkamba', $cleric['full_name']);
                    $name_no_title = trim(preg_replace('/^(Fr\.?|Rev\.?|Bishop|Msgr\.?|Monsignor|Deacon)\s+/i', '', $clean_name));
                    $parts = preg_split('/\s+/', $name_no_title);
                    $cleric_initials = strtoupper(substr($parts[0] ?? 'C', 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                ?>
                    <div class="cleric-card">
                        <div class="cleric-header-group">
                            <div class="avatar-ring">
                                <?php echo $cleric_initials; ?>
                            </div>
                            <div class="cleric-info" style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                    <h3 style="font-family: 'Outfit'; color: white; margin-bottom: 5px;"><?php echo h($clean_name); ?></h3>
                                    <span class="assignment-badge"><?php echo h($cleric['role']); ?></span>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                                    <ion-icon name="mail-outline"></ion-icon> <?php echo h(str_ireplace(['mnkmba', 'mnkamba'], 'nkamba', $cleric['username'])); ?>
                                </p>
                            </div>
                        </div>

                        <div class="mission-box" style="background: rgba(0,0,0,0.25); padding: 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <label style="font-size: 0.65rem; text-transform: uppercase; color: var(--accent); font-weight: 800; display: block; margin-bottom: 6px; letter-spacing: 0.5px;">Current Mission</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="business-outline" style="font-size: 1.3rem; color: white; flex-shrink: 0;"></ion-icon>
                                <div style="min-width: 0;">
                                    <div style="color: white; font-weight: 700; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo h($cleric['parish_name'] ?: 'Unassigned'); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo h($cleric['assignment_role'] ?: 'No active role'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="cleric-actions">
                            <a href="clergy_dossier.php?id=<?php echo $cleric['user_id']; ?>" class="btn btn-secondary">
                                <ion-icon name="folder-open-outline"></ion-icon> Dossier
                            </a>
                            <button type="button" class="btn btn-primary" onclick="reassign(<?php echo $cleric['user_id']; ?>)">
                                <ion-icon name="swap-horizontal-outline"></ion-icon> Reassign
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Empty Search Results Message -->
            <div id="clergy-no-results" style="display: none; padding: 4rem 2rem; text-align: center; background: rgba(255,255,255,0.02); border-radius: 20px; border: 1px dashed rgba(255,255,255,0.1); margin-top: 1.5rem;">
                <ion-icon name="search-outline" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></ion-icon>
                <h4 style="color: white; font-family: 'Outfit'; margin-bottom: 0.5rem;">No Clergy Members Found</h4>
                <p style="color: var(--text-muted); font-size: 0.9rem;">No results match your search query. Try searching by surname, mission, or role.</p>
            </div>

            <!-- Recent Movements Section -->
            <div style="margin-top: 3.5rem;">
                <h3 style="font-family: 'Outfit'; color: white; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                    <ion-icon name="list-outline" style="color: var(--accent);"></ion-icon>
                    Recent Canonical Movements
                </h3>
                <div class="card bg-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05);">
                    <div class="data-table-container" style="overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%;">
                        <table class="data-table" style="width: 100%; min-width: 650px;">
                            <thead>
                                <tr>
                                    <th>Cleric</th>
                                    <th>Mission</th>
                                    <th>Role</th>
                                    <th>Effective Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($assignments)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">No canonical movements recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($assignments as $a): ?>
                                        <tr>
                                            <td style="font-weight: 700; color: white;"><?php echo h(str_ireplace(['Mnkmba', 'Mnkamba'], 'Nkamba', $a['full_name'])); ?></td>
                                            <td><?php echo h($a['parish_name']); ?></td>
                                            <td><?php echo h($a['role']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($a['start_date'])); ?></td>
                                            <td>
                                                <span class="status-pill <?php echo strtolower($a['status']); ?>">
                                                    <?php echo h($a['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Assignment Modal -->
    <div id="assign-modal" class="modal" onclick="if(event.target === this) hideAssignModal();" style="display: none; pointer-events: none; opacity: 0; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(10px); padding: 1rem;">
        <div class="card" style="width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; padding: 2rem; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 25px 50px rgba(0,0,0,0.7); position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 1rem;">
                <h3 style="color: white; font-family: 'Outfit'; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <ion-icon name="person-add-outline" style="color: var(--accent);"></ion-icon>
                    New Mission Assignment
                </h3>
                <button type="button" onclick="hideAssignModal()" style="background: none; border: none; color: var(--text-muted); font-size: 1.6rem; cursor: pointer; display: flex; align-items: center; padding: 4px;" aria-label="Close modal">
                    <ion-icon name="close-outline"></ion-icon>
                </button>
            </div>
            <form action="../actions/save_assignment.php" method="POST">
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Select Cleric</label>
                    <select name="user_id" class="input-field" style="width: 100%;" required>
                        <?php foreach($clergy as $c): 
                            $clean_c = h(str_ireplace(['Mnkmba', 'Mnkamba'], 'Nkamba', $c['full_name']));
                        ?>
                            <option value="<?php echo $c['user_id']; ?>"><?php echo $clean_c; ?> (<?php echo h($c['role']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Target Mission / Parish</label>
                    <select name="parish_id" class="input-field" style="width: 100%;" required>
                        <?php 
                        $all_parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");
                        foreach($all_parishes as $p): ?>
                            <option value="<?php echo $p['parish_id']; ?>"><?php echo h($p['parish_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Canonical Role</label>
                        <select name="role" class="input-field" style="width: 100%;">
                            <option>Parish Priest</option>
                            <option>Assistant Priest</option>
                            <option>Deacon</option>
                            <option>Mission Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Effective Date</label>
                        <input type="date" name="start_date" class="input-field" style="width: 100%;" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div style="margin-top: 2rem; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 2; padding: 12px; font-weight: 700;">Confirm Assignment</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1; padding: 12px;" onclick="hideAssignModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAssignModal() {
            const modal = document.getElementById('assign-modal');
            if (modal) {
                modal.classList.add('active');
                modal.style.display = 'flex';
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
                document.body.style.overflow = 'hidden';
            }
        }
        function hideAssignModal() {
            const modal = document.getElementById('assign-modal');
            if (modal) {
                modal.classList.remove('active');
                modal.style.display = 'none';
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                document.body.style.overflow = '';
            }
        }
        function viewHistory(userId) {
            window.location.href = '../actions/clergy_history.php?id=' + userId;
        }
        function reassign(userId) {
            const select = document.querySelector('select[name="user_id"]');
            if (select) {
                select.value = userId;
            }
            showAssignModal();
        }
        function filterClergy() {
            const searchInput = document.getElementById('clergy-search');
            const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
            const cards = document.querySelectorAll('.cleric-card');
            const isList = document.querySelector('.clergy-grid') && document.querySelector('.clergy-grid').classList.contains('list-view');
            const targetDisplay = isList ? 'flex' : 'flex'; // card has display: flex by default for column/row layout
            
            let visibleCount = 0;
            cards.forEach(card => {
                // Safely search across entire card text (name, username/email, mission, role)
                const cardText = (card.textContent || card.innerText || '').toLowerCase();
                if (!query || cardText.includes(query)) {
                    card.style.display = targetDisplay;
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const noResults = document.getElementById('clergy-no-results');
            if (noResults) {
                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }
        function toggleView(view) {
            const grid = document.querySelector('.clergy-grid');
            const gridBtn = document.getElementById('grid-toggle-btn');
            const listBtn = document.getElementById('list-toggle-btn');
            
            if (!grid || !gridBtn || !listBtn) return;

            if (view === 'list') {
                grid.classList.add('list-view');
                listBtn.classList.add('active');
                gridBtn.classList.remove('active');
            } else {
                grid.classList.remove('list-view');
                gridBtn.classList.add('active');
                listBtn.classList.remove('active');
            }
            // Update display states
            filterClergy();
        }

        // Close modal on Escape key press
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideAssignModal();
            }
        });
    </script>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
