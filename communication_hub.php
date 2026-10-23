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
$parish_id = $_SESSION['parish_id'] ?? null;
$is_admin = ($_SESSION['role'] === 'admin');

// Ensure Chancery Staff has a valid originating parish ID for foreign key constraints
if (!$parish_id) {
    $parish_id = db_fetch("SELECT parish_id FROM parishes WHERE parish_name LIKE '%Cathedral%' OR parish_name LIKE '%Chancery%' LIMIT 1")['parish_id'] ?? 1;
}

// Mark messages as read
if (!$is_admin && !empty($_SESSION['parish_id'])) {
    db_query("UPDATE communications SET is_read = 1 WHERE recipient_parish_id = ?", [$_SESSION['parish_id']]);
    db_query("UPDATE communications SET is_read = 1 WHERE parish_id = ? AND admin_response IS NOT NULL", [$_SESSION['parish_id']]);
} elseif ($is_admin) {
    // If admin is viewing a specific thread, mark it as read
    if (!empty($_GET['thread'])) {
        db_query("UPDATE communications SET is_read = 1 WHERE comm_id = ?", [(int)$_GET['thread']]);
    }
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle New Message Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject'] ?? '');
    $type = $_POST['type'] ?? 'Registry Query';
    $message = trim($_POST['message'] ?? '');
    $recipient_id = !empty($_POST['recipient_parish_id']) ? (int)$_POST['recipient_parish_id'] : null;

    if ($subject && $message) {
        db_query("INSERT INTO communications (sender_id, parish_id, recipient_parish_id, subject, message, type) VALUES (?, ?, ?, ?, ?, ?)", 
            [$user_id, $parish_id, $recipient_id, $subject, $message, $type]);
        $new_id = $pdo->lastInsertId();
        header("Location: communication_hub.php?thread=" . $new_id . "&success=" . urlencode("Canonical dispatch successful."));
        exit;
    } else {
        $error = "Subject and Message details are required.";
    }
}

// Handle Follow-up or Chancery Dispatch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $thread_id = (int)($_POST['thread_id'] ?? 0);
    $reply_text = trim($_POST['reply_text'] ?? '');
    
    if ($thread_id && $reply_text) {
        if ($is_admin) {
            db_query("UPDATE communications SET admin_response = ?, status = 'Resolved', replied_at = datetime('now'), is_read = 0 WHERE comm_id = ?", 
                [$reply_text, $thread_id]);
            $success = "Chancery response dispatched.";
        } else {
            $existing = db_fetch("SELECT message FROM communications WHERE comm_id = ?", [$thread_id]);
            if ($existing) {
                $updated_msg = $existing['message'] . "\n\n--- Follow-up (" . date('M d, Y H:i') . ") ---\n" . $reply_text;
                db_query("UPDATE communications SET message = ?, status = 'Pending', is_read = 0 WHERE comm_id = ?", 
                    [$updated_msg, $thread_id]);
                $success = "Follow-up dispatched to Chancery.";
            }
        }
        header("Location: communication_hub.php?thread=" . $thread_id . "&success=" . urlencode($success));
        exit;
    } else {
        $error = "Reply content cannot be empty.";
    }
}

// Fetch Parishes for Recipient Selection
if ($is_admin) {
    $other_parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");
} else {
    $other_parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes WHERE parish_id != ? ORDER BY parish_name", [$parish_id]);
}

