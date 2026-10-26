# -*- coding: utf-8 -*-
"""
Hwange Diocese Records Management System (RMS)
User & Administration Manual Compiler
Generates print-ready HTML and compiles vector-sharp PDF via headless Chrome.
"""

import os
import sys
import base64
import subprocess

def get_base64_image(path):
    if os.path.exists(path):
        with open(path, "rb") as f:
            return base64.b64encode(f.read()).decode("utf-8")
    return ""

def generate_manual():
    base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
    docs_dir = os.path.join(base_dir, "docs")
    os.makedirs(docs_dir, exist_ok=True)

    seal_b64 = get_base64_image(os.path.join(base_dir, "assets", "img", "seal.png"))
    logo_b64 = get_base64_image(os.path.join(base_dir, "assets", "img", "diocesan_logo.png"))

    html_content = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hwange Diocese Sacramental RMS - User Manual</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700;800&display=swap');

        :root {{
            --burgundy: #800020;
            --gold: #b45309;
            --gold-light: #fbbf24;
            --navy: #0f172a;
            --navy-light: #1e293b;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --accent-blue: #0284c7;
            --emerald: #059669;
            --card-bg: #f8fafc;
            --border: #e2e8f0;
        }}

        @page {{
            size: A4 portrait;
            margin: 18mm 15mm 20mm 15mm;
            @bottom-center {{
                content: "Catholic Diocese of Hwange — Sacramental Records Management System";
                font-family: 'Inter', sans-serif;
                font-size: 8pt;
                color: #94a3b8;
            }}
            @bottom-right {{
                content: "Page " counter(page);
                font-family: 'Inter', sans-serif;
                font-size: 8pt;
                font-weight: 600;
                color: #64748b;
            }}
        }}

        body {{
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-main);
            line-height: 1.6;
            font-size: 10pt;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }}

        .page-break {{
            page-break-after: always;
        }}

        .avoid-break {{
            page-break-inside: avoid;
        }}

        /* COVER PAGE */
        .cover-page {{
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
            padding: 40px 20px 20px 20px;
            box-sizing: border-box;
            border: 4px double var(--burgundy);
            background: radial-gradient(circle at center, #ffffff 60%, #fdf8f6 100%);
        }}

        .cover-header {{
            margin-top: 20px;
        }}

        .cover-diocese {{
            font-family: 'Cinzel', serif;
            font-size: 22pt;
            font-weight: 900;
            color: var(--burgundy);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 0;
        }}

        .cover-country {{
            font-family: 'Outfit', sans-serif;
            font-size: 11pt;
            font-weight: 600;
            color: var(--gold);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 6px;
        }}

        .cover-logo-box {{
            margin: 35px auto;
            width: 140px;
            height: 140px;
        }}

        .cover-logo-box img {{
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 8px 16px rgba(128, 0, 32, 0.15));
        }}

        .cover-title-box {{
            margin: 20px 0;
        }}

        .cover-title {{
            font-family: 'Cinzel', serif;
            font-size: 25pt;
            font-weight: 900;
            color: var(--navy);
            line-height: 1.2;
            margin: 0 0 10px 0;
        }}

        .cover-subtitle {{
            font-family: 'Outfit', sans-serif;
            font-size: 13pt;
            font-weight: 600;
            color: var(--burgundy);
            margin: 0 0 15px 0;
        }}

        .cover-edition {{
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 9pt;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: 1px solid #fde68a;
        }}

        .cover-footer {{
            margin-bottom: 25px;
            border-top: 2px solid #f1f5f9;
            padding-top: 20px;
        }}

        .cover-meta {{
            font-size: 9pt;
            color: var(--text-muted);
            line-height: 1.5;
        }}

        .cover-url {{
            font-family: monospace;
            font-weight: 700;
            color: var(--accent-blue);
            font-size: 9.5pt;
            margin-top: 6px;
        }}

        /* TYPOGRAPHY & HEADINGS */
        h1 {{
            font-family: 'Cinzel', serif;
            font-size: 16pt;
            color: var(--burgundy);
            border-bottom: 2px solid var(--burgundy);
            padding-bottom: 6px;
            margin-top: 28px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }}

        h2 {{
            font-family: 'Outfit', sans-serif;
            font-size: 12.5pt;
            color: var(--navy);
            margin-top: 20px;
            margin-bottom: 8px;
            border-left: 3px solid var(--gold);
            padding-left: 8px;
        }}

        h3 {{
            font-family: 'Outfit', sans-serif;
            font-size: 10.5pt;
            color: #334155;
            margin-top: 14px;
            margin-bottom: 6px;
        }}

        p {{
            margin: 0 0 10px 0;
            text-align: justify;
        }}

        /* CALLOUT BOXES */
        .callout {{
            border-radius: 8px;
            padding: 12px 16px;
            margin: 12px 0;
            font-size: 9pt;
            line-height: 1.5;
            page-break-inside: avoid;
        }}

        .callout-canon {{
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 5px solid #d97706;
            color: #92400e;
        }}

        .callout-canon strong {{
            color: #78350f;
            display: block;
            margin-bottom: 4px;
            font-family: 'Outfit', sans-serif;
            font-size: 9.5pt;
        }}

        .callout-tip {{
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 5px solid var(--emerald);
            color: #166534;
        }}

        .callout-tip strong {{
            color: #14532d;
            display: block;
            margin-bottom: 4px;
        }}

        .callout-warn {{
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 5px solid #ef4444;
            color: #991b1b;
        }}

        .callout-warn strong {{
            color: #7f1d1d;
            display: block;
            margin-bottom: 4px;
        }}

        /* TABLES */
        table {{
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0;
            font-size: 8.5pt;
            page-break-inside: avoid;
        }}

        th {{
            background: var(--navy);
            color: #ffffff;
            font-weight: 700;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid var(--navy);
            text-transform: uppercase;
            font-size: 7.5pt;
            letter-spacing: 0.5px;
        }}

        td {{
            padding: 7px 10px;
            border: 1px solid var(--border);
            vertical-align: top;
        }}

        tr:nth-child(even) td {{
            background: #f8fafc;
        }}

        /* BADGES */
        .badge {{
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
        }}

        .badge-verified {{
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }}

        .badge-draft {{
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }}

        .badge-canon {{
            background: #ede9fe;
            color: #6d28d9;
            border: 1px solid #ddd6fe;
        }}

        /* STEPS LIST */
        .steps {{
            margin: 10px 0 15px 0;
            padding-left: 20px;
        }}

        .steps li {{
            margin-bottom: 6px;
            font-size: 9.5pt;
        }}

        .steps li strong {{
            color: var(--navy);
        }}

        /* TABLE OF CONTENTS */
        .toc-list {{
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }}

        .toc-item {{
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 10px;
            border-bottom: 1px dotted #cbd5e1;
            padding-bottom: 2px;
            font-size: 9.5pt;
        }}

        .toc-title {{
            font-weight: 600;
            color: var(--navy);
        }}

        .toc-title span {{
            font-weight: 400;
            color: var(--text-muted);
            margin-left: 6px;
            font-size: 8.5pt;
        }}

        .toc-page {{
            font-weight: 700;
            color: var(--burgundy);
            font-family: monospace;
        }}

        .section-header {{
            background: #f8fafc;
            border-left: 4px solid var(--burgundy);
            padding: 8px 12px;
            margin: 20px 0 10px 0;
            font-weight: 700;
            color: var(--navy);
            font-size: 10.5pt;
        }}

        /* KEYBOARD & SHORTCUTS */
        kbd {{
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 2px 6px;
            font-family: monospace;
            font-size: 8pt;
            font-weight: 700;
            color: #334155;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }}
    </style>
</head>
<body>

    <!-- ==================== COVER PAGE ==================== -->
    <div class="cover-page page-break">
        <div class="cover-header">
            <h1 class="cover-diocese">Catholic Diocese of Hwange</h1>
            <div class="cover-country">Zimbabwe &bull; Church Records Office</div>
        </div>

        <div class="cover-logo-box">
            <img src="data:image/png;base64,{seal_b64}" alt="Diocese Seal">
        </div>

        <div class="cover-title-box">
            <h1 class="cover-title">Sacramental Records Management System</h1>
            <div class="cover-subtitle">(Hwange RMS) &mdash; Comprehensive Operational Manual</div>
            <div class="cover-edition">Clergy & Parish Administration Edition &bull; Version 2.4</div>
        </div>

        <div class="cover-footer">
            <div class="cover-meta">
                <strong>Published by:</strong> Diocesan Chancery & Archives Office, Hwange, Zimbabwe<br>
                <strong>Canonical Authority:</strong> Code of Canon Law (CIC 1983) Canons 486, 491, 535, 895, 1067, 1122<br>
                <strong>Audience:</strong> Priests-in-Charge, Assistant Priests, Deacons, Parish Secretaries, Chancery Staff
            </div>
            <div class="cover-url">https://hwange-diocesan-sacramental-rms.vercel.app</div>
        </div>
    </div>

    <!-- ==================== TABLE OF CONTENTS ==================== -->
    <div class="avoid-break page-break">
        <h1>Table of Contents</h1>
        <p style="font-size: 9pt; color: var(--text-muted); margin-bottom: 20px;">
            This manual serves as the standard diocesan reference guide for the administration, recording, verification, and archival preservation of sacramental registers across all parishes, missions, and outstations of the Catholic Diocese of Hwange.
        </p>

        <ul class="toc-list">
            <li class="toc-item"><span class="toc-title">1. Executive Summary & Canonical Foundation <span>(Can. 535, 486, 491)</span></span><span class="toc-page">Ch. 1</span></li>
            <li class="toc-item"><span class="toc-title">2. System Access, Authentication & Self-Service Password Reset</span><span class="toc-page">Ch. 2</span></li>
            <li class="toc-item"><span class="toc-title">3. User Roles & Permission Hierarchy</span><span class="toc-page">Ch. 3</span></li>
            <li class="toc-item"><span class="toc-title">4. Parishioner Directory & Search Systems <span>(Global Command Palette)</span></span><span class="toc-page">Ch. 4</span></li>
            <li class="toc-item"><span class="toc-title">5. Sacramental Registries & Step-by-Step Data Entry</span><span class="toc-page">Ch. 5</span></li>
            <li class="toc-item" style="padding-left: 20px;"><span class="toc-title">5.1 Holy Baptism Register <span>(Infants & Adults)</span></span><span class="toc-page">5.1</span></li>
            <li class="toc-item" style="padding-left: 20px;"><span class="toc-title">5.2 First Holy Communion Register</span><span class="toc-page">5.2</span></li>
            <li class="toc-item" style="padding-left: 20px;"><span class="toc-title">5.3 Sacrament of Confirmation & Notification <span>(Can. 895)</span></span><span class="toc-page">5.3</span></li>
            <li class="toc-item" style="padding-left: 20px;"><span class="toc-title">5.4 Holy Matrimony & Prenuptial Investigation (PNI) <span>(Can. 1067)</span></span><span class="toc-page">5.4</span></li>
            <li class="toc-item" style="padding-left: 20px;"><span class="toc-title">5.5 Holy Matrimony Inter-Parish Baptism Notifications <span>(Can. 1122)</span></span><span class="toc-page">5.5</span></li>
            <li class="toc-item" style="padding-left: 20px;"><span class="toc-title">5.6 Christian Funerals & Death Register</span><span class="toc-page">5.6</span></li>
            <li class="toc-item" style="padding-left: 20px;"><span class="toc-title">5.7 Holy Orders & Religious Profession</span><span class="toc-page">5.7</span></li>
            <li class="toc-item" style="padding-left: 20px;"><span class="toc-title">5.8 RCIA & Receptions into Full Communion</span><span class="toc-page">5.8</span></li>
            <li class="toc-item"><span class="toc-title">6. Canonical Verification, Integrity Hashes & Archival Sealing</span><span class="toc-page">Ch. 6</span></li>
            <li class="toc-item"><span class="toc-title">7. Certificate Generation & Official Documents <span>(Heirloom Edition)</span></span><span class="toc-page">Ch. 7</span></li>
            <li class="toc-item"><span class="toc-title">8. Inter-Parish Communications & Pastoral Inquiries</span><span class="toc-page">Ch. 8</span></li>
            <li class="toc-item"><span class="toc-title">9. Parish Transitions & Canonical Handovers <span>(Can. 535 §5)</span></span><span class="toc-page">Ch. 9</span></li>
            <li class="toc-item"><span class="toc-title">10. Annual Register Returns & Episcopal Visitation <span>(Can. 535 §4)</span></span><span class="toc-page">Ch. 10</span></li>
            <li class="toc-item"><span class="toc-title">11. Financial Stewardship, Daily Ledger & 23-Line Diocesan Cashbook</span><span class="toc-page">Ch. 11</span></li>
            <li class="toc-item"><span class="toc-title">12. Parish Temporal Goods & Asset Inventory <span>(Can. 1283 §2)</span></span><span class="toc-page">Ch. 12</span></li>
            <li class="toc-item"><span class="toc-title">13. Mission Outstations & Rural Mass Centres Directory</span><span class="toc-page">Ch. 13</span></li>
            <li class="toc-item"><span class="toc-title">14. Canonical Mass Intentions & Stipends Book <span>(Can. 945–958)</span></span><span class="toc-page">Ch. 14</span></li>
            <li class="toc-item"><span class="toc-title">15. Data Vault, USB Rectory Backups & Disaster Recovery</span><span class="toc-page">Ch. 15</span></li>
            <li class="toc-item"><span class="toc-title">16. Rural Mission Guide & Mobile Phone Navigation</span><span class="toc-page">Ch. 16</span></li>
            <li class="toc-item"><span class="toc-title">17. Troubleshooting & Frequently Asked Questions (FAQ)</span><span class="toc-page">Ch. 17</span></li>
        </ul>

        <div class="callout callout-canon">
            <strong>The "Auxiliary Record" Principle</strong>
            Under Canon Law, the physical parochial ledger (<em>Liber Baptizatorum, Liber Matrimoniorum, etc.</em>) bound in paper remains the primary canonical record. The Hwange Diocesan RMS is a <strong>Smart Digital Auxiliary Archive</strong> ensuring instant retrieval, tamper-evident cryptographic verification, disaster recovery, and automated inter-parish communication.
        </div>
    </div>

    <!-- ==================== CHAPTER 1 ==================== -->
    <div class="avoid-break">
        <h1>Chapter 1: Executive Summary & Canonical Foundation</h1>
        <p>
            The <strong>Hwange Diocesan Sacramental Records Management System (RMS)</strong> is the official centralized digital recordkeeping platform of the Catholic Diocese of Hwange, Zimbabwe. Covering parishes and missions across the Binga, Victoria Falls, Dete, and Hwange urban deaneries, it bridges the pastoral realities of rural mission administration with modern canonical compliance.
        </p>

        <h2>1.1 Canonical Mandates (CIC 1983)</h2>
        <p>The system is architected in strict conformity with the Code of Canon Law:</p>

        <table>
            <thead>
                <tr>
                    <th style="width: 22%;">Canon</th>
                    <th style="width: 38%;">Canonical Requirement</th>
                    <th style="width: 40%;">System Implementation in Hwange RMS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Can. 535 §1</strong></td>
                    <td>Each parish must maintain registers for baptisms, marriages, deaths, and confirmations.</td>
                    <td>Seven dedicated digital registers with Book, Page, and Entry reference mappings matching physical ledgers.</td>
                </tr>
                <tr>
                    <td><strong>Can. 535 §2</strong></td>
                    <td>In the baptismal register, marginal notes must be made of confirmation, marriage, adoption, holy orders, and religious vows.</td>
                    <td>Automated cross-linking of sacramental events to parishioner baptism records with marginal notation generators.</td>
                </tr>
                <tr>
                    <td><strong>Can. 535 §4</strong></td>
                    <td>Parishes must transmit annual copies of their registers to the diocesan archive for episcopal inspection.</td>
                    <td><strong>Annual Register Returns Hub</strong> for 1-click compiling, canonical attestation, and transmission of closed-year volumes.</td>
                </tr>
                <tr>
                    <td><strong>Can. 535 §5</strong></td>
                    <td>When a pastor leaves office, the registers, seal, and parochial goods must be formally handed over to the successor.</td>
                    <td><strong>Canonical Handover Hub</strong> allowing outgoing priests to sign off register status notes and incoming priests to formally accept custody.</td>
                </tr>
                <tr>
                    <td><strong>Can. 895 & 1122</strong></td>
                    <td>The pastor celebrating a marriage or confirmation must promptly notify the pastor of the place of baptism.</td>
                    <td>1-click <strong>Baptism Notification Slips</strong> pre-filled with canonical data, recipient addresses, and return receipt receipts.</td>
                </tr>
                <tr>
                    <td><strong>Can. 486 §1</strong></td>
                    <td>All documents pertaining to the diocese or parishes must be safeguarded with diligent care in a secure archive.</td>
                    <td>Tamper-resistant SQLite data vault with cloud synchronization, automated backups, and SHA-256 verification hashes.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ==================== CHAPTER 2 ==================== -->
    <div class="avoid-break page-break">
        <h1>Chapter 2: System Access, Authentication & Password Recovery</h1>
        
        <h2>2.1 Accessing the Portal</h2>
        <p>The system is accessible from any computer, tablet, or smartphone connected to the internet:</p>
        <div style="background: #f1f5f9; padding: 10px 15px; border-radius: 8px; font-family: monospace; font-size: 10pt; font-weight: bold; color: var(--navy); margin-bottom: 12px;">
            https://hwange-diocesan-sacramental-rms.vercel.app
        </div>

        <h2>2.2 Signing In</h2>
        <ol class="steps">
            <li>Enter your registered <strong>Username</strong> or <strong>Email address</strong> (e.g. <code>nicolas_nkamba</code> or <code>binga@hwangediocese.org</code>).</li>
            <li>Enter your secure password. Click the eye icon (<ion-icon name="eye-outline"></ion-icon>) to toggle password visibility.</li>
            <li>Check <strong>"Remember my Username"</strong> on your private computer or rectory laptop so your identifier is remembered for 60 days.</li>
            <li>Click <strong>"Sign In to Records"</strong>.</li>
        </ol>

        <h2>2.3 Priest Autonomous Password Reset (Self-Service)</h2>
        <p>
            Priests and parish secretaries have the autonomous right to reset their forgotten password at any time without waiting for Chancery admin approval:
        </p>
        
        <div class="callout callout-tip">
            <strong>Self-Service Reset URL:</strong>
            <code>https://hwange-diocesan-sacramental-rms.vercel.app/forgot_password.php</code>
        </div>

        <ol class="steps">
            <li>On the login page, click <strong>"Forgot Password?"</strong>.</li>
            <li>Select your account from the <strong>"Quick Select Clergy / Mission Account"</strong> dropdown list (all 40 parish and clergy accounts are listed with deanery affiliations), OR type your username/email.</li>
            <li>Enter your <strong>New Password</strong> (minimum 8 characters; combining letters, numbers, and symbols is recommended).</li>
            <li>Confirm your new password and click <strong>"Update Password & Sign In"</strong>.</li>
            <li>Your password is immediately updated across the diocesan database and cloud vault. You are redirected to log in with instant access.</li>
        </ol>
    </div>

    <!-- ==================== CHAPTER 3 ==================== -->
    <div class="avoid-break">
        <h1>Chapter 3: User Roles & Permission Hierarchy</h1>
        <p>
            The Hwange RMS enforces strict Role-Based Access Control (RBAC) to ensure that priests maintain jurisdictional autonomy over their parishes while the Bishop and Chancery exercise canonical oversight.
        </p>

        <table>
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Scope & Jurisdiction</th>
                    <th>Permissions & Rights</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Admin (Chancery Lead)</strong></td>
                    <td>Diocese-Wide</td>
                    <td>Full administrative rights; create parishes; assign clergy; manage user credentials; oversee financial remittances; manage cloud backups.</td>
                </tr>
                <tr>
                    <td><strong>Chancellor</strong></td>
                    <td>Diocese-Wide</td>
                    <td>Canonical inspection of all parish registers; marriage dispensation protocol oversight; review annual statistical returns; audit logs.</td>
                </tr>
                <tr>
                    <td><strong>Priest (Pastor / Priest-in-Charge)</strong></td>
                    <td>Assigned Parish / Mission</td>
                    <td>Create & edit parish sacramental records; <strong>Canonically Verify & Seal</strong> entries; issue official certificates; download parish database backups; execute handovers.</td>
                </tr>
                <tr>
                    <td><strong>Curate / Assistant Priest</strong></td>
                    <td>Assigned Parish / Mission</td>
                    <td>Draft sacramental entries; query parish records; issue certificate drafts for pastor verification; generate baptism notification slips.</td>
                </tr>
                <tr>
                    <td><strong>Parish Secretary / Admin</strong></td>
                    <td>Assigned Parish / Mission</td>
                    <td>Historical register digitization; parishioner profile entry; draft baptism notices; print verified letters of standing.</td>
                </tr>
                <tr>
                    <td><strong>Bookkeeper / Parish Treasurer</strong></td>
                    <td>Assigned Parish / Mission</td>
                    <td>Record daily cashbook transactions; upload receipt vouchers; prepare monthly returns; download financial CSVs. <em>Strictly restricted from confidential sacramental registers.</em></td>
                </tr>
                <tr>
                    <td><strong>Observer / Canonical Auditor</strong></td>
                    <td>Diocese-Wide (Read-Only)</td>
                    <td>Read-only access for pastoral audits, statistical analysis, and demographic studies. Cannot alter records.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ==================== CHAPTER 4 ==================== -->
    <div class="avoid-break page-break">
        <h1>Chapter 4: Parishioner Directory & Search Systems</h1>
        
        <h2>4.1 The Central Parishioner Record</h2>
        <p>
            In the Hwange RMS, each individual is represented by a single master <strong>Parishioner Profile</strong>. All sacraments received throughout their life (Baptism, First Communion, Confirmation, Marriage, Holy Orders) link back to this single profile, preventing duplicated identities and conflicting records.
        </p>

        <h2>4.2 Adding a New Parishioner</h2>
        <ol class="steps">
            <li>Navigate to <strong>Parishioners</strong> in the sidebar and click <strong>"Register New Parishioner"</strong>.</li>
            <li>Enter Full Canonical First Name and Surname.</li>
            <li>Enter <strong>Date of Birth</strong>, <strong>Gender</strong>, and <strong>Current Parish / Mission</strong>.</li>
            <li>Record <strong>Father's Full Name</strong> and <strong>Mother's Full Name (including Maiden Name)</strong>.</li>
            <li>Record Place of Residence and Place of Baptism (crucial for inter-parish canonical notifications).</li>
            <li>Click <strong>"Save Parishioner Record"</strong>.</li>
        </ol>

        <h2>4.3 Global Mission Command (GMC) Instant Search</h2>
        <p>
            The system includes a diocesan-wide keyboard-driven command palette:
        </p>
        <ul>
            <li>Press <kbd>Alt</kbd> + <kbd>S</kbd> anywhere in the application (or click the search box in the sidebar).</li>
            <li>Start typing any parishioner surname, baptism number, or mission name.</li>
            <li>The autocomplete engine searches across all tables in real-time, showing matching parishioners and their sacramental status.</li>
        </ul>
    </div>

    <!-- ==================== CHAPTER 5 ==================== -->
    <div class="avoid-break">
        <h1>Chapter 5: Sacramental Registries & Step-by-Step Data Entry</h1>

        <h2>5.1 Holy Baptism Register (Can. 877)</h2>
        <p>The foundation of all sacramental life. Entering a baptism creates or links the parishioner's primary sacramental identity.</p>
        <ol class="steps">
            <li>Go to <strong>Sacramental Hub &rarr; Baptisms &rarr; Record New Baptism</strong>.</li>
            <li>Select or create the Parishioner.</li>
            <li>Enter <strong>Date of Baptism</strong> and <strong>Date of Birth</strong>.</li>
            <li>Specify the <strong>Minister</strong> (e.g. <code>Fr. Nicolas Nkamba</code>).</li>
            <li>Record <strong>Godfather</strong> and <strong>Godmother</strong> (Sponsors).</li>
            <li>Enter the physical book mapping: <strong>Register Book Number</strong>, <strong>Page Number</strong>, and <strong>Entry Number</strong>.</li>
            <li>Click <strong>"Save Baptism Entry"</strong>. The record is created in <span class="badge badge-draft">Draft</span> status awaiting canonical verification.</li>
        </ol>

        <h2>5.2 Sacrament of Confirmation (Can. 895)</h2>
        <p>
            When a confirmation is recorded, the minister, sponsor, confirmation saint name, and physical register reference are archived.
        </p>
        <div class="callout callout-canon">
            <strong>Mandatory Notification to Parish of Baptism (Can. 895):</strong>
            Canon 895 requires that the pastor of the place of confirmation advise the pastor of the place of baptism so that marginal notations can be entered into the original baptismal register.
        </div>
        <p>
            In Hwange RMS, after saving the confirmation:
        </p>
        <ol class="steps">
            <li>Open the Confirmation detail page (<code>view_confirmation.php?id=...</code>).</li>
            <li>Click the blue <strong>"Baptism Notification Slip (Can. 895)"</strong> button in the header.</li>
            <li>The system generates a ready-to-mail formal canonical notification slip pre-addressed to the Pastor of the Parish of Baptism, complete with parent details, date of confirmation, and an acknowledgment tear-off slip.</li>
            <li>Print the document and dispatch it to the baptismal parish.</li>
        </ol>
    </div>

    <!-- ==================== CHAPTER 5 (CONTINUED) ==================== -->
    <div class="avoid-break page-break">
        <h2>5.3 Holy Matrimony & Prenuptial Investigation (Can. 1067 & 1122)</h2>
        <p>
            Holy Matrimony requires rigorous canonical oversight before and after celebration. The Hwange RMS provides an integrated Prenuptial Investigation (PNI) module and a formal Marriage Register.
        </p>

        <h3>A. Prenuptial Investigation (PNI Module)</h3>
        <ol class="steps">
            <li>Go to <strong>Marriage Prep (PNI) &rarr; New Investigation</strong>.</li>
            <li>Select Groom and Bride profiles.</li>
            <li>Record canonical freedom to marry, absence of impediments, and mutual consent on unity, indissolubility, and procreation.</li>
            <li>Enter the publication dates for the <strong>Banns of Marriage</strong> (1st, 2nd, and 3rd callings).</li>
            <li><strong>6-Month Baptismal Certificate Check:</strong> Record the date of issue for both baptismal certificates (must be issued within 6 months to establish canonical status).</li>
            <li>If a dispensation is required (Disparity of Cult, Mixed Religion, Canonical Form), record the <strong>Chancery Protocol Number</strong> and granting date.</li>
        </ol>

        <h3>B. Marriage Registration & 1-Click Baptism Notifications (Can. 1122)</h3>
        <p>
            Once the wedding is celebrated, record the marriage in <strong>Sacramental Hub &rarr; Marriages &rarr; Record New Marriage</strong>.
        </p>
        <div class="callout callout-tip">
            <strong>1-Click Canonical Notification Slips (Can. 1122):</strong>
            Open the marriage detail page (<code>view_marriage.php</code>). The header actions provide two dedicated notification buttons:
            <ul>
                <li><strong>Groom Baptism Notice:</strong> Prints a pre-filled canonical notification to the Groom's parish of baptism.</li>
                <li><strong>Bride Baptism Notice:</strong> Prints a pre-filled canonical notification to the Bride's parish of baptism.</li>
            </ul>
        </div>

        <h2>5.4 Christian Funerals & Death Register (Can. 1182)</h2>
        <p>
            Records the faithful departed, including Date of Death, Date of Christian Burial, Officiating Minister, Cemetery / Burial Location, and surviving Next of Kin.
        </p>

        <h2>5.5 Holy Orders & Religious Profession</h2>
        <p>
            Archives ordinations to the Diaconate and Priesthood, as well as First and Perpetual Religious Professions, recording the Ordaining Bishop, Superior General, and canonical decree references.
        </p>
    </div>

    <!-- ==================== CHAPTER 6 ==================== -->
    <div class="avoid-break">
        <h1>Chapter 6: Canonical Verification & Archival Sealing</h1>
        <p>
            To prevent accidental alteration and ensure legal integrity, the Hwange RMS employs a dual-stage workflow: <strong>Draft</strong> and <strong>Officially Sealed</strong>.
        </p>

        <h2>6.1 The Verification Workflow</h2>
        <ol class="steps">
            <li>When an entry is first typed by a parish secretary or curate, it is marked <span class="badge badge-draft">Canonical Draft</span>. Official Heirloom Certificates cannot be printed while in draft mode.</li>
            <li>The <strong>Priest-in-Charge</strong> reviews the entry against the handwritten physical register ledger.</li>
            <li>The priest clicks <strong>"Confirm & Verify Entry"</strong>.</li>
            <li>The system records the priest's User ID, assigns a timestamp, changes the status to <span class="badge badge-verified">Officially Sealed</span>, and computes a unique <strong>SHA-256 Cryptographic Hash</strong>.</li>
            <li>The record is now locked and authorized for official certificate printing.</li>
        </ol>

        <div class="callout callout-warn">
            <strong>Tamper Resistance:</strong>
            Any subsequent attempt to modify a verified record generates a security alert in the Chancery Audit Log. Minor clerical corrections must be logged with an explanatory note.
        </div>
    </div>

    <!-- ==================== CHAPTER 7 ==================== -->
    <div class="avoid-break page-break">
        <h1>Chapter 7: Official Documents & Heirloom Certificates</h1>
        <p>
            The Hwange RMS includes a presentation printing engine designed for high-resolution A4 output on parchment or official diocesan certificate stock.
        </p>

        <h2>7.1 Certificate Types Available</h2>
        <ul>
            <li><strong>Certificate of Holy Baptism (Heirloom Edition):</strong> Features the Diocesan Crest, parish title, canonical references, godparent names, and physical ledger citation.</li>
            <li><strong>Certificate of Confirmation:</strong> Documents the conferral of the Holy Spirit, minister, sponsor, and confirmation name.</li>
            <li><strong>Certificate of Holy Matrimony:</strong> Formal covenant document with groom, bride, witnesses, officiant, and dispensation notes.</li>
            <li><strong>Letter of Good Standing & Transfer:</strong> Official canonical letter addressed to receiving pastors for relocation, godparent eligibility, or marriage preparation.</li>
        </ul>

        <h2>7.2 Dynamic QR Code Verification</h2>
        <p>
            Every printed certificate features an embedded high-density QR code. When scanned by any smartphone camera or diocesan chancery scanner:
        </p>
        <ol class="steps">
            <li>The QR code points to the secure verification endpoint (<code>/verify.php?hash=...</code>).</li>
            <li>The system instantly confirms whether the certificate is genuine, matches the central diocesan archive, and displays the sealing date and minister.</li>
            <li>This protects our faithful from forged baptismal or marriage extracts.</li>
        </ol>

        <h2>7.3 Printer Configuration Tips</h2>
        <ul>
            <li><strong>Paper Size:</strong> Ensure printer is set to <strong>A4</strong> (210 &times; 297 mm).</li>
            <li><strong>Margins:</strong> In the browser print dialog, set Margins to <strong>"None"</strong> or <strong>"Default"</strong>.</li>
            <li><strong>Background Graphics:</strong> Ensure <strong>"Background Graphics"</strong> is checked to display the gold borders and watermarks.</li>
        </ul>
    </div>

    <!-- ==================== CHAPTER 8 & 9 ==================== -->
    <div class="avoid-break">
        <h1>Chapter 8: Parish Transitions & Canonical Handovers (Can. 535 §5)</h1>
        <p>
            Priestly transfers are a vital moment in parochial life. Canon 535 §5 mandates that when a parish priest leaves office, a formal handover of registers and parish property must occur.
        </p>

        <h2>8.1 The Handover Hub Protocol</h2>
        <ol class="steps">
            <li><strong>Initiation:</strong> The Chancery or departing priest accesses <strong>Mission Transitions</strong> in the sidebar.</li>
            <li><strong>Outgoing Sign-Off:</strong> The departing priest reviews the status of physical ledgers (Baptisms, Marriages, Funerals) and writes a <strong>Registry Status Report</strong> (e.g. <em>"All baptisms up to April 2026 fully entered and verified. Physical books locked in parish safe."</em>).</li>
            <li>The departing priest submits the digital sign-off.</li>
            <li><strong>Incoming Acceptance:</strong> The newly appointed priest inspects the physical books, reviews the report, and clicks <strong>"Accept Mission & Records"</strong>.</li>
            <li>The transition is permanently archived in the diocesan database with dual timestamps, and a <strong>Canonical Handover Certificate</strong> is generated for the Bishop's archives.</li>
        </ol>
    </div>

    <!-- ==================== CHAPTER 11 & 12 ==================== -->
    <div class="avoid-break page-break">
        <h1>Chapter 11: Financial Stewardship, Daily Ledger & 23-Line Diocesan Cashbook</h1>
        <p>
            Parish financial administration is an integral part of pastoral accountability. The Hwange RMS incorporates the official 23-Line Double-Entry Accounting framework mandated by the Diocese of Hwange.
        </p>

        <h2>11.1 Daily Cash & Bank Ledger (<code>dashboard/daily_ledger.php</code>)</h2>
        <p>
            Priests, administrators, and parish bookkeepers record all day-to-day cash receipts, bank deposits, and operational expenditures in real-time.
        </p>
        <ol class="steps">
            <li><strong>Target Accounts:</strong> Choose between <strong>Cash at Hand</strong> (parish cash safe / box) and <strong>Cash in Bank</strong> (commercial account: Stanbic, CBZ, etc.).</li>
            <li><strong>Line 1 Tithe / Unsupo / Mutulo:</strong> Captures faithful offerings with native appreciation of Nambya, Tonga, and Ndebele parish traditions.</li>
            <li><strong>Segregated Special Collections:</strong> Annual collections (Seminary, Mission Sunday, Peter's Pence, Holy Land, Missionary Childhood, Social Communications, Lenten Campaign, Caritas) are held as pass-through funds separate from parish operational revenues.</li>
            <li><strong>Digital Voucher Upload:</strong> Attach scanned receipts, photo slips, or bank deposit slips (PDF, JPG, PNG up to 12MB) directly to each transaction.</li>
            <li><strong>Outstation Attribution:</strong> Tag collections to specific rural outstations for transparent reporting.</li>
        </ol>

        <h2>11.2 Monthly Returns & Chancery Audit Lock (<code>dashboard/finance_submission.php</code>)</h2>
        <p>
            At the close of each month, the system automates preparation of the official diocesan return:
        </p>
        <ol class="steps">
            <li>Click <strong>"Sync from Daily Ledger"</strong> to instantly populate all 23 standard diocesan income and expenditure lines.</li>
            <li>Confirm multi-currency balances in <strong>USD ($)</strong>, <strong>ZiG</strong>, or <strong>ZAR (R)</strong>.</li>
            <li>Submit the return to the Chancery. Once audited and marked as <strong>"Verified"</strong> by the Diocesan Finance Office, the return is canonically sealed against unauthorized alteration.</li>
            <li>Export verified monthly cashbooks to Microsoft Word (<code>.docx</code>) or CSV spreadsheets.</li>
        </ol>

        <h1>Chapter 12: Parish Temporal Goods & Asset Inventory (Can. 1283 §2)</h1>
        <p>
            Canon 1283 §2 requires administrators to draw up an accurate and clear inventory of immovable goods, movable goods, and precious items, with estimated valuations and physical descriptions.
        </p>

        <h2>12.1 Managing Temporal Goods (<code>dashboard/assets.php</code>)</h2>
        <ul>
            <li><strong>Sacred Vessels & Vestments:</strong> Chalices, ciboria, monstrances, and reliquaries are highlighted with reverence badges and tracked for sacred preservation.</li>
            <li><strong>Transport & Vehicles:</strong> Land Cruisers, 4x4 trucks, motorcycles, and tractors tracked with registration numbers, engine codes, and roadworthiness status.</li>
            <li><strong>Mission Infrastructure:</strong> Deep boreholes, solar pumping arrays, inverters, storage tanks, rectory buildings, and schools.</li>
            <li><strong>Condition Tracking:</strong> Items are categorized as <em>Good, Fair, Poor, Under Repair,</em> or <em>Condemned</em> with replacement cost estimates.</li>
            <li><strong>Handover Inventory Export:</strong> 1-click printable asset inventory for canonical parish transitions.</li>
        </ul>
    </div>

    <!-- ==================== CHAPTER 13 & 14 ==================== -->
    <div class="avoid-break page-break">
        <h1>Chapter 13: Mission Outstations & Rural Mass Centres Directory</h1>
        <p>
            Rural missions across the Diocese of Hwange oversee extensive networks of bush centres spanning vast distances. The <strong>Outstations Directory</strong> (<code>dashboard/outstations.php</code>) centralizes pastoral intelligence:
        </p>
        <ul>
            <li><strong>Geographic Distance:</strong> Tracks road distance in kilometers from the mission centre (e.g. St. Augustine, Binga, Dete, Fatima, Jambezi, Lupane).</li>
            <li><strong>Pastoral Leadership Directory:</strong> Instant telephone and WhatsApp contacts for the Head Catechist and Parish Pastoral Council Chairperson.</li>
            <li><strong>Liturgical Schedule:</strong> Mass celebration cadence (e.g. 1st & 3rd Sundays of the month) and patronal feast day celebrations.</li>
            <li><strong>Household Census:</strong> Active Catholic family counts for pastoral planning and episcopal visitations.</li>
        </ul>

        <h1>Chapter 14: Canonical Mass Intentions & Stipends Book (Can. 945–958)</h1>
        <p>
            In accordance with Canons 945–958, priests must keep an accurate register of Mass intentions received, stipends collected, and celebrations fulfilled.
        </p>

        <h2>14.1 Intention Management (<code>dashboard/mass_intentions.php</code>)</h2>
        <ol class="steps">
            <li><strong>Intention Classification:</strong> Categorized as <em>Repose of the Soul / Deceased</em>, <em>Thanksgiving</em>, <em>Healing / Sick</em>, <em>Special Intention</em>, or <em>Pro Populo</em> (obligatory Pastor's Mass for the People under Canon 534).</li>
            <li><strong>Stipend Accounting:</strong> Record offering amounts, donor names, contact details, and receipt dates.</li>
            <li><strong>Celebrant Assignment:</strong> Assign intentions to resident or visiting priests.</li>
            <li><strong>1-Click Fulfillment:</strong> Mark Masses as <strong>Celebrated</strong> with live celebration dates or <strong>Transferred</strong> to Chancery.</li>
            <li><strong>Sacristy Printout:</strong> Generates a clean liturgical Mass intention schedule for the sacristy noticeboard.</li>
        </ol>
    </div>

    <!-- ==================== CHAPTER 15, 16 & 17 ==================== -->
    <div class="avoid-break page-break">
        <h1>Chapter 15: Data Vault, USB Rectory Backups & Disaster Recovery</h1>
        <p>
            Rural mission stations in Hwange Diocese frequently face erratic electricity, cellular network dropouts, or equipment damage. To ensure records are never lost, the RMS implements a <strong>3-2-1 Diocesan Backup Strategy</strong>.
        </p>

        <table>
            <thead>
                <tr>
                    <th>Storage Layer</th>
                    <th>Mechanism</th>
                    <th>Frequency</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Layer 1: Local Offline USB</strong></td>
                    <td>Priest downloads <code>.sqlite</code> snapshot from <strong>Data Vault (Backups)</strong> to an encrypted flash drive kept in the rectory safe.</td>
                    <td>1st of every month</td>
                </tr>
                <tr>
                    <td><strong>Layer 2: Local Rectory PC</strong></td>
                    <td>Standby copy stored on the parish office laptop.</td>
                    <td>Continuous</td>
                </tr>
                <tr>
                    <td><strong>Layer 3: Cloud Vault Sync</strong></td>
                    <td>Encrypted transmission to the Chancery Cloud Vault on Vercel/GitHub whenever internet is active.</td>
                    <td>Automated (Live)</td>
                </tr>
            </tbody>
        </table>

        <h2>15.1 Downloading an Offline Backup & CSV Data Exports</h2>
        <ol class="steps">
            <li>Click <strong>Data Vault (Backups)</strong> in the sidebar (accessible to all priests, bookkeepers, and administrators).</li>
            <li>Click the blue <strong>"Download Standalone Archive (.sqlite)"</strong> button for full system backup.</li>
            <li>Use the one-click CSV export grid to download tabular data for <strong>Baptisms</strong>, <strong>Confirmations</strong>, <strong>Marriages</strong>, <strong>Parishioners</strong>, <strong>Daily Ledger</strong>, <strong>Monthly Returns</strong>, <strong>Temporal Goods</strong>, <strong>Outstations</strong>, and <strong>Mass Intentions</strong>.</li>
        </ol>

        <h1>Chapter 16: Rural Mission Guide & Mobile Phone Usability</h1>
        <p>
            When celebrating sacraments in distant outstations (e.g. in Binga or Dete bush parishes), priests often only have a smartphone. The Hwange RMS is fully responsive:
        </p>
        <ul>
            <li><strong>Mobile Hamburger Menu:</strong> Tap the cyan menu button (<ion-icon name="menu-outline"></ion-icon>) in the top header. The navigation drawer slides smoothly into view.</li>
            <li><strong>Touch Backdrop:</strong> Tap anywhere outside the drawer to dismiss the menu.</li>
            <li><strong>Auto-Zoom Prevention:</strong> Text input fields are optimized to prevent awkward zooming on mobile keyboards.</li>
            <li><strong>Table Scrolling:</strong> On small screens, data tables scroll horizontally with smooth finger swipes.</li>
        </ul>

        <h1>Chapter 17: Frequently Asked Questions & Troubleshooting</h1>

        <div class="avoid-break" style="margin-bottom: 12px;">
            <h3>Q: A priest forgot his password. Must he wait for the Chancery Admin to reset it?</h3>
            <p><strong>Answer:</strong> No. Any priest can navigate directly to <code>/forgot_password.php</code>, select his name or parish from the quick dropdown, enter a new password (min. 8 characters), and immediately log in.</p>
        </div>

        <div class="avoid-break" style="margin-bottom: 12px;">
            <h3>Q: Why does the system say "Print Locked (Draft)" when I try to print a certificate?</h3>
            <p><strong>Answer:</strong> Under diocesan policy, official Heirloom Certificates can only be issued for canonized entries. The Priest-in-Charge must first open the record and click <strong>"Confirm & Verify"</strong> to seal the record.</p>
        </div>

        <div class="avoid-break" style="margin-bottom: 12px;">
            <h3>Q: What should I do if a parishioner was baptized in another diocese (e.g. Bulawayo or Harare)?</h3>
            <p><strong>Answer:</strong> In the Parishioner record, enter the Place of Baptism (e.g. <em>"St. Mary's Cathedral, Bulawayo"</em>). When you record their Marriage or Confirmation, the 1-click notification slip will automatically address the notification to that parish priest.</p>
        </div>

        <div class="avoid-break" style="margin-bottom: 12px;">
            <h3>Q: How do we correct a misspelled surname after a record has been verified?</h3>
            <p><strong>Answer:</strong> The Priest-in-Charge can open the record, click <strong>"Edit Record"</strong>, update the canonical spelling, and provide an amendment note. The cryptographic verification hash will update automatically.</p>
        </div>

        <div style="margin-top: 35px; border-top: 2px solid #e2e8f0; padding-top: 20px; text-align: center; font-size: 8.5pt; color: var(--text-muted);">
            <strong>Catholic Diocese of Hwange &bull; Records Management System Manual</strong><br>
            For canonical or technical inquiries, contact the Chancery Office at <code>chancery@hwangediocese.org</code><br>
            <em>"Omnia in bonum et ad maiorem Dei gloriam."</em>
        </div>
    </div>

</body>
</html>
"""

    html_path = os.path.join(docs_dir, "Hwange_Diocesan_Sacramental_RMS_Manual.html")
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(html_content)

    print(f"HTML Manual generated at: {html_path}")

    # Compile to PDF using Chrome
    chrome_path = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe"
    pdf_path = os.path.join(docs_dir, "Hwange_Diocesan_Sacramental_RMS_Manual.pdf")
    root_pdf_path = os.path.join(base_dir, "Hwange_Diocesan_Sacramental_RMS_Manual.pdf")

    cmd = [
        chrome_path,
        "--headless=new",
        "--disable-gpu",
        "--no-pdf-header-footer",
        f"--print-to-pdf={pdf_path}",
        html_path
    ]

    print(f"Executing: {' '.join(cmd)}")
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if os.path.exists(pdf_path) and os.path.getsize(pdf_path) > 0:
        # Also copy to root directory for easy access
        import shutil
        shutil.copy2(pdf_path, root_pdf_path)
        print(f"SUCCESS: PDF Manual compiled successfully!")
        print(f"Size: {os.path.getsize(pdf_path):,} bytes")
        print(f"Docs PDF: {pdf_path}")
        print(f"Root PDF: {root_pdf_path}")
        return True
    else:
        print("ERROR: Failed to generate PDF")
        print("stdout:", result.stdout)
        print("stderr:", result.stderr)
        return False

if __name__ == "__main__":
    success = generate_manual()
    sys.exit(0 if success else 1)
