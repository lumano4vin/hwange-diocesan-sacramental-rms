<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Unified Command Header Component
 */

// Helper: Calculate Global Pending Verifications for the Notification Bell
function get_global_pending_count() {
    $count = 0;
    if (in_array($_SESSION['role'], ['admin', 'chancellor', 'priest', 'deacon'])) {
        $params = [];
        $filter = get_parish_filter($params);
        $where = "WHERE status = 'Draft' " . $filter;
        
        $sql = "SELECT 
            (SELECT COUNT(*) FROM baptisms $where) as c1,
            (SELECT COUNT(*) FROM marriages $where) as c2,
            (SELECT COUNT(*) FROM confirmations $where) as c3,
            (SELECT COUNT(*) FROM deaths $where) as c4,
            (SELECT COUNT(*) FROM ordinations_professions $where) as c5
        ";
        $p_full = array_merge($params, $params, $params, $params, $params);
        $res = db_fetch($sql, $p_full);
        if ($res) {
            $count += array_sum($res);
        }
    }
    
    // Add Communication Hub notifications
    if ($_SESSION['role'] === 'admin') {
        $count += db_fetch("SELECT COUNT(*) as count FROM communications WHERE status = 'Pending'")['count'] ?? 0;
    } else {
        $count += db_fetch("SELECT COUNT(*) as count FROM communications WHERE (parish_id = ? AND admin_response IS NOT NULL AND is_read = 0) OR (recipient_parish_id = ? AND is_read = 0)", [$_SESSION['parish_id'], $_SESSION['parish_id']])['count'] ?? 0;
    }

    return $count;
}

$global_pending = get_global_pending_count();
$initials = strtoupper(substr($_SESSION['full_name'], 0, 1) . substr(strrchr($_SESSION['full_name'], " "), 1, 1));
if (strlen($initials) < 2) $initials = strtoupper(substr($_SESSION['full_name'], 0, 2));

// Default title if not provided
$header_title = $header_title ?? 'Diocesan Command Center';
$header_subtitle = $header_subtitle ?? 'Canonical registry status and oversight.';

$base = get_base_url();
?>

