<?php
/**
 * Hwange Diocese Records Management System
 * Letter of Good Standing - Printable Form
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$id = $_GET['id'] ?? null;
$destination = $_GET['destination'] ?? 'To Whom It May Concern';
$reason = $_GET['reason'] ?? 'General Reference';

if (!$id) die("Parishioner ID required.");

// Fetch parishioner and their baptismal details
$record = db_fetch("
    SELECT p.*, b.baptism_id, b.date_of_baptism, b.register_book_number, b.page_number, b.entry_number,
           pr.parish_name as baptism_parish_name,
           cp.parish_name as current_parish_name
    FROM parishioners p
    LEFT JOIN baptisms b ON p.person_id = b.person_id AND b.status = 'Verified'
    LEFT JOIN parishes pr ON b.parish_id = pr.parish_id
    LEFT JOIN parishes cp ON p.current_parish_id = cp.parish_id
    WHERE p.person_id = ?
", [$id]);

if (!$record) die("Parishioner record not found.");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Letter of Good Standing - <?php echo h($record['first_name'] . ' ' . $record['last_name']); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Great+Vibes&family=Inter:wght@400;600;800&family=Outfit:wght@500;700&display=swap');

        :root {
            --blue: #1e3a8a;
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
                width: 210mm !important; 
                height: 297mm !important; 
                box-sizing: border-box !important;
                padding: 15mm 20mm !important; 
                border: none !important;
                outline: none !important;
            }
        }

        body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

        .certificate-container {
            width: 794px;
            height: 1122px;
            padding: 40px 60px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid var(--blue);
            padding-bottom: 20px;
            margin-bottom: 40px;
        }

        .logo-box {
            width: 100px;
            height: 100px;
            margin-right: 20px;
        }

        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .diocese-info {
            flex: 1;
        }

        .diocese-name {
            font-family: 'Cinzel', serif;
            font-size: 26px;
            font-weight: 900;
            color: var(--blue);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .parish-name {
            font-size: 16px;
            font-weight: 600;
            margin: 5px 0 0 0;
        }

        .letter-title {
            text-align: center;
            font-family: 'Cinzel', serif;
            font-size: 24px;
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 30px;
        }

        .recipient {
            font-weight: 600;
            margin-bottom: 20px;
            white-space: pre-wrap;
        }

        .body-text {
            font-size: 16px;
            line-height: 1.6;
            text-align: justify;
        }

        .signature-block {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 40px;
        }

        .seal-space {
            width: 120px;
            height: 120px;
            border: 2px dashed #cbd5e1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .signature-line-box {
            width: 250px;
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid black;
            height: 40px;
            margin-bottom: 10px;
        }

    </style>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body>

    <div class="no-print" style="text-align: center; margin: 20px;">
        <button onclick="window.print()" style="background: #1e3a8a; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 16px;">
            <ion-icon name="print-outline" style="vertical-align: middle;"></ion-icon> Print Letter
        </button>
    </div>

    <div class="certificate-container">
        <div class="header">
            <div class="logo-box">
                <img src="../assets/img/seal.png" alt="Diocese Seal">
            </div>
            <div class="diocese-info">
                <h1 class="diocese-name"><?php echo $DIOCESE_NAME; ?></h1>
                <h2 class="parish-name"><?php echo h($record['current_parish_name'] ?: 'Diocesan Chancery'); ?></h2>
                <div style="font-size: 12px; color: #64748b; margin-top: 5px;">Zimbabwe Catholic Records Exchange (ZCRE)</div>
            </div>
        </div>

        <div style="text-align: right; margin-bottom: 20px;">
            <strong>Date:</strong> <?php echo date('F j, Y'); ?>
        </div>

        <div class="recipient">
            <?php echo nl2br(h($destination)); ?>
        </div>

        <div class="letter-title">LETTER OF GOOD STANDING</div>

        <div class="body-text">
            <p>Dear Rev. Father / To Whom It May Concern,</p>
            
            <p>This letter serves to certify that <strong><?php echo h($record['first_name'] . ' ' . $record['last_name']); ?></strong> (DOB: <?php echo date('d M Y', strtotime($record['dob'])); ?>) is a baptized Catholic and a registered parishioner of <strong><?php echo h($record['current_parish_name'] ?: 'this Diocese'); ?></strong>.</p>
            
            <?php if ($record['baptism_id']): ?>
                <p>According to our canonical registers, the above-named person was baptized on <strong><?php echo date('d M Y', strtotime($record['date_of_baptism'])); ?></strong> at <strong><?php echo h($record['baptism_parish_name']); ?></strong> 
                (Ref: Book <?php echo h($record['register_book_number']); ?>, Page <?php echo h($record['page_number']); ?>).</p>
            <?php else: ?>
                <p>Please note: The official canonical baptismal record for this individual has not yet been fully digitized or verified in the central registry. Further verification may be required.</p>
            <?php endif; ?>

            <p>To the best of our knowledge, they are a practicing Catholic in good standing, free from canonical censures, and are fully eligible for the purpose of: <strong><?php echo h($reason); ?></strong>.</p>
            
            <p>If you require any further information or clarification, please do not hesitate to contact our parish office.</p>

            <p>Sincerely in Christ,</p>
        </div>

        <div class="signature-block">
            <div class="seal-space">
                Parish Seal
            </div>
            <div class="signature-line-box">
                <div class="signature-line"></div>
                <strong>Priest in Charge</strong><br>
                <?php echo h($record['current_parish_name'] ?: 'Diocesan Chancery'); ?>
            </div>
        </div>
    </div>

</body>
</html>
