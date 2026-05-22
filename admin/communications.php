<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Diocesan Communication Center - Admin Interface
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_admin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Admin Response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_response'])) {
    $comm_id = $_POST['comm_id'] ?? '';
    $response = $_POST['admin_response'] ?? '';
    $status = $_POST['status'] ?? 'Resolved';

    if ($comm_id && $response) {
        db_query("UPDATE communications SET admin_response = ?, status = ?, replied_at = datetime('now'), is_read = 0 WHERE comm_id = ?", 
            [$response, $status, $comm_id]);
        $success = "Response sent to the mission site.";
    } else {
        $error = "Please provide a response message.";
    }
}

// Fetch all messages from all parishes
$messages = db_fetchAll("
    SELECT c.*, u.full_name as sender_name, p_orig.parish_name as origin_parish_name, p_dest.parish_name as dest_parish_name
    FROM communications c 
    JOIN users u ON c.sender_id = u.user_id 
    JOIN parishes p_orig ON c.parish_id = p_orig.parish_id 
    LEFT JOIN parishes p_dest ON c.recipient_parish_id = p_dest.parish_id
    ORDER BY CASE WHEN c.status = 'Pending' THEN 0 ELSE 1 END, c.created_at DESC
");

$header_title = "Diocesan Communication Center";
$header_subtitle = "Manage and respond to canonical queries and mission requests.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communication Center - Admin - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0;">
                
                <div class="section-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; gap: 1rem;">
                        <div class="stat-badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 800; font-size: 0.8rem;">
                            <?php 
                                $pending_count = 0;
                                foreach($messages as $m) if($m['status'] == 'Pending') $pending_count++;
                                echo $pending_count; 
                            ?> PENDING REQUESTS
                        </div>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin-bottom: 2rem;"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="messages-list" style="display: grid; gap: 1.5rem;">
                    <?php if (empty($messages)): ?>
                        <div class="card bg-card" style="padding: 5rem; text-align: center; border-radius: 2rem;">
                            <h3 style="color: var(--text-muted);">The communication log is clear.</h3>
                            <p style="color: var(--text-muted);">All mission queries have been addressed.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): 
                            $status_class = strtolower(str_replace(' ', '-', $msg['status']));
                        ?>
                            <div class="card bg-card message-item <?php echo $msg['status'] == 'Pending' ? 'pending-glow' : ''; ?>" style="padding: 0; border-radius: 1.5rem; overflow: hidden; border: 1px solid rgba(255,255,255,0.05);">
                                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 0;">
                                    
                                    <!-- Sender Info Pane -->
                                    <div style="padding: 1.5rem; background: rgba(255,255,255,0.02); border-right: 1px solid rgba(255,255,255,0.05);">
                                        <div style="font-size: 0.65rem; color: var(--accent); text-transform: uppercase; font-weight: 800; letter-spacing: 1px; margin-bottom: 8px;">Originating Mission</div>
                                        <h4 style="color: white; font-size: 1.1rem; margin-bottom: 4px;"><?php echo h($msg['origin_parish_name']); ?></h4>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">By: <?php echo h($msg['sender_name']); ?></div>
                                        
                                        <?php if ($msg['dest_parish_name']): ?>
                                            <div style="font-size: 0.65rem; color: #38bdf8; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; margin-bottom: 4px;">Sent To</div>
                                            <div style="color: white; font-size: 0.9rem; margin-bottom: 1.5rem;"><ion-icon name="arrow-forward-outline"></ion-icon> <?php echo h($msg['dest_parish_name']); ?></div>
                                        <?php else: ?>
                                            <div style="font-size: 0.65rem; color: #10b981; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; margin-bottom: 4px;">Sent To</div>
                                            <div style="color: white; font-size: 0.9rem; margin-bottom: 1.5rem;"><ion-icon name="shield-checkmark-outline"></ion-icon> Diocesan Chancery</div>
                                        <?php endif; ?>
                                        
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <div style="font-size: 0.7rem; color: var(--text-muted);"><ion-icon name="calendar-outline" style="vertical-align: middle;"></ion-icon> <?php echo date('d M Y', strtotime($msg['created_at'])); ?></div>
                                            <div style="font-size: 0.7rem; color: var(--text-muted);"><ion-icon name="pricetag-outline" style="vertical-align: middle;"></ion-icon> <?php echo h($msg['type']); ?></div>
                                        </div>
                                        
                                        <div style="margin-top: 2rem;">
                                            <span class="status-badge <?php echo $status_class; ?>" style="padding: 6px 12px; border-radius: 8px; font-weight: 900; font-size: 0.7rem;"><?php echo $msg['status']; ?></span>
                                        </div>
                                    </div>

                                    <!-- Content & Response Pane -->
                                    <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                                        <div>
                                            <h3 style="color: white; font-family: 'Outfit'; margin-bottom: 8px;"><?php echo h($msg['subject']); ?></h3>
                                            <p style="color: #cbd5e1; line-height: 1.6; font-size: 0.95rem; background: rgba(0,0,0,0.1); padding: 1rem; border-radius: 12px;"><?php echo nl2br(h($msg['message'])); ?></p>
                                        </div>

                                        <?php if ($msg['admin_response']): ?>
                                            <div style="margin-top: 0.5rem;">
                                                <div style="font-size: 0.7rem; color: #10b981; text-transform: uppercase; font-weight: 800; margin-bottom: 4px;">Sent Response (<?php echo date('d M Y', strtotime($msg['replied_at'])); ?>)</div>
                                                <div style="color: white; font-size: 0.9rem; background: rgba(16, 185, 129, 0.05); border-left: 3px solid #10b981; padding: 1rem; border-radius: 0 8px 8px 0;"><?php echo nl2br(h($msg['admin_response'])); ?></div>
                                                <button onclick="toggleResponseForm(<?php echo $msg['comm_id']; ?>)" class="btn btn-sm btn-secondary" style="margin-top: 10px; font-size: 0.7rem;">Update Response</button>
                                            </div>
                                        <?php else: ?>
                                            <button onclick="toggleResponseForm(<?php echo $msg['comm_id']; ?>)" class="btn btn-primary" style="width: fit-content; font-weight: 800; padding: 0.6rem 1.5rem;">Address Request</button>
                                        <?php endif; ?>

                                        <!-- Hidden Response Form -->
                                        <div id="form-<?php echo $msg['comm_id']; ?>" style="display: none; margin-top: 1rem; background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1);">
                                            <form method="POST">
                                                <input type="hidden" name="comm_id" value="<?php echo $msg['comm_id']; ?>">
                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                                    <div class="form-group">
                                                        <label style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Update Status</label>
                                                        <select name="status" style="width: 100%; padding: 0.6rem; background: #000; border: 1px solid #333; color: white; border-radius: 6px;">
                                                            <option value="In Review" <?php echo $msg['status'] == 'In Review' ? 'selected' : ''; ?>>Mark In Review</option>
                                                            <option value="Resolved" <?php echo $msg['status'] == 'Resolved' ? 'selected' : ''; ?>>Mark Resolved</option>
                                                            <option value="Archived" <?php echo $msg['status'] == 'Archived' ? 'selected' : ''; ?>>Move to Archive</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group" style="margin-bottom: 1rem;">
                                                    <label style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Response Content</label>
                                                    <textarea name="admin_response" required rows="4" style="width: 100%; padding: 0.8rem; background: #000; border: 1px solid #333; color: white; border-radius: 8px; resize: none;"><?php echo h($msg['admin_response']); ?></textarea>
                                                </div>
                                                <div style="display: flex; gap: 10px;">
                                                    <button type="submit" name="submit_response" class="btn btn-sm btn-primary">Send Response</button>
                                                    <button type="button" onclick="toggleResponseForm(<?php echo $msg['comm_id']; ?>)" class="btn btn-sm btn-secondary">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <script>
        function toggleResponseForm(id) {
            const f = document.getElementById('form-' + id);
            f.style.display = f.style.display === 'none' ? 'block' : 'none';
        }
    </script>
    <style>
        .pending-glow { border: 1px solid var(--accent) !important; box-shadow: 0 0 15px rgba(251, 191, 36, 0.1); }
        .status-badge.pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-badge.in-review { background: rgba(56, 189, 248, 0.1); color: #38bdf8; }
        .status-badge.resolved { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-badge.archived { background: rgba(148, 163, 184, 0.1); color: #94a3b8; }
    </style>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
