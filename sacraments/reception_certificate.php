<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Certificate of Reception into Full Communion - Printable View
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? null;
if (!$id) redirect('reception_list.php');

// Fetch record
$record = db_fetch("
    SELECT r.*, p.first_name, p.last_name, p.other_names, p.dob, p.place_of_birth, p.father_name, p.mother_name, pa.parish_name, pa.location as parish_location
    FROM receptions r 
    JOIN parishioners p ON r.person_id = p.person_id 
    JOIN parishes pa ON r.parish_id = pa.parish_id 
    WHERE r.reception_id = ?
", [$id]);

if (!$record) redirect('reception_list.php');

// Language Support
$lang = $_GET['lang'] ?? 'en';
$t = get_certificate_text($lang);

// Generate verified canonical verification URLs
$verify_urls = get_certificate_verification_url('reception', $id, $record['verification_hash'] ?? '', $record['guid'] ?? '');

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <title>Reception Certificate - <?php echo h($record['first_name'] . ' ' . $record['last_name']); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Great+Vibes&family=Inter:wght@400;600;800&family=Outfit:wght@500;700&display=swap');

        :root {
            --gold: #d4af37;
            --navy: #1e3a8a;
            --parchment: #fffef2;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            .no-print, .theme-selector { display: none !important; }
            html, body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                overflow: hidden !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .certificate-container { 
                margin: 10mm auto !important; 
                box-shadow: none !important; 
                width: 186mm !important; 
                height: 274mm !important; 
                max-height: 274mm !important;
                box-sizing: border-box !important;
                padding: 6mm 10mm 4mm 10mm !important; 
                overflow: hidden !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
            }
            body.theme-heirloom .certificate-container {
                border: 10px solid #1e3a8a !important;
                outline: 2px solid #d4af37 !important;
                outline-offset: -6px !important;
                padding: 5mm 8mm 3mm 8mm !important;
            }
            body.theme-heirloom .certificate-container::after {
                margin: 8px !important;
                background-size: 40px 40px !important;
            }
            body.theme-heirloom .heirloom-only {
                margin-top: 1px !important;
                padding-top: 1px !important;
            }
            body.theme-heirloom .certificate-title {
                margin: 2px 0 !important;
                font-size: 26px !important;
            }
            body.theme-heirloom .sacrament-declaration {
                margin: 2px 0 !important;
                font-size: 17px !important;
            }
            .header { margin-bottom: 8px !important; }
            .diocese-name { font-size: 21px !important; letter-spacing: 2px !important; }
            .diocese-sub { font-size: 10px !important; letter-spacing: 2px !important; margin-top: 1px !important; }
            .certificate-title { font-size: 36px !important; margin: 4px 0 !important; line-height: 1.15 !important; }
            .content { 
                font-size: 14px !important; 
                line-height: 1.35 !important; 
                flex: 1 1 auto !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                align-items: center !important;
                margin: auto 0 !important;
                width: 100% !important;
            }
            .content p { margin: 5px 0 !important; }
            .field-value { font-size: 19px !important; padding-bottom: 2px !important; min-width: 280px !important; }
            .field-label { font-size: 9.5px !important; margin-top: 4px !important; letter-spacing: 1.5px !important; }
            .sacrament-declaration { font-size: 21px !important; margin: 5px 0 !important; letter-spacing: 1.5px !important; }
            .details-grid { 
                gap: 8px 30px !important; 
                margin: 12px auto 0 auto !important; 
                width: 100% !important;
                max-width: 560px !important;
                box-sizing: border-box !important;
            }
            .details-grid div div { font-size: 14px !important; padding-bottom: 2px !important; }
            .footer { margin-top: auto !important; padding-top: 6px !important; }
            .seal { width: 70px !important; height: 70px !important; font-size: 7.5px !important; padding: 4px !important; }
            .signature-block { width: 220px !important; }
            .signature-block #qrcode { 
                background: #ffffff !important; 
                padding: 3px !important; 
                border: 1px solid #cbd5e1 !important; 
                border-radius: 4px !important; 
                box-shadow: none !important; 
                display: inline-flex !important; 
                margin-bottom: 3px !important; 
            }
            .signature-block #qrcode canvas, .signature-block #qrcode img { 
                width: 72px !important; 
                height: 72px !important; 
                image-rendering: -webkit-optimize-contrast !important; 
                image-rendering: crisp-edges !important; 
                image-rendering: pixelated !important; 
            }
            .signature-line { margin-top: 6px !important; margin-bottom: 2px !important; }
            .signature-label { font-size: 10.5px !important; }
            .canonical-meta { font-size: 7.5px !important; line-height: 1.2 !important; margin-top: 4px !important; }
        }

        body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .certificate-container {
            width: 794px;
            height: 1122px;
            padding: 20px 40px;
            margin: 10px auto;
            background: var(--parchment);
            box-shadow: 0 40px 100px rgba(0,0,0,0.2);
            position: relative;
            box-sizing: border-box;
            border: 15px solid var(--navy);
            outline: 5px solid var(--gold);
            outline-offset: -15px;
            overflow: hidden;
            transition: all 0.5s ease;
            display: flex;
            flex-direction: column;
        }

        /* Heirloom Edition Theme */
        body.theme-heirloom .certificate-container {
            border: 15px solid #1e3a8a;
            outline: 2px solid #d4af37;
            outline-offset: -10px;
            background: #fffef2;
            position: relative;
        }

        body.theme-heirloom .header { margin-top: 0px; }

        body.theme-heirloom .certificate-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M50 50m-40 0a40 40 0 1 0 80 0a40 40 0 1 0 -80 0' fill='none' stroke='%23d4af37' stroke-width='0.5' stroke-opacity='0.1'/%3E%3Cpath d='M50 50m-30 0a30 30 0 1 0 60 0a30 30 0 1 0 -60 0' fill='none' stroke='%23d4af37' stroke-width='0.5' stroke-opacity='0.1'/%3E%3C/svg%3E");
            opacity: 0.5;
            z-index: 0;
            pointer-events: none;
        }

        body.theme-heirloom .certificate-container::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: 
                url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 20 L0 0 L20 0 M0 10 L10 0' fill='none' stroke='%23d4af37' stroke-width='2'/%3E%3C/svg%3E"),
                url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M80 0 L100 0 L100 20 M90 0 L100 10' fill='none' stroke='%23d4af37' stroke-width='2'/%3E%3C/svg%3E"),
                url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 80 L0 100 L20 100 M0 90 L10 100' fill='none' stroke='%23d4af37' stroke-width='2'/%3E%3C/svg%3E"),
                url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M80 100 L100 100 L100 80 M90 100 L100 90' fill='none' stroke='%23d4af37' stroke-width='2'/%3E%3C/svg%3E");
            background-position: top left, top right, bottom left, bottom right;
            background-repeat: no-repeat;
            background-size: 60px 60px;
            opacity: 0.8;
            z-index: 2;
            pointer-events: none;
            margin: 15px;
        }

        body.theme-heirloom .certificate-title { color: #1e3a8a; font-size: 38px; margin: 10px 0; }
        body.theme-heirloom .sacrament-declaration { color: #d4af37; font-size: 24px; text-transform: uppercase; letter-spacing: 2px; }


        .theme-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 0.6rem;
            border-radius: 6px;
            cursor: pointer;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .theme-btn.active { background: var(--navy); color: white; border-color: var(--navy); }

        .header {
            text-align: center;
            position: relative;
            z-index: 1;
            margin-bottom: 15px;
        }

        .diocese-name {
            font-family: 'Cinzel', serif;
            font-size: 24px;
            font-weight: 900;
            color: var(--navy);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 4px;
        }

        .certificate-title {
            font-family: 'Great Vibes', cursive;
            font-size: 42px;
            color: var(--gold);
            margin: 5px 0;
        }

        .content {
            text-align: center;
            position: relative;
            z-index: 1;
            line-height: 1.35;
            font-size: 16px;
            color: #334155;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: auto 0;
            width: 100%;
        }

        .content p {
            margin: 6px 0;
        }

        .field-label {
            color: #94a3b8;
            font-size: 13px;
            display: block;
            margin-top: 8px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .field-value {
            font-family: 'Cinzel', serif;
            font-size: 24px;
            font-weight: 700;
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            display: inline-block;
            min-width: 400px;
            color: var(--navy);
            padding-bottom: 5px;
        }

        .sacrament-declaration {
            font-family: 'Cinzel', serif;
            font-size: 26px;
            font-weight: 900;
            color: var(--gold);
            margin: 8px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 30px;
            margin: 16px auto 0 auto;
            text-align: center;
            width: 100%;
            max-width: 600px;
            box-sizing: border-box;
        }

        .footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            position: relative;
            z-index: 1;
            width: 100%;
        }

        .seal-container {
            text-align: center;
        }

        .signature-block {
            text-align: center;
            width: 250px;
        }

        .signature-block #qrcode {
            background: #ffffff;
            padding: 6px;
            border-radius: 8px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
            border: 1px solid rgba(0, 0, 0, 0.08);
            margin-bottom: 6px;
        }

        .signature-block #qrcode canvas, .signature-block #qrcode img {
            display: block;
            width: 90px;
            height: 90px;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
            image-rendering: pixelated;
        }

        .signature-line {
            border-top: 2px solid var(--navy);
            margin-top: 40px;
            margin-bottom: 8px;
        }

        .canonical-meta {
            margin-top: 15px;
            padding-top: 10px;
            width: 100%;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            font-family: monospace;
        }

        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }

        .lang-selector {
            background: white;
            border: 1px solid var(--navy);
            padding: 12px;
            border-radius: 8px;
            font-weight: 700;
        }

        body.theme-heirloom .heirloom-only { display: block !important; }
        body.theme-heirloom .heirloom-hide { display: none !important; }
    </style>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script src="../assets/js/qrcode.min.js"></script>
    <script>
        if (typeof QRCode === 'undefined') {
            document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>');
        }
    </script>
