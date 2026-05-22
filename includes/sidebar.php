<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Common Sidebar Navigation Component
 */

// Determine the current page for active state highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Handle Security (Session auto-logout)
handle_session_timeout();

$base = get_base_url();
$is_admin = ($_SESSION['role'] === 'admin');
?>
<?php
require_once 'version.php';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <ion-icon name="bookmarks" class="logo-icon"></ion-icon>
            <span class="logo-text" style="font-size: 0.9rem; line-height: 1.3; font-weight: 800;"><?php echo get_diocese_branding(); ?> <br> <span style="font-size: 0.7rem; opacity: 0.8; font-weight: 600;">Sacramental Database System</span></span>
        </div>
    </div>

    <!-- Global Search Component -->
    <div class="sidebar-search">
        <div class="search-box">
            <ion-icon name="search-outline"></ion-icon>
            <input type="text" id="global-search-query" placeholder="Global search... (Alt+S)" autocomplete="off">
        </div>
        <div id="global-search-results" class="global-results-popup"></div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>dashboard/index.php">
                    <ion-icon name="grid-outline"></ion-icon>
                    <span>Main Dashboard</span>
                </a>
            </li>
            <?php if (in_array($_SESSION['role'], ['admin', 'chancellor', 'observer'])): ?>
            <li class="<?php echo $current_page == 'observer.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>dashboard/observer.php">
                    <ion-icon name="eye-outline" style="color: #10b981;"></ion-icon>
                    <span>Oversight Portal</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="<?php echo ($current_page == 'parishes.php' || $current_page == 'add_parish.php' || $current_page == 'edit_parish.php') ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>admin/parishes.php">
                    <ion-icon name="business-outline"></ion-icon>
                    <span>Parishes</span>
                </a>
            </li>
            <?php if ($is_admin): ?>
            <li class="<?php echo ($current_page == 'clergy_management.php') ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>admin/clergy_management.php">
                    <ion-icon name="ribbon-outline" style="color: var(--accent);"></ion-icon>
                    <span>Clergy Command</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="<?php echo ($current_page == 'parishioners.php' || $current_page == 'add_parishioner.php' || $current_page == 'edit_parishioner.php') ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>parishioners.php">
                    <ion-icon name="people-outline"></ion-icon>
                    <span>Parishioners</span>
                </a>
            </li>
            <li class="<?php echo (strpos($current_page, 'marriage_pni') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>sacraments/marriage_pni_list.php">
                    <ion-icon name="document-text-outline" style="color: #fbbf24;"></ion-icon>
                    <span>Marriage Prep (PNI)</span>
                </a>
            </li>
            
            <?php
            // Calculate Pending Count for Badge
            $badge_count = 0;
            if (in_array($_SESSION['role'], ['admin', 'chancellor', 'priest', 'deacon'])) {
                $p_params = [];
                $p_filter = get_parish_filter($p_params);
                $badge_count += db_fetch("SELECT COUNT(*) as count FROM baptisms WHERE status = 'Draft' $p_filter", $p_params)['count'] ?? 0;
                
                $p_params = [];
                $p_filter = get_parish_filter($p_params);
                $badge_count += db_fetch("SELECT COUNT(*) as count FROM marriages WHERE status = 'Draft' $p_filter", $p_params)['count'] ?? 0;
            }
            ?>
            <li class="<?php echo in_array($current_page, ['baptisms.php', 'confirmations.php', 'marriages.php', 'deaths.php', 'ordinations.php', 'sacraments.php']) ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>sacraments.php" style="position: relative;">
                    <ion-icon name="ribbon-outline"></ion-icon>
                    <span>Sacramental Hub</span>
                    <?php if ($badge_count > 0): ?>
                        <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: #ef4444; color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 10px; font-weight: 800; border: 2px solid #1e293b;">
                            <?php echo $badge_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>dashboard/reports.php">
                    <ion-icon name="stats-chart-outline"></ion-icon>
                    <span>Strategic Analytics</span>
                </a>
            </li>
            <li class="<?php echo $current_page == 'finance_submission.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>dashboard/finance_submission.php">
                    <ion-icon name="cash-outline" style="color: #10b981;"></ion-icon>
                    <span>Financial Remittance</span>
                </a>
            </li>
            <li class="<?php echo $current_page == 'parish_reports.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>dashboard/parish_reports.php">
                    <ion-icon name="document-text-outline" style="color: var(--accent);"></ion-icon>
                    <span>Parish Report Builder</span>
                </a>
            </li>

            <?php
            // Communication Hub Badge
            $comm_badge = 0;
            if ($is_admin) {
                $comm_badge = db_fetch("SELECT COUNT(*) as count FROM communications WHERE status = 'Pending'")['count'] ?? 0;
            } else {
                $comm_badge = db_fetch("SELECT COUNT(*) as count FROM communications WHERE (parish_id = ? AND admin_response IS NOT NULL AND is_read = 0) OR (recipient_parish_id = ? AND is_read = 0)", [$_SESSION['parish_id'], $_SESSION['parish_id']])['count'] ?? 0;
            }
            $comm_link = $is_admin ? $base . "admin/communications.php" : $base . "communication_hub.php";
            ?>
            <li class="<?php echo ($current_page == 'communications.php' || $current_page == 'communication_hub.php') ? 'active' : ''; ?>">
                <a href="<?php echo $comm_link; ?>" style="position: relative;">
                    <ion-icon name="chatbubbles-outline"></ion-icon>
                    <span>Communication Hub</span>
                    <?php if ($comm_badge > 0): ?>
                        <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: var(--accent); color: #000; font-size: 0.65rem; padding: 2px 6px; border-radius: 10px; font-weight: 800; border: 2px solid #1e293b;">
                            <?php echo $comm_badge; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>

            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chancellor'): ?>
            <li class="<?php echo $current_page == 'finance_overview.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>admin/finance_overview.php">
                    <ion-icon name="cash-outline" style="color: #10b981;"></ion-icon>
                    <span>Financial Stewardship</span>
                </a>
            </li>
            <li class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>admin/users_list.php">
                    <ion-icon name="shield-checkmark-outline"></ion-icon>
                    <span>Staff / Users</span>
                </a>
            </li>
            <li class="<?php echo $current_page == '2fa_setup.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>dashboard/2fa_setup.php">
                    <ion-icon name="shield-lock-outline"></ion-icon>
                    <span>Security Settings</span>
                </a>
            </li>
            <li class="<?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>dashboard/audit_logs.php">
                    <ion-icon name="list-outline"></ion-icon>
                    <span>Archival Audit</span>
                </a>
            </li>
            <li class="<?php echo $current_page == 'archive_ocr.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>dashboard/archive_ocr.php">
                    <ion-icon name="camera-outline"></ion-icon>
                    <span>Archives OCR Helper</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-divider"></li>
            <li class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>dashboard/settings.php">
                    <ion-icon name="settings-outline"></ion-icon>
                    <span>System Settings</span>
                </a>
            </li>
            <li class="<?php echo $current_page == 'manual.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base; ?>docs/manual.php">
                    <ion-icon name="help-circle-outline" style="color: var(--accent);"></ion-icon>
                    <span>User Manual / Help</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base; ?>auth/logout.php" class="logout-link">
                    <ion-icon name="log-out-outline"></ion-icon>
                    <span>Logout</span>
                </a>
            </li>
        </ul>

        <!-- Living Action Area -->
        <?php if (can_edit()): ?>
        <div style="margin-top: 2rem; padding: 0 0.5rem;">
            <a href="<?php echo $base; ?>sacraments/baptism_add.php" class="btn btn-primary" style="width: 100%; justify-content: flex-start; padding: 1rem; border-radius: 12px; background: linear-gradient(135deg, var(--accent) 0%, #d97706 100%); color: #000; box-shadow: 0 10px 20px rgba(251, 191, 36, 0.2); position: relative; overflow: hidden; font-weight: 800;">
                <ion-icon name="add-circle" class="heartbeat-icon" style="font-size: 1.5rem;"></ion-icon>
                <span style="margin-left: 8px;">Record Sacrament</span>
                <div style="position: absolute; right: -10px; top: -10px; opacity: 0.1; font-size: 4rem;">
                    <ion-icon name="ribbon"></ion-icon>
                </div>
            </a>
            <p style="font-size: 0.65rem; color: var(--text-muted); margin-top: 8px; text-align: center; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Preserving Hwange's Legacy</p>
        </div>
        <?php else: ?>
        <div style="margin-top: 2rem; padding: 0 0.5rem;">
            <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); text-align: center;">
                <ion-icon name="shield-checkmark-outline" style="font-size: 1.5rem; color: var(--accent); opacity: 0.5;"></ion-icon>
                <p style="font-size: 0.65rem; color: var(--text-muted); margin-top: 4px; font-weight: 700; text-transform: uppercase;">Canonical Oversight Mode</p>
            </div>
        </div>
        <?php endif; ?>
        <!-- System Attribution & Branding -->
        <div class="sidebar-footer" style="padding: 1.25rem 1rem; margin-top: auto; border-top: 1px solid rgba(255,255,255,0.05); background: rgba(15, 23, 42, 0.3); white-space: normal;">
            
            <div class="sidebar-footer-content">
                <!-- Living Digital Mission Creed -->
                <div class="mission-creed" style="margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px dashed rgba(255,255,255,0.1); text-align: center;">
                    <p style="font-size: 0.65rem; color: var(--accent); font-style: italic; line-height: 1.5; margin: 0; opacity: 0.8; font-weight: 500; white-space: normal;">
                        "A Database of Souls, A Network of Grace. Preserving the sacred heartbeat of Hwange through digital devotion."
                    </p>
                </div>

                <div class="brand-signature" style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem; text-align: center;">
                    <!-- 3D CSS Diamond Logo -->
                    <div class="lumsystems-diamond" style="width: 28px; height: 28px; position: relative; display: flex; align-items: center; justify-content: center; margin-bottom: 0.25rem; flex-shrink: 0;">
                         <div style="position: absolute; width: 100%; height: 100%; background: linear-gradient(135deg, #1e293b 0%, #334155 100%); clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%); border: 1px solid rgba(56, 189, 248, 0.3);"></div>
                         <div style="position: absolute; width: 60%; height: 60%; background: #38bdf8; clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%); opacity: 0.8; box-shadow: 0 0 10px rgba(56, 189, 248, 0.4);"></div>
                    </div>

                <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 0.5rem;">
                    <span style="font-weight: 900; font-family: 'Outfit'; letter-spacing: 2px; color: white; font-size: 0.85rem; display: block; line-height: 1.2;">LUMSYSTEMS</span>
                    <span style="font-size: 0.5rem; color: var(--accent); letter-spacing: 0.5px; font-weight: 700; text-transform: uppercase; opacity: 0.9; display: block; line-height: 1.4; white-space: normal; max-width: 180px; margin: 0 auto;">Honoring Legacy, Illuminating Excellence, Engineering the Future</span>
                </div>
                </div>

                <div style="margin-top: 1.25rem; text-align: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1rem;">
                    <p style="font-size: 0.65rem; color: var(--accent); margin: 0 0 5px 0; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Authorized</p>
                    <p style="font-size: 0.6rem; color: var(--text-muted); margin: 0; font-weight: 600; line-height: 1.4;">Licensed for the Catholic Diocese of Hwange. <br> &copy; 2026 LumSystems Architecture.</p>
                    <div style="margin-top: 0.6rem; display: flex; justify-content: center; gap: 0.4rem; opacity: 0.7;">
                        <span style="font-size: 0.55rem; color: var(--text-muted); font-weight: 500;">v<?php echo APP_VERSION; ?></span>
                        <span style="font-size: 0.55rem; color: var(--text-muted); opacity: 0.4;">•</span>
                        <span style="font-size: 0.55rem; color: var(--text-muted); font-weight: 500;"><?php echo APP_RELEASE_DATE; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'privacy_footer.php'; ?>
    </nav>
</aside>
