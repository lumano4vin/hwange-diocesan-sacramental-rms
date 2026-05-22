<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Notice of Marriage to Parish of Baptism (Canon 1122)
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$marriage_id = $_GET['id'] ?? null;
$party = $_GET['party'] ?? 'groom'; // 'groom' or 'bride'

if (!$marriage_id) die("Invalid Marriage ID");

// Fetch marriage and specific party details
$sql = "
    SELECT m.*, 
           g.first_name as g_first, g.last_name as g_last, g.father_name as g_father, g.mother_name as g_mother,
           b.first_name as b_first, b.last_name as b_last, b.father_name as b_father, b.mother_name as b_mother,
           pa.parish_name as marriage_parish, pa.address as marriage_parish_address,
           bp_g.date_of_baptism as g_bap_date, bp_g.register_book_number as g_bap_book, bp_g.page_number as g_bap_page, bp_g.entry_number as g_bap_entry,
           bp_b.date_of_baptism as b_bap_date, bp_b.register_book_number as b_bap_book, bp_b.page_number as b_bap_page, bp_b.entry_number as b_bap_entry,
           p_g.parish_name as g_bap_parish, p_b.parish_name as b_bap_parish
    FROM marriages m
    JOIN parishioners g ON m.groom_person_id = g.person_id
    JOIN parishioners b ON m.bride_person_id = b.person_id
    JOIN parishes pa ON m.parish_id = pa.parish_id
    LEFT JOIN baptisms bp_g ON m.groom_person_id = bp_g.person_id
    LEFT JOIN parishes p_g ON bp_g.parish_id = p_g.parish_id
    LEFT JOIN baptisms bp_b ON m.bride_person_id = bp_b.person_id
    LEFT JOIN parishes p_b ON bp_b.parish_id = p_b.parish_id
    WHERE m.marriage_id = ?
";
$record = db_fetch($sql, [$marriage_id]);

if (!$record) die("Marriage record not found.");

$is_groom = ($party === 'groom');
$subject_name = $is_groom ? ($record['g_first'] . ' ' . $record['g_last']) : ($record['b_first'] . ' ' . $record['b_last']);
$spouse_name = $is_groom ? ($record['b_first'] . ' ' . $record['b_last']) : ($record['g_first'] . ' ' . $record['g_last']);
$bap_date = $is_groom ? $record['g_bap_date'] : $record['b_bap_date'];
$bap_parish = $is_groom ? $record['g_bap_parish'] : $record['b_bap_parish'];
$bap_ref = $is_groom ? ("Book: " . $record['g_bap_book'] . ", Page: " . $record['g_bap_page'] . ", Entry: " . $record['g_bap_entry']) : ("Book: " . $record['b_bap_book'] . ", Page: " . $record['b_bap_page'] . ", Entry: " . $record['b_bap_entry']);

// Mark as notified if printing
if (isset($_GET['mark_done'])) {
    $col = $is_groom ? 'baptism_notified_groom' : 'baptism_notified_bride';
    db_query("UPDATE marriages SET $col = 1 WHERE marriage_id = ?", [$marriage_id]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notice of Marriage - Canon 1122</title>
    <style>
        @page { size: A4; margin: 30mm; }
        body { font-family: 'Times New Roman', serif; line-height: 1.6; color: #000; font-size: 12pt; }
        .letter-container { max-width: 700px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 50px; border-bottom: 1px solid #000; padding-bottom: 20px; }
        .diocese { font-size: 14pt; font-weight: bold; text-transform: uppercase; }
        .parish { font-size: 12pt; font-weight: bold; }
        
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

    <div class="no-print" style="background: #f8fafc; padding: 1rem; border: 1px solid #e2e8f0; margin-bottom: 30px; text-align: center; border-radius: 8px;">
        <p style="margin-bottom: 10px; font-weight: bold; color: #1e293b;">Canonical Notice to Parish of Baptism</p>
        <button onclick="window.print()" style="padding: 10px 20px; background: #38bdf8; color: #000; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">Print & Mark as Sent</button>
        <button onclick="window.history.back()" style="padding: 10px 20px; margin-left: 10px; cursor: pointer; border-radius: 5px; border: 1px solid #ccc;">Cancel</button>
    </div>

    <div class="letter-container">
        <div class="header">
            <div class="diocese">Catholic Diocese of Hwange - Zimbabwe</div>
            <div class="parish"><?php echo h($record['marriage_parish']); ?></div>
            <div style="font-size: 9pt;"><?php echo nl2br(h($record['marriage_parish_address'])); ?></div>
        </div>

        <div class="date-row">
            Date: <?php echo date('d F Y'); ?>
        </div>

        <div class="recipient">
            To the Reverend Parish Priest,<br>
            <strong><?php echo h($bap_parish ?: '[Enter Parish Name]'); ?></strong><br>
            Catholic Church.
        </div>

        <div class="subject">
            Notification of Marriage (Canon 1122 §2)
        </div>

        <div class="content">
            <p>Reverend and Dear Father,</p>
            <p>In accordance with the requirements of Canon Law, I hereby notify you that the following person, who is recorded as having been baptized in your Parish, has contracted Holy Matrimony in this Parish.</p>
            
            <p>Please ensure that a notation of this marriage is entered in the Baptismal Register of your Parish alongside the original entry of baptism.</p>

            <table class="data-table">
                <tr>
                    <td class="label">Name of Party:</td>
                    <td><strong><?php echo h($subject_name); ?></strong></td>
                </tr>
                <tr>
                    <td class="label">Parents:</td>
                    <td><?php echo h($is_groom ? ($record['g_father'] . ' & ' . $record['g_mother']) : ($record['b_father'] . ' & ' . $record['b_mother'])); ?></td>
                </tr>
                <tr>
                    <td class="label">Baptism Ref:</td>
                    <td><?php echo h($bap_ref); ?> on <?php echo $bap_date ? date('d/m/Y', strtotime($bap_date)) : '[Unknown Date]'; ?></td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 20px 0 10px 0; font-weight: bold; text-decoration: underline;">MARRIED TO:</td>
                </tr>
                <tr>
                    <td class="label">Name of Spouse:</td>
                    <td><strong><?php echo h($spouse_name); ?></strong></td>
                </tr>
                <tr>
                    <td class="label">Date of Marriage:</td>
                    <td><?php echo date('d F Y', strtotime($record['date_of_marriage'])); ?></td>
                </tr>
                <tr>
                    <td class="label">Officiant:</td>
                    <td><?php echo h($record['officiant']); ?></td>
                </tr>
                <tr>
                    <td class="label">Marriage Registry:</td>
                    <td>Book: <?php echo h($record['register_book_number']); ?>, Page: <?php echo h($record['page_number']); ?>, Entry: <?php echo h($record['entry_number']); ?></td>
                </tr>
            </table>

            <p>Kindly acknowledge the receipt of this notification and confirm that the notation has been made.</p>
            
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

        <div style="margin-top: 50px; font-size: 8pt; text-align: center; color: #999;">
            Document Generated by Hwange Diocesan RMS | Ref: M-<?php echo $marriage_id; ?>-<?php echo strtoupper($party[0]); ?>
        </div>
    </div>

    <script>
        window.onafterprint = function() {
            // Optional: Auto-redirect or update UI after print
            const url = new URL(window.location);
            url.searchParams.set('mark_done', '1');
            window.location = url.href;
        }
    </script>
</body>
</html>
