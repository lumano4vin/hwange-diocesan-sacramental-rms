<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Official Confirmation Certificate - Premium Canonical Extract
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? 0;
$lang = $_GET['lang'] ?? 'en';

// Fetch Confirmation Details
$sql = "SELECT c.*, p.first_name, p.last_name, p.dob, pr.parish_name, pr.location
        FROM confirmations c
        JOIN parishioners p ON c.person_id = p.person_id
        JOIN parishes pr ON c.parish_id = pr.parish_id
        WHERE c.confirmation_id = ?";

$record = db_fetch($sql, [$id]);

if (!$record) {
    echo "<h1>Record Not Found</h1>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation Extract - <?php echo h($record['first_name'] . ' ' . $record['last_name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Great+Vibes&family=Inter:wght@400;600&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 portrait; margin: 0; }
        @media print {
            body { background: white !important; margin: 0 !important; padding: 0 !important; }
            .certificate-container { 
                box-shadow: none !important; 
                border: 10px solid #d97706 !important; 
                margin: 0 !important; 
                width: 794px !important; 
                height: 1122px !important; 
                padding: 30px 50px !important;
                page-break-after: always;
            }
            .no-print { display: none !important; }
            .print-fab { display: none !important; }
            .digital-seal { top: 25px !important; right: 25px !important; width: 85px !important; height: 85px !important; }
        }

        body { background: #fffcf0; padding: 20px 0; font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; }
        
        .certificate-container {
            width: 794px;
            height: 1122px;
            margin: 0 auto;
            background: white;
            padding: 25px 40px;
            box-shadow: 0 0 50px rgba(217, 119, 6, 0.1);
            position: relative;
            overflow: hidden;
            border: 15px solid #d97706;
            outline: 2px solid #d97706;
            outline-offset: -22px;
            display: flex;
            flex-direction: column;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 5rem;
            color: rgba(217, 119, 6, 0.02);
            font-family: 'Cinzel', serif;
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            text-transform: uppercase;
            letter-spacing: 12px;
        }

        .header { text-align: center; margin-bottom: 10px; position: relative; z-index: 1; }
        .diocese-name { font-family: 'Cinzel', serif; font-size: 1.5rem; color: #92400e; margin-bottom: 2px; text-transform: uppercase; }
        .document-title { font-family: 'Outfit', sans-serif; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 2px; color: #d97706; margin-top: 2px; border-top: 1px solid #fef3c7; border-bottom: 1px solid #fef3c7; display: inline-block; padding: 2px 12px; }

        .flame-icon { font-size: 2.5rem; color: #f59e0b; margin-top: 5px; }

        .content { position: relative; z-index: 1; margin-top: 5px; font-size: 0.85rem; line-height: 1.2; color: #0f172a; text-align: center; flex-grow: 0; }
        .name-highlight { font-family: 'Outfit', sans-serif; font-size: 1.5rem; color: #92400e; font-weight: 700; margin: 4px 0; display: block; border-bottom: 2px solid #fde68a; display: inline-block; padding: 0 30px; }

        .data-grid { margin-top: 10px; text-align: left; max-width: 580px; margin-left: auto; margin-right: auto; }
        .data-row { margin-bottom: 3px; display: flex; align-items: flex-end; border-bottom: 1px solid #fef3c7; padding-bottom: 1px; }
        .label { width: 170px; font-weight: 600; color: #b45309; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; }
        .value { flex: 1; font-family: 'Outfit', sans-serif; font-size: 0.95rem; color: #1e293b; padding-left: 8px; font-weight: 700; }

        .footer { margin-top: auto; display: flex; justify-content: space-between; align-items: flex-end; position: relative; z-index: 1; padding-bottom: 5px; }
        .sig-box { width: 180px; border-top: 1px solid #92400e; text-align: center; padding-top: 4px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: #92400e; }
        
        .official-notice { text-align: center; font-size: 0.65rem; color: #94a3b8; margin-top: 8px; border-top: 1px solid #fef3c7; padding-top: 4px; }
        
        .print-fab { position: fixed; bottom: 30px; right: 30px; z-index: 100; }
        .btn-fab { background: #d97706; color: white; border: none; padding: 15px 25px; border-radius: 50px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 15px rgba(217, 119, 6, 0.3); display: flex; align-items: center; gap: 10px; font-family: 'Outfit', sans-serif; }
        
        /* Language Selector */
        .lang-selector {
            position: fixed;
            top: 30px;
            right: 30px;
            z-index: 100;
            background: white;
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #fef3c7;
            display: flex;
            gap: 5px;
        }
        .lang-btn {
            border: none;
            padding: 5px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            background: #fef3c7;
            color: #b45309;
            transition: all 0.2s;
        }
        .lang-btn.active {
            background: #d97706;
            color: white;
        }
    </style>
</head>
<body>

    <div class="lang-selector no-print">
        <button class="lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>" onclick="location.search='?id=<?php echo $id; ?>&lang=en'">EN</button>
        <button class="lang-btn <?php echo $lang === 'nd' ? 'active' : ''; ?>" onclick="location.search='?id=<?php echo $id; ?>&lang=nd'">ND</button>
        <button class="lang-btn <?php echo $lang === 'sh' ? 'active' : ''; ?>" onclick="location.search='?id=<?php echo $id; ?>&lang=sh'">SH</button>
        <button class="lang-btn <?php echo $lang === 'nb' ? 'active' : ''; ?>" onclick="location.search='?id=<?php echo $id; ?>&lang=nb'">NB</button>
        <button class="lang-btn <?php echo $lang === 'to' ? 'active' : ''; ?>" onclick="location.search='?id=<?php echo $id; ?>&lang=to'">TO</button>
        <button class="lang-btn <?php echo $lang === 'cw' ? 'active' : ''; ?>" onclick="location.search='?id=<?php echo $id; ?>&lang=cw'">CW</button><button class="lang-btn <?php echo $lang === 'la' ? 'active' : ''; ?>" onclick="location.search='?id=<?php echo $id; ?>&lang=la'">LA</button>
    </div>


    <div class="print-fab no-print">
        <button onclick="window.print()" class="btn-fab">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"></path></svg>
            Print Confirmation Extract
        </button>
    </div>

    <div class="certificate-container">
        <div class="watermark"><?php echo strtoupper(DIOCESE_CITY); ?></div>

        <?php if (($record['status'] ?? 'Verified') !== 'Verified'): ?>
        <div class="draft-watermark" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 6rem; color: rgba(217, 119, 6, 0.08); font-weight: 900; z-index: 10; pointer-events: none; white-space: nowrap; text-transform: uppercase; border: 15px solid rgba(217, 119, 6, 0.08); padding: 20px; font-family: 'Outfit', sans-serif;">UNVERIFIED DRAFT</div>
        <?php endif; ?>
        


        <header class="header">
            <div class="diocese-name"><?php echo DIOCESE_NAME; ?></div>
            <div class="document-title"><?php echo get_localized_label('cert_title', $lang); ?></div>
            <div class="flame-icon">
                <ion-icon name="flame"></ion-icon>
            </div>
        </header>

        <section class="content">
            <?php echo get_localized_label('certify', $lang); ?> <br>
            <span class="name-highlight"><?php echo h($record['first_name'] . ' ' . $record['last_name']); ?></span><br>
            
            <div class="data-grid">
                <div class="data-row">
                    <span class="label"><?php echo get_localized_label('date_of_confirmation', $lang); ?></span>
                    <span class="value"><?php echo date('d F Y', strtotime($record['date_of_confirmation'])); ?></span>
                </div>
                <div class="data-row">
                    <span class="label"><?php echo get_localized_label('parish', $lang); ?></span>
                    <span class="value"><?php echo h($record['parish_name']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label"><?php echo get_localized_label('minister', $lang); ?></span>
                    <span class="value"><?php echo h($record['minister']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label"><?php echo get_localized_label('sponsor', $lang); ?></span>
                    <span class="value"><?php echo h($record['sponsor']); ?></span>
                </div>

            </div>
        </section>

        <footer class="footer" style="margin-top: auto; display: flex; justify-content: space-between; align-items: flex-end; position: relative; z-index: 1; padding-bottom: 5px;">
            <!-- Golden Seal -->
            <div class="seal-container" style="width: 180px; text-align: left;">
                <img src="../assets/img/diocesan_logo.png" alt="Diocesan Seal" style="width: 100px; height: 100px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));" onerror="this.src='../assets/img/seal_placeholder.png'">
            </div>
            
            <!-- Secure Verification QR -->
            <?php 
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $hash_val = !empty($record['verification_hash']) ? $record['verification_hash'] : md5($id . 'CNF');
                $verify_url = urlencode("$protocol://$host/verify.php?id={$id}&hash={$hash_val}");
                $qr_api = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=$verify_url&choe=UTF-8";
            ?>
            <div class="qr-container" style="text-align: center; width: 180px;">
                <img src="<?php echo $qr_api; ?>" alt="Verify QR" style="width: 85px; height: 85px; margin-bottom: 5px;">
            </div>

            <!-- Signature Line -->
            <div class="sig-box" style="width: 180px; border-top: 2px solid #92400e; text-align: center; padding-top: 8px; font-size: 0.75rem; font-weight: 700; color: #92400e; text-transform: uppercase;">
                <?php echo get_localized_label('priest', $lang); ?>
            </div>
        </footer>

        <div class="official-notice" style="text-align: center; font-size: 0.7rem; color: #64748b; border-top: 1px solid #fef3c7; padding-top: 10px; margin-top: 10px;">
            <p style="margin-bottom: 4px; font-weight: 600;"><strong><?php echo get_localized_label('register_ref', $lang); ?>:</strong> Book <?php echo h($record['register_book_number'] ?? '___'); ?> | Page <?php echo h($record['page_number'] ?? '___'); ?> | Entry <?php echo h($record['entry_number'] ?? '___'); ?></p>
            <p>&copy; <?php echo date('Y'); ?> <?php echo DIOCESE_NAME; ?> Records Management System &bull; Verified Record ID: <?php echo generate_record_id('CNF', $id); ?></p>
        </div>
    </div>

</body>
</html>



