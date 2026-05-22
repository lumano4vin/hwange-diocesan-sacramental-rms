<?php
/**
 * Sacramental Notification Generator
 * Generates a formal notification to be sent to the Parish of Baptism.
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$parishioner_id = $_GET['parishioner_id'] ?? null;
$type = $_GET['type'] ?? 'Marriage';

if (!$parishioner_id) die("Parishioner ID required.");

$pdo = getDB();

// Fetch Parishioner Data
$stmt = $pdo->prepare("
    SELECT p.*, b.date_of_baptism, b.place_of_baptism, b.entry_number AS baptism_number
    FROM parishioners p
    LEFT JOIN baptisms b ON p.person_id = b.person_id
    WHERE p.person_id = ?
");
$stmt->execute([$parishioner_id]);
$parishioner = $stmt->fetch();

if (!$parishioner) die("Parishioner not found.");

// Fetch Sacrament Data (Marriage, Confirmation, etc.)
$sacrament_data = [];
if ($type === 'Marriage') {
    $stmt = $pdo->prepare("
        SELECT m.*, pa.parish_name, h.first_name as h_first, h.last_name as h_last, w.first_name as w_first, w.last_name as w_last
        FROM marriages m
        JOIN parishes pa ON m.parish_id = pa.parish_id
        JOIN parishioners h ON m.groom_person_id = h.person_id
        JOIN parishioners w ON m.bride_person_id = w.person_id
        WHERE m.groom_person_id = ? OR m.bride_person_id = ?
        ORDER BY m.date_of_marriage DESC LIMIT 1
    ");
    $stmt->execute([$parishioner_id, $parishioner_id]);
    $sacrament_data = $stmt->fetch();
} elseif ($type === 'Confirmation') {
    $stmt = $pdo->prepare("
        SELECT c.*, pa.parish_name
        FROM confirmations c
        JOIN parishes pa ON c.parish_id = pa.parish_id
        WHERE c.person_id = ?
        ORDER BY c.date_of_confirmation DESC LIMIT 1
    ");
    $stmt->execute([$parishioner_id]);
    $sacrament_data = $stmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notification of <?= e($type) ?> - Hwange Diocese</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Montserrat:wght@400;700&display=swap');

        body {
            margin: 0; padding: 20mm;
            background: #f0f0f0;
            font-family: 'Montserrat', sans-serif;
            line-height: 1.6;
        }

        .document-container {
            width: 210mm;
            min-height: 297mm;
            padding: 25mm;
            margin: auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            box-sizing: border-box;
            position: relative;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #2a4365;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }

        .diocese-header {
            font-family: 'Cinzel', serif;
            font-size: 18px;
            color: #2a4365;
            margin: 0;
            text-transform: uppercase;
        }

        .doc-title {
            font-size: 24px;
            font-weight: 700;
            margin: 15px 0;
            color: #1a202c;
        }

        .to-line {
            margin-bottom: 30px;
        }

        .content-body {
            text-align: justify;
            font-size: 16px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
            margin: 25px 0;
        }

        .label {
            font-weight: 700;
            color: #4a5568;
        }

        .footer {
            margin-top: 60px;
        }

        .sig-block {
            float: right;
            text-align: center;
            width: 250px;
        }

        .sig-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 40px;
        }

        @media print {
            body { background: white; padding: 0; }
            .document-container { box-shadow: none; margin: 0; }
            .no-print { display: none; }
        }

        .no-print {
            position: fixed; top: 20px; right: 20px;
            background: #2a4365; color: white;
            padding: 10px 20px; border-radius: 5px;
            text-decoration: none; font-weight: bold;
            cursor: pointer; border: none;
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print Notification</button>

    <div class="document-container">
        <div class="header">
            <h1 class="diocese-header">Catholic Diocese of Hwange - Zimbabwe</h1>
            <p style="font-size: 14px; margin: 5px 0;">CHURCH RECORDS OFFICE</p>
            <h2 class="doc-title">Notification of <?= e($type) ?></h2>
        </div>

        <div class="to-line">
            <p><strong>TO:</strong> The Reverend Parish Priest / Administrator<br>
            <strong>PARISH:</strong> <?= e($parishioner['place_of_baptism'] ?: 'Parish of Baptism') ?><br>
            <strong>SUBJECT:</strong> Notification of Sacramental Act (Can. 535 §2)</p>
        </div>

        <div class="content-body">
            <p>Dear Father,</p>
            <p>In accordance with the requirements of Canon Law, I am writing to notify you that the following person, who was baptized at your parish, has received the Sacrament of <strong><?= e($type) ?></strong> as detailed below:</p>

            <div class="info-grid">
                <span class="label">Full Name:</span>
                <span><?= e($parishioner['first_name'] . ' ' . $parishioner['last_name']) ?></span>
                
                <span class="label">Parents:</span>
                <span><?= e($parishioner['father_name']) ?> & <?= e($parishioner['mother_name']) ?></span>

                <span class="label">Date of Baptism:</span>
                <span><?= formatDate($parishioner['date_of_baptism']) ?> <?= $parishioner['baptism_number'] ? '(Entry No: ' . e($parishioner['baptism_number']) . ')' : '' ?></span>

                <hr style="grid-column: span 2; width: 100%; border: 0; border-top: 1px solid #eee;">

                <span class="label">Sacrament Received:</span>
                <span><?= e($type) ?></span>

                <span class="label">Date of Sacrament:</span>
                <span><?= formatDate($sacrament_data['date_of_marriage'] ?? $sacrament_data['date_of_confirmation'] ?? '') ?></span>

                <span class="label">Place / Parish:</span>
                <span><?= e($sacrament_data['parish_name'] ?? '') ?></span>

                <?php if ($type === 'Marriage'): ?>
                    <span class="label">Spouse:</span>
                    <span>
                        <?= ($sacrament_data['groom_person_id'] == $parishioner_id) ? e($sacrament_data['w_first'] . ' ' . $sacrament_data['w_last']) : e($sacrament_data['h_first'] . ' ' . $sacrament_data['h_last']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <p>We kindly request that you make the appropriate notation in your original Baptismal Register. Please acknowledge receipt of this notification.</p>
        </div>

        <div class="footer">
            <p>With Every Best Wish,</p>
            <div class="sig-block">
                <div class="sig-line">
                    <strong>Parish Priest / Secretary</strong><br>
                    Date: <?= date('d M Y') ?>
                </div>
            </div>
            
            <div style="margin-top: 100px; border: 1px dashed #cbd5e0; padding: 15px; font-size: 13px; color: #718096; width: 300px;">
                <p><strong>Acknowledgement Receipt</strong><br>
                This is to acknowledge that the marginal notation has been made in the Baptismal Register for the above person.<br><br>
                Signed: _______________________ Date: ________</p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px; font-size: 11px; color: #a0aec0; border-top: 1px solid #eee; padding-top: 10px;">
            Hwange Diocesan Sacramental Records Management System | Official Document
        </div>
    </div>
</body>
</html>
