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
            --accent: #0284c7;
            --accent-gold: #b45309;
            --text: #334155;
        }
        body { font-family: 'Inter', sans-serif; line-height: 1.6; color: var(--text); padding: 4rem; max-width: 960px; margin: 0 auto; background: white; }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; color: var(--primary); }
        h1 { font-size: 2.8rem; border-bottom: 4px solid var(--accent); padding-bottom: 1rem; margin-bottom: 2rem; }
        h2 { font-size: 1.6rem; margin-top: 2.5rem; border-left: 6px solid var(--accent); padding-left: 1rem; }
        h3 { font-size: 1.2rem; color: #0f172a; margin-top: 1.5rem; }
        .feature-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin: 1.25rem 0; }
        .badge { display: inline-block; padding: 2px 8px; background: #e2e8f0; border-radius: 4px; font-size: 0.75rem; font-weight: 700; }
        .badge-gold { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-green { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.9rem; }
        th, td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; }
        th { background: #f1f5f9; color: var(--primary); font-weight: 700; }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            button { display: none; }
            .page-break { page-break-before: always; }
        }
        
        .print-btn {
            position: fixed; top: 2rem; right: 2rem;
            padding: 0.9rem 1.8rem; background: var(--accent); color: white;
            border: none; border-radius: 50px; font-family: 'Outfit';
            font-weight: 700; cursor: pointer; box-shadow: 0 10px 15px rgba(2, 132, 199, 0.3);
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Print / Save as PDF</button>

    <header style="text-align: center; margin-bottom: 4rem;">
        <h1 style="border: none; margin-bottom: 0.5rem;">User & Pastoral Operations Manual</h1>
        <p style="font-family: 'Cinzel'; font-size: 1.2rem; color: #64748b;">Catholic Diocese of Hwange — Sacramental & Temporal Records Management System</p>
        <p style="margin-top: 0.5rem; font-weight: 600; color: #0284c7;">Comprehensive Diocesan Edition • Version 4.0</p>
    </header>

    <h2>1. System Access & Authentication</h2>
    <p>The RMS is a secure web-based application designed for diocesan oversight and mission record-keeping:</p>
    <div class="feature-box">
        <ol>
            <li><strong>Web URL</strong>: Access the system via your assigned address (`http://localhost:8000` or diocesan cloud URL).</li>
            <li><strong>Login Credentials</strong>: Enter your assigned username (e.g. surname, parish code, or <code>bishop_hwange</code>).</li>
            <li><strong>Episcopal Recognition</strong>: The Bishop of Hwange (<strong>Rt. Rev. Raphael Mabuza Ncube</strong>) is dynamically recognized and greeted by name upon entering and leaving the system.</li>
            <li><strong>Security Timeout</strong>: For data protection, sessions expire after inactivity to safeguard canonical and financial registers.</li>
        </ol>
    </div>

    <h2>2. User Roles & Permission Safeguards</h2>
    <table>
        <thead>
            <tr>
                <th>Canonical Role</th>
                <th>Accessible Modules</th>
                <th>Strictly Restricted Areas</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Bishop / Chancellor</strong></td>
                <td>Diocesan-wide oversight, Episcopal visitations, audit logs, chancery financial audits, user management.</td>
                <td>None.</td>
            </tr>
            <tr>
                <td><strong>Parish Priest / Mission Admin</strong></td>
                <td>Sacramental Hub, Parishioners, Daily Ledger, Monthly Returns, Temporal Goods, Outstations, Mass Intentions, Handover Hub.</td>
                <td>Other parishes' private records.</td>
            </tr>
            <tr>
                <td><strong>Bookkeeper / Treasurer</strong></td>
                <td>Daily Ledger, Monthly Financial Returns, Financial Reports, and Data Vault.</td>
                <td><span class="badge" style="background: #fee2e2; color: #991b1b;">CONFIDENTIAL</span> Locked out of Baptisms, Marriage PNI, RCIA, and Letters of Standing.</td>
            </tr>
            <tr>
                <td><strong>Deacon / Parish Secretary</strong></td>
                <td>Sacramental data entry (Drafts), Letters of Standing, Parishioner directory.</td>
                <td>Chancery audit verification, financial ledger modifications.</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>3. Sacramental Hub & Official Heirloom Certificates</h2>
    <p>The core of the system protects and digitizes the sacred sacramental registers of the Church:</p>
    <ul>
        <li><strong>Holy Baptism (Canon 877)</strong>: Primary canonical record containing birth dates, parents, sponsors, minister, and notation margins for Confirmation, Marriage, and Holy Orders.</li>
        <li><strong>Sacrament of Confirmation (Canon 895)</strong>: Records confirming bishop/minister, sponsor, and date.</li>
        <li><strong>Holy Matrimony & Prenuptial Investigation (Canons 1067 & 1122)</strong>: Complete PNI investigation questionnaire, banns publication, canonical dispensations, and marriage certificate records.</li>
        <li><strong>Funerals & Deaths (Canon 1182)</strong>: Christian burial records with cemetery and presiding minister.</li>
        <li><strong>Letters of Standing</strong>: Immediate generation of official canonical suitability certificates for godparents, sponsors, and out-of-parish weddings.</li>
        <li><strong>Dynamic QR Code Verification</strong>: Issued certificates carry an encrypted verification QR code for instant chancery validation.</li>
    </ul>

    <h2>4. Financial Stewardship & 23-Line Diocesan Cashbook</h2>
    <p>Configured specifically to match the official financial return format used across the Catholic Diocese of Hwange:</p>
    
    <div class="feature-box">
        <h3>4.1 Daily Cash & Bank Ledger (<code>dashboard/daily_ledger.php</code>)</h3>
        <ul>
            <li><strong>Cash at Hand vs. Cash in Bank</strong>: Separate balance journals for parish cash boxes and commercial bank accounts (Stanbic, CBZ, etc.).</li>
            <li><strong>Line 1 Tithe / Unsupo / Mutulo</strong>: Dedicated tracking for parishioners' monthly tithes.</li>
            <li><strong>Parish Collections vs. Diocesan Collections</strong>: Parish/Mission Sunday collections remain local income, while annual diocesan collections are segregated.</li>
            <li><strong>Annual Diocesan Special Collections (Pass-Through)</strong>: Tracks Mission Sunday, Seminary Collection, Peter's Pence, Holy Land (Good Friday), Missionary Childhood, Social Communications, Lenten Campaign, and Caritas collections.</li>
            <li><strong>Voucher & Receipt Uploads</strong>: Attach digital photos or PDF vouchers (up to 12MB) per transaction for audit compliance.</li>
            <li><strong>Outstation Attribution</strong>: Tag income lines to the rural outstation where the funds were collected.</li>
        </ul>

        <h3>4.2 Monthly Financial Return & Chancery Audit Lock (<code>dashboard/finance_submission.php</code>)</h3>
        <ul>
            <li><strong>1-Click Sync</strong>: Automatically aggregates all daily ledger transactions for the month into the 23 official lines.</li>
            <li><strong>Multi-Currency</strong>: Supports USD ($), ZiG, and ZAR (R) reporting.</li>
            <li><strong>Chancery Audit Lock</strong>: Once verified by the Diocesan Finance Office (status <em>Verified</em>), the monthly return is canonically sealed against further edits while preserving audit logs.</li>
            <li><strong>Official Export</strong>: Export verified monthly cashbooks into Microsoft Word (.docx) or CSV.</li>
        </ul>
    </div>

    <div class="page-break"></div>

    <h2>5. Parish Temporal Goods & Asset Inventory (Canon 1283 §2)</h2>
    <p>Canon law requires that parish administrators maintain a detailed, signed inventory of all ecclesiastical property:</p>
    <div class="feature-box">
        <ul>
            <li><strong>Sacred Vessels & Vestments</strong>: Chalices, ciboria, monstrances, reliquaries, and vestments receive priority reverence badges.</li>
            <li><strong>Mission Vehicles & Transport</strong>: Tracks parish vehicles, off-road 4x4s, motorbikes, and tractors with registration, engine numbers, and mileage.</li>
            <li><strong>Solar & Boreholes</strong>: Vital rural infrastructure including solar panel capacity, inverters, deep-well borehole pumps, and water tanks.</li>
            <li><strong>Condition Monitoring</strong>: Records physical condition (Good, Fair, Poor, Under Repair, Condemned) and estimated replacement valuations.</li>
            <li><strong>Photo Documentation</strong>: Upload asset images to accompany handover inspections.</li>
            <li><strong>Handover Inventory Export</strong>: Print-ready inventory sheet for incoming and outgoing parish priests.</li>
        </ul>
    </div>

    <h2>6. Mission Outstations & Rural Mass Centres Directory</h2>
    <p>Manages the extensive rural outreach networks across Hwange Diocese (e.g. Binga, Dete, Fatima, Jambezi, Lupane, Kamativi, St. Augustine):</p>
    <ul>
        <li><strong>Distance from Mission Centre</strong>: Records road kilometers from the main mission house.</li>
        <li><strong>Pastoral Directory</strong>: Direct telephone and WhatsApp contacts for the Head Catechist and Parish Pastoral Council Chairperson.</li>
        <li><strong>Liturgical Schedule</strong>: Frequency of Holy Mass (e.g. 1st Sunday of month, bi-weekly).</li>
        <li><strong>Household Census</strong>: Catholic family counts for pastoral planning and episcopal visits.</li>
    </ul>

    <h2>7. Canonical Mass Intentions & Stipends Book (Canons 945–958)</h2>
    <p>Fulfills the sacred canonical duty to account for all Mass offerings and intention fulfillments:</p>
    <div class="feature-box">
        <ul>
            <li><strong>Intention Classifications</strong>: Repose of Soul / Deceased, Thanksgiving, Healing / Sick, Special Intention, and <em>Pro Populo</em> (obligatory Pastor's Mass for the People).</li>
            <li><strong>Stipend Accounting</strong>: Tracks stipend amounts and donor contact information.</li>
            <li><strong>Celebrant Assignment</strong>: Assigns Masses to parish priests, assistants, or visiting clergy.</li>
            <li><strong>1-Click Status Toggling</strong>: Mark intentions as <em>Pending</em>, <em>Celebrated</em> (with celebration date), or <em>Transferred</em> to Chancery.</li>
            <li><strong>Sacristy Printout</strong>: Produces an official daily and weekly schedule for the sacristy noticeboard.</li>
        </ul>
    </div>

    <h2>8. Canonical Handover Protocol (Handshake Hub)</h2>
    <p>Canon 535 §5 requires an orderly transfer of pastoral and temporal responsibilities upon priest reassignment:</p>
    <ol>
        <li><strong>Outgoing Sign-Off</strong>: Outgoing priest verifies register totals, temporal asset counts, and bank balances.</li>
        <li><strong>Incoming Acceptance</strong>: The new pastor reviews records on-site and accepts pastoral governance.</li>
        <li><strong>Chancery Archival Certification</strong>: The Chancellor validates and archives the transition digitally.</li>
    </ol>

    <h2>9. Data Vault, Backups & Disaster Recovery</h2>
    <p>Located at <code>dashboard/backup_vault.php</code>, providing comprehensive data preservation:</p>
    <ul>
        <li><strong>Instant CSV Exports</strong>: 1-click download of Baptisms, Marriages, Confirmations, Parishioners, Daily Ledger, Monthly Returns, Temporal Goods, Outstations, and Mass Intentions.</li>
        <li><strong>Encrypted Cloud Vault</strong>: Automated synchronization with the secure GitHub Cloud Vault to ensure database survival across serverless redeployments.</li>
    </ul>

    <h2>10. Troubleshooting & Technical Support</h2>
    <div class="feature-box" style="background: #fff1f2; border-color: #fecdd3;">
        <p><strong>Forgotten Password?</strong> Click "Forgot Password?" on the login page to receive a recovery token via your registered diocesan email.</p>
        <p><strong>Screen Display Issues?</strong> Press <strong>Ctrl + F5</strong> to perform a hard refresh and load updated styles.</p>
        <p><strong>Official Inquiries?</strong> Use the in-app <strong>Communication Hub</strong> to message the Diocesan Chancery Office directly.</p>
    </div>

    <footer style="margin-top: 4rem; border-top: 1px solid #e2e8f0; padding-top: 2rem; text-align: center; color: #64748b; font-size: 0.85rem;">
        <p style="font-weight: 700; color: #1e293b; margin-bottom: 4px;">CATHOLIC DIOCESE OF HWANGE — RECORDS MANAGEMENT SYSTEM</p>
        <p style="margin: 0;">Preserving Heritage • Ensuring Canonical Integrity • Enabling Modern Mission Governance</p>
        <p style="margin-top: 8px; font-size: 0.75rem;">Copyright &copy; <?php echo date('Y'); ?> LumSystems & Diocese of Hwange. All rights reserved.</p>
    </footer>
</body>
</html>
