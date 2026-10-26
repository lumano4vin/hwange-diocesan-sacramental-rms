<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * User Manual & Help Center
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';

require_login();

$header_title = "User Manual & Help";
$header_subtitle = "Official guide for the Catholic Diocese of Hwange Sacramental & Temporal RMS.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Manual - <?php echo get_diocese_branding(); ?> RMS</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.6">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">

        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">

            <?php include 'includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0; max-width: 920px; margin: 0 auto;">

                <!-- Hero Banner -->
                <div style="background: linear-gradient(135deg, rgba(56, 189, 248, 0.12) 0%, rgba(15,23,42,0.85) 100%); border: 1px solid rgba(56, 189, 248, 0.25); border-radius: 1.5rem; padding: 2.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1.5rem;">
                        <div style="font-size: 3.5rem; color: var(--accent); flex-shrink: 0;">
                            <ion-icon name="book-outline"></ion-icon>
                        </div>
                        <div>
                            <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; color: white; margin-bottom: 0.5rem;">Hwange Diocesan Pastoral & Sacramental RMS</h1>
                            <p style="color: var(--text-muted); line-height: 1.6; margin: 0; max-width: 580px;">Official operations guide for Parish Priests, Mission Administrators, Chancellors, and Bookkeepers across the Diocese of Hwange.</p>
                        </div>
                    </div>
                    <div>
                        <a href="docs/manual.php" target="_blank" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; padding: 0.8rem 1.4rem;">
                            <ion-icon name="print-outline"></ion-icon> Printable PDF Manual
                        </a>
                    </div>
                </div>

                <!-- Section 1: Accessing the System & Roles -->
                <div class="card bg-card" style="margin-bottom: 1.5rem; border-radius: 1.25rem;">
                    <div class="card-header" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem 1.5rem 1rem;">
                        <div style="width: 40px; height: 40px; background: rgba(56, 189, 248, 0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #38bdf8; font-size: 1.25rem; flex-shrink: 0;">
                            <ion-icon name="log-in-outline"></ion-icon>
                        </div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: white; margin: 0;">1. System Access & User Roles</h2>
                    </div>
                    <div class="card-body" style="padding: 0 1.5rem 1.5rem;">
                        <h4 style="color: white; margin-bottom: 0.75rem; font-size: 0.95rem;">How to Sign In</h4>
                        <ol style="color: var(--text-muted); padding-left: 1.5rem; line-height: 2;">
                            <li>Navigate to the system web address (e.g. <code style="background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 4px; color: #38bdf8; font-size: 0.85rem;">http://localhost:8000</code> or your cloud deployment URL).</li>
                            <li>Enter your assigned <strong style="color: white;">Username</strong> and <strong style="color: white;">Password</strong>.</li>
                            <li>The Bishop of Hwange (<strong style="color: white;">Rt. Rev. Raphael Mabuza Ncube</strong>) is recognized and greeted dynamically by name under the username <code style="color: var(--accent);">bishop_hwange</code>.</li>
                        </ol>

                        <h4 style="color: white; margin: 1.25rem 0 0.5rem; font-size: 0.95rem;">Canonical Roles & Protections</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-top: 0.75rem;">
                            <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);">
                                <strong style="color: #38bdf8; display: block; margin-bottom: 4px;">Bishop / Chancellor</strong>
                                <span style="color: var(--text-muted); font-size: 0.85rem;">Diocesan-wide canonical authority, audit logs, chancery financial locks, episcopal visitations.</span>
                            </div>
                            <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);">
                                <strong style="color: #10b981; display: block; margin-bottom: 4px;">Parish Priest / Pastor</strong>
                                <span style="color: var(--text-muted); font-size: 0.85rem;">Sacraments, Mass intentions, parishioners, daily cash ledger, monthly returns, temporal assets, handover hub.</span>
                            </div>
                            <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);">
                                <strong style="color: #fbbf24; display: block; margin-bottom: 4px;">Bookkeeper / Treasurer</strong>
                                <span style="color: var(--text-muted); font-size: 0.85rem;">Dedicated financial operations. Strictly restricted from private sacramental and matrimonial registers.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Financial Stewardship & 23-Line Return -->
                <div class="card bg-card" style="margin-bottom: 1.5rem; border-radius: 1.25rem;">
                    <div class="card-header" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem 1.5rem 1rem;">
                        <div style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 1.25rem; flex-shrink: 0;">
                            <ion-icon name="cash-outline"></ion-icon>
                        </div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: white; margin: 0;">2. Financial Stewardship & 23-Line Cashbook</h2>
                    </div>
                    <div class="card-body" style="padding: 0 1.5rem 1.5rem;">
                        <p style="color: var(--text-muted); margin-bottom: 1rem;">The financial module bridges daily parochial bookkeeping with the official Roman Catholic Diocese of Hwange financial format.</p>
                        
                        <h4 style="color: white; margin-bottom: 0.5rem; font-size: 0.95rem;">Daily Cash & Bank Ledger (`dashboard/daily_ledger.php`)</h4>
                        <ul style="color: var(--text-muted); padding-left: 1.5rem; line-height: 2;">
                            <li><strong style="color: white;">Dual Accounts:</strong> Log transactions into <span style="color: #38bdf8;">Cash at Hand</span> (parish safe/cash box) or <span style="color: #10b981;">Cash in Bank</span> (commercial bank account).</li>
                            <li><strong style="color: white;">Line 1 Tithe / Unsupo / Mutulo:</strong> Captures faithful tithes in indigenous and English traditions.</li>
                            <li><strong style="color: white;">Annual Diocesan Special Collections:</strong> Segregates pass-through collections (Mission Sunday, Seminary, Peter's Pence, Holy Land, Missionary Childhood, Social Communications, Lenten Campaign, Caritas) from parish operational income.</li>
                            <li><strong style="color: white;">Receipt & Voucher Uploads:</strong> Attach PDF vouchers, bank deposit slips, or camera photos up to 12MB per transaction.</li>
                            <li><strong style="color: white;">Outstation Attribution:</strong> Tag collections to specific rural outstations.</li>
                        </ul>

                        <h4 style="color: white; margin: 1.25rem 0 0.5rem; font-size: 0.95rem;">Monthly Return & Chancery Lock (`dashboard/finance_submission.php`)</h4>
                        <p style="color: var(--text-muted); margin-bottom: 0.75rem;">Click <strong style="color: var(--accent);">"Sync from Daily Ledger"</strong> to instantly aggregate the month's receipts and disbursements into the 23 lines. When audited and marked as <span style="color: #10b981; font-weight: 700;">Verified</span> by the Chancery, returns are protected with an anti-tamper lock.</p>
                    </div>
                </div>

                <!-- Section 3: Parish Temporal Goods & Assets (Canon 1283 §2) -->
                <div class="card bg-card" style="margin-bottom: 1.5rem; border-radius: 1.25rem;">
                    <div class="card-header" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem 1.5rem 1rem;">
                        <div style="width: 40px; height: 40px; background: rgba(245, 158, 11, 0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #f59e0b; font-size: 1.25rem; flex-shrink: 0;">
                            <ion-icon name="cube-outline"></ion-icon>
                        </div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: white; margin: 0;">3. Temporal Goods & Asset Inventory (Canon 1283 §2)</h2>
                    </div>
                    <div class="card-body" style="padding: 0 1.5rem 1.5rem;">
                        <p style="color: var(--text-muted); margin-bottom: 1rem;">Located at <code style="color: #f59e0b;">dashboard/assets.php</code>, this register fulfills the canonical obligation to maintain a signed inventory of parish property:</p>
                        <ul style="color: var(--text-muted); padding-left: 1.5rem; line-height: 2;">
                            <li><strong style="color: white;">Sacred Vessels & Vestments:</strong> Chalices, ciboria, monstrances, reliquaries with dedicated reverence badges.</li>
                            <li><strong style="color: white;">Vehicles & Transport:</strong> Land Cruisers, 4x4 trucks, tractors, motorbikes with registration and engine numbers.</li>
                            <li><strong style="color: white;">Mission Infrastructure:</strong> Deep boreholes, solar pumping arrays, water storage tanks, buildings.</li>
                            <li><strong style="color: white;">Handover Inventory Export:</strong> 1-click printable PDF inventory for priest transfers and episcopal visitations.</li>
                        </ul>
                    </div>
                </div>

                <!-- Section 4: Outstations Directory & Mass Intentions -->
                <div class="card bg-card" style="margin-bottom: 1.5rem; border-radius: 1.25rem;">
                    <div class="card-header" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem 1.5rem 1rem;">
                        <div style="width: 40px; height: 40px; background: rgba(168, 85, 247, 0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #a855f7; font-size: 1.25rem; flex-shrink: 0;">
                            <ion-icon name="navigate-outline"></ion-icon>
                        </div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: white; margin: 0;">4. Mission Outstations & Canonical Mass Intentions</h2>
                    </div>
                    <div class="card-body" style="padding: 0 1.5rem 1.5rem;">
                        <h4 style="color: white; margin-bottom: 0.5rem; font-size: 0.95rem;">Outstations & Rural Centres (`dashboard/outstations.php`)</h4>
                        <p style="color: var(--text-muted); margin-bottom: 1rem;">Maintains the geographic network of rural mission stations. Records distance in kilometers, Head Catechist phone numbers, Parish Council Chairperson contacts, Mass celebration frequency, and Catholic household counts.</p>

                        <h4 style="color: white; margin-bottom: 0.5rem; font-size: 0.95rem;">Mass Intentions & Stipends Book (`dashboard/mass_intentions.php`)</h4>
                        <p style="color: var(--text-muted); margin-bottom: 0.75rem;">Fulfills Canon 958. Classifies intentions (Repose of Soul / Deceased, Thanksgiving, Healing / Sick, Special, and <em>Pro Populo</em>). Tracks celebrant priests and stipend offerings, and provides 1-click status updates for sacristy management.</p>
                    </div>
                </div>

                <!-- Section 5: Data Vault & Support -->
                <div class="card bg-card" style="margin-bottom: 1.5rem; border-radius: 1.25rem;">
                    <div class="card-header" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem 1.5rem 1rem;">
                        <div style="width: 40px; height: 40px; background: rgba(56, 189, 248, 0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #38bdf8; font-size: 1.25rem; flex-shrink: 0;">
                            <ion-icon name="server-outline"></ion-icon>
                        </div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: white; margin: 0;">5. Data Vault & Diocesan Support</h2>
                    </div>
                    <div class="card-body" style="padding: 0 1.5rem 1.5rem;">
                        <p style="color: var(--text-muted); margin-bottom: 1rem;">The <strong style="color: white;">Data Vault</strong> (<code>dashboard/backup_vault.php</code>) enables one-click CSV spreadsheet exports for all sacramental and temporal records. The encrypted GitHub Cloud Vault guarantees zero record loss across serverless deployments.</p>
                        
                        <div style="display: flex; gap: 1rem; margin-top: 1.25rem; flex-wrap: wrap;">
                            <a href="communication_hub.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                                <ion-icon name="chatbubbles-outline"></ion-icon> Open Communication Hub
                            </a>
                            <a href="docs/manual.php" target="_blank" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
                                <ion-icon name="document-text-outline"></ion-icon> View Full Print Manual
                            </a>
                        </div>
                    </div>
                </div>

                <?php include 'includes/privacy_footer.php'; ?>

            </div>

        </main>
    </div>

    <script src="assets/js/main.js?v=1.6.2"></script>
</body>
</html>
