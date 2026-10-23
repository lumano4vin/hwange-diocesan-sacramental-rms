<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Canonical Sacramental Inquiry & Comprehensive Status Certificate
 * Dual-Mode: "It is required..." (Request/Inquiry) & "This is..." (Official Extract)
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$person_id = $_GET['person_id'] ?? $_GET['id'] ?? null;
$initial_mode = $_GET['mode'] ?? 'certificate'; // 'request' or 'certificate'
$cert_for = $_GET['cert_for'] ?? 'BAPTISM & SACRAMENTAL STATUS';

// Current logged in user's parish
$user_parish_id = $_SESSION['parish_id'] ?? null;
$my_parish = null;
if ($user_parish_id) {
    $my_parish = db_fetch("SELECT * FROM parishes WHERE parish_id = ?", [$user_parish_id]);
}

// Fetch Parishioner details if person_id provided
$person = null;
$baptism = null;
$confirmation = null;
$marriage = null;
$death = null;

if ($person_id) {
    $person = db_fetch("
        SELECT p.*, pa.parish_name as current_parish_name, pa.location as current_parish_location
        FROM parishioners p
        LEFT JOIN parishes pa ON p.current_parish_id = pa.parish_id
        WHERE p.person_id = ?
    ", [$person_id]);

    if ($person) {
        $baptism = db_fetch("
            SELECT b.*, pa.parish_name
            FROM baptisms b
            JOIN parishes pa ON b.parish_id = pa.parish_id
            WHERE b.person_id = ?
            ORDER BY b.date_of_baptism ASC LIMIT 1
        ", [$person_id]);

        $confirmation = db_fetch("
            SELECT c.*, pa.parish_name
            FROM confirmations c
            JOIN parishes pa ON c.parish_id = pa.parish_id
            WHERE c.person_id = ?
            ORDER BY c.date_of_confirmation ASC LIMIT 1
        ", [$person_id]);

        $marriage = db_fetch("
            SELECT m.*, pa.parish_name,
                   g.first_name as g_first, g.last_name as g_last,
                   b.first_name as b_first, b.last_name as b_last
            FROM marriages m
            JOIN parishes pa ON m.parish_id = pa.parish_id
            JOIN parishioners g ON m.groom_person_id = g.person_id
            JOIN parishioners b ON m.bride_person_id = b.person_id
            WHERE m.groom_person_id = ? OR m.bride_person_id = ?
            ORDER BY m.date_of_marriage DESC LIMIT 1
        ", [$person_id, $person_id]);

        $death = db_fetch("
            SELECT d.*, pa.parish_name
            FROM deaths d
            JOIN parishes pa ON d.parish_id = pa.parish_id
            WHERE d.person_id = ?
            ORDER BY d.date_of_death DESC LIMIT 1
        ", [$person_id]);
    }
}

// If person has baptism, generate verification URL
$verify_urls = null;
if ($baptism) {
    $verify_urls = get_certificate_verification_url('baptism', $baptism['baptism_id'], $baptism['verification_hash'] ?? '', $baptism['guid'] ?? '');
}

// Pre-fill values
$mission_name = $my_parish['parish_name'] ?? ($person['current_parish_name'] ?? 'St. Ignatius Cathedral');
$mission_location = $my_parish['location'] ?? 'Hwange';
$po_box = 'P.O. Box 24, ' . $mission_location;
$today_date = date('d F Y');

$full_name = $person ? trim($person['first_name'] . ' ' . ($person['other_names'] ?? '') . ' ' . $person['last_name']) : '';
$gender = strtolower($person['gender'] ?? 'male');
$father_name = $person['father_name'] ?? '';
$mother_name = $person['mother_name'] ?? '';
$birth_place = $person['place_of_birth'] ?? '';
$birth_date = !empty($person['dob']) ? format_certificate_date($person['dob']) : '';

// Baptism
$bap_parish = $baptism['parish_name'] ?? ($person['place_of_baptism'] ?? '');
$bap_date = !empty($baptism['date_of_baptism']) ? format_certificate_date($baptism['date_of_baptism']) : '';
$bap_reg = $baptism['entry_number'] ? $baptism['entry_number'] : ($baptism['baptism_id'] ? 'B-' . str_pad($baptism['baptism_id'], 4, '0', STR_PAD_LEFT) : '');
if (!empty($baptism['register_book_number']) || !empty($baptism['page_number'])) {
    $bap_reg .= ($bap_reg ? ' | ' : '') . 'Bk: ' . ($baptism['register_book_number'] ?: 'I') . ' Pg: ' . ($baptism['page_number'] ?: '-');
}

// Confirmation
$conf_parish = $confirmation['parish_name'] ?? '';
$conf_date = !empty($confirmation['date_of_confirmation']) ? format_certificate_date($confirmation['date_of_confirmation']) : '';
$conf_reg = $confirmation['entry_number'] ? $confirmation['entry_number'] : ($confirmation['confirmation_id'] ? 'C-' . str_pad($confirmation['confirmation_id'], 4, '0', STR_PAD_LEFT) : '');
if (!empty($confirmation['register_book_number']) || !empty($confirmation['page_number'])) {
    $conf_reg .= ($conf_reg ? ' | ' : '') . 'Bk: ' . ($confirmation['register_book_number'] ?: 'I') . ' Pg: ' . ($confirmation['page_number'] ?: '-');
}

// Marriage
$spouse_name = '';
if ($marriage) {
    if ($person && $marriage['groom_person_id'] == $person['person_id']) {
        $spouse_name = trim($marriage['b_first'] . ' ' . $marriage['b_last']);
    } else {
        $spouse_name = trim($marriage['g_first'] . ' ' . $marriage['g_last']);
    }
}
$marr_parish = $marriage['parish_name'] ?? '';
$marr_date = !empty($marriage['date_of_marriage']) ? format_certificate_date($marriage['date_of_marriage']) : '';
$marr_reg = $marriage['certificate_number'] ?: ($marriage['entry_number'] ?: ($marriage['marriage_id'] ? 'M-' . str_pad($marriage['marriage_id'], 4, '0', STR_PAD_LEFT) : ''));

// Death
$death_place = $death['parish_name'] ?? ($death['place_of_death'] ?? '');
$death_date = !empty($death['date_of_death']) ? format_certificate_date($death['date_of_death']) : '';
$death_reg = $death['entry_number'] ? $death['entry_number'] : ($death['death_id'] ? 'D-' . str_pad($death['death_id'], 4, '0', STR_PAD_LEFT) : '');

// Books kept at
$books_kept_at = $bap_parish ?: $mission_name;

// Default remarks
$remarks_text = "";
if ($person) {
    if (!$marriage && !$death) {
        $remarks_text = "According to the registers kept in this parish/mission, there is no record of marriage or canonical impediments. The subject possesses canonical Freedom to Marry (Status Liber).";
    } elseif ($marriage) {
        $remarks_text = "Canonical Marriage registered. In full communion with the Catholic Church.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sacramental Inquiry / Status Certificate - <?php echo h($full_name ?: 'Diocese of Hwange'); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;500;600;700&family=Outfit:wght@500;700&display=swap');

        :root {
            --navy: #0f172a;
            --blue: #1e3a8a;
            --gold: #b45309;
            --paper-bg: #fffefb;
            --line-color: #475569;
        }

        * {
            box-sizing: border-box;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            .no-print { display: none !important; }
            body { 
                background: white !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .canonical-sheet { 
                margin: 0 auto !important; 
                box-shadow: none !important; 
                width: 198mm !important; 
                max-width: 198mm !important;
                height: 284mm !important; 
                max-height: 284mm !important;
                box-sizing: border-box !important;
                padding: 8mm 14mm !important; 
                overflow: hidden !important;
                page-break-inside: avoid !important;
                page-break-before: avoid !important;
                page-break-after: avoid !important;
                break-inside: avoid !important;
                break-before: avoid !important;
                break-after: avoid !important;
                border: 2px solid #000 !important;
            }
            .editable-field {
                border-bottom: 1px dotted #000 !important;
                background: transparent !important;
            }
        }

        body {
            margin: 0;
            padding: 0;
            background: #e2e8f0;
            font-family: 'Lora', Georgia, serif;
            color: #0f172a;
            line-height: 1.5;
        }

        /* Top Action Bar (no-print) */
        .no-print.control-bar {
            position: sticky;
            top: 0;
            background: #ffffff;
            padding: 10px 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            font-family: 'Inter', sans-serif;
            border-bottom: 1px solid #cbd5e1;
        }

        .mode-toggle-group {
            display: inline-flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }

        .mode-btn {
            border: none;
            background: transparent;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            color: #475569;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .mode-btn.active {
            background: var(--blue);
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(30, 58, 138, 0.3);
        }

        .action-btn {
            background: var(--blue);
            color: white;
            border: none;
            padding: 7px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .action-btn.secondary {
            background: #0ea5e9;
        }

        .action-btn.ghost {
            background: transparent;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Document Canvas (A4 Dimensions) */
        .canonical-sheet {
            width: 198mm;
            min-height: 284mm;
            margin: 20px auto 40px auto;
            background: var(--paper-bg);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            padding: 10mm 15mm;
            position: relative;
            border: 2px solid #1e293b;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .canonical-sheet.pdf-generation {
            margin: 0 auto !important;
            box-shadow: none !important;
            width: 794px !important;
            min-width: 794px !important;
            max-width: 794px !important;
            height: 1120px !important;
            min-height: 1120px !important;
            max-height: 1120px !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }

        /* Header Layout */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
        }

        .diocese-branding {
            flex: 1;
        }

        .diocese-branding h1 {
            font-family: 'Cinzel', serif;
            font-size: 26px;
            font-weight: 900;
            letter-spacing: 2px;
            margin: 0;
            color: #0f172a;
            text-transform: uppercase;
        }

        .diocese-branding h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            letter-spacing: 4px;
            margin: 2px 0 0 0;
            color: var(--gold);
            font-weight: 700;
            text-transform: uppercase;
        }

        .mission-info-box {
            text-align: right;
            font-size: 13px;
            color: #1e293b;
            min-width: 240px;
        }

        .mission-info-row {
            margin-bottom: 4px;
            display: flex;
            justify-content: flex-end;
            align-items: baseline;
            gap: 6px;
        }

        .mission-info-label {
            font-weight: 700;
            font-size: 12px;
            color: #475569;
            text-transform: uppercase;
            font-family: 'Inter', sans-serif;
        }

        .editable-field {
            border-bottom: 1px dotted #475569;
            outline: none;
            padding: 0 4px;
            min-height: 20px;
            font-family: 'Lora', Georgia, serif;
            color: #0f172a;
            display: inline-block;
        }

        .editable-field:focus {
            border-bottom: 1.5px solid var(--blue);
            background: rgba(224, 242, 254, 0.4);
        }

        /* Salutation & Dual-Brace Selector */
        .salutation-section {
            margin: 8px 0 12px 0;
            font-size: 15px;
        }

        .dear-father {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 8px;
            font-style: italic;
        }

        .dual-brace-container {
            display: flex;
            align-items: center;
            margin: 6px 0 14px 0;
            gap: 12px;
        }

        .brace-options {
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
            padding-right: 18px;
        }

        .brace-option-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            user-select: none;
        }

        .brace-option-row .check-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border: 1.5px solid #0f172a;
            font-size: 12px;
            font-weight: 900;
            line-height: 1;
        }

        .curly-brace-symbol {
            font-size: 44px;
            font-weight: 300;
            line-height: 1;
            font-family: serif;
            color: #0f172a;
            margin-left: -8px;
        }

        .cert-type-announcement {
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: baseline;
            gap: 8px;
            flex: 1;
        }

        /* Form Lines */
        .data-lines-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 14px;
        }

        .data-row {
            display: flex;
            align-items: baseline;
            width: 100%;
        }

        .data-label {
            font-weight: 700;
            white-space: nowrap;
            margin-right: 6px;
            color: #0f172a;
        }

        .data-fill {
            flex: 1;
            border-bottom: 1px dotted #334155;
            padding: 0 6px 1px 6px;
            min-height: 22px;
            outline: none;
        }

        .data-fill.bold {
            font-weight: 700;
            color: #1e3a8a;
        }

        .sub-data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            width: 100%;
        }

        .inline-group {
            display: flex;
            align-items: baseline;
        }

        /* Remarks Section */
        .remarks-box {
            margin-top: 10px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
        }

        .remarks-title {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 1px;
            margin-bottom: 4px;
            color: #0f172a;
        }

        .remarks-content {
            width: 100%;
            min-height: 58px;
            line-height: 22px;
            background-image: linear-gradient(to bottom, transparent 21px, #cbd5e1 21px);
            background-size: 100% 22px;
            outline: none;
            font-size: 13.5px;
            padding: 0 4px;
        }

        /* Footer Section */
        .doc-footer {
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1.5px solid #0f172a;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .seal-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .seal-image-wrap {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 2px solid #b45309;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            padding: 3px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .seal-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .seal-caption {
            font-family: 'Outfit', sans-serif;
            font-size: 9px;
            font-weight: 700;
            color: #b45309;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
            max-width: 110px;
        }

        .qr-cell {
            text-align: center;
        }

        .qr-cell #qrcode {
            background: #ffffff;
            padding: 4px;
            border-radius: 6px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            border: 1px solid #cbd5e1;
            margin-bottom: 4px;
        }

        .qr-cell #qrcode canvas, .qr-cell #qrcode img {
            display: block;
            width: 72px;
            height: 72px;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
            image-rendering: pixelated;
        }

        .signature-cell {
            text-align: center;
            min-width: 200px;
        }

        .sig-line {
            width: 100%;
            border-top: 1px solid #0f172a;
            margin-top: 36px;
            padding-top: 4px;
        }

        .sig-title {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1e3a8a;
            letter-spacing: 0.5px;
        }

        .meta-footnote {
            font-family: monospace;
            font-size: 9px;
            color: #64748b;
            text-align: center;
            margin-top: 8px;
            width: 100%;
        }

        /* Parishioner Search Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-card {
            background: white;
            border-radius: 12px;
            width: 480px;
            max-width: 90vw;
            padding: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            font-family: 'Inter', sans-serif;
        }
    </style>
    <script src="../assets/js/qrcode.min.js"></script>
    <script>
        if (typeof QRCode === 'undefined') {
            document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>');
        }
    </script>
</head>
<body>

    <!-- Control Top Bar (Hidden on Print) -->
    <div class="no-print control-bar">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div class="mode-toggle-group">
                <button type="button" class="mode-btn <?php echo $initial_mode === 'request' ? 'active' : ''; ?>" id="btn-mode-req" onclick="setMode('request')">
                    <span style="font-size: 14px;">✉️</span> It is required (Inquiry)
                </button>
                <button type="button" class="mode-btn <?php echo $initial_mode === 'certificate' ? 'active' : ''; ?>" id="btn-mode-cert" onclick="setMode('certificate')">
                    <span style="font-size: 14px;">📜</span> This is (Status Certificate)
                </button>
            </div>

            <button type="button" class="action-btn ghost" onclick="openSearchModal()">
                <span style="font-size: 14px;">🔍</span> <?php echo $person ? 'Switch Parishioner' : 'Find Parishioner'; ?>
            </button>
            <?php if ($person): ?>
                <a href="canonical_inquiry.php" class="action-btn ghost" title="Clear form for manual entry">
                    Clear Blank
                </a>
            <?php endif; ?>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <button onclick="window.print()" class="action-btn">
                <span>🖨️</span> Print Form
            </button>
            <button onclick="downloadPDF()" class="action-btn secondary">
                <span>📥</span> PDF
            </button>
            <?php if ($person): ?>
                <a href="../parishioners/view.php?id=<?php echo $person_id; ?>" class="action-btn ghost">Exit</a>
            <?php else: ?>
                <a href="../parishioners.php" class="action-btn ghost">Exit</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- The Printable Canonical Document -->
    <div class="canonical-sheet" id="print-sheet">
        
        <div>
            <!-- Header -->
            <div class="doc-header">
                <div class="diocese-branding">
                    <h1>DIOCESE OF HWANGE</h1>
                    <h2>ZIMBABWE</h2>
                </div>
                <div class="mission-info-box">
                    <div class="mission-info-row">
                        <span class="mission-info-label">Mission:</span>
                        <span class="editable-field" contenteditable="true" style="min-width: 170px;"><?php echo h($mission_name); ?></span>
                    </div>
                    <div class="mission-info-row">
                        <span class="mission-info-label">P.O.:</span>
                        <span class="editable-field" contenteditable="true" style="min-width: 170px;"><?php echo h($po_box); ?></span>
                    </div>
                    <div class="mission-info-row">
                        <span class="mission-info-label">Date:</span>
                        <span class="editable-field" contenteditable="true" style="min-width: 170px;"><?php echo h($today_date); ?></span>
                    </div>
                </div>
            </div>

            <!-- Salutation & Canonical Dual Bracket -->
            <div class="salutation-section">
                <div class="dear-father">Dear Father,</div>

                <div class="dual-brace-container">
                    <div class="brace-options">
                        <div class="brace-option-row" onclick="setMode('request')">
                            <span class="check-box" id="box-request"><?php echo $initial_mode === 'request' ? 'X' : '&nbsp;'; ?></span>
                            <span>It is required</span>
                        </div>
                        <div class="brace-option-row" onclick="setMode('certificate')">
                            <span class="check-box" id="box-certificate"><?php echo $initial_mode === 'certificate' ? 'X' : '&nbsp;'; ?></span>
                            <span>This is</span>
                        </div>
                    </div>
                    <div class="curly-brace-symbol">}</div>
                    <div class="cert-type-announcement">
                        <span>the CERTIFICATE of</span>
                        <span class="editable-field" id="cert-type-title" contenteditable="true" style="flex: 1; min-width: 220px; font-weight: 800; color: #1e3a8a; text-transform: uppercase;">
                            <?php echo h($cert_for); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Canonical Record Rows (Matching the Scanned Diocesan Sheet) -->
            <div class="data-lines-container">
                <!-- Name -->
                <div class="data-row">
                    <span class="data-label">Name</span>
                    <div class="data-fill bold" contenteditable="true" style="font-size: 16px;"><?php echo h($full_name); ?></div>
                </div>

                <!-- Son / Daughter of -->
                <div class="data-row">
                    <span class="data-label">
                        <span id="label-son-daughter"><?php echo $gender === 'female' ? 'Daughter' : 'Son'; ?></span> of
                    </span>
                    <div class="data-fill" contenteditable="true" style="flex: 1;"><?php echo h($father_name); ?></div>
                    <span class="data-label" style="margin-left: 10px; margin-right: 6px;">and</span>
                    <div class="data-fill" contenteditable="true" style="flex: 1;"><?php echo h($mother_name); ?></div>
                </div>

                <!-- Born at ... on ... -->
                <div class="data-row">
                    <span class="data-label">Born at</span>
                    <div class="data-fill" contenteditable="true" style="flex: 1.2;"><?php echo h($birth_place); ?></div>
                    <span class="data-label" style="margin-left: 10px; margin-right: 6px;">on</span>
                    <div class="data-fill" contenteditable="true" style="flex: 1;"><?php echo h($birth_date); ?></div>
                </div>

                <!-- Baptised at ... on ... Reg. No. ... -->
                <div class="data-row">
                    <span class="data-label">Baptised at</span>
                    <div class="data-fill" contenteditable="true" style="flex: 1.5;"><?php echo h($bap_parish); ?></div>
                    <span class="data-label" style="margin-left: 8px; margin-right: 4px;">on</span>
                    <div class="data-fill" contenteditable="true" style="flex: 1;"><?php echo h($bap_date); ?></div>
                    <span class="data-label" style="margin-left: 8px; margin-right: 4px;">Reg. No.</span>
                    <div class="data-fill" contenteditable="true" style="flex: 0.9; font-family: monospace; font-size: 12.5px;"><?php echo h($bap_reg); ?></div>
                </div>

                <!-- Confirmed at ... on ... Reg. No. ... -->
                <div class="data-row">
                    <span class="data-label">Confirmed at</span>
                    <div class="data-fill" contenteditable="true" style="flex: 1.5;"><?php echo h($conf_parish); ?></div>
                    <span class="data-label" style="margin-left: 8px; margin-right: 4px;">on</span>
                    <div class="data-fill" contenteditable="true" style="flex: 1;"><?php echo h($conf_date); ?></div>
                    <span class="data-label" style="margin-left: 8px; margin-right: 4px;">Reg. No.</span>
                    <div class="data-fill" contenteditable="true" style="flex: 0.9; font-family: monospace; font-size: 12.5px;"><?php echo h($conf_reg); ?></div>
                </div>

                <!-- Married in Church (or before Baptism) -->
                <div>
                    <div class="data-label" style="margin-bottom: 2px;">Married in Church (or before Baptism)</div>
                    <div class="data-row" style="margin-left: 18px; width: calc(100% - 18px);">
                        <span class="data-label">to</span>
                        <div class="data-fill" contenteditable="true" style="flex: 1;"><?php echo h($spouse_name); ?></div>
                    </div>
                    <div class="data-row" style="margin-left: 18px; width: calc(100% - 18px); margin-top: 4px;">
                        <span class="data-label">at</span>
                        <div class="data-fill" contenteditable="true" style="flex: 1.5;"><?php echo h($marr_parish); ?></div>
                        <span class="data-label" style="margin-left: 8px; margin-right: 4px;">on</span>
                        <div class="data-fill" contenteditable="true" style="flex: 1;"><?php echo h($marr_date); ?></div>
                        <span class="data-label" style="margin-left: 8px; margin-right: 4px;">Reg. No.</span>
                        <div class="data-fill" contenteditable="true" style="flex: 0.9; font-family: monospace; font-size: 12.5px;"><?php echo h($marr_reg); ?></div>
                    </div>
                </div>

                <!-- Died at ... on ... Reg. No. ... -->
                <div class="data-row">
                    <span class="data-label">Died at</span>
                    <div class="data-fill" contenteditable="true" style="flex: 1.5;"><?php echo h($death_place); ?></div>
                    <span class="data-label" style="margin-left: 8px; margin-right: 4px;">on</span>
                    <div class="data-fill" contenteditable="true" style="flex: 1;"><?php echo h($death_date); ?></div>
                    <span class="data-label" style="margin-left: 8px; margin-right: 4px;">Reg. No.</span>
                    <div class="data-fill" contenteditable="true" style="flex: 0.9; font-family: monospace; font-size: 12.5px;"><?php echo h($death_reg); ?></div>
                </div>

                <!-- Recorded in the books kept at -->
                <div class="data-row">
                    <span class="data-label">Recorded in the books kept at</span>
                    <div class="data-fill bold" contenteditable="true" style="flex: 1;"><?php echo h($books_kept_at); ?></div>
                </div>
            </div>

            <!-- Remarks -->
            <div class="remarks-box">
                <div class="remarks-title">REMARKS</div>
                <div class="remarks-content" contenteditable="true">
                    <?php echo nl2br(h($remarks_text)); ?>
                </div>
            </div>
        </div>

        <!-- Footer: Seal, QR Verification, & Priest Signature -->
        <div>
            <div class="doc-footer">
                <div class="seal-cell">
                    <div class="seal-image-wrap">
                        <img src="../assets/img/seal.png" alt="Diocese Seal">
                    </div>
                    <div class="seal-caption">
                        Official Canonical Seal<br>Diocese of Hwange
                    </div>
                </div>

                <?php if ($verify_urls): ?>
                    <div class="qr-cell" id="qr-container">
                        <a href="<?php echo htmlspecialchars($verify_urls['click_url']); ?>" target="_blank" title="Scan or click to verify canonical status" style="text-decoration: none; display: inline-block;">
                            <div id="qrcode" style="display: flex; justify-content: center; margin-bottom: 3px;"></div>
                            <div style="font-size: 7.5px; font-weight: 700; color: #475569; font-family: 'Inter', sans-serif; letter-spacing: 0.5px;">SCAN TO VERIFY</div>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="qr-cell" id="qr-container" style="min-width: 80px;">
                        <div style="width: 58px; height: 58px; border: 1px dashed #cbd5e1; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #94a3b8; font-family: 'Inter', sans-serif; margin: 0 auto;">
                            PARISH/MISSION STAMP
                        </div>
                    </div>
                <?php endif; ?>

                <div class="signature-cell">
                    <div class="sig-line"></div>
                    <div class="sig-title">Parish/Mission Priest / Rector</div>
                    <div style="font-size: 10px; color: #64748b; font-family: 'Inter', sans-serif; margin-top: 3px;">
                        Diocese of Hwange, Zimbabwe
                    </div>
                </div>
            </div>

            <div class="meta-footnote">
                Official Canonical Extract & Inquiry Instrument • Hwange Diocesan Sacramental RMS (Can. 535 §2)
                <?php if ($person && !empty($person['guid'])): ?>
                    • GUID: <?php echo h($person['guid']); ?>
                <?php endif; ?>
                <br>
                <span style="font-style: italic;">CANONICAL NOTICE (Can. 1068): For matrimonial preparation, this canonical extract is valid for six (6) months from the date of issue.</span>
            </div>
        </div>

    </div>

    <!-- Quick Search Parishioner Modal -->
    <div class="modal-overlay" id="searchModal">
        <div class="modal-card">
            <h3 style="margin-top: 0; margin-bottom: 15px; color: #0f172a; font-size: 1.15rem;">Select Parishioner for Form</h3>
            <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">
                Search by name or baptism entry to automatically populate the canonical inquiry or status certificate.
            </p>
            <input type="text" id="parishioner-search-input" placeholder="Type name, surname, or entry number..." 
                   style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; margin-bottom: 12px; outline: none;"
                   onkeyup="filterParishioners(this.value)">
            
            <div id="search-results-list" style="max-height: 220px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 16px;">
                <div style="padding: 12px; text-align: center; color: #94a3b8; font-size: 0.85rem;">Type to search records...</div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="action-btn ghost" onclick="closeSearchModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        let currentMode = '<?php echo $initial_mode; ?>';

        function setMode(mode) {
            currentMode = mode;
            const reqBox = document.getElementById('box-request');
            const certBox = document.getElementById('box-certificate');
            const btnReq = document.getElementById('btn-mode-req');
            const btnCert = document.getElementById('btn-mode-cert');
            const titleField = document.getElementById('cert-type-title');

            if (mode === 'request') {
                reqBox.innerHTML = 'X';
                certBox.innerHTML = '&nbsp;';
                btnReq.classList.add('active');
                btnCert.classList.remove('active');
                if (titleField.innerText.trim() === 'BAPTISM & SACRAMENTAL STATUS') {
                    titleField.innerText = 'BAPTISM';
                }
            } else {
                certBox.innerHTML = 'X';
                reqBox.innerHTML = '&nbsp;';
                btnCert.classList.add('active');
                btnReq.classList.remove('active');
                if (titleField.innerText.trim() === 'BAPTISM') {
                    titleField.innerText = 'BAPTISM & SACRAMENTAL STATUS';
                }
            }
        }

        // Initialize QR code if verify_urls available
        <?php if ($verify_urls): ?>
        (function() {
            function renderQR() {
                var qrTarget = document.getElementById("qrcode");
                if (qrTarget && typeof QRCode !== 'undefined') {
                    qrTarget.innerHTML = '';
                    new QRCode(qrTarget, {
                        text: <?php echo json_encode($verify_urls['qr_url']); ?>,
                        width: 256,
                        height: 256,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.M
                    });
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener("DOMContentLoaded", renderQR);
            } else {
                renderQR();
            }
        })();
        <?php endif; ?>

        // Search modal helpers
        function openSearchModal() {
            document.getElementById('searchModal').classList.add('open');
            document.getElementById('parishioner-search-input').focus();
        }

        function closeSearchModal() {
            document.getElementById('searchModal').classList.remove('open');
        }

        let debounceTimer;
        function filterParishioners(term) {
            clearTimeout(debounceTimer);
            if (term.trim().length < 2) {
                document.getElementById('search-results-list').innerHTML = '<div style="padding: 12px; text-align: center; color: #94a3b8; font-size: 0.85rem;">Type at least 2 characters...</div>';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch('../api/search_parishioners.php?q=' + encodeURIComponent(term))
                    .then(res => res.json())
                    .then(data => {
                        const list = document.getElementById('search-results-list');
                        if (!data || data.length === 0) {
                            list.innerHTML = '<div style="padding: 12px; text-align: center; color: #94a3b8; font-size: 0.85rem;">No parishioners found.</div>';
                            return;
                        }
                        let html = '';
                        data.forEach(p => {
                            html += `
                                <div style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; cursor: pointer; display: flex; justify-content: space-between; align-items: center;"
                                     onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'"
                                     onclick="location.href='canonical_inquiry.php?person_id=' + ${p.person_id} + '&mode=' + currentMode">
                                    <div>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.9rem;">${p.first_name} ${p.last_name}</div>
                                        <div style="font-size: 0.75rem; color: #64748b;">DOB: ${p.dob || 'Unknown'} | Parish: ${p.parish_name || 'Hwange'}</div>
                                    </div>
                                    <span style="font-size: 0.75rem; color: #0284c7; font-weight: 700;">Select →</span>
                                </div>
                            `;
                        });
                        list.innerHTML = html;
                    })
                    .catch(err => {
                        document.getElementById('search-results-list').innerHTML = '<div style="padding: 12px; text-align: center; color: #ef4444; font-size: 0.85rem;">Search endpoint unavailable.</div>';
                    });
            }, 300);
        }
    </script>

    <!-- PDF Generation Bundle -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('print-sheet');
            if (!element) return;
            element.classList.add('pdf-generation');
            <?php 
                $safe_title = trim($full_name ?: 'Diocese_of_Hwange_Canonical_Certificate');
                $pdf_inq_filename = 'Status_Certificate_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $safe_title) . '.pdf';
            ?>
            const opt = {
                margin:       0,
                filename:     <?php echo json_encode($pdf_inq_filename); ?>,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { 
                    scale: 2, 
                    useCORS: true, 
                    scrollY: 0, 
                    scrollX: 0,
                    width: 794,
                    height: 1120,
                    windowWidth: 794
                },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).toPdf().get('pdf').then(function(pdf) {
                while (pdf.internal.getNumberOfPages() > 1) {
                    pdf.deletePage(pdf.internal.getNumberOfPages());
                }
            }).save().then(() => {
                element.classList.remove('pdf-generation');
            }).catch((err) => {
                console.error('PDF generation error:', err);
                element.classList.remove('pdf-generation');
            });
        }
    </script>
</body>
</html>
