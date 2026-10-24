<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Official Diocesan Financial Monthly Report (.docx) Generator
 * Clones and populates the canonical 23-line Diocesan Cashbook format
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/diocesan_financial_categories.php';

require_login();

$submission_id = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;
$parish_id = isset($_GET['parish_id']) ? (int)$_GET['parish_id'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

if ($submission_id > 0) {
    $sub = db_fetch("
        SELECT s.*, p.parish_name, p.location, p.deanery, p.contact_number, u.full_name as priest_name
        FROM parish_financial_submissions s
        JOIN parishes p ON s.parish_id = p.parish_id
        LEFT JOIN users u ON p.priest_in_charge_id = u.user_id
        WHERE s.submission_id = ?
    ", [$submission_id]);
} elseif ($parish_id > 0) {
    $sub = db_fetch("
        SELECT s.*, p.parish_name, p.location, p.deanery, p.contact_number, u.full_name as priest_name
        FROM parish_financial_submissions s
        JOIN parishes p ON s.parish_id = p.parish_id
        LEFT JOIN users u ON p.priest_in_charge_id = u.user_id
        WHERE s.parish_id = ? AND s.fiscal_year = ? AND s.fiscal_month = ?
    ", [$parish_id, $year, $month]);
} else {
    die("Error: No report specified.");
}

if (!$sub) {
    set_flash("Financial report not found.", "error");
    redirect("dashboard/finance_submission.php");
    exit;
}

$template_path = __DIR__ . '/../data/templates/diocesan_financial_template.docx';
if (!file_exists($template_path)) {
    // Fallback if template missing in data/templates
    $alt_template = "C:/Users/VINCENT/Documents/ST. AUGUSTINE MISSION/ADMINISTRATION/2026 FINANCES/JAN 2026.docx";
    if (file_exists($alt_template)) {
        copy($alt_template, $template_path);
    } else {
        die("Error: Official Diocesan template (.docx) not found on server.");
    }
}

// Decode JSON financial details if available
$details = !empty($sub['financial_details']) ? json_decode($sub['financial_details'], true) : [];
$inc_cats = get_diocesan_income_categories();
$exp_cats = get_diocesan_expenditure_categories();

// Fallback values from first-class columns if details array was empty
$income_data = [];
$expenditure_data = [];

for ($i = 1; $i <= 23; $i++) {
    $income_data[$i] = isset($details['income'][$i]) ? (float)$details['income'][$i] : 0.0;
    $expenditure_data[$i] = isset($details['expenditure'][$i]) ? (float)$details['expenditure'][$i] : 0.0;
}

// Ensure Line 3 (Tithe/Unsupo/Mutulo) & Line 4 (Sunday Collection) reflect column data if details wasn't saved yet
if ($income_data[3] == 0 && !empty($sub['tithe'])) {
    $income_data[3] = (float)$sub['tithe'];
}
if ($income_data[4] == 0 && !empty($sub['total_collections'])) {
    $income_data[4] = (float)$sub['total_collections'];
}

// Map the 8 annual special diocesan collections if present in financial details
if (!empty($details['special_collections'])) {
    $sc = $details['special_collections'];
    if ($income_data[17] == 0 && !empty($sc['holy_land'])) {
        $income_data[17] = (float)$sc['holy_land'];
    }
    if ($income_data[20] == 0 && !empty($sc['lenten_collection'])) {
        $income_data[20] = (float)$sc['lenten_collection'];
    }
    if ($income_data[22] == 0 && !empty($sc['peters_pence'])) {
        $income_data[22] = (float)$sc['peters_pence'];
    }
    if ($expenditure_data[18] == 0 && !empty($sc['holy_land'])) {
        $expenditure_data[18] = (float)$sc['holy_land'];
    }
    if ($expenditure_data[19] == 0 && !empty($sc['missionary_childhood'])) {
        $expenditure_data[19] = (float)$sc['missionary_childhood'];
    }
    if ($expenditure_data[21] == 0 && !empty($sc['mission_sunday'])) {
        $expenditure_data[21] = (float)$sc['mission_sunday'];
    }
}

// Ensure Expenditure Line 4 (Diocesan Levy 10%) is (Line 3 + Line 4) * 0.10
$income_data[3] = (float)$income_data[3];
$income_data[4] = (float)$income_data[4];
$expenditure_data[4] = ($income_data[3] + $income_data[4]) * 0.10;

$total_inc = array_sum($income_data);
$total_exp = array_sum($expenditure_data);
$net_surplus_deficit = $total_inc - $total_exp;
$opening_bal = isset($details['opening_balance']) ? (float)$details['opening_balance'] : (float)($sub['opening_balance'] ?? 0);
$closing_bal = $net_surplus_deficit + $opening_bal;

$cash_hand = isset($details['cash_at_hand']) ? (float)$details['cash_at_hand'] : (float)($sub['cash_at_hand'] ?? $closing_bal);
$cash_bank = isset($details['cash_in_bank']) ? (float)$details['cash_in_bank'] : (float)($sub['cash_in_bank'] ?? 0);
$savings = isset($details['savings']) ? (float)$details['savings'] : (float)($sub['savings'] ?? 0);
$investments = isset($details['investments']) ? (float)$details['investments'] : (float)($sub['investments'] ?? 0);

// Create temporary working docx
$temp_file = tempnam(sys_get_temp_dir(), 'dio_fin_') . '.docx';
copy($template_path, $temp_file);

$zip = new ZipArchive();
if ($zip->open($temp_file) !== true) {
    die("Error: Failed to process Word document container.");
}

$xml = $zip->getFromName('word/document.xml');
$dom = new DOMDocument();
$dom->loadXML($xml);
$ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

function setWordCellText($tc, $newText, $ns) {
    $tNodes = $tc->getElementsByTagNameNS($ns, 't');
    if ($tNodes->length > 0) {
        $tNodes->item(0)->nodeValue = $newText;
        for ($k = 1; $k < $tNodes->length; $k++) {
            $tNodes->item($k)->nodeValue = '';
        }
    }
}

// 1. Update Month Header (e.g. "SEPTEMBER 2026")
$month_num = (int)$sub['fiscal_month'];
$year_num = (int)$sub['fiscal_year'];
$month_str = strtoupper(date('F Y', mktime(0, 0, 0, $month_num, 1, $year_num)));

$pNodes = $dom->getElementsByTagNameNS($ns, 'p');
foreach ($pNodes as $p) {
    $text = $p->textContent;
    if (preg_match('/\b(JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER)\s+\d{4}\b/i', $text)) {
        $tNodes = $p->getElementsByTagNameNS($ns, 't');
        if ($tNodes->length > 0) {
            $tNodes->item(0)->nodeValue = $month_str;
            for ($k = 1; $k < $tNodes->length; $k++) {
                $tNodes->item($k)->nodeValue = '';
            }
        }
    }
}

// 2. Populate 23 Income & 23 Expenditure Rows
$rows = $dom->getElementsByTagNameNS($ns, 'tr');

for ($i = 1; $i <= 23; $i++) {
    $row_idx = $i + 1; // Row 2 corresponds to item 1
    if ($row_idx < $rows->length) {
        $row = $rows->item($row_idx);
        $cells = $row->getElementsByTagNameNS($ns, 'tc');
        if ($cells->length >= 5) {
            // Explicitly set terminology for Line 3 & Line 4
            if ($i === 3) {
                setWordCellText($cells->item(1), 'Tithe/Unsupo/Mutulo', $ns);
            } elseif ($i === 4) {
                setWordCellText($cells->item(1), 'Sunday Collection', $ns);
            }

            // Cell 2 = Income Amount
            $inc_val = $income_data[$i];
            setWordCellText($cells->item(2), 'US$' . number_format($inc_val, 2), $ns);

            // Cell 4 = Expenditure Amount
            $exp_val = $expenditure_data[$i];
            setWordCellText($cells->item(4), 'US$' . number_format($exp_val, 2), $ns);
        }
    }
}

// 3. Populate Summary & Reconciliation Rows (Rows 28 to 32)
if ($rows->length >= 33) {
    // Row 28: Total income & Cash at Hand
    $cells28 = $rows->item(28)->getElementsByTagNameNS($ns, 'tc');
    if ($cells28->length >= 5) {
        setWordCellText($cells28->item(2), 'US$' . number_format($total_inc, 2), $ns);
        setWordCellText($cells28->item(4), ($cash_hand < 0 ? '-US$' : 'US$') . number_format(abs($cash_hand), 2), $ns);
    }

    // Row 29: Total Expenditure & Cash in Bank
    $cells29 = $rows->item(29)->getElementsByTagNameNS($ns, 'tc');
    if ($cells29->length >= 5) {
        setWordCellText($cells29->item(2), 'US$' . number_format($total_exp, 2), $ns);
        setWordCellText($cells29->item(4), 'US$' . number_format($cash_bank, 2), $ns);
    }

    // Row 30: Total Income Less Total Expenditure & Savings
    $cells30 = $rows->item(30)->getElementsByTagNameNS($ns, 'tc');
    if ($cells30->length >= 5) {
        setWordCellText($cells30->item(2), ($net_surplus_deficit < 0 ? '-US$' : 'US$') . number_format(abs($net_surplus_deficit), 2), $ns);
        setWordCellText($cells30->item(4), 'US$' . number_format($savings, 2), $ns);
    }

    // Row 31: Add Opening Balance & Investments
    $cells31 = $rows->item(31)->getElementsByTagNameNS($ns, 'tc');
    if ($cells31->length >= 5) {
        setWordCellText($cells31->item(2), ($opening_bal < 0 ? '-US$' : 'US$') . number_format(abs($opening_bal), 2), $ns);
        setWordCellText($cells31->item(4), 'US$' . number_format($investments, 2), $ns);
    }

    // Row 32: Closing Balance & Total Assets
    $cells32 = $rows->item(32)->getElementsByTagNameNS($ns, 'tc');
    if ($cells32->length >= 5) {
        setWordCellText($cells32->item(2), ($closing_bal < 0 ? '-US$' : 'US$') . number_format(abs($closing_bal), 2), $ns);
        setWordCellText($cells32->item(4), ($closing_bal < 0 ? '-US$' : 'US$') . number_format(abs($closing_bal), 2), $ns);
    }
}

// Save modified document.xml back into zip
$zip->addFromString('word/document.xml', $dom->saveXML());
$zip->close();

// Stream file to browser
$clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sub['parish_name']);
$filename = "{$clean_name}_Financial_Report_" . date('M_Y', mktime(0,0,0,$month_num,1,$year_num)) . ".docx";

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($temp_file));

readfile($temp_file);
@unlink($temp_file);
exit();
