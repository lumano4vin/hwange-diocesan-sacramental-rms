<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Official Marriage Certificate - Premium Canonical Extract
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? 0;
$lang = $_GET['lang'] ?? 'en';

// Handle Security
handle_session_timeout();

// Fetch Marriage Details
$sql = "SELECT m.*, 
               p1.first_name as g_first, p1.last_name as g_last, p1.dob as g_dob,
               p2.first_name as b_first, p2.last_name as b_last, p2.dob as b_dob,
               pr.parish_name, pr.location
        FROM marriages m
        JOIN parishioners p1 ON m.groom_person_id = p1.person_id
        JOIN parishioners p2 ON m.bride_person_id = p2.person_id
        JOIN parishes pr ON m.parish_id = pr.parish_id
        WHERE m.marriage_id = ?";

$record = db_fetch($sql, [$id]);

if (!$record) {
    echo "<h1>Record Not Found</h1>";
    exit;
}
// Canonical Access Check
$can_print = has_record_access($record['parish_id'], 'print');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marriage Extract - <?php echo h($record['g_first'] . ' & ' . $record['b_first']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Great+Vibes&family=Inter:wght@400;600&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 portrait; margin: 0; }
        @media print {
            body { background: white !important; margin: 0 !important; padding: 0 !important; }
            .certificate-container { 
                box-shadow: none !important; 
                border: 10px solid #be185d !important; 
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

        body { background: #fff1f2; padding: 20px 0; font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; }
        
        .certificate-container {
            width: 794px;
            height: 1122px;
            margin: 0 auto;
            background: white;
            padding: 25px 40px;
            box-shadow: 0 0 50px rgba(131, 24, 67, 0.1);
            position: relative;
            overflow: hidden;
            border: 15px solid #831843;
            outline: 2px solid #831843;
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
            color: rgba(131, 24, 67, 0.02);
            font-family: 'Cinzel', serif;
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            text-transform: uppercase;
            letter-spacing: 12px;
        }

        .header { text-align: center; margin-bottom: 10px; position: relative; z-index: 1; }
        .diocese-name { font-family: 'Cinzel', serif; font-size: 1.5rem; color: #831843; margin-bottom: 2px; text-transform: uppercase; }
        .document-title { font-family: 'Outfit', sans-serif; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 2px; color: #be185d; margin-top: 2px; border-top: 1px solid #fce7f3; border-bottom: 1px solid #fce7f3; display: inline-block; padding: 2px 12px; }

        .title-fancy { font-family: 'Great Vibes', cursive; font-size: 3rem; color: #be185d; margin-top: 4px; text-shadow: 1px 1px 0 rgba(0,0,0,0.05); }

        .content { position: relative; z-index: 1; margin-top: 5px; font-size: 0.85rem; line-height: 1.2; color: #0f172a; text-align: center; flex-grow: 0; }
        .name-highlight { font-family: 'Outfit', sans-serif; font-size: 1.5rem; color: #831843; font-weight: 700; margin: 4px 0; display: block; border-bottom: 2px solid #fbcfe8; display: inline-block; padding: 0 30px; }
        .joiner { font-family: 'Great Vibes', cursive; font-size: 1.8rem; margin: 1px 0; color: #db2777; }

        .data-grid { margin-top: 12px; text-align: left; max-width: 580px; margin-left: auto; margin-right: auto; }
        .data-row { margin-bottom: 3px; border-bottom: 1px solid #fce7f3; padding-bottom: 1px; display: flex; align-items: flex-end; }
        .label { width: 170px; font-weight: 600; color: #be185d; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; }
        .value { flex: 1; font-family: 'Outfit', sans-serif; font-size: 0.95rem; color: #1e293b; padding-left: 8px; font-weight: 700; }

        .footer { margin-top: auto; display: flex; justify-content: space-between; align-items: flex-end; position: relative; z-index: 1; padding-bottom: 5px; }
        .sig-box { width: 180px; border-top: 1px solid #831843; text-align: center; padding-top: 4px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; }
        
        .official-notice { text-align: center; font-size: 0.65rem; color: #94a3b8; margin-top: 8px; border-top: 1px solid #fce7f3; padding-top: 4px; }
        
        .print-fab { position: fixed; bottom: 30px; right: 30px; z-index: 100; }
        .btn-fab { background: #be185d; color: white; border: none; padding: 15px 25px; border-radius: 50px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 15px rgba(190, 24, 93, 0.3); display: flex; align-items: center; gap: 10px; font-family: 'Outfit', sans-serif; }
        
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
            border: 1px solid #fce7f3;
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
            background: #fff1f2;
            color: #be185d;
            transition: all 0.2s;
        }
        .lang-btn.active {
            background: #be185d;
            color: white;
        }
    </style>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
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
        <?php if ($can_print): ?>
            <button onclick="window.print()" class="btn-fab">
                <ion-icon name="print-outline"></ion-icon>
                Print Marriage Extract
            </button>
        <?php else: ?>
            <div style="background: #be185d; color: white; padding: 12px 20px; border-radius: 50px; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">
                <ion-icon name="lock-closed-outline"></ion-icon>
                Official Extract must be requested from <?php echo h($record['parish_name']); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="certificate-container">
        <div class="watermark"><?php echo strtoupper(DIOCESE_CITY); ?></div>
        
        <?php if (($record['status'] ?? 'Verified') !== 'Verified'): ?>
        <div class="draft-watermark" style="position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 4rem; color: rgba(190, 24, 93, 0.05); font-weight: 900; z-index: 10; pointer-events: none; white-space: nowrap; text-transform: uppercase; border: 5px solid rgba(190, 24, 93, 0.05); padding: 10px; font-family: 'Outfit', sans-serif; letter-spacing: 10px;">UNVERIFIED DRAFT</div>
        <?php endif; ?>
        


        <header class="header">
            <div class="diocese-name"><?php echo DIOCESE_NAME; ?></div>
            <div class="document-title"><?php echo get_localized_label('marriage_cert_title', $lang); ?></div>
            <div class="title-fancy">Holy Matrimony</div>
        </header>

        <section class="content">
            <?php echo get_localized_label('certify', $lang); ?> <br>
            <span class="name-highlight"><?php echo h($record['g_first'] . ' ' . $record['g_last']); ?></span><br>
            <div class="joiner">and</div>
            <span class="name-highlight"><?php echo h($record['b_first'] . ' ' . $record['b_last']); ?></span><br>
            
            <div class="data-grid">
                <div class="data-row">
                    <span class="label"><?php echo get_localized_label('date_of_marriage', $lang); ?></span>
                    <span class="value"><?php echo date('d F Y', strtotime($record['date_of_marriage'])); ?></span>
                </div>
                <div class="data-row">
                    <span class="label"><?php echo get_localized_label('parish', $lang); ?></span>
                    <span class="value"><?php echo h($record['parish_name']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label"><?php echo get_localized_label('minister_officiant', $lang); ?></span>
                    <span class="value"><?php echo h($record['officiant']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label"><?php echo get_localized_label('witnesses', $lang); ?></span>
                    <span class="value"><?php echo h($record['witnesses_names']); ?></span>
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
                $hash_val = !empty($record['verification_hash']) ? $record['verification_hash'] : md5($id . 'MRG');
                $verify_url = urlencode("$protocol://$host/verify.php?id={$id}&hash={$hash_val}");
                $qr_api = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=$verify_url&choe=UTF-8";
            ?>
            <div class="qr-container" style="text-align: center; width: 180px;">
                <img src="<?php echo $qr_api; ?>" alt="Verify QR" style="width: 85px; height: 85px; margin-bottom: 5px;">
            </div>

            <!-- Signature Line -->
            <div class="sig-box" style="width: 180px; border-top: 2px solid #831843; text-align: center; padding-top: 8px; font-size: 0.75rem; font-weight: 700; color: #831843; text-transform: uppercase;">
                <?php echo get_localized_label('priest', $lang); ?>
            </div>
        </footer>

        <div class="official-notice" style="text-align: center; font-size: 0.7rem; color: #64748b; border-top: 1px solid #fce7f3; padding-top: 10px; margin-top: 10px;">
            <p style="margin-bottom: 4px; font-weight: 600;"><strong><?php echo get_localized_label('register_ref', $lang); ?>:</strong> Book <?php echo h($record['register_book_number'] ?? '___'); ?> | Page <?php echo h($record['page_number'] ?? '___'); ?> | Entry <?php echo h($record['entry_number'] ?? '___'); ?></p>
            <p>&copy; <?php echo date('Y'); ?> <?php echo DIOCESE_NAME; ?> Records Management System &bull; Verified Record ID: <?php echo generate_record_id('MRG', $id); ?></p>
        </div>
    </div>

</body>
</html>



