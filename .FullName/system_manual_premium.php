<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Premium Unified User Manual - Version 3.0
 * Includes: Core Operations, Canonical Protocols, and Communication Hub
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if we are in "print mode" or "dashboard mode"
$is_print = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium User Manual - Hwange Diocese SRMS</title>
    
    <!-- Design Tokens -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <style>
        :root {
            --bg-main: #0f172a;
            --bg-card: rgba(30, 41, 59, 0.7);
            --accent: #38bdf8;
            --accent-glow: rgba(56, 189, 248, 0.3);
            --text-primary: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            line-height: 1.6;
            background-image: 
                radial-gradient(at 0% 0%, rgba(56, 189, 248, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(56, 189, 248, 0.05) 0px, transparent 50%);
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }

        /* Hero Header */
        header {
            text-align: center;
            margin-bottom: 5rem;
            position: relative;
        }

        .diocese-logo {
            font-family: 'Cinzel', serif;
            font-size: 1.2rem;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.2em;
            margin-bottom: 1rem;
            display: block;
        }

        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 3.5rem;
            font-weight: 900;
            background: linear-gradient(to bottom right, #fff, var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            line-height: 1.1;
        }

        .version-badge {
            display: inline-block;
            padding: 6px 16px;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid var(--accent);
            border-radius: 50px;
            color: var(--accent);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        /* Manual Content Sections */
        .section {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 3rem;
            margin-bottom: 3rem;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }

        .section:hover {
            border-color: rgba(56, 189, 248, 0.4);
            transform: translateY(-5px);
        }

        .section-icon {
            width: 56px;
            height: 56px;
            background: rgba(56, 189, 248, 0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
            box-shadow: 0 0 20px var(--accent-glow);
        }

        h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            margin: 2rem 0 1rem;
            color: var(--accent);
        }

        p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            font-size: 1.05rem;
        }

        ul, ol {
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
            color: var(--text-muted);
        }

        li { margin-bottom: 0.75rem; }

        strong { color: white; }

        code {
            background: rgba(0,0,0,0.3);
            padding: 2px 6px;
            border-radius: 4px;
            color: var(--accent);
            font-family: monospace;
        }

        /* Callout Boxes */
        .callout {
            background: rgba(56, 189, 248, 0.05);
            border-left: 4px solid var(--accent);
            border-radius: 0 16px 16px 0;
            padding: 1.5rem;
            margin: 2rem 0;
            display: flex;
            gap: 1.25rem;
        }

        .callout ion-icon {
            font-size: 1.8rem;
            color: var(--accent);
            flex-shrink: 0;
        }

        .callout-text {
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        .callout-title {
            display: block;
            font-weight: 700;
            color: white;
            margin-bottom: 4px;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        /* Action Buttons */
        .nav-controls {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            gap: 1rem;
            z-index: 100;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 50px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: var(--accent);
            color: #000;
            box-shadow: 0 10px 20px var(--accent-glow);
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 30px var(--accent-glow);
        }

        .btn-blur {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            color: white;
            border: 1px solid var(--border);
        }

        .btn-blur:hover {
            background: rgba(56, 189, 248, 0.1);
            border-color: var(--accent);
        }

        /* Print Overrides */
        @media print {
            body { background: white; color: #000; }
            .container { padding: 0; max-width: 100%; }
            .section { 
                background: none; border: none; padding: 0; margin-bottom: 4rem;
                backdrop-filter: none; color: #000;
                break-inside: avoid;
            }
            .section:hover { transform: none; }
            h1 { -webkit-text-fill-color: #000; background: none; font-size: 3rem; }
            h2, h3, strong { color: #000; }
            p, ul, ol, .callout-text { color: #334; }
            .section-icon, .nav-controls, .version-badge { display: none; }
            .callout { background: #f8fafc; border-color: #38bdf8; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>

    <div class="container">
        
        <header>
            <span class="diocese-logo">Hwange Diocese Registry</span>
            <h1>Operations & Protocols<br>System Manual</h1>
            <div class="version-badge">Version 3.0 • Unified Build</div>
        </header>

        <!-- Section 1: Authentication -->
        <div class="section">
            <div class="section-icon"><ion-icon name="shield-checkmark-outline"></ion-icon></div>
            <h2>1. System Access & Security</h2>
            <p>The Sacramental Records Management System (SRMS) is the definitive repository for diocesan canonical records. Security is our highest priority.</p>
            
            <h3>Signing In</h3>
            <ol>
                <li>Launch the local server using <code>LAUNCH_RMS.bat</code>.</li>
                <li>Navigate to <code>http://localhost:8000</code> in your browser.</li>
                <li>Enter your unique username and secure password.</li>
            </ol>

            <div class="callout">
                <ion-icon name="alert-circle-outline"></ion-icon>
                <div class="callout-text">
                    <span class="callout-title">Security Protocol</span>
                    If this is your first login, the system will force a password change. Choose a phrase known only to you that includes letters, numbers, and symbols.
                </div>
            </div>
        </div>

        <!-- Section 2: Navigation -->
        <div class="section">
            <div class="section-icon"><ion-icon name="compass-outline"></ion-icon></div>
            <h2>2. The Unified Dashboard</h2>
            <p>The dashboard is your "Command Center," providing real-time oversight of your jurisdiction.</p>
            
            <ul>
                <li><strong>Universal Search</strong>: Use the top search bar to find any parishioner by name or baptismal ID instantly.</li>
                <li><strong>Oversight Cards</strong>: High-level metrics showing total faithful, recent sacraments, and pending tasks.</li>
                <li><strong>Sidebar</strong>: Navigate between Parishes, Clergy, Sacraments, and the Communication Hub.</li>
            </ul>
        </div>

        <!-- Section 3: Canonical Handover -->
        <div class="section">
            <div class="section-icon"><ion-icon name="hand-right-outline"></ion-icon></div>
            <h2>3. The Canonical Handshake</h2>
            <p>To ensure registry integrity during priest reassignments, the system enforces a digital handover protocol.</p>
            
            <h3>Handover Steps:</h3>
            <ol>
                <li><strong>Outgoing Report</strong>: The departing priest logs his final status report in the <em>Handover Hub</em>.</li>
                <li><strong>Inventory Check</strong>: Physical registries must be verified against digital counts.</li>
                <li><strong>Succession Acceptance</strong>: The incoming priest reviews the report and digitally signs for the mission.</li>
            </ol>

            <div class="callout" style="border-left-color: #fbbf24;">
                <ion-icon name="warning-outline" style="color: #fbbf24;"></ion-icon>
                <div class="callout-text">
                    <span class="callout-title" style="color: #fbbf24;">Chancery Rule</span>
                    Access to the previous mission's records is transferred to the new priest immediately upon Chancery confirmation.
                </div>
            </div>
        </div>

        <div class="page-break"></div>

        <!-- Section 4: Communication Hub -->
        <div class="section">
            <div class="section-icon"><ion-icon name="chatbubbles-outline"></ion-icon></div>
            <h2>4. Communication Hub</h2>
            <p>This is the official digital channel for all canonical queries and registry adjustments between Missions and the Chancery.</p>
            
            <ul>
                <li><strong>Ticket System</strong>: Every request is assigned a tracking ID for accountability.</li>
                <li><strong>Notification Center</strong>: Real-time alerts when the Bishop or Chancellor responds to your query.</li>
                <li><strong>Archive</strong>: All past canonical correspondence is stored securely for future reference.</li>
            </ul>
        </div>

        <!-- Section 5: Troubleshooting -->
        <div class="section" style="border-color: rgba(239, 68, 68, 0.2);">
            <div class="section-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);"><ion-icon name="medkit-outline"></ion-icon></div>
            <h2>5. Support & Troubleshooting</h2>
            
            <h3>Common Issues:</h3>
            <ul>
                <li><strong>Forgotten Password</strong>: Use the "Forgot Password" link to trigger a reset through your registered email.</li>
                <li><strong>Account Lock</strong>: After 5 failed attempts, your account is locked for 15 minutes.</li>
                <li><strong>Sync Errors</strong>: If data seems outdated, click the "Refresh Cache" button in your Profile Settings.</li>
            </ul>

            <div class="callout" style="border-left-color: #10b981;">
                <ion-icon name="help-buoy-outline" style="color: #10b981;"></ion-icon>
                <div class="callout-text">
                    <span class="callout-title" style="color: #10b981;">Technical Support</span>
                    For urgent system failures, contact the Diocesan Information Office or use the Communication Hub with priority 'URGENT'.
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer style="text-align: center; color: var(--text-muted); font-size: 0.9rem; padding-top: 3rem; border-top: 1px solid var(--border);">
            <div style="margin-bottom: 1.5rem; font-style: italic; font-size: 0.8rem; line-height: 1.6; max-width: 600px; margin-left: auto; margin-right: auto; opacity: 0.8;">
                "Every person has the right to their own privacy." (Canon 220). Access to these archival records is restricted to authorized personnel. All view and edit actions are logged for canonical accountability.
            </div>
            &copy; <?php echo date('Y'); ?> Catholic Diocese of Hwange - Zimbabwe. All rights reserved.<br>
            <span style="font-family: 'Cinzel'; margin-top: 10px; display: block;">Ad Majorem Dei Gloriam</span>
        </footer>

    </div>

    <!-- Navigation Controls -->
    <div class="nav-controls">
        <button class="btn btn-blur" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
            <ion-icon name="arrow-up-outline"></ion-icon> Top
        </button>
        <button class="btn btn-primary" onclick="window.print()">
            <ion-icon name="print-outline"></ion-icon> Print / Save PDF
        </button>
    </div>

    <script src="assets/js/main.js?v=1.6.2"></script>
</body>
</html>
