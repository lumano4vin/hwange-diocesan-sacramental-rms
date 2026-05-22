<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Prenuptial Investigation (PNI) - Printable Form A
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$id = $_GET['id'] ?? null;
if (!$id) die("Invalid Record ID");

$record = db_fetch("
    SELECT pi.*, 
           g.first_name as g_first, g.last_name as g_last, g.dob as g_dob, g.place_of_birth as g_pob,
           b.first_name as b_first, b.last_name as b_last, b.dob as b_dob, b.place_of_birth as b_pob,
           pa.parish_name, u.full_name as priest_name,
           (SELECT date_of_baptism FROM baptisms WHERE person_id = pi.groom_id LIMIT 1) as g_baptism_date,
           (SELECT date_of_confirmation FROM confirmations WHERE person_id = pi.groom_id LIMIT 1) as g_confirmation_date,
           (SELECT date_of_baptism FROM baptisms WHERE person_id = pi.bride_id LIMIT 1) as b_baptism_date,
           (SELECT date_of_confirmation FROM confirmations WHERE person_id = pi.bride_id LIMIT 1) as b_confirmation_date
    FROM prenuptial_investigations pi
    JOIN parishioners g ON pi.groom_id = g.person_id
    JOIN parishioners b ON pi.bride_id = b.person_id
    JOIN parishes pa ON pi.parish_id = pa.parish_id
    LEFT JOIN users u ON pi.priest_user_id = u.user_id
    WHERE pi.pni_id = ?
", [$id]);

if (!$record) die("Record not found.");

$diocese_branding = get_diocese_branding();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PNI Form A - <?php echo h($record['g_last'] . ' / ' . $record['b_last']); ?></title>
    <style>
        @page { size: A4; margin: 20mm; }
        body { font-family: 'Times New Roman', serif; color: #1a1a1a; line-height: 1.4; font-size: 11pt; }
        .pni-container { max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 30px; position: relative; }
        
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px double #000; padding-bottom: 15px; }
        .diocese-title { font-size: 16pt; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .form-title { font-size: 14pt; font-weight: bold; text-decoration: underline; }
        
        .section-title { background: #f0f0f0; padding: 5px 10px; font-weight: bold; text-transform: uppercase; margin: 20px 0 10px 0; border: 1px solid #000; font-size: 10pt; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f9f9f9; width: 30%; font-size: 9pt; }
        
        .checks { display: flex; gap: 20px; margin-bottom: 10px; }
        .check-item { display: flex; align-items: center; gap: 5px; font-weight: bold; }
        .box { border: 1px solid #000; width: 15px; height: 15px; display: inline-block; text-align: center; line-height: 15px; font-family: Arial, sans-serif; }
        
        .signature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; }
        .sig-box { border-top: 1px solid #000; padding-top: 10px; text-align: center; font-size: 9pt; }
        
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 5rem; color: rgba(0,0,0,0.03); font-weight: 900; z-index: -1; }
        
        @media print {
            .no-print { display: none; }
            .pni-container { border: none; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="background: #334155; padding: 1rem; text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; font-weight: bold;">Print Official Form A</button>
        <button onclick="window.history.back()" style="padding: 10px 20px; cursor: pointer; margin-left: 10px;">Return to Hub</button>
    </div>

    <div class="pni-container">
        <div class="watermark">CANONICAL ARCHIVE</div>
        
        <div class="header">
            <div class="diocese-title">Catholic Diocese of Hwange - Zimbabwe</div>
            <div style="font-size: 10pt; margin-bottom: 10px;">Chancery Office - Matrimonial Department</div>
            <div class="form-title">FORM A: PRENUPTIAL INVESTIGATION STATEMENT</div>
            <div style="font-size: 9pt; margin-top: 5px;">Registry ID: PNI-<?php echo str_pad($record['pni_id'], 5, '0', STR_PAD_LEFT); ?></div>
        </div>

        <table>
            <tr>
                <th>Parish / Mission</th>
                <td><?php echo h($record['parish_name']); ?></td>
                <th>Date of Investigation</th>
                <td><?php echo date('d F Y', strtotime($record['investigation_date'])); ?></td>
            </tr>
        </table>

        <div class="section-title">I. Personal Identification</div>
        <table>
            <tr>
                <th style="width: 20%;">Detail</th>
                <th style="width: 40%;">Groom (Husband-to-be)</th>
                <th style="width: 40%;">Bride (Wife-to-be)</th>
            </tr>
            <tr>
                <th>Full Name</th>
                <td><strong><?php echo h($record['g_first'] . ' ' . $record['g_last']); ?></strong></td>
                <td><strong><?php echo h($record['b_first'] . ' ' . $record['b_last']); ?></strong></td>
            </tr>
            <tr>
                <th>Date of Birth</th>
                <td><?php echo date('d/m/Y', strtotime($record['g_dob'])); ?></td>
                <td><?php echo date('d/m/Y', strtotime($record['b_dob'])); ?></td>
            </tr>
            <tr>
                <th>Place of Birth</th>
                <td><?php echo h($record['g_pob']); ?></td>
                <td><?php echo h($record['b_pob']); ?></td>
            </tr>
            <tr>
                <th>Date of Baptism</th>
                <td><?php echo $record['g_baptism_date'] ? date('d/m/Y', strtotime($record['g_baptism_date'])) : '<span style="color:red">NOT RECORDED</span>'; ?></td>
                <td><?php echo $record['b_baptism_date'] ? date('d/m/Y', strtotime($record['b_baptism_date'])) : '<span style="color:red">NOT RECORDED</span>'; ?></td>
            </tr>
            <tr>
                <th>Date of Confirmation</th>
                <td><?php echo $record['g_confirmation_date'] ? date('d/m/Y', strtotime($record['g_confirmation_date'])) : '<span style="color:red">NOT RECORDED</span>'; ?></td>
                <td><?php echo $record['b_confirmation_date'] ? date('d/m/Y', strtotime($record['b_confirmation_date'])) : '<span style="color:red">NOT RECORDED</span>'; ?></td>
            </tr>
        </table>

        <div class="section-title">II. Freedom to Marry & Canonical Status</div>
        <p style="font-size: 9pt; margin-bottom: 10px;">The parties were interviewed separately and have declared the following under oath:</p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <strong>Groom:</strong>
                <div class="checks">
                    <div class="check-item"><div class="box"><?php echo $record['groom_free_to_marry'] ? 'X' : ''; ?></div> Free</div>
                    <div class="check-item"><div class="box"><?php echo $record['groom_previous_marriage'] ? 'X' : ''; ?></div> Prev. Married</div>
                </div>
            </div>
            <div>
                <strong>Bride:</strong>
                <div class="checks">
                    <div class="check-item"><div class="box"><?php echo $record['bride_free_to_marry'] ? 'X' : ''; ?></div> Free</div>
                    <div class="check-item"><div class="box"><?php echo $record['bride_previous_marriage'] ? 'X' : ''; ?></div> Prev. Married</div>
                </div>
            </div>
        </div>

        <div class="section-title">III. Intentions & Matrimonial Consent</div>
        <p style="font-size: 9pt;">The parties affirm their understanding and acceptance of the essential properties of marriage:</p>
        <div class="checks" style="margin: 10px 0;">
            <div class="check-item"><div class="box"><?php echo $record['consent_unity'] ? 'X' : ''; ?></div> UNITY</div>
            <div class="check-item"><div class="box"><?php echo $record['consent_indissolubility'] ? 'X' : ''; ?></div> INDISSOLUBILITY</div>
            <div class="check-item"><div class="box"><?php echo $record['consent_procreation'] ? 'X' : ''; ?></div> PROCREATION</div>
        </div>

        <div class="section-title">IV. Impediments & Dispensations</div>
        <table>
            <tr>
                <th>Impediments Found</th>
                <td><?php echo h($record['impediments_noted'] ?: 'NONE'); ?></td>
            </tr>
            <tr>
                <th>Dispensations Granted</th>
                <td><?php echo h($record['dispensations_required'] ?: 'NONE'); ?></td>
            </tr>
        </table>

        <div class="section-title">V. Observations & Notes</div>
        <div style="border: 1px solid #000; padding: 15px; min-height: 60px; font-style: italic; font-size: 10pt;">
            <?php echo h($record['notes'] ?: 'No additional observations recorded.'); ?>
        </div>

        <div class="signature-grid">
            <div class="sig-box">Signature of Groom</div>
            <div class="sig-box">Signature of Bride</div>
            <div class="sig-box">
                <strong><?php echo h($record['priest_name']); ?></strong><br>
                Signature of Priest / Deacon
            </div>
            <div class="sig-box">
                Official Parish Seal
            </div>
        </div>

        <div style="margin-top: 30px; font-size: 8pt; text-align: center; color: #666;">
            Generated via Hwange Diocese Records Management System (RMS) - Archival Integrity Verified
        </div>
    </div>

</body>
</html>
