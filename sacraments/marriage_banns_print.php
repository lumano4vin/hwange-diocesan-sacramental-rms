<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Publication of Banns of Marriage - Official Notice
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$id = $_GET['id'] ?? null;
if (!$id) die("Invalid Record ID");

$record = db_fetch("
    SELECT pi.*, 
           g.first_name as g_first, g.last_name as g_last, g.father_name as g_father, g.mother_name as g_mother,
           b.first_name as b_first, b.last_name as b_last, b.father_name as b_father, b.mother_name as b_mother,
           pa.parish_name as home_parish, bp.parish_name as banns_parish
    FROM prenuptial_investigations pi
    JOIN parishioners g ON pi.groom_id = g.person_id
    JOIN parishioners b ON pi.bride_id = b.person_id
    JOIN parishes pa ON pi.parish_id = pa.parish_id
    LEFT JOIN parishes bp ON pi.banns_parish_id = bp.parish_id
    WHERE pi.pni_id = ?
", [$id]);

if (!$record) die("Record not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Publication of Banns - <?php echo h($record['g_last'] . ' & ' . $record['b_last']); ?></title>
    <style>
        @page { size: A4; margin: 25mm; }
        body { font-family: 'Times New Roman', serif; line-height: 1.6; color: #000; }
        .banns-container { max-width: 700px; margin: 0 auto; border: 2px solid #000; padding: 40px; position: relative; min-height: 900px; }
        .header { text-align: center; margin-bottom: 40px; }
        .diocese { font-size: 16pt; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .parish { font-size: 14pt; font-weight: bold; }
        
        .title { text-align: center; font-size: 18pt; font-weight: bold; text-decoration: underline; margin: 30px 0; }
        
        .content { font-size: 12pt; text-align: justify; }
        .party-box { border: 1px solid #ddd; padding: 15px; margin: 20px 0; background: #fcfcfc; }
        
        .banns-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 50px; }
        .banns-box { border: 1px solid #000; padding: 15px; text-align: center; font-size: 10pt; }
        .date-line { border-bottom: 1px solid #000; margin-top: 15px; min-height: 20px; }
        
        .footer { margin-top: 80px; display: flex; justify-content: space-between; align-items: flex-end; }
        .sig-line { border-top: 1px solid #000; width: 200px; text-align: center; font-size: 10pt; padding-top: 5px; }
        
        .seal-spot { width: 100px; height: 100px; border: 1px dashed #ccc; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 8pt; color: #ccc; }
        
        @media print { .no-print { display: none; } .banns-container { border: none; } }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-weight: bold; cursor: pointer;">Print Banns Notice</button>
        <button onclick="window.history.back()" style="padding: 10px 20px; margin-left: 10px; cursor: pointer;">Back</button>
    </div>

    <div class="banns-container">
        <div class="header">
            <div class="diocese">Catholic Diocese of Hwange - Zimbabwe</div>
            <div class="parish"><?php echo h($record['banns_parish'] ?: $record['home_parish']); ?></div>
            <div style="font-size: 10pt; font-style: italic;">"What God has joined, let no one separate" (Mt 19:6)</div>
        </div>

        <div class="title">PUBLICATION OF BANNS OF MARRIAGE</div>

        <div class="content">
            <p>Notice is hereby given that the following persons intend to contract Holy Matrimony in the Catholic Church:</p>
            
            <div class="party-box">
                <strong>GROOM:</strong> <?php echo h($record['g_first'] . ' ' . $record['g_last']); ?><br>
                <strong>Son of:</strong> <?php echo h($record['g_father'] ?: '---'); ?> and <?php echo h($record['g_mother'] ?: '---'); ?><br>
                <strong>Parish:</strong> <?php echo h($record['home_parish']); ?>
            </div>

            <div class="party-box">
                <strong>BRIDE:</strong> <?php echo h($record['b_first'] . ' ' . $record['b_last']); ?><br>
                <strong>Daughter of:</strong> <?php echo h($record['b_father'] ?: '---'); ?> and <?php echo h($record['b_mother'] ?: '---'); ?><br>
                <strong>Parish:</strong> <?php echo h($record['home_parish']); ?>
            </div>

            <p>If anyone knows of any canonical impediment why these two persons should not be joined in Holy Matrimony, they are bound in conscience to reveal the same to the Parish Priest as soon as possible.</p>
        </div>

        <div class="banns-grid">
            <div class="banns-box">
                <strong>FIRST BANNS</strong>
                <div class="date-line"><?php echo $record['banns_date_1'] ? date('d/m/Y', strtotime($record['banns_date_1'])) : ''; ?></div>
                <div style="margin-top: 10px; opacity: 0.5;">Date Read</div>
            </div>
            <div class="banns-box">
                <strong>SECOND BANNS</strong>
                <div class="date-line"><?php echo $record['banns_date_2'] ? date('d/m/Y', strtotime($record['banns_date_2'])) : ''; ?></div>
                <div style="margin-top: 10px; opacity: 0.5;">Date Read</div>
            </div>
            <div class="banns-box">
                <strong>THIRD BANNS</strong>
                <div class="date-line"><?php echo $record['banns_date_3'] ? date('d/m/Y', strtotime($record['banns_date_3'])) : ''; ?></div>
                <div style="margin-top: 10px; opacity: 0.5;">Date Read</div>
            </div>
        </div>

        <div class="footer">
            <div style="display: flex; flex-direction: column; gap: 40px;">
                <div class="sig-line">Date of Issue</div>
                <div class="sig-line">Parish Priest / Deacon</div>
            </div>
            <div class="seal-spot">L.S.<br>(Parish Seal)</div>
        </div>

        <div style="margin-top: 40px; text-align: center; font-size: 8pt; color: #666; border-top: 1px solid #eee; padding-top: 10px;">
            Hwange Diocesan Records Management System - Official Canonical Document
        </div>
    </div>

</body>
</html>
