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
    SELECT u.*, p.parish_name, pa.role as assignment_role, pa.start_date, pa.status as assignment_status
    FROM users u 
    LEFT JOIN parish_assignments pa ON u.user_id = pa.user_id AND pa.status = 'Active'
    LEFT JOIN parishes p ON pa.parish_id = p.parish_id
    WHERE u.role IN ('admin', 'priest', 'mission_admin')
    ORDER BY u.full_name ASC
");

// Fetch assignments for the timeline
$assignments = db_fetchAll("
    SELECT pa.*, p.parish_name, u.full_name
    FROM parish_assignments pa 
    JOIN parishes p ON pa.parish_id = p.parish_id
    JOIN users u ON pa.user_id = u.user_id
    ORDER BY pa.start_date DESC
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
        .clergy-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .cleric-card { 
            background: var(--card-bg); 
            border-radius: 20px; 
            padding: 1.5rem; 
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .cleric-card:hover { transform: translateY(-5px); border-color: var(--accent); box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .cleric-card::before { 
            content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; 
            background: var(--accent); opacity: 0.5;
        }
        
        .avatar-ring { 
            width: 60px; height: 60px; border-radius: 50%; 
            background: linear-gradient(45deg, var(--accent), #fbbf24); 
            display: flex; align-items: center; justify-content: center; 
            font-size: 1.5rem; color: #000; font-weight: 900;
            margin-bottom: 1rem;
        }
        
        .assignment-badge { 
            display: inline-block; padding: 4px 12px; border-radius: 20px; 
            font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
            background: rgba(56, 189, 248, 0.1); color: var(--accent);
        }

        .timeline-item {
            padding: 1rem;
            border-left: 2px dashed rgba(255,255,255,0.1);
            margin-left: 1rem;
            position: relative;
        }
        .timeline-item::before {
            content: ''; position: absolute; left: -7px; top: 1.5rem; 
            width: 12px; height: 12px; border-radius: 50%; background: var(--accent);
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
                $additional_header_actions = '
                    <div style="display: flex; background: rgba(0,0,0,0.2); padding: 4px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); margin-right: 10px;">
                        <button class="view-toggle active" onclick="toggleView(\'grid\')" id="grid-toggle-btn" style="background: none; border: none; color: white; padding: 6px 12px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 700; transition: all 0.2s;">
                            <ion-icon name="grid-outline"></ion-icon> Grid
                        </button>
                        <button class="view-toggle" onclick="toggleView(\'list\')" id="list-toggle-btn" style="background: none; border: none; color: var(--text-muted); padding: 6px 12px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 700; transition: all 0.2s;">
                            <ion-icon name="list-outline"></ion-icon> List
                        </button>
                    </div>
                    <div class="search-box" style="width: 250px; margin-bottom: 0;">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="clergy-search" placeholder="Search clergy..." onkeyup="filterClergy()">
                    </div>
                    <button class="btn btn-primary" onclick="showAssignModal()">
                        <ion-icon name="person-add-outline"></ion-icon> New Assignment
                    </button>
                ';
                include '../includes/header.php'; 
            ?>


            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo h($success); ?></div>
            <?php endif; ?>

            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-top: 1rem;">
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
                    <span class="stat-value" style="color: #ef4444;">2</span>
                </div>
            </div>

            <div class="clergy-grid">
                <?php foreach ($clergy as $cleric): ?>
                    <div class="cleric-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div class="avatar-ring">
                                <?php echo substr($cleric['full_name'], 0, 1); ?>
                            </div>
                            <span class="assignment-badge"><?php echo $cleric['role']; ?></span>
                        </div>
                        
                        <h3 style="font-family: 'Outfit'; color: white; margin-bottom: 5px;"><?php echo h($cleric['full_name']); ?></h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
                            <ion-icon name="mail-outline"></ion-icon> <?php echo h($cleric['username']); ?>
                        </p>

                        <div style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <label style="font-size: 0.65rem; text-transform: uppercase; color: var(--accent); font-weight: 800; display: block; margin-bottom: 8px;">Current Mission</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="business-outline" style="font-size: 1.2rem; color: white;"></ion-icon>
                                <div>
                                    <div style="color: white; font-weight: 700; font-size: 0.9rem;"><?php echo $cleric['parish_name'] ?: 'Unassigned'; ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $cleric['assignment_role'] ?: 'No active role'; ?></div>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <a href="clergy_dossier.php?id=<?php echo $cleric['user_id']; ?>" class="btn btn-secondary" style="font-size: 0.75rem; padding: 10px; display: flex; align-items: center; justify-content: center; gap: 5px;">
                                <ion-icon name="folder-open-outline"></ion-icon> Dossier
                            </a>
                            <button class="btn btn-primary" style="font-size: 0.75rem; padding: 10px;" onclick="reassign(<?php echo $cleric['user_id']; ?>)">
                                <ion-icon name="swap-horizontal-outline"></ion-icon> Reassign
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 3rem;">
                <h3 style="font-family: 'Outfit'; color: white; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                    <ion-icon name="list-outline" style="color: var(--accent);"></ion-icon>
                    Recent Canonical Movements
                </h3>
                <div class="card bg-card" style="padding: 0; overflow: hidden;">
                    <table class="data-table">
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
                            <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td style="font-weight: 700; color: white;"><?php echo h($a['full_name']); ?></td>
                                    <td><?php echo h($a['parish_name']); ?></td>
                                    <td><?php echo h($a['role']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($a['start_date'])); ?></td>
                                    <td>
                                        <span class="status-pill <?php echo strtolower($a['status']); ?>">
                                            <?php echo $a['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Assignment Modal (Placeholder for logic) -->
    <div id="assign-modal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
        <div class="card" style="width: 500px; padding: 2rem;">
            <h3 style="color: white; font-family: 'Outfit'; margin-bottom: 1.5rem;">New Mission Assignment</h3>
            <form action="../actions/save_assignment.php" method="POST">
                <div class="form-group">
                    <label>Select Cleric</label>
                    <select name="user_id" class="input-field" style="width: 100%;">
                        <?php foreach($clergy as $c) echo "<option value='{$c['user_id']}'>{$c['full_name']}</option>"; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Target Mission</label>
                    <select name="parish_id" class="input-field" style="width: 100%;">
                        <?php 
                        $all_parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");
                        foreach($all_parishes as $p) echo "<option value='{$p['parish_id']}'>{$p['parish_name']}</option>";
                        ?>
                    </select>
                </div>
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Canonical Role</label>
                        <select name="role" class="input-field" style="width: 100%;">
                            <option>Parish Priest</option>
                            <option>Assistant Priest</option>
                            <option>Deacon</option>
                            <option>Mission Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Effective Date</label>
                        <input type="date" name="start_date" class="input-field" style="width: 100%;" required>
                    </div>
                </div>
                <div style="margin-top: 2rem; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 2;">Confirm Assignment</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="hideAssignModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAssignModal() {
            document.getElementById('assign-modal').style.display = 'flex';
        }
        function hideAssignModal() {
            document.getElementById('assign-modal').style.display = 'none';
        }
        function viewHistory(userId) {
            window.location.href = '../actions/clergy_history.php?id=' + userId;
        }
        function reassign(userId) {
            // Pre-select the user in the assign modal
            const select = document.querySelector('select[name="user_id"]');
            select.value = userId;
            showAssignModal();
        }
        function filterClergy() {
            const query = document.getElementById('clergy-search').value.toLowerCase();
            const cards = document.querySelectorAll('.cleric-card');
            const isList = document.getElementById('list-toggle-btn').classList.contains('active');
            const displayStyle = isList ? 'flex' : 'block';
            
            cards.forEach(card => {
                const name = card.querySelector('h3').innerText.toLowerCase();
                const mission = card.querySelector('.cleric-card div div div').innerText.toLowerCase();
                if (name.includes(query) || mission.includes(query)) {
                    card.style.display = displayStyle;
                } else {
                    card.style.display = 'none';
                }
            });
        }
        function toggleView(view) {
            const grid = document.querySelector('.clergy-grid');
            const gridBtn = document.getElementById('grid-toggle-btn');
            const listBtn = document.getElementById('list-toggle-btn');
            
            if (view === 'list') {
                grid.style.gridTemplateColumns = '1fr';
                grid.querySelectorAll('.cleric-card').forEach(card => {
                    card.style.display = 'flex';
                    card.style.alignItems = 'center';
                    card.style.justifyContent = 'space-between';
                    card.style.padding = '0.75rem 1.5rem';
                });
                listBtn.classList.add('active');
                listBtn.style.background = 'var(--accent)';
                listBtn.style.color = '#000';
                gridBtn.classList.remove('active');
                gridBtn.style.background = 'none';
                gridBtn.style.color = 'var(--text-muted)';
            } else {
                grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
                grid.querySelectorAll('.cleric-card').forEach(card => {
                    card.style.display = 'block';
                    card.style.padding = '1.5rem';
                });
                gridBtn.classList.add('active');
                gridBtn.style.background = 'var(--accent)';
                gridBtn.style.color = '#000';
                listBtn.classList.remove('active');
                listBtn.style.background = 'none';
                listBtn.style.color = 'var(--text-muted)';
            }
        }
        
        // Set initial active state
        document.getElementById('grid-toggle-btn').style.background = 'var(--accent)';
        document.getElementById('grid-toggle-btn').style.color = '#000';
    </script>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