<header class="content-header">
    <div class="welcome-text" style="display: flex; align-items: center; gap: 1.5rem;">
        <button type="button" class="toggle-btn" id="sidebar-toggle" onclick="toggleMobileMenu()" title="Toggle Menu" style="margin-right: 0.5rem; background: rgba(56, 189, 248, 0.1); border: 1px solid var(--accent); color: var(--accent); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; cursor: pointer;">
            <ion-icon name="menu-outline"></ion-icon>
        </button>
        <img src="<?php echo $base . $DIOCESE_LOGO; ?>" alt="Diocese Logo" style="width: 50px; height: 50px; object-fit: contain; border-radius: 50%; background: white; padding: 3px; border: 2px solid var(--accent); box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
        <div>
            <h2 style="font-family: 'Outfit', sans-serif; letter-spacing: -0.5px;"><?php echo h($header_title); ?></h2>
            <p><?php echo h($header_subtitle); ?></p>
        </div>
    </div>

    <div class="header-right">
        
        <!-- Notification Center -->
        <div class="notification-center" style="display: flex; align-items: center; gap: 1rem;">
            
            <!-- Auto-Sync Indicator -->
            <div id="sync-indicator" title="Sync Status" style="font-size: 1.5rem; color: var(--text-muted); display: flex; align-items: center; justify-content: center;">
                <ion-icon name="cloud-done-outline"></ion-icon>
            </div>

            <button type="button" class="bell-btn <?php echo $global_pending > 0 ? 'pulse' : ''; ?>" id="bell-toggle" title="Pending Verifications">
                <ion-icon name="notifications-outline"></ion-icon>
                <?php if ($global_pending > 0): ?>
                    <span class="bell-badge"><?php echo $global_pending; ?></span>
                <?php endif; ?>
            </button>
            <div class="glass-dropdown" id="notification-dropdown">
                <div class="dropdown-header">
                    <h5>Notifications</h5>
                    <p>You have <?php echo $global_pending; ?> records awaiting verification.</p>
                </div>
                <?php if ($global_pending > 0): ?>
                    <a href="<?php echo $base; ?>sacraments.php" class="dropdown-link">
                        <ion-icon name="shield-checkmark-outline"></ion-icon>
                        Review Pending Records
                    </a>
                    
                    <?php
                    // Fetch recent unread communication
                    $comm_items = [];
                    if ($_SESSION['role'] === 'admin') {
                        $comm_items = db_fetchAll("SELECT subject, created_at FROM communications WHERE status = 'Pending' ORDER BY created_at DESC LIMIT 3");
                        $comm_url = $base . "admin/communications.php";
                    } else {
                        $comm_items = db_fetchAll("SELECT subject, created_at FROM communications WHERE (parish_id = ? AND admin_response IS NOT NULL AND is_read = 0) OR (recipient_parish_id = ? AND is_read = 0) ORDER BY created_at DESC LIMIT 3", [$_SESSION['parish_id'], $_SESSION['parish_id']]);
                        $comm_url = $base . "communication_hub.php";
                    }
                    ?>
                    <?php foreach ($comm_items as $ci): ?>
                        <a href="<?php echo $comm_url; ?>" class="dropdown-link" style="background: rgba(251, 191, 36, 0.05); border-left: 3px solid var(--accent);">
                            <ion-icon name="chatbubble-ellipses-outline" style="color: var(--accent);"></ion-icon>
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 0.75rem; font-weight: 700; color: white;"><?php echo h($ci['subject']); ?></span>
                                <span style="font-size: 0.6rem; color: var(--text-muted);"><?php echo $is_admin ? 'New Mission Query' : 'New Response from Chancery'; ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 1rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                        All records are verified. 🛡️
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- User Profile Command -->
        <div class="user-profile-menu">
            <div class="profile-trigger" id="profile-toggle">
                <div class="avatar-circle"><?php echo $initials; ?></div>
                <div class="user-info-brief">
                    <?php
                    $display_role = ucfirst($_SESSION['role']);
                    $context_subtitle = "Diocesan Chancery";
                    
                    if (in_array(strtolower($_SESSION['role']), ['priest', 'deacon', 'secretary']) && !empty($_SESSION['parish_id'])) {
                        $p_id = $_SESSION['parish_id'];
                        $p_name = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$p_id])['parish_name'] ?? 'Diocesan Chancery';
                        $context_subtitle = ($p_name !== 'Diocesan Chancery') ? $p_name . " Registry" : $p_name;
                    }
                    ?>
                    <span class="user-name-text"><?php echo h($_SESSION['full_name']); ?></span>
                    <span class="user-role-text"><?php echo $display_role; ?> • <?php echo h($context_subtitle); ?></span>
                </div>
                <ion-icon name="chevron-down-outline" style="color: var(--text-muted); font-size: 0.8rem;"></ion-icon>
            </div>
            
            <div class="glass-dropdown" id="profile-dropdown">
                <div class="dropdown-header">
                    <h5>Account Overview</h5>
                    <p><?php echo h($_SESSION['username']); ?></p>
                </div>
                
                <a href="<?php echo $base; ?>dashboard/profile.php" class="dropdown-link">
                    <ion-icon name="person-outline"></ion-icon>
                    My User Profile
                </a>
                
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chancellor'): ?>
                <a href="<?php echo $base; ?>admin/users_list.php" class="dropdown-link">
                    <ion-icon name="people-outline"></ion-icon>
                    Manage Staff
                </a>
                <?php endif; ?>
                
                <a href="<?php echo $base; ?>dashboard/2fa_setup.php" class="dropdown-link">
                    <ion-icon name="shield-lock-outline"></ion-icon>
                    Security Settings
                </a>
                
                <div class="dropdown-divider"></div>
                
                <a href="<?php echo $base; ?>auth/logout.php" class="dropdown-link logout">
                    <ion-icon name="log-out-outline"></ion-icon>
                    Sign Out
                </a>
            </div>
        </div>

        <!-- Additional Page-Specific Actions -->
        <?php echo $additional_header_actions ?? ''; ?>

        <!-- Quick Entry (Moved to header for consistency) -->
        <?php if (can_edit()): ?>
        <div class="dropdown-container">
            <button type="button" class="btn btn-primary" id="new-entry-btn">
                <ion-icon name="add-circle-outline"></ion-icon>
                New Entry
            </button>
            <div class="dropdown-menu" id="new-entry-dropdown">
                <a href="<?php echo $base; ?>sacraments/baptism_add.php" class="dropdown-item">New Baptism</a>
                <a href="<?php echo $base; ?>sacraments/marriage_add.php" class="dropdown-item">New Marriage</a>
                <a href="<?php echo $base; ?>sacraments/confirmation_add.php" class="dropdown-item">New Confirmation</a>
                <a href="<?php echo $base; ?>sacraments/burial_add.php" class="dropdown-item">New Death</a>
                <a href="<?php echo $base; ?>sacraments/reception_add.php" class="dropdown-item" style="border-top: 1px solid rgba(255,255,255,0.05); color: #8b5cf6;">New Reception / Profession</a>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/responsive.css?v=1.0">
    <script>
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('mobile-active');
            
            // Log for audit if needed
            console.log('Mobile menu toggled');
        }
    </script>
</header>

<!-- Flash Notifications (Global) -->
<div class="flash-container" style="padding: 0 2rem; margin-top: 1rem; position: relative; z-index: 50;">
    <?php display_flash(); ?>
</div>

<!-- Global Mission Command (GMC) Overlay -->
<div class="gmc-overlay" id="gmc-overlay">
    <div class="gmc-modal">
        <div class="gmc-header">
            <ion-icon name="flashlight-outline" style="font-size: 1.5rem; color: var(--accent);"></ion-icon>
            <input type="text" class="gmc-input" id="gmc-input" placeholder="Search parishes, parishioners, or commands..." autocomplete="off">
            <div style="background: rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 6px; font-size: 0.6rem; color: var(--text-muted); font-weight: 800;">ESC</div>
        </div>
        <div class="gmc-results" id="gmc-results">
            <!-- Results will be injected here -->
            <div style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                Type to search the Diocesan Archives...
            </div>
        </div>
        <div class="gmc-footer">
            <div style="display: flex; gap: 1.5rem;">
                <span><span class="gmc-key">??</span> Navigate</span>
                <span><span class="gmc-key">ENTER</span> Select</span>
            </div>
            <div>
                <span style="color: var(--accent); font-weight: 800;">DIOCESAN COMMAND CENTER</span>
            </div>
        </div>
    </div>
</div>

<!-- Auto-Sync Cloud Engine -->
<script src="<?php echo $base; ?>assets/js/autosync.js"></script>
