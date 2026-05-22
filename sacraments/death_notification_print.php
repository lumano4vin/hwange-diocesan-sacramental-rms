<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Notice of Death to Parish of Baptism
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$death_id = $_GET['id'] ?? null;

if (!$death_id) die("Invalid Death ID");

// Fetch death and baptismal details
$sql = "
    SELECT d.*, p.first_name, p.last_name, p.father_name, p.mother_name, p.place_of_baptism,
           pa.parish_name as burial_parish, pa.address as burial_parish_address,
           b.date_of_baptism, b.register_book_number as b_book, b.page_number as b_page, b.entry_number as b_entry,
           bp.parish_name as bap_parish
    FROM deaths d
    JOIN parishioners p ON d.person_id = p.person_id
    JOIN parishes pa ON d.parish_id = pa.parish_id
    LEFT JOIN baptisms b ON d.person_id = b.person_id
    LEFT JOIN parishes bp ON b.parish_id = bp.parish_id
    WHERE d.death_id = ?
";
$record = db_fetch($sql, [$death_id]);

if (!$record) die("Death record not found.");

// Mark as notified if printing
if (isset($_GET['mark_done'])) {
    db_query("UPDATE deaths SET baptism_notified = 1 WHERE death_id = ?", [$death_id]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notice of Death - Hwange Diocese</title>
    <style>
        @page { size: A4; margin: 30mm; }
        body { font-family: 'Times New Roman', serif; line-height: 1.6; color: #000; font-size: 12pt; }
        .letter-container { max-width: 700px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 50px; border-bottom: 1px solid #000; padding-bottom: 20px; }
        .diocese { font-size: 14pt; font-weight: bold; text-transform: uppercase; }
        
        .date-row { text-align: right; margin-bottom: 30px; }
        .recipient { margin-bottom: 40px; }
        
        .subject { font-weight: bold; text-decoration: underline; margin-bottom: 30px; text-transform: uppercase; }
        
        .content { margin-bottom: 50px; text-align: justify; }
        
        .data-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .data-table td { padding: 5px 0; vertical-align: top; }
        .label { font-weight: bold; width: 180px; }
        
        .footer { margin-top: 60px; display: flex; justify-content: space-between; }
        .signature-block { border-top: 1px solid #000; width: 250px; text-align: center; padding-top: 10px; margin-top: 50px; }
        
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <div class="no-print" style="background: #f1f5f9; padding: 1rem; border: 1px solid #cbd5e1; margin-bottom: 30px; text-align: center; border-radius: 8px;">
        <p style="margin-bottom: 10px; font-weight: bold;">Canonical Death Notification</p>
        <button onclick="window.print()" style="padding: 10px 20px; background: #64748b; color: white; border: none; border-radius: 5px; cursor: pointer;">Print & Mark as Sent</button>
        <button onclick="window.history.back()" style="padding: 10px 20px; margin-left: 10px; cursor: pointer; border-radius: 5px; border: 1px solid #ccc;">Cancel</button>
    </div>

    <div class="letter-container">
        <div class="header">
            <div class="diocese">Catholic Diocese of Hwange - Zimbabwe</div>
            <div style="font-size: 12pt; font-weight: bold;"><?php echo h($record['burial_parish']); ?></div>
            <div style="font-size: 9pt;"><?php echo nl2br(h($record['burial_parish_address'])); ?></div>
        </div>

        <div class="date-row">
            Date: <?php echo date('d F Y'); ?>
        </div>

        <div class="recipient">
            To the Reverend Parish Priest,<br>
            <strong><?php echo h($record['bap_parish'] ?: ($record['place_of_baptism'] ?: '[Enter Parish Name]')); ?></strong><br>
            Catholic Church.
        </div>

        <div class="subject">
            Notification of Death (Recording in Baptismal Register)
        </div>

        <div class="content">
            <p>Reverend and Dear Father,</p>
            <p>I am writing to inform you of the death of the following person, who was baptized in your Parish. Please make the appropriate notation in your Baptismal Register.</p>

            <table class="data-table">
                <tr>
                    <td class="label">Name of Deceased:</td>
                    <td><strong><?php echo h($record['first_name'] . ' ' . $record['last_name']); ?></strong></td>
                </tr>
                <tr>
                    <td class="label">Parents:</td>
                    <td><?php echo h($record['father_name'] . ' & ' . $record['mother_name']); ?></td>
                </tr>
                <tr>
                    <td class="label">Baptism Ref:</td>
                    <td><?php echo (!empty($record['b_book']) || !empty($record['b_page'])) ? "Book: " . h($record['b_book']) . ", Page: " . h($record['b_page']) . ", Entry: " . h($record['b_entry']) : "Baptised Externally / Record Held at " . h($record['place_of_baptism'] ?: 'Parish of Baptism'); ?></td>
                </tr>
                <tr>
                    <td class="label">Date of Death:</td>
                    <td><?php echo date('d F Y', strtotime($record['date_of_death'])); ?></td>
                </tr>
                <tr>
                    <td class="label">Date of Burial:</td>
                    <td><?php echo $record['date_of_burial'] ? date('d F Y', strtotime($record['date_of_burial'])) : 'Not Recorded'; ?></td>
                </tr>
                <tr>
                    <td class="label">Place of Burial:</td>
                    <td><?php echo h($record['place_of_burial'] ?: $record['burial_parish']); ?></td>
                </tr>
                <tr>
                    <td class="label">Officiant:</td>
                    <td><?php echo h($record['minister']); ?></td>
                </tr>
            </table>

            <p>Kindly acknowledge this notice and confirm that the record has been updated.</p>
            
            <p>Yours sincerely in Christ,</p>
        </div>

        <div class="footer">
            <div class="signature-block">
                Parish Priest / Administrator
            </div>
            <div style="width: 120px; height: 120px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; font-size: 8pt; color: #ccc; border-radius: 50%;">
                Parish Seal
            </div>
        </div>
    </div>

    <script>
        window.onafterprint = function() {
            const url = new URL(window.location);
            url.searchParams.set('mark_done', '1');
            window.location = url.href;
        }
    </script>
</body>
</html>
