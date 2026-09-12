<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Canonical Communication Hub - Professional Interface
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Protect the page
require_login();

$user_id = $_SESSION['user_id'];
$parish_id = $_SESSION['parish_id'];
$is_admin = ($_SESSION['role'] === 'admin');

// Mark messages as read
if (!$is_admin) {
    db_query("UPDATE communications SET is_read = 1 WHERE recipient_parish_id = ?", [$parish_id]);
    db_query("UPDATE communications SET is_read = 1 WHERE parish_id = ? AND admin_response IS NOT NULL", [$parish_id]);
}

$success = '';
$error = '';

// Handle New Message Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = $_POST['subject'] ?? '';
    $type = $_POST['type'] ?? 'Query';
    $message = $_POST['message'] ?? '';
    $recipient_id = $_POST['recipient_parish_id'] ?? null;

    if ($subject && $message) {
        db_query("INSERT INTO communications (sender_id, parish_id, recipient_parish_id, subject, message, type) VALUES (?, ?, ?, ?, ?, ?)", 
            [$user_id, $parish_id, $recipient_id, $subject, $message, $type]);
        $success = "Canonical dispatch successful.";
    } else {
        $error = "Subject and Message are required.";
    }
}

// Fetch Parishes
$other_parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes WHERE parish_id != ? ORDER BY parish_name", [$parish_id]);

// Fetch Messages
$params = [];
$filter = "";
if (!$is_admin) {
    $filter = "WHERE c.parish_id = ? OR c.recipient_parish_id = ?";
    $params = [$parish_id, $parish_id];
}