// Fetch Messages
$params = [];
$filter = "";
if (!$is_admin && !empty($_SESSION['parish_id'])) {
    $filter = "WHERE c.parish_id = ? OR c.recipient_parish_id = ?";
    $params = [$_SESSION['parish_id'], $_SESSION['parish_id']];
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

// Determine active thread
$selected_thread_id = isset($_GET['thread']) ? (int)$_GET['thread'] : null;
$active = null;
if ($selected_thread_id) {
    foreach ($messages as $m) {
        if ($m['comm_id'] == $selected_thread_id) {
            $active = $m;
            break;
        }
    }
}
if (!$active && !empty($messages)) {
    $active = $messages[0];
}

$header_title = "Diocesan Communication Hub";
$header_subtitle = "Canonical dialogue and mission dispatches across the Diocese.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communication Hub - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="assets/css/responsive.css?v=1.3">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        .messenger-container { 
            display: grid; 
            grid-template-columns: 360px 1fr; 
            height: calc(100vh - 170px); 
            min-height: 550px;
            background: var(--card-bg); 
            border-radius: 24px; 
            border: 1px solid rgba(255,255,255,0.05); 
            overflow: hidden; 
            margin-top: 1rem;
        }
        .inbox-sidebar { 
            border-right: 1px solid rgba(255,255,255,0.05); 
            display: flex; 
            flex-direction: column; 
            height: 100%;
            overflow: hidden;
            background: rgba(15, 23, 42, 0.4);
        }
        .thread-view { 
            background: rgba(0,0,0,0.15); 
            display: flex; 
            flex-direction: column; 
            height: 100%;
            overflow: hidden;
        }
        
        .msg-item { 
            padding: 1.25rem 1.5rem; 
            border-bottom: 1px solid rgba(255,255,255,0.03); 
            cursor: pointer; 
            transition: all 0.2s; 
            border-left: 4px solid transparent;
        }
        .msg-item:hover { background: rgba(255,255,255,0.02); }
        .msg-item.active { background: rgba(56, 189, 248, 0.08); border-left-color: var(--accent); }
        .msg-item.unread { border-left-color: #38bdf8; background: rgba(56, 189, 248, 0.03); }
        
        .chat-bubble { max-width: 85%; margin-bottom: 1.5rem; }
        .bubble-inner { padding: 1.25rem; border-radius: 20px; font-size: 0.9rem; line-height: 1.6; }
        .left .bubble-inner { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.05); border-bottom-left-radius: 0; }
        .right .bubble-inner { background: linear-gradient(135deg, var(--accent) 0%, #0284c7 100%); color: #000; font-weight: 600; border-bottom-right-radius: 0; }

        @media (max-width: 900px) {
            .messenger-container {
                grid-template-columns: 1fr;
                height: auto;
                min-height: unset;
            }
            .inbox-sidebar {
                border-right: none;
                border-bottom: 1px solid rgba(255,255,255,0.05);
                max-height: 380px;
            }
            .thread-view {
                min-height: 480px;
            }
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="content-body" style="padding: 1.5rem;">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" style="margin-bottom: 1rem;"><?php echo h($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" style="margin-bottom: 1rem;"><?php echo h($error); ?></div>
                <?php endif; ?>

                <div class="messenger-container">
                    <!-- Inbox Sidebar -->
                    <div class="inbox-sidebar">
                        <div style="padding: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <h3 style="color: white; font-family: 'Outfit'; margin: 0; font-size: 1.2rem;">Canonical Inbox</h3>
                                <button type="button" class="btn btn-primary" onclick="showModal()" style="padding: 8px 14px; font-size: 0.8rem; display: flex; align-items: center; gap: 4px;">
                                    <ion-icon name="add-outline"></ion-icon> New
                                </button>
                            </div>
                            <div class="search-box" style="background: rgba(0,0,0,0.25); border-radius: 12px; padding: 10px 14px; display: flex; align-items: center; gap: 10px; border: 1px solid rgba(255,255,255,0.08);">
                                <ion-icon name="search-outline" style="color: var(--text-muted); font-size: 1.1rem;"></ion-icon>
                                <input type="text" id="thread-search" placeholder="Search threads..." oninput="filterThreads()" style="background: none; border: none; color: white; width: 100%; outline: none; font-size: 0.85rem;">
                            </div>
                        </div>

                        <div id="thread-list" style="flex: 1; overflow-y: auto;">
                            <?php if (empty($messages)): ?>
                                <div style="padding: 3rem 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                                    <ion-icon name="file-tray-outline" style="font-size: 2.5rem; margin-bottom: 0.5rem; opacity: 0.5;"></ion-icon>
                                    <p>No dispatches found in registry.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): 
                                    $is_current = ($active && $active['comm_id'] == $msg['comm_id']);
                                ?>
                                    <div class="msg-item <?php echo $is_current ? 'active' : ''; ?> <?php echo !$msg['is_read'] ? 'unread' : ''; ?>" onclick="loadThread(<?php echo $msg['comm_id']; ?>)">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                            <span style="color: var(--accent); font-size: 0.65rem; font-weight: 800; text-transform: uppercase;"><?php echo h($msg['type']); ?></span>
                                            <span style="font-size: 0.65rem; color: var(--text-muted);"><?php echo time_elapsed_string($msg['created_at']); ?></span>
                                        </div>
                                        <div style="color: white; font-weight: 700; margin-bottom: 4px; font-size: 0.9rem;"><?php echo h($msg['subject']); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo h($msg['message']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div id="no-threads-match" style="display: none; padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                                No threads match your query.
                            </div>
                        </div>
                    </div>

                    <!-- Thread View -->
                    <div class="thread-view">
                        <?php if (empty($active)): ?>
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0.4; padding: 2rem;">
                                <ion-icon name="mail-open-outline" style="font-size: 5rem; margin-bottom: 1rem; color: var(--accent);"></ion-icon>
                                <h4 style="color: white; margin-bottom: 0.5rem;">Select a dialogue to begin</h4>
                                <p style="color: var(--text-muted); font-size: 0.85rem;">Click a thread from the inbox or compose a new canonical dispatch.</p>
                            </div>
                        <?php else: ?>
                            <!-- Thread Header -->
                            <div style="padding: 1.25rem 1.5rem; background: var(--card-bg); border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <h4 style="color: white; font-family: 'Outfit'; margin: 0; font-size: 1.1rem;"><?php echo h($active['subject']); ?></h4>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 3px;">
                                        Ref: #<?php echo str_pad($active['comm_id'], 4, '0', STR_PAD_LEFT); ?> • 
                                        Category: <span style="color: var(--accent);"><?php echo h($active['type']); ?></span>
                                    </div>
                                </div>
                                <span class="status-pill <?php echo strtolower($active['status']); ?>"><?php echo h($active['status']); ?></span>
                            </div>

                            <!-- Messages Stream -->
                            <div style="flex: 1; overflow-y: auto; padding: 2rem;">
                                <!-- Originating Message -->
                                <div class="chat-bubble left">
                                    <div class="bubble-inner">
                                        <div style="font-size: 0.7rem; color: var(--accent); font-weight: 800; margin-bottom: 8px;">
                                            FROM: <?php echo h($active['origin_parish_name']); ?> (<?php echo h($active['sender_name']); ?>)
                                        </div>
                                        <?php echo nl2br(h($active['message'])); ?>
                                    </div>
                                    <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 8px;">
                                        <?php echo date('M d, Y H:i', strtotime($active['created_at'])); ?>
                                    </div>
                                </div>

                                <!-- Chancery Response -->
                                <?php if (!empty($active['admin_response'])): ?>
                                    <div class="chat-bubble right" style="margin-left: auto;">
                                        <div class="bubble-inner">
                                            <div style="font-size: 0.7rem; color: rgba(0,0,0,0.6); font-weight: 900; margin-bottom: 8px;">
                                                CHANCERY DISPATCH
                                            </div>
                                            <?php echo nl2br(h($active['admin_response'])); ?>
                                        </div>
                                        <div style="font-size: 0.65rem; color: var(--text-muted); text-align: right; margin-top: 8px;">
                                            <?php echo date('M d, Y H:i', strtotime($active['replied_at'] ?? $active['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Reply / Follow-up Box -->
                            <div style="padding: 1.25rem 1.5rem; background: var(--card-bg); border-top: 1px solid rgba(255,255,255,0.05);">
                                <form method="POST" action="communication_hub.php?thread=<?php echo $active['comm_id']; ?>" style="display: flex; gap: 1rem; align-items: center;">
                                    <input type="hidden" name="thread_id" value="<?php echo $active['comm_id']; ?>">
                                    <input type="text" name="reply_text" placeholder="<?php echo $is_admin ? 'Type Chancery response or resolution...' : 'Type a follow-up query...'; ?>" style="flex: 1; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 12px 15px; color: white; outline: none; font-size: 0.9rem;" required>
                                    <button type="submit" name="send_reply" class="btn btn-primary" style="padding: 12px 20px; white-space: nowrap; font-weight: 700;">
                                        <ion-icon name="paper-plane-outline"></ion-icon> <?php echo $is_admin ? 'Send Response' : 'Send Follow-up'; ?>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php include 'includes/privacy_footer.php'; ?>
            </div>
        </main>
    </div>

    <!-- New Canonical Inquiry Modal -->
    <div id="msg-modal" class="modal" onclick="if(event.target === this) hideModal();" style="display: none; pointer-events: none; opacity: 0; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(10px); padding: 1rem;">
        <div class="card modal-content" style="width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; padding: 2rem; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.7); position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 1rem;">
                <h3 style="color: white; font-family: 'Outfit'; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <ion-icon name="chatbubbles-outline" style="color: var(--accent);"></ion-icon>
                    New Canonical Inquiry
                </h3>
                <button type="button" onclick="hideModal()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.6rem; display: flex; align-items: center;" aria-label="Close modal">
                    <ion-icon name="close-outline"></ion-icon>
                </button>
            </div>
            <form method="POST" action="communication_hub.php">
                <div class="form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Subject</label>
                        <input type="text" name="subject" class="input-field" placeholder="Brief subject of inquiry..." style="width: 100%;" required>
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Recipient</label>
                        <select name="recipient_parish_id" class="input-field" style="width: 100%;">
                            <option value="">Diocesan Chancery</option>
                            <?php foreach($other_parishes as $p): ?>
                                <option value="<?php echo $p['parish_id']; ?>"><?php echo h($p['parish_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Category</label>
                        <select name="type" class="input-field" style="width: 100%;">
                            <option>Registry Query</option>
                            <option>Correction Request</option>
                            <option>Canonical Permission</option>
                            <option>Administrative</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Message Detail</label>
                        <textarea name="message" class="input-field" rows="5" placeholder="State the canonical facts, record dates, or request details..." style="width: 100%; resize: vertical;" required></textarea>
                    </div>
                </div>
                <div style="margin-top: 1.5rem; display: flex; gap: 10px;">
                    <button type="submit" name="send_message" class="btn btn-primary" style="flex: 2; padding: 12px; font-weight: 700;">Dispatch Message</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1; padding: 12px;" onclick="hideModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showModal() { 
            const modal = document.getElementById('msg-modal');
            if (modal) {
                modal.classList.add('active');
                modal.style.display = 'flex';
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
                document.body.style.overflow = 'hidden';
            }
        }
        function hideModal() { 
            const modal = document.getElementById('msg-modal');
            if (modal) {
                modal.classList.remove('active');
                modal.style.display = 'none';
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                document.body.style.overflow = '';
            }
        }
        function loadThread(id) { 
            window.location.href = 'communication_hub.php?thread=' + id;
        }
        function filterThreads() {
            const query = (document.getElementById('thread-search')?.value || '').toLowerCase().trim();
            const items = document.querySelectorAll('.msg-item');
            let visibleCount = 0;
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                const match = !query || text.includes(query);
                item.style.display = match ? 'block' : 'none';
                if (match) visibleCount++;
            });
            const noRes = document.getElementById('no-threads-match');
            if (noRes) noRes.style.display = (visibleCount === 0 && items.length > 0) ? 'block' : 'none';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') hideModal();
        });
    </script>
    <script src="assets/js/main.js?v=1.6.2"></script>
</body>
</html>