</head>
<body>
    <!-- Theme Selector Top Bar (Hidden on Print) -->
    <div class="no-print theme-selector" style="position: sticky; top: 0; background: white; padding: 0.8rem 2rem; border-bottom: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.05); z-index: 1000; display: flex; align-items: center; justify-content: center; gap: 1.5rem; font-family: 'Inter', sans-serif;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <h4 style="margin: 0; font-family: 'Outfit'; font-size: 0.9rem; color: #0f172a; white-space: nowrap;">Aesthetics:</h4>
            <div style="display: flex; gap: 0.5rem;">
                <button onclick="setTheme('default')" class="theme-btn active" data-theme="default" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">Classic</button>
                <button onclick="setTheme('heirloom')" class="theme-btn" data-theme="heirloom" style="background: linear-gradient(135deg, #1e3a8a, #d4af37); color: white; border: none; padding: 0.4rem 0.8rem; font-size: 0.75rem;">Heirloom ✨</button>
            </div>
        </div>
        
        <div style="height: 24px; width: 1px; background: #e2e8f0;"></div>

        <div style="display: flex; align-items: center; gap: 1rem;">
            <select class="lang-selector" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #e2e8f0; font-weight: 600; font-size: 0.8rem;" onchange="location.href='?id=<?php echo $id; ?>&lang='+this.value">
                <option value="en" <?php echo $lang == 'en' ? 'selected' : ''; ?>>English</option>
            </select>
            <button onclick="window.print()" style="background: var(--navy); color: white; border: none; padding: 0.5rem 1.2rem; border-radius: 6px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem;">
                <ion-icon name="print-outline"></ion-icon> Print
            </button>
            <button onclick="downloadPDF()" style="background: #0ea5e9; color: white; border: none; padding: 0.5rem 1.2rem; border-radius: 6px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem;">
                <ion-icon name="download-outline"></ion-icon> PDF
            </button>
            <a href="reception_list.php" style="font-size: 0.8rem; color: #64748b; text-decoration: none; font-weight: 600;">Exit</a>
        </div>
    </div>

    <script>
        function setTheme(theme) {
            document.body.classList.remove('theme-heirloom');
            if (theme !== 'default') {
                document.body.classList.add('theme-' + theme);
            }
            document.querySelectorAll('.theme-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('data-theme') === theme) {
                    btn.classList.add('active');
                }
            });
        }
    </script>

    <div class="certificate-container">
        <div class="header">
            <h1 class="diocese-name"><?php echo $DIOCESE_NAME; ?></h1>
            <div style="font-family: 'Outfit'; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 5px; margin-top: 5px;"><?php echo get_country_branding(); ?></div>
            <div class="heirloom-only" style="display: none; font-family: 'Cinzel'; font-size: 18px; color: #d4af37; letter-spacing: 4px; margin-top: 5px; font-weight: 700;">SACRAMENTAL CERTIFICATE</div>
            <div class="heirloom-only" style="display: none; font-family: 'Outfit'; font-size: 10px; color: #64748b; letter-spacing: 2px; margin-bottom: 0px;">HEIRLOOM EDITION</div>
            <h1 class="certificate-title">Certificate of Reception</h1>
        </div>

        <div class="content">
            <p><?php echo $t['certify']; ?></p>
            <div class="field-value"><?php echo h($record['first_name'] . ' ' . ($record['other_names'] ? $record['other_names'] . ' ' : '') . $record['last_name']); ?></div>
            <span class="field-label">Full Name of Subject</span>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                <div>
                    <span class="field-label">Date of Birth</span>
                    <div class="field-value" style="min-width: 180px; font-size: 18px;"><?php echo format_certificate_date($record['dob']); ?></div>
                </div>
                <div>
                    <span class="field-label">Place of Birth</span>
                    <div class="field-value" style="min-width: 180px; font-size: 18px;"><?php echo h($record['place_of_birth']); ?></div>
                </div>
            </div>

            <p style="margin-top: 20px;">
                having been previously baptized in the <span style="font-weight: 700; color: var(--navy);"><?php echo h($record['previous_denomination'] ?: 'Christian'); ?></span> denomination
                <?php if ($record['previous_baptism_church']): ?>
                    at <span style="font-weight: 700; color: var(--navy);"><?php echo h($record['previous_baptism_church']); ?></span>
                <?php endif; ?>
                <?php if ($record['previous_baptism_date']): ?>
                    on <span style="font-weight: 700; color: var(--navy);"><?php echo format_certificate_date($record['previous_baptism_date']); ?></span>
                <?php endif; ?>,<br>
                was this day solemnly received into the <span style="font-weight: 700; color: var(--navy);">Full Communion</span> of the Catholic Church
            </p>
            <div class="sacrament-declaration">Profession of Faith</div>

            <p><?php echo $t['in_parish']; ?></p>
            <div class="field-value"><?php echo h($record['parish_name']); ?></div>
            <p><?php echo $t['on_date']; ?></p>
            <div class="field-value"><?php echo format_certificate_date($record['date_of_reception']); ?></div>

            <div style="margin-top: 25px;">
                <span class="field-label">Minister & Sponsors</span>
                <div style="font-family: 'Cinzel', serif; font-size: 16px; color: var(--navy);">
                    Minister: <?php echo h($record['minister']); ?> | Sponsors: <?php echo h($record['godparents_sponsors']); ?>
                </div>
            </div>
            
            <div class="heirloom-only" style="display: none; margin-top: 15px; font-family: 'Cinzel', serif; font-size: 13px; color: var(--navy); border-top: 1px solid rgba(212, 175, 55, 0.2); padding-top: 10px;">
                <span style="color: #d4af37; font-weight: 700;">REGISTRY REF:</span> <?php echo h($record['entry_number'] ?: 'R-'.str_pad($record['reception_id'], 4, '0', STR_PAD_LEFT)); ?> &nbsp; | &nbsp; 
                <span style="color: #d4af37; font-weight: 700;">BOOK:</span> <?php echo h($record['register_book_number'] ?: 'I'); ?> &nbsp; | &nbsp; 
                <span style="color: #d4af37; font-weight: 700;">PAGE:</span> <?php echo h($record['page_number'] ?: '-'); ?>
            </div>
            
            <div class="heirloom-only" style="display: none; margin-top: 5px; font-family: 'Cinzel', serif; font-size: 14px; color: var(--navy);">
                <?php echo $t['given_at']; ?> <?php echo format_certificate_date(date('Y-m-d')); ?>
            </div>
        </div>

        <div class="footer">
            <div class="seal-container">
                <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; border: 4px solid #d4b34d; box-shadow: 0 0 0 2px #895c17, 0 10px 25px rgba(0,0,0,0.3), 0 0 40px rgba(212,179,77,0.2); margin-bottom: 10px; display: inline-flex; align-items: center; justify-content: center; background: #fff;">
                    <img src="../assets/img/seal.png" alt="Diocese Seal" style="width: 100%; height: 100%; object-fit: cover; transform: scale(1.20);">
                </div>
                <div style="font-size: 7px; color: var(--gold); font-weight: 800; text-transform: uppercase;"><?php echo $t['seal_text']; ?></div>
            </div>
            
            <div class="signature-block">
                <a href="<?php echo htmlspecialchars($verify_urls['click_url']); ?>" target="_blank" title="Scan or click to verify canonical record" style="text-decoration: none; display: inline-block;">
                    <div id="qrcode" style="margin-bottom: 6px; display: flex; justify-content: center;"></div>
                    <div style="font-size: 8px; color: #64748b; font-weight: 700; letter-spacing: 0.5px; text-align: center; margin-bottom: 4px;">SCAN / CLICK TO VERIFY</div>
                </a>
                <div class="signature-line"></div>
                <div style="font-size: 13px; font-weight: 800; color: #1e3a8a; text-transform: uppercase;"><?php echo $t['priest']; ?></div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 8px;"><?php echo $t['date_issue']; ?>: <?php echo date('d M Y'); ?></div>
                <div class="heirloom-only" style="display: none; font-size: 8px; color: #cbd5e1; margin-top: 5px; font-family: monospace; letter-spacing: 0.5px;">GUID: <?php echo $record['guid']; ?></div>
            </div>
        </div>

        <script>
            (function() {
                function renderQR() {
                    var qrTarget = document.getElementById("qrcode");
                    if (qrTarget && typeof QRCode !== 'undefined') {
                        qrTarget.innerHTML = '';
                        new QRCode(qrTarget, {
                            text: <?php echo json_encode($verify_urls['qr_url']); ?>,
                            width: 256,
                            height: 256,
                            colorDark : "#000000",
                            colorLight : "#ffffff",
                            correctLevel : QRCode.CorrectLevel.M
                        });
                    }
                }
                if (document.readyState === 'loading') {
                    document.addEventListener("DOMContentLoaded", renderQR);
                } else {
                    renderQR();
                }
            })();
        </script>
        
        <div class="canonical-meta heirloom-hide">
            Registry Ref: <?php echo h($record['register_book_number']); ?> / <?php echo h($record['page_number']); ?> / <?php echo h($record['entry_number']); ?><br>
            Generated via Hwange Diocesan Sacramental RMS
        </div>
    </div>

    <!-- PDF Generation Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.querySelector('.certificate-container');
            const opt = {
                margin:       0,
                filename:     'certificate.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            
            // Add a temporary class to fix any layout issues during PDF generation
            element.classList.add('pdf-generation');
            
            html2pdf().set(opt).from(element).save().then(() => {
                element.classList.remove('pdf-generation');
            });
        }
    </script>
</body>
</html>