$messages = db_fetchAll("
    SELECT c.*, u.full_name as sender_name, p_orig.parish_name as origin_parish_name, p_dest.parish_name as dest_parish_name
    FROM communications c 
    JOIN users u ON c.sender_id = u.user_id 
    JOIN parishes p_orig ON c.parish_id = p_orig.parish_id 
    LEFT JOIN parishes p_dest ON c.recipient_parish_id = p_dest.parish_id
    $filter 
    ORDER BY c.created_at DESC
", $params);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communication Hub - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .messenger-container { 
            display: grid; grid-template-columns: 380px 1fr; 
            height: calc(100vh - 140px); 
            background: var(--card-bg); 
            border-radius: 24px; 
            border: 1px solid rgba(255,255,255,0.05); 
            overflow: hidden; 
            margin-top: 1rem;
        }
        .inbox-sidebar { border-right: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; }
        .thread-view { background: rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        
        .msg-item { 
            padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.03); 
            cursor: pointer; transition: all 0.2s; border-left: 4px solid transparent;
        }
        .msg-item:hover { background: rgba(255,255,255,0.02); }
        .msg-item.active { background: rgba(56, 189, 248, 0.05); border-left-color: var(--accent); }
        .msg-item.unread { border-left-color: var(--accent); }
        
        .chat-bubble { max-width: 80%; margin-bottom: 1.5rem; }
        .bubble-inner { padding: 1.25rem; border-radius: 20px; font-size: 0.9rem; line-height: 1.6; }
        .left .bubble-inner { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.05); border-bottom-left-radius: 0; }
        .right .bubble-inner { background: linear-gradient(135deg, var(--accent) 0%, #0284c7 100%); color: #000; font-weight: 600; border-bottom-right-radius: 0; }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="content-body" style="padding: 1.5rem; overflow: hidden;">
                <div class="messenger-container">
                    <!-- Inbox -->
                    <div class="inbox-sidebar">
                        <div style="padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <h3 style="color: white; font-family: 'Outfit'; margin: 0; font-size: 1.2rem;">Canonical Inbox</h3>
                                <button class="btn btn-primary" onclick="showModal()" style="padding: 8px 12px; font-size: 0.8rem;">
                                    <ion-icon name="add-outline"></ion-icon> New
                                </button>
                            </div>
                            <div class="search-box" style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 10px 15px; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="search-outline" style="color: var(--text-muted);"></ion-icon>
                                <input type="text" placeholder="Search threads..." style="background: none; border: none; color: white; width: 100%; outline: none; font-size: 0.85rem;">
                            </div>
                        </div>

                        <div style="flex: 1; overflow-y: auto;">
                            <?php foreach ($messages as $idx => $msg): ?>
                                <div class="msg-item <?php echo $idx === 0 ? 'active' : ''; ?> <?php echo !$msg['is_read'] ? 'unread' : ''; ?>" onclick="loadThread(<?php echo $msg['comm_id']; ?>)">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span style="color: var(--accent); font-size: 0.65rem; font-weight: 800; text-transform: uppercase;"><?php echo h($msg['type']); ?></span>
                                        <span style="font-size: 0.65rem; color: var(--text-muted);"><?php echo time_elapsed_string($msg['created_at']); ?></span>
                                    </div>
                                    <div style="color: white; font-weight: 700; margin-bottom: 5px;"><?php echo h($msg['subject']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo h($msg['message']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Thread -->
                    <div class="thread-view">
                        <?php if (empty($messages)): ?>
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0.3;">
                                <ion-icon name="mail-open-outline" style="font-size: 5rem; margin-bottom: 1rem;"></ion-icon>
                                <p>Select a dialogue to begin</p>
                            </div>
                        <?php else: $active = $messages[0]; ?>
                            <div style="padding: 1.5rem; background: var(--card-bg); border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="color: white; font-family: 'Outfit'; margin: 0;"><?php echo h($active['subject']); ?></h4>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Ref: #<?php echo str_pad($active['comm_id'], 4, '0', STR_PAD_LEFT); ?></div>
                                </div>
                                <span class="status-pill <?php echo strtolower($active['status']); ?>"><?php echo $active['status']; ?></span>
                            </div>

                            <div style="flex: 1; overflow-y: auto; padding: 2rem;">
                                <!-- Message -->
                                <div class="chat-bubble left">
                                    <div class="bubble-inner">
                                        <div style="font-size: 0.7rem; color: var(--accent); font-weight: 800; margin-bottom: 8px;">FROM: <?php echo h($active['origin_parish_name']); ?></div>
                                        <?php echo nl2br(h($active['message'])); ?>
                                    </div>
                                    <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 8px;"><?php echo date('M d, Y H:i', strtotime($active['created_at'])); ?></div>
                                </div>

                                <!-- Response -->
                                <?php if ($active['admin_response']): ?>
                                    <div class="chat-bubble right" style="margin-left: auto;">
                                        <div class="bubble-inner">
                                            <div style="font-size: 0.7rem; color: rgba(0,0,0,0.5); font-weight: 900; margin-bottom: 8px;">CHANCERY DISPATCH</div>
                                            <?php echo nl2br(h($active['admin_response'])); ?>
                                        </div>
                                        <div style="font-size: 0.65rem; color: var(--text-muted); text-align: right; margin-top: 8px;"><?php echo date('M d, Y H:i', strtotime($active['replied_at'])); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div style="padding: 1.5rem; background: var(--card-bg); border-top: 1px solid rgba(255,255,255,0.05);">
                                <div style="display: flex; gap: 1rem;">
                                    <input type="text" placeholder="Type a follow-up query..." style="flex: 1; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 12px 15px; color: white; outline: none;">
                                    <button class="btn btn-primary" style="padding: 12px 24px;">Send Dispatch</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php include 'includes/privacy_footer.php'; ?>

            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="msg-modal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
        <div class="card" style="width: 600px; padding: 2.5rem; border-radius: 2rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 2rem;">
                <h3 style="color: white; font-family: 'Outfit'; margin: 0;">New Canonical Inquiry</h3>
                <button onclick="hideModal()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;"><ion-icon name="close-outline"></ion-icon></button>
            </div>
            <form method="POST">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Subject</label>
                        <input type="text" name="subject" class="input-field" required>
                    </div>
                    <div class="form-group">
                        <label>Recipient</label>
                        <select name="recipient_parish_id" class="input-field" style="width: 100%;">
                            <option value="">Diocesan Chancery</option>
                            <?php foreach($other_parishes as $p) echo "<option value='{$p['parish_id']}'>".h($p['parish_name'])."</option>"; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="type" class="input-field" style="width: 100%;">
                            <option>Registry Query</option>
                            <option>Correction Request</option>
                            <option>Administrative</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Message Detail</label>
                        <textarea name="message" class="input-field" rows="5" required></textarea>
                    </div>
                </div>
                <button type="submit" name="send_message" class="btn btn-primary" style="width: 100%; margin-top: 2rem; padding: 1.25rem;">Dispatch Message</button>
            </form>
        </div>
    </div>

    <script>
        function showModal() { document.getElementById('msg-modal').style.display = 'flex'; }
        function hideModal() { document.getElementById('msg-modal').style.display = 'none'; }
        function loadThread(id) { showToast('Synchronizing secure thread...', 'info'); }
    </script>
    <script src="assets/js/main.js?v=1.6.2"></script>
</body>
</html>
