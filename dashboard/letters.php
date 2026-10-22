<?php
/**
 * Hwange Diocese Records Management System
 * Letters of Good Standing / Transfer
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

// Handle search
$search = $_GET['q'] ?? '';
$where_parts = [];
$params = [];

// Base Query
$sql = "SELECT p.*, pr.parish_name 
        FROM parishioners p
        LEFT JOIN parishes pr ON p.current_parish_id = pr.parish_id";

// Role-based Isolation
$parish_filter = get_parish_filter($params, 'p', 'current_parish_id');
if ($parish_filter) {
    $where_parts[] = ltrim($parish_filter, ' AND ');
}

// Search Term
if (!empty($search)) {
    $where_parts[] = "(p.first_name LIKE ? OR p.last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where_parts)) {
    $sql .= " WHERE " . implode(" AND ", $where_parts);
}

$sql .= " ORDER BY p.first_name ASC LIMIT 50";
$parishioners = db_fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Letters of Good Standing - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .letter-card { background: var(--card-bg); padding: 1.5rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .letter-info h3 { margin: 0 0 0.25rem 0; font-family: 'Outfit'; color: white; }
        .letter-info p { margin: 0; font-size: 0.85rem; color: var(--text-muted); }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 1.5rem;
            box-sizing: border-box;
        }
        .modal.active {
            display: flex !important;
        }
        .modal-content {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1.25rem;
            padding: 2rem;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            position: relative;
            color: white;
            box-sizing: border-box;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .modal-header h2 {
            margin: 0;
            font-size: 1.3rem;
            font-family: 'Outfit', sans-serif;
            color: white;
            font-weight: 700;
        }
        .modal-close {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted, #94a3b8);
            font-size: 1.3rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .modal-close:hover {
            color: white;
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
        }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <?php 
                $header_title = "Letters of Good Standing";
                $header_subtitle = "Generate formal canonical transfer and standing letters for parishioners.";
                include '../includes/header.php'; 
            ?>

            <div class="filter-bar" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <form method="GET" action="" style="display: flex; gap: 1rem; width: 100%; max-width: 500px;">
                    <div class="search-box" style="flex: 1;">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" name="q" placeholder="Search parishioner by name..." value="<?php echo h($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>

                <div>
                    <a href="../sacraments/canonical_inquiry.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; text-decoration: none;">
                        <ion-icon name="document-text-outline" style="font-size: 1.1rem;"></ion-icon> Canonical Inquiry Form
                    </a>
                </div>
            </div>

            <?php if (empty($parishioners)): ?>
                <div class="empty-state" style="text-align: center; padding: 3rem; background: var(--card-bg); border-radius: 12px;">
                    <ion-icon name="document-text-outline" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></ion-icon>
                    <h3>No Parishioners Found</h3>
                    <p style="color: var(--text-muted);">Search for a parishioner to generate a letter of good standing or sacramental status extract.</p>
                </div>
            <?php else: ?>
                <div class="letter-list">
                    <?php foreach ($parishioners as $p): ?>
                        <div class="letter-card">
                            <div class="letter-info">
                                <h3><?php echo h($p['first_name'] . ' ' . $p['last_name']); ?></h3>
                                <p>DOB: <?php echo date('d M Y', strtotime($p['dob'])); ?> | Parish: <?php echo h($p['parish_name'] ?: 'Not Assigned'); ?></p>
                            </div>
                            <div class="letter-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <a href="../sacraments/canonical_inquiry.php?person_id=<?php echo $p['person_id']; ?>" class="btn btn-secondary" style="padding: 0.5rem 0.9rem; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                    <ion-icon name="ribbon-outline"></ion-icon> Status Extract / Inquiry
                                </a>
                                <button type="button" class="btn btn-primary btn-open-letter" 
                                        data-id="<?php echo $p['person_id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="openLetterModal(<?php echo $p['person_id']; ?>, '<?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name'], ENT_QUOTES, 'UTF-8'); ?>')"
                                        style="padding: 0.5rem 1rem; font-size: 0.85rem; cursor: pointer;">
                                    <ion-icon name="document-text-outline"></ion-icon> Letter of Standing
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- Generate Letter Modal -->
    <div class="modal" id="letterModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Generate Letter of Standing</h2>
                <button type="button" class="modal-close" onclick="closeLetterModal()"><ion-icon name="close-outline"></ion-icon></button>
            </div>
            <form action="../sacraments/transfer_letter_print.php" method="GET" target="_blank" onsubmit="return validateLetterForm()">
                <input type="hidden" name="id" id="modal_person_id" required>
                
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">Generating canonical letter for: <strong id="modal_person_name" style="color: white;"></strong></p>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-size: 0.85rem; font-weight: 600;">Destination (To Whom It May Concern) *</label>
                    <input type="text" name="destination" class="form-control" placeholder="e.g. The Parish Priest, St. Mary's Parish, Bulawayo" required style="width: 100%; box-sizing: border-box;">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-size: 0.85rem; font-weight: 600;">Purpose / Reason *</label>
                    <select name="reason" class="form-control" required style="width: 100%; box-sizing: border-box;">
                        <option value="Transfer of Membership">Transfer of Membership (Relocation)</option>
                        <option value="Marriage Preparation">Marriage Preparation</option>
                        <option value="Godparent Sponsor Eligibility">Godparent / Sponsor Eligibility</option>
                        <option value="General Reference">General Reference</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeLetterModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" onclick="setTimeout(closeLetterModal, 600)">
                        <ion-icon name="print-outline"></ion-icon> Preview & Print
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
    <script>
        function openLetterModal(personId, personName) {
            var idInput = document.getElementById('modal_person_id');
            var nameSpan = document.getElementById('modal_person_name');
            var modal = document.getElementById('letterModal');
            if (idInput) idInput.value = personId;
            if (nameSpan) nameSpan.textContent = personName;
            if (modal) {
                modal.classList.add('active');
                var destInput = modal.querySelector('input[name="destination"]');
                if (destInput) {
                    setTimeout(function() { destInput.focus(); }, 100);
                }
            }
        }

        function closeLetterModal() {
            var modal = document.getElementById('letterModal');
            if (modal) modal.classList.remove('active');
        }

        function validateLetterForm() {
            var id = document.getElementById('modal_person_id').value;
            if (!id) {
                alert('Please select a parishioner first.');
                return false;
            }
            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('letterModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) closeLetterModal();
                });
            }
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeLetterModal();
            });
        });
    </script>
</body>
</html>
