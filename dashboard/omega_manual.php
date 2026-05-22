<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OMEGA User Manual - Diocese of Hwange</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=Outfit:wght@300;600;900&display=swap" rel="stylesheet">
    <style>
        :root { --accent: #b91c1c; --bg: #0f172a; }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; padding: 40px; }
        .manual-canvas { 
            background: white; 
            max-width: 900px; 
            margin: 0 auto; 
            padding: 50px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            border-radius: 8px;
            color: #1e293b;
            line-height: 1.6;
        }
        h1, h2, h3 { font-family: 'Outfit'; color: #0f172a; }
        h1 { border-bottom: 4px solid var(--accent); padding-bottom: 10px; margin-bottom: 30px; }
        h2 { margin-top: 30px; color: var(--accent); font-size: 1.4rem; }
        .btn-export { 
            display: block; width: 100%; max-width: 900px; margin: 20px auto; 
            padding: 15px; background: var(--accent); color: white; border: none; 
            border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 1rem;
            text-align: center; text-decoration: none;
        }
        .btn-export:hover { background: #991b1b; }
        ul { padding-left: 20px; }
        li { margin-bottom: 10px; }
        .seal-wrapper { display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; height: 90px; width: 90px; border-radius: 50%; overflow: hidden; border: 2px solid #e2e8f0; box-shadow: 0 5px 15px rgba(0,0,0,0.2); background: #fff; }
        .seal-img { height: auto; width: 100%; object-fit: contain; transform: scale(1.05); }
    </style>
</head>
<body>

    <button onclick="exportManualPDF()" class="btn-export">Download This Manual as PDF</button>

    <div class="manual-canvas" id="manual-doc">
        <div class="seal-wrapper">
            <img src="../assets/img/seal.png" class="seal-img" alt="Diocese Seal">
        </div>
        <div style="text-align:center; color:#64748b; font-size:0.8rem; font-weight:800; letter-spacing:2px; margin-bottom:5px;">SYSTEM DOCUMENTATION</div>
        <h1>Annua Statistica OMEGA: User Manual</h1>
        <p style="font-weight:600; color:#64748b;">Edition v3.0 • Diocese of Hwange Sacramental RMS</p>

        <h2>1. Introduction</h2>
        <p>The <strong>Annua Statistica OMEGA</strong> system is a high-fidelity reporting engine designed to fulfill the canonical requirements for "Total Accountability" across all parish missions. It consolidates sacramental, vocational, educational, health, and financial data into a single, standardized archival document.</p>

        <h2>2. Accessing the Report</h2>
        <ul>
            <li>Log in to the <strong>Diocesan Sacramental RMS</strong>.</li>
            <li>From the main dashboard, navigate to <strong>Parish Reports</strong>.</li>
            <li>Select the target <strong>Parish/Mission</strong> and the <strong>Reporting Year</strong>.</li>
            <li>Click on the <strong>OMEGA Edition</strong> tab to open the advanced reporting interface.</li>
        </ul>

        <h2>3. Data Entry Modules</h2>
        <p>The report is divided into ten (10) mandatory sections:</p>
        <ul>
            <li><strong>I. Churches and Pastoral Centres</strong>: Track Blessed Sacrament locations and pastoral engagement.</li>
            <li><strong>II. Governance and Personnel</strong>: Record ecclesiastical leadership and personnel census.</li>
            <li><strong>III. Vocations Pipeline</strong>: Monitor major and minor seminarians and religious training.</li>
            <li><strong>IV. Parish Guilds & Sacraments</strong>: Track member counts for associations and sacramental milestones.</li>
            <li><strong>V. Education Matrix</strong>: Census of students and teachers across all school levels.</li>
            <li><strong>VI. Health & Care Matrix</strong>: Reporting on Mission Hospitals, Clinics, and specialized care.</li>
            <li><strong>VII. Financial Ledger</strong>: Granular 14-line ledger with multi-currency support (USD, ZiG, ZAR, BWP).</li>
            <li><strong>VIII. Status Animarum</strong>: Population dynamics tracking (Baptisms, Immigrants, Deaths, etc.).</li>
            <li><strong>IX. Marriage Accountability</strong>: Canonical breakdown of marriages.</li>
            <li><strong>X. Observations</strong>: Qualitative pastoral reporting and context.</li>
        </ul>

        <h2>4. Smart Automation Features</h2>
        <ul>
            <li><strong>Auto-Fetch</strong>: Automatically pulls totals for Baptisms, Deaths, and Marriages from the registry.</li>
            <li><strong>Dynamic Balancing</strong>: Automatically calculates the population balance in Status Animarum.</li>
            <li><strong>Multi-Currency</strong>: Prepends the correct currency symbol based on your selection.</li>
        </ul>

        <h2>5. Exporting for the Archive</h2>
        <p>To generate the final document:</p>
        <ul>
            <li>Click <strong>"Export Final Canonical PDF"</strong> at the bottom of the report preview.</li>
            <li>The PDF includes the <strong>Diocese Seal</strong>, <strong>Page Numbers</strong>, and <strong>Archival Footers</strong>.</li>
        </ul>

        <h2>6. Archival Best Practices</h2>
        <ul>
            <li>Reports should be finalized annually by December 31st.</li>
            <li>PDFs should be printed, signed, and stamped by the Parish Priest before submission to the Chancery.</li>
        </ul>

        <div style="margin-top:50px; border-top:1px solid #e2e8f0; padding-top:20px; font-size:0.7rem; color:#94a3b8; text-align:center;">
            Official Documentation • Diocese of Hwange RMS • 2026
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function exportManualPDF() {
            const element = document.getElementById('manual-doc');
            const opt = {
                margin: 15,
                filename: 'OMEGA_System_User_Manual.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
