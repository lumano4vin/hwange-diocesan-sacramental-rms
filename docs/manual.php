<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Official User Manual - Print Optimized
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Manual - Hwange Diocese SRMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b;
            --accent: #38bdf8;
            --text: #334155;
        }
        body { font-family: 'Inter', sans-serif; line-height: 1.6; color: var(--text); padding: 4rem; max-width: 900px; margin: 0 auto; background: white; }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; color: var(--primary); }
        h1 { font-size: 3rem; border-bottom: 4px solid var(--accent); padding-bottom: 1rem; margin-bottom: 3rem; }
        h2 { font-size: 1.8rem; margin-top: 3rem; border-left: 6px solid var(--accent); padding-left: 1rem; }
        .feature-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin: 1.5rem 0; }
        .badge { display: inline-block; padding: 2px 8px; background: #e2e8f0; border-radius: 4px; font-size: 0.75rem; font-weight: 700; }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            button { display: none; }
            .page-break { page-break-before: always; }
        }
        
        .print-btn {
            position: fixed; top: 2rem; right: 2rem;
            padding: 1rem 2rem; background: var(--accent); color: white;
            border: none; border-radius: 50px; font-family: 'Outfit';
            font-weight: 700; cursor: pointer; box-shadow: 0 10px 15px rgba(56, 189, 248, 0.3);
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>

    <header style="text-align: center; margin-bottom: 5rem;">
        <h1 style="border: none; margin-bottom: 0;">User Operations Manual</h1>
        <p style="font-family: 'Cinzel'; font-size: 1.2rem; color: #64748b;">Hwange Diocesan Sacramental Records Management System</p>
        <p style="margin-top: 1rem;">Version 2.0 • Stable Build</p>
    </header>

    <h2>1. System Access & Authentication</h2>
    <p>The SRMS is a secure web-based application. To access the system, follow these steps:</p>
    <div class="feature-box">
        <ol>
            <li><strong>URL Access</strong>: Navigate to the system web address provided by the Chancery.</li>
            <li><strong>Login Credentials</strong>: Enter your assigned <strong>Username</strong> and <strong>Password</strong>.
                <ul>
                    <li><em>Initial Login</em>: If this is your first time logging in, you will be prompted to change your temporary password immediately for security.</li>
                </ul>
            </li>
            <li><strong>Session Security</strong>: The system will automatically log you out after 30 minutes of inactivity to protect sensitive diocesan data.</li>
        </ol>
    </div>

    <h2>2. Understanding Your Dashboard</h2>
    <p>Upon successful login, you will land on the <strong>Main Dashboard</strong>. Your view is determined by your canonical role:</p>
    <ul>
        <li><strong>Administrators</strong>: Full oversight of all parishes, clergy assignments, and system settings.</li>
        <li><strong>Parish Priests</strong>: Access to their specific mission's parishioners and sacramental hub.</li>
        <li><strong>Bishop/Chancery</strong>: High-level analytics and diocesan-wide mission transitions.</li>
    </ul>

    <h2>3. Navigation & Interface</h2>
    <p>The system uses a <strong>Unified Dashboard</strong> with a glassmorphism design language. Key navigational tools include:</p>
    <ul>
        <li><strong>Global Search (Alt+S)</strong>: Find any parishioner or registry entry from any page.</li>
        <li><strong>Sidebar Navigation</strong>: Quick access to Parishes, Clergy, Faithful, and the Sacramental Hub.</li>
        <li><strong>Oversight Cards</strong>: Quick-action cards at the top of the dashboard for high-priority tasks.</li>
    </ul>

    <h2>3. Bishop's Oversight Portal</h2>
    <div class="feature-box">
        <span class="badge">SECURE ACCESS</span>
        <p>Accessible only by the Bishop, Chancellor, and Administrators. This portal provides a high-level view of the entire Diocese:</p>
        <ul>
            <li><strong>Mission Analytics</strong>: View growth trajectories in Baptisms and Marriages.</li>
            <li><strong>Deanery Heatmaps</strong>: Identify active missions and pastoral needs.</li>
            <li><strong>Transition Monitor</strong>: Track active reassignments and parish handovers.</li>
        </ul>
    </div>

    <h2>4. Clergy Management & Assignments</h2>
    <p>Administrators can manage the movement of clergy through the Clergy Command Center. Use the <strong>Search Bar</strong> to find a cleric and the <strong>Reassign</strong> button to move them to a new mission.</p>
    <p><strong>Note:</strong> Reassigning a "Parish Priest" or "Mission Administrator" automatically triggers the Handover Protocol.</p>

    <div class="page-break"></div>

    <h2>5. The Canonical Handshake (Handover Protocol)</h2>
    <p>When a priest is reassigned, a digital handover process is initiated to ensure registry integrity:</p>
    <ol>
        <li><strong>Outgoing Sign-Off</strong>: The leaving priest must log into the <strong>Handover Hub</strong> and provide a status report on the registry.</li>
        <li><strong>Incoming Acceptance</strong>: The new priest reviews the notes and "Accepts" the mission.</li>
        <li><strong>Chancery Verification</strong>: The transition is marked as complete once both parties have signed off.</li>
    </ol>

    <h2>6. Sacramental Hub</h2>
    <p>The core of the system. Each sacrament (Baptism, Communion, Confirmation, Marriage) has its own dedicated registry:</p>
    <ul>
        <li><strong>Verification</strong>: Records must be verified to appear in official reports.</li>
        <li><strong>Certificates</strong>: One-click generation of canonical certificates.</li>
        <li><strong>Digital Scriptorium</strong>: Access to OCR-digitized historical archives.</li>
    </ul>

    <h2>7. Troubleshooting & Support</h2>
    <div class="feature-box" style="background: #fff1f2; border-color: #fecdd3;">
        <p><strong>Forgot Password?</strong> Click the "Forgot Password" link on the login page to initiate a recovery via your registered diocesan email.</p>
        <p><strong>Unresponsive Dashboard?</strong> If cards do not respond, perform a "Hard Refresh" by pressing <strong>Ctrl+F5</strong>.</p>
        <p><strong>Data Discrepancies?</strong> Please contact the Chancery IT department for canonical record adjustments that fall outside your parish jurisdiction.</p>
    </div>
    <footer style="margin-top: 5rem; border-top: 1px solid #e2e8f0; padding-top: 2rem; text-align: center; color: #64748b; font-size: 0.9rem;">
            <div class="brand-signature" style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <div class="lumsystems-diamond" style="width: 24px; height: 24px; position: relative; display: flex; align-items: center; justify-content: center;">
                     <div style="position: absolute; width: 100%; height: 100%; background: linear-gradient(135deg, #1e293b 0%, #334155 100%); clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);"></div>
                     <div style="position: absolute; width: 60%; height: 60%; background: #38bdf8; clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%); opacity: 0.8;"></div>
                </div>
                <div style="text-align: center; line-height: 1.1;">
                    <span style="font-weight: 900; font-family: 'Outfit'; letter-spacing: 2px; color: var(--primary); font-size: 1rem; display: block;">LUMSYSTEMS</span>
                    <span style="font-size: 0.5rem; color: var(--accent); letter-spacing: 1px; font-weight: 800; text-transform: uppercase; opacity: 0.8;">Honoring Legacy, Illuminating Excellence, Engineering the Future</span>
                </div>
            </div>
        <p style="margin: 0; font-weight: 600;">Copyright &copy; <?php echo date('Y'); ?> LumSystems. All rights reserved.</p>
        <span style="font-size: 0.75rem; opacity: 0.7;">System Architecture & Canonical Integrity Optimization</span>
    </footer>
</body>
</html>
