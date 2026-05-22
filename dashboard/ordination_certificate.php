<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Official Ordination / Profession Certificate - Premium Canonical Extract
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? 0;
$lang = $_GET['lang'] ?? 'en';

// Fetch Ordination Details
$sql = "SELECT op.*, p.first_name, p.last_name, p.dob, p.title, p.father_name, p.mother_name, pr.parish_name, pr.location
        FROM ordinations_professions op
        JOIN parishioners p ON op.person_id = p.person_id
        JOIN parishes pr ON op.parish_id = pr.parish_id
        WHERE op.record_id = ?";

$record = db_fetch($sql, [$id]);

if (!$record) {
    echo "<h1>Record Not Found</h1>";
    exit;
}

$title_map = [
    'Diaconate' => 'Sacred Order of the Diaconate',
    'Priesthood' => 'Sacred Order of the Priesthood',
    'Episcopate' => 'Sacred Order of the Episcopate',
    'First Vows' => 'Religious Profession of First Vows',
    'Perpetual Profession' => 'Perpetual Profession of Religious Vows'
];
$title_display = $title_map[$record['record_type']] ?? 'Sacred Record';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Extract - <?php echo h($record['first_name'] . ' ' . $record['last_name']); ?></title>
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
                border: 10px solid #7c3aed !important; 
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

        body { background: #f5f3ff; padding: 20px 0; font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; }
        
        .certificate-container {
            width: 794px;
            height: 1122px;
            margin: 0 auto;
            background: white;
            padding: 25px 40px;
            box-shadow: 0 0 50px rgba(124, 58, 237, 0.1);
            position: relative;
            overflow: hidden;
            border: 15px solid #7c3aed;
            outline: 2px solid #7c3aed;
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
            color: rgba(124, 58, 237, 0.02);
            font-family: 'Cinzel', serif;
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            text-transform: uppercase;
            letter-spacing: 12px;
        }

        .header { text-align: center; margin-bottom: 10px; position: relative; z-index: 1; }
        .diocese-name { font-family: 'Cinzel', serif; font-size: 1.5rem; color: #4c1d95; margin-bottom: 2px; text-transform: uppercase; }
        .document-title { font-family: 'Outfit', sans-serif; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 2px; color: #7c3aed; margin-top: 2px; border-top: 1px solid #ede9fe; border-bottom: 1px solid #ede9fe; display: inline-block; padding: 2px 12px; }

        .ordination-icon { font-size: 2.5rem; color: #8b5cf6; margin-top: 5px; }

        .content { position: relative; z-index: 1; margin-top: 5px; font-size: 0.85rem; line-height: 1.2; color: #1e1b4b; text-align: center; flex-grow: 0; }
        .name-highlight { font-family: 'Outfit', sans-serif; font-size: 1.5rem; color: #4c1d95; font-weight: 700; margin: 4px 0; display: block; border-bottom: 2px solid #ddd6fe; display: inline-block; padding: 0 30px; }

        .data-grid { margin-top: 10px; text-align: left; max-width: 580px; margin-left: auto; margin-right: auto; }
        .data-row { margin-bottom: 3px; display: flex; align-items: flex-end; border-bottom: 1px solid #ddd6fe; padding-bottom: 1px; }
        .label { width: 170px; font-weight: 600; color: #7c3aed; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; }
        .value { flex: 1; font-family: 'Outfit', sans-serif; font-size: 0.95rem; color: #1e1b4b; padding-left: 8px; font-weight: 700; }

        .footer { margin-top: auto; display: flex; justify-content: space-between; align-items: flex-end; position: relative; z-index: 1; padding-bottom: 5px; }
        .sig-box { width: 180px; border-top: 1px solid #4c1d95; text-align: center; padding-top: 4px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: #4c1d95; }
        
        .official-notice { text-align: center; font-size: 0.65rem; color: #94a3b8; margin-top: 8px; border-top: 1px solid #ede9fe; padding-top: 4px; }
        
        .print-fab { position: fixed; bottom: 30px; right: 30px; z-index: 100; }
        .btn-fab { background: #7c3aed; color: white; border: none; padding: 15px 25px; border-radius: 50px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 15px rgba(124, 58, 237, 0.3); display: flex; align-items: center; gap: 10px; font-family: 'Outfit', sans-serif; }
        
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
            border: 1px solid #ede9fe;
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
            background: #ede9fe;
            color: #4c1d95;
            transition: all 0.2s;
        }
        .lang-btn.active {
            background: #7c3aed;
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
            <ion-icon name="print-outline" style="font-size: 1.2rem;"></ion-icon>
            Print Official Extract
        </button>
    </div>

    <div class="certificate-container">
        <div class="watermark"><?php echo strtoupper(DIOCESE_CITY); ?></div>
        
        <?php if (($record['status'] ?? 'Verified') !== 'Verified'): ?>
        <div class="draft-watermark" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 6rem; color: rgba(124, 58, 237, 0.08); font-weight: 900; z-index: 10; pointer-events: none; white-space: nowrap; text-transform: uppercase; border: 15px solid rgba(124, 58, 237, 0.08); padding: 20px; font-family: 'Outfit', sans-serif;">UNVERIFIED DRAFT</div>
        <?php endif; ?>
        


        <header class="header">
            <div class="diocese-name"><?php echo DIOCESE_NAME; ?></div>
            <div class="document-title"><?php echo get_localized_label('cert_title', $lang); ?></div>
            <div class="ordination-icon">
                <ion-icon name="ribbon-outline"></ion-icon>
            </div>
        </header>

        <section class="content">
            To all who shall see this present document,<br>
            Greetings in our Lord Jesus Christ.<br><br>
            
            <?php echo get_localized_label('certify', $lang); ?> <br>
            <span class="name-highlight"><?php echo h($record['title'] . ' ' . $record['first_name'] . ' ' . $record['last_name']); ?></span><br>
            <?php if (!empty($record['congregation'])): ?>
                of the <b><?php echo h($record['congregation']); ?></b><br>
            <?php endif; ?>
            
            <div class="data-grid">
                <div class="data-row">
                    <span class="label">Canonical Status</span>
                    <span class="value"><?php echo h($title_display); ?></span>
                </div>
                <div class="data-row">
                    <span class="label"><?php echo get_localized_label('minister_officiant', $lang); ?></span>
                    <span class="value"><?php echo date('d F Y', strtotime($record['event_date'])); ?></span>
                </div>
                <div class="data-row">
                    <span class="label"><?php echo get_localized_label('parish', $lang); ?></span>
                    <span class="value"><?php echo h($record['place'] ?: $record['parish_name']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label"><?php echo get_localized_label('minister', $lang); ?></span>
                    <span class="value"><?php echo h($record['celebrant_superior']); ?></span>
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
                $hash_val = !empty($record['verification_hash']) ? $record['verification_hash'] : md5($id . 'ORD');
                $verify_url = urlencode("$protocol://$host/verify.php?id={$id}&hash={$hash_val}");
                $qr_api = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=$verify_url&choe=UTF-8";
            ?>
            <div class="qr-container" style="text-align: center; width: 180px;">
                <img src="<?php echo $qr_api; ?>" alt="Verify QR" style="width: 85px; height: 85px; margin-bottom: 5px;">
            </div>

            <!-- Signature Line -->
            <div class="sig-box" style="width: 180px; border-top: 2px solid #4c1d95; text-align: center; padding-top: 8px; font-size: 0.75rem; font-weight: 700; color: #4c1d95; text-transform: uppercase;">
                Bishop / Superior General
            </div>
        </footer>

        <div class="official-notice" style="text-align: center; font-size: 0.7rem; color: #64748b; border-top: 1px solid #ede9fe; padding-top: 10px; margin-top: 10px;">
            <p style="margin-bottom: 4px; font-weight: 600;"><strong><?php echo get_localized_label('register_ref', $lang); ?>:</strong> Book <?php echo h($record['register_book_number'] ?? '___'); ?> | Page <?php echo h($record['page_number'] ?? '___'); ?> | Entry <?php echo h($record['entry_number'] ?? '___'); ?></p>
            <p>&copy; <?php echo date('Y'); ?> <?php echo DIOCESE_NAME; ?> Records Management System &bull; Verified Record ID: <?php echo generate_record_id('ORD', $id); ?></p>
        </div>
    </div>

</body>
</html>



