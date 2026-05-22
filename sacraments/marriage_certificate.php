<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Marriage Certificate - Printable View
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? null;
if (!$id) die("Record ID required.");

// Fetch record with full details
$record = db_fetch("
    SELECT m.*, 
           g.first_name as g_first, g.other_names as g_other, g.last_name as g_last,
           b.first_name as b_first, b.other_names as b_other, b.last_name as b_last,
           pa.parish_name, pa.location as parish_location
    FROM marriages m 
    JOIN parishioners g ON m.groom_person_id = g.person_id 
    JOIN parishioners b ON m.bride_person_id = b.person_id
    JOIN parishes pa ON m.parish_id = pa.parish_id 
    WHERE m.marriage_id = ?
", [$id]);

if (!$record) die("Canonical record not found in the archives.");

// Language Support
$lang = $_GET['lang'] ?? 'en';
$t = get_certificate_text($lang);

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <title>Marriage Certificate - <?php echo h($record['g_last'] . ' & ' . $record['b_last']); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Great+Vibes&family=Inter:wght@400;600;800&family=Outfit:wght@500;700&display=swap');

        :root {
            --maroon: #7c2d12;
            --gold: #d4af37;
            --navy: #0f172a;
            --parchment: #fffef2;
        }

        @page {
            size: A4;
            margin: 0;
        }


        @media print {
            .no-print { display: none !important; }
            body { background: white !important; margin: 0 !important; padding: 0 !important; }
            .certificate-container { 
                margin: 0 !important; 
                box-shadow: none !important; 
                width: 794px !important; 
                height: 1122px !important; 
                padding: 20px 40px !important;
                overflow: hidden !important;
            }
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
            border: 15px solid var(--maroon);
            outline: 5px solid var(--gold);
            outline-offset: -15px;
            overflow: hidden;
            transition: all 0.5s ease;
            display: flex;
            flex-direction: column;
        }

        /* Gold Leaf Theme */
        body.theme-gold-leaf .certificate-container {
            border-color: #92400e;
            outline-color: #f59e0b;
            background: #fffcf0;
        }
        body.theme-gold-leaf .certificate-title { color: #b45309; }
        body.theme-gold-leaf .sacrament-declaration { color: #92400e; }

        /* Traditional Latin Theme */
        body.theme-latin .certificate-container {
            border: 20px double #1e293b;
            outline: none;
            background: #ffffff;
        }
        body.theme-latin .certificate-title { font-family: 'Cinzel', serif; font-weight: 900; font-size: 42px; text-transform: uppercase; color: #1e293b; }
        body.theme-latin .diocese-name { color: #000; }

        /* Modern Minimal Theme */
        body.theme-minimal .certificate-container {
            border: 2px solid #e2e8f0;
            outline: none;
            box-shadow: none;
            background: white;
            padding: 20mm;
        }
        body.theme-minimal .certificate-title { font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 40px; color: #0f172a; text-transform: uppercase; letter-spacing: -1px; }

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

        body.theme-heirloom .certificate-title { color: #1e3a8a; font-size: 36px; margin: 10px 0; }
        body.theme-heirloom .sacrament-declaration { color: #d4af37; font-size: 22px; text-transform: uppercase; letter-spacing: 2px; }


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
        .theme-btn.active { background: var(--maroon); color: white; border-color: var(--maroon); }

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
            color: var(--maroon);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .certificate-title {
            font-family: 'Great Vibes', cursive;
            font-size: 40px;
            color: var(--gold);
            margin: 5px 0;
        }

        .content {
            text-align: center;
            position: relative;
            z-index: 1;
            line-height: 1.25;
            font-size: 15px;
            color: #334155;
            flex-grow: 1;
        }

        .couple-display {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin: 10px 0;
            padding: 10px;
            background: rgba(124, 45, 18, 0.02);
            border-radius: 15px;
        }

        .field-label {
            color: #94a3b8;
            font-size: 11px;
            display: block;
            margin-top: 10px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .field-value {
            font-family: 'Cinzel', serif;
            font-size: 22px;
            font-weight: 700;
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            display: inline-block;
            min-width: 400px;
            color: var(--navy);
            padding-bottom: 5px;
        }

        .sacrament-declaration {
            font-family: 'Cinzel', serif;
            font-size: 24px;
            font-weight: 900;
            color: var(--maroon);
            margin: 10px 0;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
            text-align: center;
        }

        .footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            position: relative;
            z-index: 1;
        }

        .seal {
            width: 130px;
            height: 130px;
            border: 4px double var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-weight: 900;
            font-size: 10px;
            text-align: center;
            padding: 15px;
            box-sizing: border-box;
            background: rgba(212, 175, 55, 0.05);
            text-transform: uppercase;
        }

        .signature-block {
            text-align: center;
            width: 250px;
        }

        .signature-line {
            border-top: 2px solid var(--maroon);
            margin-bottom: 5px;
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

        .btn-print {
            background: var(--maroon);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 800;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .lang-selector {
            background: white;
            border: 1px solid var(--maroon);
            padding: 12px;
            border-radius: 8px;
            font-weight: 700;
        }

                @media print {
            .no-print { display: none !important; }
            body { background: white !important; margin: 0 !important; padding: 0 !important; }
            .certificate-container { 
                margin: 0 !important; 
                box-shadow: none !important; 
                width: 210mm !important; 
                height: 297mm !important; 
                box-sizing: border-box !important;
                padding: 10mm 15mm 12mm 15mm !important; 
                overflow: hidden !important;
            }
            body.theme-heirloom .certificate-container {
                border: 15px solid #1e3a8a !important;
                outline: 2px solid #d4af37 !important;
                outline-offset: -10px !important;
            }
        }
    </style>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
    <!-- Theme Selector Top Bar (Hidden on Print) -->
    <div class="no-print theme-selector" style="position: sticky; top: 0; background: white; padding: 0.8rem 2rem; border-bottom: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.05); z-index: 1000; display: flex; align-items: center; justify-content: center; gap: 1.5rem; font-family: 'Inter', sans-serif;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <h4 style="margin: 0; font-family: 'Outfit'; font-size: 0.9rem; color: #0f172a; white-space: nowrap;">Aesthetics:</h4>
            <div style="display: flex; gap: 0.5rem;">
                <button onclick="setTheme('default')" class="theme-btn active" data-theme="default" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">Classic</button>
                <button onclick="setTheme('heirloom')" class="theme-btn" data-theme="heirloom" style="background: linear-gradient(135deg, #1e3a8a, #d4af37); color: white; border: none; padding: 0.4rem 0.8rem; font-size: 0.75rem;">Heirloom ✨</button>
                <button onclick="setTheme('gold-leaf')" class="theme-btn" data-theme="gold-leaf" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">Gold Leaf</button>
                <button onclick="setTheme('latin')" class="theme-btn" data-theme="latin" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">Latin</button>
                <button onclick="setTheme('minimal')" class="theme-btn" data-theme="minimal" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">Minimal</button>
            </div>
        </div>
        
        <div style="height: 24px; width: 1px; background: #e2e8f0;"></div>

        <div style="display: flex; align-items: center; gap: 1rem;">
            <select class="lang-selector" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #e2e8f0; font-weight: 600; font-size: 0.8rem;" onchange="location.href='?id=<?php echo $id; ?>&lang='+this.value">
                <option value="en" <?php echo $lang == 'en' ? 'selected' : ''; ?>>English</option>
                <option value="to" <?php echo $lang == 'to' ? 'selected' : ''; ?>>Tonga</option>
                <option value="nb" <?php echo $lang == 'nb' ? 'selected' : ''; ?>>Nambya</option>
                <option value="cw" <?php echo $lang == 'cw' ? 'selected' : ''; ?>>Chewa</option>
                <option value="nd" <?php echo $lang == 'nd' ? 'selected' : ''; ?>>Ndebele</option>
                <option value="sh" <?php echo $lang == 'sh' ? 'selected' : ''; ?>>Shona</option>
            
                <option value="la" <?php echo $lang == 'la' ? 'selected' : ''; ?>>Latin</option>
            </select>
            <button onclick="window.print()" style="background: var(--maroon); color: white; border: none; padding: 0.5rem 1.2rem; border-radius: 6px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem;">
                <ion-icon name="print-outline"></ion-icon> Print
            </button>
            <a href="marriage_list.php" style="font-size: 0.8rem; color: #64748b; text-decoration: none; font-weight: 600;">Exit</a>
        </div>
    </div>

    <script>
        function setTheme(theme) {
            document.body.classList.remove('theme-gold-leaf', 'theme-latin', 'theme-minimal');
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
            <div style="font-family: 'Outfit'; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 4px; margin-top: 5px;"><?php echo get_country_branding(); ?></div>
            <div class="heirloom-only" style="display: none; font-family: 'Cinzel'; font-size: 18px; color: #d4af37; letter-spacing: 4px; margin-top: 5px; font-weight: 700;">SACRAMENTAL CERTIFICATE</div>
            <div class="heirloom-only" style="display: none; font-family: 'Outfit'; font-size: 10px; color: #64748b; letter-spacing: 2px; margin-bottom: 0px;">HEIRLOOM EDITION</div>
            <div class="certificate-title"><?php echo $t['cert_marriage']; ?></div>
        </div>

        <div class="content">
            <?php if ($lang == 'en'): ?>
                <p><?php echo $t['joined_in']; ?></p>
            <?php endif; ?>
            <div class="sacrament-declaration"><?php echo $t['married']; ?></div>

            <div class="couple-display">
                <div>
                    <div class="field-value"><?php echo h($record['g_first'] . ' ' . ($record['g_other'] ? $record['g_other'] . ' ' : '') . $record['g_last']); ?></div>
                    <span class="field-label"><?php echo $t['groom']; ?></span>
                </div>
                <div style="font-family: 'Great Vibes'; font-size: 24px; color: var(--gold); margin: 5px 0;"><?php echo $t['and']; ?></div>
                <div>
                    <div class="field-value"><?php echo h($record['b_first'] . ' ' . ($record['b_other'] ? $record['b_other'] . ' ' : '') . $record['b_last']); ?></div>
                    <span class="field-label"><?php echo $t['bride']; ?></span>
                </div>
            </div>

            <p><?php echo $t['in_parish']; ?></p>
            <div class="field-value" style="min-width: 300px;"><?php echo h($record['parish_name']); ?></div>
            
            <p><?php echo $t['on_date']; ?></p>
            <div class="field-value" style="min-width: 250px;"><?php echo format_certificate_date($record['date_of_marriage']); ?></div>

            <div class="details-grid">
                <div>
                    <span class="field-label"><?php echo $t['officiant']; ?></span>
                    <div style="font-family: 'Cinzel', serif; font-weight: 700; color: var(--navy);"><?php echo h($record['officiant'] ?: '-'); ?></div>
                </div>
                <div>
                    <span class="field-label">Godparents</span>
                    <div style="font-family: 'Cinzel', serif; font-weight: 700; color: var(--navy);"><?php echo h($record['witnesses_names'] ?: '-'); ?></div>
                </div>
            </div>

            <div class="heirloom-only" style="display: none; margin-top: 10px; font-family: 'Cinzel', serif; font-size: 13px; color: var(--navy); border-top: 1px solid rgba(212, 175, 55, 0.2); padding-top: 10px;">
                <span style="color: #d4af37; font-weight: 700;"><?php echo strtoupper($t['canonical_ref']); ?>:</span> M-<?php echo h(str_pad($record['marriage_id'], 5, '0', STR_PAD_LEFT)); ?> &nbsp; | &nbsp; 
                <span style="color: #d4af37; font-weight: 700;">BOOK:</span> <?php echo h($record['register_book_number'] ?: 'I'); ?> &nbsp; | &nbsp; 
                <span style="color: #d4af37; font-weight: 700;">PAGE:</span> <?php echo h($record['page_number'] ?: '-'); ?>
            </div>

            <div class="heirloom-only" style="display: none; margin-top: 5px; font-family: 'Cinzel', serif; font-size: 14px; color: var(--navy);">
                <?php echo $t['given_at']; ?> <?php echo format_certificate_date(date('Y-m-d')); ?>
            </div>
        </div>

        <div class="footer" style="margin-top: 15px;">

            <div class="seal-container" style="text-align: center;">
                <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; border: 4px solid #d4b34d; box-shadow: 0 0 0 2px #895c17, 0 10px 25px rgba(0,0,0,0.3), 0 0 40px rgba(212,179,77,0.2); margin-bottom: 8px; display: inline-flex; align-items: center; justify-content: center; background: #fff;">
                    <img src="../assets/img/seal.png" alt="Diocese Seal" style="width: 100%; height: 100%; object-fit: cover; transform: scale(1.20);">
                </div>
                <div style="font-size: 7px; color: var(--gold); font-weight: 800; text-transform: uppercase;"><?php echo $t['seal_text']; ?></div>
            </div>
            
            <div class="signature-block">
                <div id="qrcode" style="margin-bottom: 10px; display: flex; justify-content: center;"></div>
                <div class="signature-line"></div>
                <div style="font-size: 13px; font-weight: 800; color: #1e3a8a; text-transform: uppercase;"><?php echo $t['priest']; ?></div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 5px;"><?php echo $t['date_issue']; ?>: <?php echo date('d M Y'); ?></div>
                <div class="heirloom-only" style="display: none; font-size: 8px; color: #cbd5e1; margin-top: 5px; font-family: monospace; letter-spacing: 0.5px;">GUID: <?php echo $record['guid']; ?></div>
            </div>
        </div>


        <script>
            function setTheme(theme) {
                // Remove existing themes
                document.body.classList.remove('theme-gold-leaf', 'theme-latin', 'theme-minimal', 'theme-heirloom');
                
                // Add new theme if not default
                if (theme !== 'default') {
                    document.body.classList.add('theme-' + theme);
                }

                // Update UI
                document.querySelectorAll('.theme-btn').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.getAttribute('data-theme') === theme) {
                        btn.classList.add('active');
                    }
                });
            }

            new QRCode(document.getElementById("qrcode"), {
                text: "Verification GUID: <?php echo $record['guid']; ?> | Ref: M-<?php echo str_pad($record['marriage_id'], 5, '0', STR_PAD_LEFT); ?>",
                width: 70,
                height: 70,
                colorDark : "#0f172a",
                colorLight : "#fffef2",
                correctLevel : QRCode.CorrectLevel.H
            });
        </script>
        
        <div class="canonical-meta heirloom-hide">
            <?php echo $t['canonical_ref']; ?>: M-<?php echo h(str_pad($record['marriage_id'], 5, '0', STR_PAD_LEFT)); ?> | Book: <?php echo h($record['register_book_number'] ?: 'I'); ?> | Page: <?php echo h($record['page_number'] ?: '-'); ?> <br>
            <strong>GUID:</strong> <span style="font-family: monospace;"><?php echo $record['guid']; ?></span> <br>
            © <?php echo get_diocese_branding(); ?> - Zimbabwe Catholic Records Exchange (ZCRE)
        </div>
    </div>

    <style>
        body.theme-heirloom .heirloom-only { display: block !important; }
        body.theme-heirloom .heirloom-hide { display: none !important; }
    </style>


    <style>
        body.theme-heirloom .heirloom-only { display: block !important; }
    </style>
</body>
</html>




