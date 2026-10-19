<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Annual Parochial Register Archive Returns & Closed Volume Export
 * Pursuant to Canon 535 §4 of the Code of Canon Law:
 * "In singulis paroeciis habeatur archivum, in quo parochialia volumina custodiantur...
 *  quae omnia, ab Ordinario vel eius delegato tempore visitationis inspicienda..."
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user_role = $_SESSION['role'] ?? 'viewer';
$is_admin = in_array($user_role, ['admin', 'chancellor']);
$user_parish_id = $_SESSION['parish_id'] ?? null;
$user_full_name = $_SESSION['full_name'] ?? 'Parish Priest';

// Determine Parish
$selected_parish_id = isset($_GET['parish_id']) ? (int)$_GET['parish_id'] : ($user_parish_id ? (int)$user_parish_id : 0);
if (!$is_admin && $user_parish_id) {
    $selected_parish_id = (int)$user_parish_id;
}

// Determine Year
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($selected_year < 1900 || $selected_year > 2100) {
    $selected_year = (int)date('Y');
}

// Determine Register Type filter
$selected_type = isset($_GET['type']) ? trim($_GET['type']) : 'all';

// Fetch all parishes for selector
$parishes = db_fetchAll("SELECT * FROM parishes ORDER BY parish_name ASC");

// Fetch active parish metadata
$current_parish = null;
if ($selected_parish_id > 0) {
    $current_parish = db_fetch("SELECT * FROM parishes WHERE parish_id = ?", [$selected_parish_id]);
}

// Handle Form Attestation Submission
$attestation_message = '';
$attestation_error = '';
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($request_method === 'POST' && isset($_POST['action']) && $_POST['action'] === 'attest_volume') {
    $parish_to_attest = (int)($_POST['parish_id'] ?? $selected_parish_id);
    $year_to_attest = (int)($_POST['year'] ?? $selected_year);
    $reg_type_to_attest = trim($_POST['register_type'] ?? 'all');
    $attested_by = trim($_POST['attested_by'] ?? $user_full_name);
    $attested_date = trim($_POST['attested_date'] ?? date('Y-m-d'));
    $notes = trim($_POST['notes'] ?? '');
    $total_count = (int)($_POST['total_count'] ?? 0);

    if ($parish_to_attest > 0) {
        try {
            db_query("INSERT INTO annual_register_returns 
                (parish_id, return_year, register_type, total_records, attested_by, attested_date, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Transmitted')
                ON CONFLICT(parish_id, return_year, register_type) DO UPDATE SET
                total_records = excluded.total_records,
                attested_by = excluded.attested_by,
                attested_date = excluded.attested_date,
                notes = excluded.notes,
                status = 'Transmitted'",
                [$parish_to_attest, $year_to_attest, $reg_type_to_attest, $total_count, $attested_by, $attested_date, $notes]
            );
            $attestation_message = "Canonical Attestation for Year {$year_to_attest} successfully recorded. Parochial ledger volume marked as Closed and Transmitted to Diocesan Archives.";
        } catch (Exception $e) {
            $attestation_error = "Error recording canonical attestation: " . $e->getMessage();
        }
    } else {
        $attestation_error = "Please select a specific parish to attest canonical returns.";
    }
}

// Check existing attestation record
$existing_attestation = null;
if ($selected_parish_id > 0) {
    $existing_attestation = db_fetch(
        "SELECT * FROM annual_register_returns WHERE parish_id = ? AND return_year = ? AND register_type = ?",
        [$selected_parish_id, $selected_year, $selected_type]
    );
    if (!$existing_attestation && $selected_type !== 'all') {
        $existing_attestation = db_fetch(
            "SELECT * FROM annual_register_returns WHERE parish_id = ? AND return_year = ? AND register_type = 'all'",
            [$selected_parish_id, $selected_year]
        );
    }
}

// -------------------------------------------------------------------------
// QUERY DATA FOR THE CHOSEN YEAR & PARISH
// -------------------------------------------------------------------------
// QUERY DATA FOR THE CHOSEN YEAR & PARISH
// -------------------------------------------------------------------------
$year_prefix = $selected_year . '-%';

// 1. Baptisms
$bap_where = "WHERE (strftime('%Y', b.date_of_baptism) = ? OR b.date_of_baptism LIKE ?)";
$bap_params = [(string)$selected_year, $year_prefix];
if ($selected_parish_id > 0) {
    $bap_where .= " AND b.parish_id = ?";
    $bap_params[] = $selected_parish_id;
}
$baptisms = db_fetchAll(
    "SELECT b.*, p.first_name, p.last_name, p.other_names, p.gender, p.dob, p.place_of_birth, 
            p.father_name, p.mother_name, p.mother_maiden_name, par.parish_name 
     FROM baptisms b
     LEFT JOIN parishioners p ON b.person_id = p.person_id
     LEFT JOIN parishes par ON b.parish_id = par.parish_id
     $bap_where
     ORDER BY b.entry_number ASC, b.date_of_baptism ASC",
    $bap_params
);

// 2. Confirmations
$conf_where = "WHERE (strftime('%Y', c.date_of_confirmation) = ? OR c.date_of_confirmation LIKE ?)";
$conf_params = [(string)$selected_year, $year_prefix];
if ($selected_parish_id > 0) {
    $conf_where .= " AND c.parish_id = ?";
    $conf_params[] = $selected_parish_id;
}
$confirmations = db_fetchAll(
    "SELECT c.*, p.first_name, p.last_name, p.other_names, p.gender, p.dob, par.parish_name,
            b.date_of_baptism, b.place_of_baptism
     FROM confirmations c
     LEFT JOIN parishioners p ON c.person_id = p.person_id
     LEFT JOIN parishes par ON c.parish_id = par.parish_id
     LEFT JOIN baptisms b ON c.person_id = b.person_id
     $conf_where
     ORDER BY c.entry_number ASC, c.date_of_confirmation ASC",
    $conf_params
);

// 3. Marriages
$mar_where = "WHERE (strftime('%Y', m.date_of_marriage) = ? OR m.date_of_marriage LIKE ?)";
$mar_params = [(string)$selected_year, $year_prefix];
if ($selected_parish_id > 0) {
    $mar_where .= " AND m.parish_id = ?";
    $mar_params[] = $selected_parish_id;
}
$marriages = db_fetchAll(
    "SELECT m.*, 
            p1.first_name as g_first, p1.other_names as g_other, p1.last_name as g_last, p1.father_name as g_father, p1.mother_name as g_mother,
            p2.first_name as b_first, p2.other_names as b_other, p2.last_name as b_last, p2.father_name as b_father, p2.mother_name as b_mother,
            par.parish_name
     FROM marriages m
     LEFT JOIN parishioners p1 ON m.groom_person_id = p1.person_id
     LEFT JOIN parishioners p2 ON m.bride_person_id = p2.person_id
     LEFT JOIN parishes par ON m.parish_id = par.parish_id
     $mar_where
     ORDER BY m.entry_number ASC, m.date_of_marriage ASC",
    $mar_params
);

// 4. Burials / Deaths
$bur_where = "WHERE (strftime('%Y', d.date_of_burial) = ? OR d.date_of_burial LIKE ? OR strftime('%Y', d.date_of_death) = ? OR d.date_of_death LIKE ?)";
$bur_params = [(string)$selected_year, $year_prefix, (string)$selected_year, $year_prefix];
if ($selected_parish_id > 0) {
    $bur_where .= " AND d.parish_id = ?";
    $bur_params[] = $selected_parish_id;
}
$burials = db_fetchAll(
    "SELECT d.*, p.first_name, p.last_name, p.other_names, p.gender, p.dob, par.parish_name
     FROM deaths d
     LEFT JOIN parishioners p ON d.person_id = p.person_id
     LEFT JOIN parishes par ON d.parish_id = par.parish_id
     $bur_where
     ORDER BY d.entry_number ASC, d.date_of_burial ASC",
    $bur_params
);

// Statistics Breakdown
$bap_total = count($baptisms);
$bap_infant = 0;
$bap_adult = 0;
$bap_conditional = 0;
foreach ($baptisms as $b) {
    if (!empty($b['is_conditional'])) $bap_conditional++;
    if (!empty($b['dob']) && !empty($b['date_of_baptism'])) {
        try {
            $dob = new DateTime($b['dob']);
            $dop = new DateTime($b['date_of_baptism']);
            $diff = $dob->diff($dop)->y;
            if ($diff < 7) {
                $bap_infant++;
            } else {
                $bap_adult++;
            }
        } catch (Exception $e) {
            $bap_infant++;
        }
    } else {
        $bap_infant++;
    }
}

$conf_total = count($confirmations);
$mar_total = count($marriages);
$mar_dispensations = 0;
foreach ($marriages as $m) {
    if (!empty($m['dispensation_type']) || !empty($m['dispensation_protocol_number'])) {
        $mar_dispensations++;
    }
}
$bur_total = count($burials);
$grand_total = $bap_total + $conf_total + $mar_total + $bur_total;

// -------------------------------------------------------------------------
// CSV EXPORT HANDLER
// -------------------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $parish_clean_name = $current_parish ? preg_replace('/[^A-Za-z0-9_-]/', '_', $current_parish['parish_name']) : 'All_Parishes';
    $filename = "Archival_Return_{$parish_clean_name}_{$selected_year}.csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    
    // Title row
    fputcsv($out, ['DIOCESE OF HWANGE - CANONICAL ARCHIVAL RETURN (CAN. 535 §4)']);
    fputcsv($out, ['Parish', $current_parish ? $current_parish['parish_name'] : 'Consolidated (All Parishes)']);
    fputcsv($out, ['Canonical Year', $selected_year]);
    fputcsv($out, ['Generated At', date('Y-m-d H:i:s')]);
    fputcsv($out, []);

    // Summary Matrix
    fputcsv($out, ['--- SACRAMENTAL SUMMARY MATRIX ---']);
    fputcsv($out, ['Register Type', 'Count']);
    fputcsv($out, ['Baptisms (Infant <7y)', $bap_infant]);
    fputcsv($out, ['Baptisms (Adult >=7y)', $bap_adult]);
    fputcsv($out, ['Baptisms (Sub Conditione)', $bap_conditional]);
    fputcsv($out, ['Total Baptisms', $bap_total]);
    fputcsv($out, ['Total Confirmations', $conf_total]);
    fputcsv($out, ['Total Marriages', $mar_total]);
    fputcsv($out, ['Marriages with Canonical Dispensations', $mar_dispensations]);
    fputcsv($out, ['Total Christian Burials', $bur_total]);
    fputcsv($out, ['GRAND TOTAL RECORDS', $grand_total]);
    fputcsv($out, []);

    // Baptisms Section
    if ($selected_type === 'all' || $selected_type === 'baptisms') {
        fputcsv($out, ['=== LIBER BAPTIZATORUM (BAPTISMAL REGISTER) ===']);
        fputcsv($out, ['Entry No', 'Date of Baptism', 'Date of Birth', 'Surname', 'First Name', 'Other Names', 'Father', 'Mother', 'Mother Maiden Name', 'Godparents / Sponsors', 'Christian Witness', 'Minister', 'Marginal Notations', 'Sub Conditione']);
        foreach ($baptisms as $b) {
            fputcsv($out, [
                $b['entry_number'] ?? '',
                $b['date_of_baptism'] ?? '',
                $b['dob'] ?? '',
                $b['last_name'] ?? '',
                $b['first_name'] ?? '',
                $b['other_names'] ?? '',
                $b['father_name'] ?? '',
                $b['mother_name'] ?? '',
                $b['mother_maiden_name'] ?? '',
                $b['godparents'] ?? '',
                $b['christian_witness'] ?? '',
                $b['minister'] ?? '',
                $b['marginal_notations'] ?? '',
                !empty($b['is_conditional']) ? 'YES' : 'NO'
            ]);
        }
        fputcsv($out, []);
    }

    // Confirmations Section
    if ($selected_type === 'all' || $selected_type === 'confirmations') {
        fputcsv($out, ['=== LIBER CONFIRMATORUM (CONFIRMATION REGISTER) ===']);
        fputcsv($out, ['Entry No', 'Date of Confirmation', 'Surname', 'First Name', 'Other Names', 'Date of Baptism', 'Place of Baptism', 'Sponsor', 'Officiating Minister']);
        foreach ($confirmations as $c) {
            fputcsv($out, [
                $c['entry_number'] ?? '',
                $c['date_of_confirmation'] ?? '',
                $c['last_name'] ?? '',
                $c['first_name'] ?? '',
                $c['other_names'] ?? '',
                $c['date_of_baptism'] ?? '',
                $c['place_of_baptism'] ?? '',
                $c['sponsor'] ?? '',
                $c['minister'] ?? ''
            ]);
        }
        fputcsv($out, []);
    }

    // Marriages Section
    if ($selected_type === 'all' || $selected_type === 'marriages') {
        fputcsv($out, ['=== LIBER MATRIMONIORUM (MATRIMONIAL REGISTER) ===']);
        fputcsv($out, ['Entry No', 'Date of Marriage', 'Groom Full Name', 'Groom Father', 'Groom Mother', 'Bride Full Name', 'Bride Father', 'Bride Mother', 'Canonical Witnesses', 'Officiating Minister', 'Dispensation Type', 'Dispensation Protocol', 'Dispensation Date']);
        foreach ($marriages as $m) {
            $gName = trim(($m['g_first'] ?? '') . ' ' . ($m['g_other'] ?? '') . ' ' . ($m['g_last'] ?? ''));
            $bName = trim(($m['b_first'] ?? '') . ' ' . ($m['b_other'] ?? '') . ' ' . ($m['b_last'] ?? ''));
            fputcsv($out, [
                $m['entry_number'] ?? '',
                $m['date_of_marriage'] ?? '',
                $gName,
                $m['g_father'] ?? '',
                $m['g_mother'] ?? '',
                $bName,
                $m['b_father'] ?? '',
                $m['b_mother'] ?? '',
                $m['witnesses_names'] ?? '',
                $m['officiant'] ?? '',
                $m['dispensation_type'] ?? '',
                $m['dispensation_protocol_number'] ?? '',
                $m['dispensation_date'] ?? ''
            ]);
        }
        fputcsv($out, []);
    }

    // Burials Section
    if ($selected_type === 'all' || $selected_type === 'deaths') {
        fputcsv($out, ['=== LIBER DEFUNCTORUM (BURIAL REGISTER) ===']);
        fputcsv($out, ['Entry No', 'Date of Death', 'Date of Burial', 'Full Name', 'Place of Burial', 'Officiating Minister']);
        foreach ($burials as $d) {
            $fullName = trim(($d['first_name'] ?? '') . ' ' . ($d['other_names'] ?? '') . ' ' . ($d['last_name'] ?? ''));
            fputcsv($out, [
                $d['entry_number'] ?? '',
                $d['date_of_death'] ?? '',
                $d['date_of_burial'] ?? '',
                $fullName,
                $d['place_of_burial'] ?? '',
                $d['minister'] ?? ''
            ]);
        }
    }
    fclose($out);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annual Parochial Register Return (Can. 535 §4) - <?php echo $selected_year; ?> - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6.2">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <style>
        /* Canonical Archival Styling */
        :root {
            --seal-gold: #b45309;
            --archival-navy: #1e3a8a;
            --ledger-border: #cbd5e1;
            --ledger-bg: #f8fafc;
        }

        body {
            background-color: #0b0f19;
            color: #f8fafc;
            font-family: 'Inter', -apple-system, sans-serif;
            margin: 0;
            padding: 0;
        }

        .archive-controls {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .control-select {
            background: #1e293b;
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1.25rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-print {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .btn-print:hover { background: #1e40af; transform: translateY(-1px); }

        .btn-csv {
            background: #059669;
            color: white;
        }
        .btn-csv:hover { background: #047857; }

        .btn-attest {
            background: #d97706;
            color: white;
        }
        .btn-attest:hover { background: #b45309; }

        .btn-back {
            background: #334155;
            color: white;
        }
        .btn-back:hover { background: #475569; }

        /* The Printable Archival Document */
        .document-wrapper {
            max-width: 1100px;
            margin: 2.5rem auto;
            background: #ffffff;
            color: #0f172a;
            padding: 3.5rem 4rem;
            border-radius: 4px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            line-height: 1.5;
        }

        .canonical-header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
        }

        .diocese-seal-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin: 0 auto 0.75rem auto;
            display: block;
        }

        .canonical-title {
            font-family: 'Cinzel', serif;
            font-size: 1.6rem;
            letter-spacing: 2px;
            font-weight: 900;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
        }

        .canonical-subtitle {
            font-family: 'Playfair Display', Georgia, serif;
            font-style: italic;
            font-size: 1.05rem;
            color: #475569;
            margin: 0.35rem 0 0.5rem 0;
        }

        .canonical-statute {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .meta-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 1rem 1.25rem;
            border-radius: 6px;
            margin-bottom: 2rem;
        }

        .meta-strip-item label {
            display: block;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .meta-strip-item span {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* Matrix Table */
        .section-heading {
            font-family: 'Cinzel', serif;
            font-size: 1.15rem;
            font-weight: 800;
            color: #1e3a8a;
            border-bottom: 1.5px solid #1e3a8a;
            padding-bottom: 0.35rem;
            margin: 2rem 0 1rem 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-heading .badge {
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            background: #1e3a8a;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 600;
        }

        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
            margin-bottom: 2rem;
        }

        .ledger-table th {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ledger-table td {
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
            color: #1e293b;
        }

        .ledger-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .entry-pill {
            display: inline-block;
            font-weight: 700;
            font-family: monospace;
            background: #e2e8f0;
            color: #0f172a;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-top: 3px solid #1e3a8a;
            border-radius: 6px;
            padding: 1rem;
            text-align: center;
        }

        .stat-num {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e3a8a;
            font-family: 'Outfit', sans-serif;
            line-height: 1.1;
        }

        .stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 700;
            margin-top: 4px;
        }

        .stat-detail {
            font-size: 0.7rem;
            color: #475569;
            margin-top: 4px;
        }

        /* Canonical Attestation Block */
        .attestation-box {
            border: 2px solid #0f172a;
            border-radius: 6px;
            padding: 2rem;
            background: #fafafa;
            margin-top: 3rem;
            position: relative;
        }

        .attestation-box h4 {
            font-family: 'Cinzel', serif;
            font-size: 1.1rem;
            margin: 0 0 1rem 0;
            text-align: center;
            letter-spacing: 1.5px;
            color: #0f172a;
            text-transform: uppercase;
        }

        .attestation-text {
            font-family: 'Playfair Display', Georgia, serif;
            font-style: italic;
            font-size: 0.95rem;
            line-height: 1.6;
            text-align: justify;
            color: #1e293b;
            margin-bottom: 2rem;
        }

        .attestation-signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
            text-align: center;
        }

        .sig-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
        }

        .sig-line {
            width: 100%;
            border-bottom: 1px solid #0f172a;
            margin-bottom: 0.5rem;
            height: 50px;
        }

        .sig-caption {
            font-size: 0.75rem;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sig-sub {
            font-size: 0.65rem;
            color: #64748b;
        }

        .seal-box {
            border: 1px dashed #cbd5e1;
            height: 90px;
            width: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 auto 0.5rem auto;
            font-weight: 600;
        }

        .alert-bar {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; }
        .alert-danger { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-card {
            background: #1e293b;
            color: white;
            padding: 2rem;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255,255,255,0.1);
        }

        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            .archive-controls, .no-print {
                display: none !important;
            }
            .document-wrapper {
                margin: 0 !important;
                padding: 10mm 12mm !important;
                max-width: 100% !important;
                box-shadow: none !important;
                border: none !important;
            }
            .page-break {
                page-break-before: always;
            }
            .ledger-table th {
                background: #e2e8f0 !important;
                color: black !important;
            }
            .ledger-table tbody tr:nth-child(even) {
                background: #f8fafc !important;
            }
            .attestation-box {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- Top Non-Printing Control Bar -->
    <header class="archive-controls no-print">
        <div class="control-group">
            <a href="../sacraments.php" class="btn-action btn-back">
                <ion-icon name="arrow-back-outline"></ion-icon> Hub
            </a>
            <div style="display: flex; flex-direction: column;">
                <span style="font-size: 0.95rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: white;">
                    Annual Register Returns (Can. 535 §4)
                </span>
                <span style="font-size: 0.7rem; color: #94a3b8;">
                    Authenticated Curial Duplicate of Parochial Registers
                </span>
            </div>
        </div>

        <form method="GET" class="control-group" id="filterForm">
            <!-- Parish Selector (Enabled for Admin/Chancellor) -->
            <?php if ($is_admin): ?>
                <select name="parish_id" class="control-select" onchange="this.form.submit()">
                    <option value="0" <?php echo $selected_parish_id == 0 ? 'selected' : ''; ?>>-- All Diocesan Parishes (Consolidated) --</option>
                    <?php foreach ($parishes as $p): ?>
                        <option value="<?php echo $p['parish_id']; ?>" <?php echo $selected_parish_id == $p['parish_id'] ? 'selected' : ''; ?>>
                            <?php echo h($p['parish_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <span style="font-size: 0.85rem; font-weight: 600; color: #38bdf8;">
                    <ion-icon name="business-outline"></ion-icon> <?php echo h($current_parish['parish_name'] ?? 'My Parish'); ?>
                </span>
                <input type="hidden" name="parish_id" value="<?php echo $selected_parish_id; ?>">
            <?php endif; ?>

            <!-- Year Selector -->
            <select name="year" class="control-select" onchange="this.form.submit()">
                <?php 
                $cur_y = (int)date('Y');
                for ($y = $cur_y; $y >= 2015; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                        Year <?php echo $y; ?>
                    </option>
                <?php endfor; ?>
            </select>

            <!-- Register Type Filter -->
            <select name="type" class="control-select" onchange="this.form.submit()">
                <option value="all" <?php echo $selected_type === 'all' ? 'selected' : ''; ?>>All Sacramental Registers</option>
                <option value="baptisms" <?php echo $selected_type === 'baptisms' ? 'selected' : ''; ?>>Baptisms (Liber Baptizatorum)</option>
                <option value="confirmations" <?php echo $selected_type === 'confirmations' ? 'selected' : ''; ?>>Confirmations (Liber Confirmatorum)</option>
                <option value="marriages" <?php echo $selected_type === 'marriages' ? 'selected' : ''; ?>>Marriages (Liber Matrimoniorum)</option>
                <option value="deaths" <?php echo $selected_type === 'deaths' ? 'selected' : ''; ?>>Burials (Liber Defunctorum)</option>
            </select>
        </form>

        <div class="control-group">
            <button onclick="window.print()" class="btn-action btn-print">
                <ion-icon name="print-outline"></ion-icon> Print Archival Ledger (A4)
            </button>
            <a href="?parish_id=<?php echo $selected_parish_id; ?>&year=<?php echo $selected_year; ?>&type=<?php echo $selected_type; ?>&export=csv" class="btn-action btn-csv">
                <ion-icon name="download-outline"></ion-icon> Export CSV
            </a>
            <?php if ($selected_parish_id > 0): ?>
            <button onclick="document.getElementById('attestModal').style.display = 'flex'" class="btn-action btn-attest">
                <ion-icon name="ribbon-outline"></ion-icon> Attest & Transmit Return
            </button>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Container -->
    <div style="max-width: 1100px; margin: 1.5rem auto 0 auto; padding: 0 1rem;" class="no-print">
        <?php if ($attestation_message): ?>
            <div class="alert-bar alert-success">
                <ion-icon name="checkmark-circle-outline" style="font-size: 1.3rem;"></ion-icon>
                <span><?php echo h($attestation_message); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($attestation_error): ?>
            <div class="alert-bar alert-danger">
                <ion-icon name="warning-outline" style="font-size: 1.3rem;"></ion-icon>
                <span><?php echo h($attestation_error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($existing_attestation): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 0.75rem 1.25rem; display: flex; align-items: center; justify-content: space-between; font-size: 0.85rem;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <ion-icon name="shield-checkmark" style="color: #10b981; font-size: 1.2rem;"></ion-icon>
                    <span><strong>Canonical Status:</strong> Closed & Transmitted to Diocesan Archives on <?php echo h($existing_attestation['attested_date']); ?> by <em><?php echo h($existing_attestation['attested_by']); ?></em>.</span>
                </div>
                <span style="background: #10b981; color: #000; font-weight: 800; font-size: 0.7rem; padding: 2px 8px; border-radius: 6px; text-transform: uppercase;">
                    Transmitted
                </span>
            </div>
        <?php else: ?>
            <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 0.75rem 1.25rem; display: flex; align-items: center; justify-content: space-between; font-size: 0.85rem;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <ion-icon name="time-outline" style="color: #f59e0b; font-size: 1.2rem;"></ion-icon>
                    <span><strong>Canonical Status:</strong> Open Draft. Pending formal parochial attestation for submission to the Diocesan Archives.</span>
                </div>
                <span style="background: #f59e0b; color: #000; font-weight: 800; font-size: 0.7rem; padding: 2px 8px; border-radius: 6px; text-transform: uppercase;">
                    Draft
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- The Printable Canonical Document Wrapper -->
    <div class="document-wrapper">
        
        <!-- Canonical Header -->
        <div class="canonical-header">
            <img src="../assets/img/diocese_logo.png" alt="Diocese of Hwange Crest" class="diocese-seal-img" onerror="this.style.display='none'">
            <div style="font-size: 0.85rem; font-weight: 700; letter-spacing: 1.5px; color: #475569; text-transform: uppercase; margin-bottom: 2px;">
                Dioecesis Hvangensis &bull; Diocesan Chancery & Archives
            </div>
            <h1 class="canonical-title">Annual Parochial Register Return</h1>
            <div class="canonical-subtitle">Copia Authentica Librorum Parochialium ad Archivum Dioecesanum</div>
            <div class="canonical-statute">
                Pursuant to Can. 535 §4 C.I.C. &bull; Canonical Year <?php echo $selected_year; ?>
            </div>
        </div>

        <!-- Metadata Strip -->
        <div class="meta-strip">
            <div class="meta-strip-item">
                <label>Parish / Mission</label>
                <span><?php echo h($current_parish ? $current_parish['parish_name'] : 'Consolidated (All Diocesan Parishes)'); ?></span>
            </div>
            <div class="meta-strip-item">
                <label>Location / Deanery</label>
                <span><?php echo h($current_parish ? (($current_parish['deanery'] ?? '') . (!empty($current_parish['location']) ? ' - ' . $current_parish['location'] : '')) : 'Diocese of Hwange (All Deaneries)'); ?></span>
            </div>
            <div class="meta-strip-item">
                <label>Reporting Period</label>
                <span>1 Jan <?php echo $selected_year; ?> &ndash; 31 Dec <?php echo $selected_year; ?></span>
            </div>
            <div class="meta-strip-item">
                <label>Total Sacramental Acts</label>
                <span style="color: #1e3a8a;"><?php echo $grand_total; ?> Entries</span>
            </div>
        </div>

        <!-- Statistical Recap Matrix -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-num"><?php echo $bap_total; ?></div>
                <div class="stat-label">Baptisms (Can. 849)</div>
                <div class="stat-detail"><?php echo $bap_infant; ?> Infant &bull; <?php echo $bap_adult; ?> Adult &bull; <?php echo $bap_conditional; ?> Sub Cond.</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?php echo $conf_total; ?></div>
                <div class="stat-label">Confirmations (Can. 879)</div>
                <div class="stat-detail">Sealed with the Holy Spirit</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?php echo $mar_total; ?></div>
                <div class="stat-label">Marriages (Can. 1055)</div>
                <div class="stat-detail"><?php echo $mar_dispensations; ?> with Dispensations</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?php echo $bur_total; ?></div>
                <div class="stat-label">Burials (Can. 1176)</div>
                <div class="stat-detail">Christian Funerals Recorded</div>
            </div>
        </div>

        <!-- 1. BAPTISMS SECTION -->
        <?php if ($selected_type === 'all' || $selected_type === 'baptisms'): ?>
        <div style="margin-top: 2rem;">
            <div class="section-heading">
                <span>I. Liber Baptizatorum &bull; Baptismal Register</span>
                <span class="badge"><?php echo count($baptisms); ?> Inscriptions</span>
            </div>
            <?php if (empty($baptisms)): ?>
                <p style="font-size: 0.85rem; color: #64748b; font-style: italic; padding: 1rem 0;">No baptismal entries recorded in this parochial registry for year <?php echo $selected_year; ?>.</p>
            <?php else: ?>
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th style="width: 55px;">Entry</th>
                            <th style="width: 80px;">Baptism</th>
                            <th style="width: 80px;">Birth</th>
                            <th>Child's Full Name</th>
                            <th>Parents (Father & Mother)</th>
                            <th>Godparents / Christian Witness</th>
                            <th style="width: 100px;">Minister</th>
                            <th style="width: 140px;">Marginal Notations (Can. 535 §2)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($baptisms as $b): 
                            $childName = trim(($b['first_name'] ?? '') . ' ' . ($b['other_names'] ?? '') . ' ' . ($b['last_name'] ?? ''));
                            $subCond = !empty($b['is_conditional']) ? '<span style="color:#b45309; font-weight:700;">[Sub Conditione]</span> ' : '';
                        ?>
                        <tr>
                            <td><span class="entry-pill"><?php echo h($b['entry_number'] ?: '-'); ?></span></td>
                            <td><?php echo h($b['date_of_baptism'] ?: '-'); ?></td>
                            <td><?php echo h($b['dob'] ?: '-'); ?></td>
                            <td>
                                <strong><?php echo $subCond . h($childName); ?></strong>
                                <?php if (!empty($b['gender'])): ?>
                                    <span style="font-size: 0.65rem; color: #64748b; text-transform: uppercase;">(<?php echo substr($b['gender'], 0, 1); ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><small style="color:#64748b;">F:</small> <?php echo h($b['father_name'] ?: '-'); ?></div>
                                <div><small style="color:#64748b;">M:</small> <?php echo h($b['mother_name'] ?: '-'); ?><?php echo !empty($b['mother_maiden_name']) ? ' (' . h($b['mother_maiden_name']) . ')' : ''; ?></div>
                            </td>
                            <td>
                                <div><?php echo h($b['godparents'] ?: '-'); ?></div>
                                <?php if (!empty($b['christian_witness'])): ?>
                                    <div style="font-size: 0.65rem; color:#64748b;">Witness: <?php echo h($b['christian_witness']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo h($b['minister'] ?: '-'); ?></td>
                            <td style="font-size: 0.7rem; color: #475569;">
                                <?php echo nl2br(h($b['marginal_notations'] ?: 'None (Status Liber)')); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 2. CONFIRMATIONS SECTION -->
        <?php if ($selected_type === 'all' || $selected_type === 'confirmations'): ?>
        <div style="margin-top: 2rem;" class="<?php echo $selected_type === 'all' ? 'page-break' : ''; ?>">
            <div class="section-heading">
                <span>II. Liber Confirmatorum &bull; Confirmation Register</span>
                <span class="badge"><?php echo count($confirmations); ?> Inscriptions</span>
            </div>
            <?php if (empty($confirmations)): ?>
                <p style="font-size: 0.85rem; color: #64748b; font-style: italic; padding: 1rem 0;">No confirmation entries recorded in this parochial registry for year <?php echo $selected_year; ?>.</p>
            <?php else: ?>
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th style="width: 55px;">Entry</th>
                            <th style="width: 85px;">Confirmation</th>
                            <th>Confirmand's Full Name</th>
                            <th>Place & Date of Baptism</th>
                            <th>Confirmation Sponsor</th>
                            <th>Officiating Minister</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($confirmations as $c): 
                            $confName = trim(($c['first_name'] ?? '') . ' ' . ($c['other_names'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                        ?>
                        <tr>
                            <td><span class="entry-pill"><?php echo h($c['entry_number'] ?: '-'); ?></span></td>
                            <td><?php echo h($c['date_of_confirmation'] ?: '-'); ?></td>
                            <td><strong><?php echo h($confName); ?></strong></td>
                            <td>
                                <?php echo h($c['place_of_baptism'] ?: '-'); ?>
                                <?php if (!empty($c['date_of_baptism'])): ?>
                                    <span style="font-size: 0.7rem; color: #64748b;">(<?php echo h($c['date_of_baptism']); ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo h($c['sponsor'] ?: '-'); ?></td>
                            <td><?php echo h($c['minister'] ?: '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 3. MARRIAGES SECTION -->
        <?php if ($selected_type === 'all' || $selected_type === 'marriages'): ?>
        <div style="margin-top: 2rem;" class="<?php echo $selected_type === 'all' ? 'page-break' : ''; ?>">
            <div class="section-heading">
                <span>III. Liber Matrimoniorum &bull; Matrimonial Register</span>
                <span class="badge"><?php echo count($marriages); ?> Inscriptions</span>
            </div>
            <?php if (empty($marriages)): ?>
                <p style="font-size: 0.85rem; color: #64748b; font-style: italic; padding: 1rem 0;">No marriage entries recorded in this parochial registry for year <?php echo $selected_year; ?>.</p>
            <?php else: ?>
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th style="width: 55px;">Entry</th>
                            <th style="width: 85px;">Marriage</th>
                            <th>Groom & Parents</th>
                            <th>Bride & Parents</th>
                            <th>Canonical Witnesses</th>
                            <th>Minister</th>
                            <th style="width: 140px;">Dispensations / Protocol</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($marriages as $m): 
                            $gName = trim(($m['g_first'] ?? '') . ' ' . ($m['g_other'] ?? '') . ' ' . ($m['g_last'] ?? ''));
                            $bName = trim(($m['b_first'] ?? '') . ' ' . ($m['b_other'] ?? '') . ' ' . ($m['b_last'] ?? ''));
                        ?>
                        <tr>
                            <td><span class="entry-pill"><?php echo h($m['entry_number'] ?: '-'); ?></span></td>
                            <td><?php echo h($m['date_of_marriage'] ?: '-'); ?></td>
                            <td>
                                <strong><?php echo h($gName ?: '-'); ?></strong>
                                <?php if (!empty($m['g_father']) || !empty($m['g_mother'])): ?>
                                    <div style="font-size: 0.65rem; color:#64748b;">
                                        F: <?php echo h($m['g_father'] ?: '-'); ?> | M: <?php echo h($m['g_mother'] ?: '-'); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo h($bName ?: '-'); ?></strong>
                                <?php if (!empty($m['b_father']) || !empty($m['b_mother'])): ?>
                                    <div style="font-size: 0.65rem; color:#64748b;">
                                        F: <?php echo h($m['b_father'] ?: '-'); ?> | M: <?php echo h($m['b_mother'] ?: '-'); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?php echo h($m['witnesses_names'] ?: '-'); ?></div>
                            </td>
                            <td><?php echo h($m['officiant'] ?: '-'); ?></td>
                            <td style="font-size: 0.7rem;">
                                <?php if (!empty($m['dispensation_type'])): ?>
                                    <strong style="color: #b45309;"><?php echo h($m['dispensation_type']); ?></strong>
                                    <?php if (!empty($m['dispensation_protocol_number'])): ?>
                                        <div>Prot: <?php echo h($m['dispensation_protocol_number']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($m['dispensation_date'])): ?>
                                        <div style="color: #64748b;"><?php echo h($m['dispensation_date']); ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #64748b;">None (Forma Canonica)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 4. BURIALS SECTION -->
        <?php if ($selected_type === 'all' || $selected_type === 'deaths'): ?>
        <div style="margin-top: 2rem;">
            <div class="section-heading">
                <span>IV. Liber Defunctorum &bull; Register of Christian Burials</span>
                <span class="badge"><?php echo count($burials); ?> Inscriptions</span>
            </div>
            <?php if (empty($burials)): ?>
                <p style="font-size: 0.85rem; color: #64748b; font-style: italic; padding: 1rem 0;">No burial entries recorded in this parochial registry for year <?php echo $selected_year; ?>.</p>
            <?php else: ?>
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th style="width: 55px;">Entry</th>
                            <th style="width: 80px;">Death</th>
                            <th style="width: 80px;">Burial</th>
                            <th>Deceased Full Name</th>
                            <th>Place of Burial</th>
                            <th>Officiating Minister</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($burials as $d): 
                            $defName = trim(($d['first_name'] ?? '') . ' ' . ($d['other_names'] ?? '') . ' ' . ($d['last_name'] ?? ''));
                        ?>
                        <tr>
                            <td><span class="entry-pill"><?php echo h($d['entry_number'] ?: '-'); ?></span></td>
                            <td><?php echo h($d['date_of_death'] ?: '-'); ?></td>
                            <td><?php echo h($d['date_of_burial'] ?: '-'); ?></td>
                            <td><strong><?php echo h($defName ?: '-'); ?></strong></td>
                            <td><?php echo h($d['place_of_burial'] ?: '-'); ?></td>
                            <td><?php echo h($d['minister'] ?: '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- CANONICAL ATTESTATION & CURIAL DEPOSIT BLOCK -->
        <div class="attestation-box">
            <h4>Solemn Juridical Attestation & Archival Deposit</h4>
            
            <p class="attestation-text">
                &ldquo;In fidem praemissorum omnium et singulorum, ego infrascriptus Parochus / Administrator paroeciae 
                <strong><?php echo h($current_parish ? $current_parish['parish_name'] : 'Dioecesis Hvangensis'); ?></strong> 
                testor hoc exemplar fideliter et accurate concordare cum suo autographo in libris paroecialibus asservato pro anno canonico 
                <strong><?php echo $selected_year; ?></strong>, nullo addito, dempto vel mutato quod rei substantiam vel formam mutet, 
                ad normam Canonis 535 §4 Codicis Iuris Canonici ad Archivum Dioecesanum transmittendum.&rdquo;
            </p>
            <p style="font-size: 0.75rem; color: #64748b; margin-top: -1.25rem; margin-bottom: 2rem; font-style: italic;">
                (I, the undersigned Parish Priest / Administrator, hereby solemnly certify that the sacramental inscriptions herein contained faithfully and accurately correspond with the original entries preserved in the Parochial Registers for the canonical year <?php echo $selected_year; ?>, and are transmitted to the Diocesan Curial Archives in compliance with Canon 535 §4.)
            </p>

            <div class="attestation-signatures">
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-caption">Parish Priest / Rector</div>
                    <div class="sig-sub">
                        <?php echo h($existing_attestation['attested_by'] ?? $user_full_name); ?><br>
                        Date: <?php echo h($existing_attestation['attested_date'] ?? date('d/m/Y')); ?>
                    </div>
                </div>

                <div class="sig-block">
                    <div class="seal-box">
                        Locus Sigilli<br>[Parish Seal]
                    </div>
                    <div class="sig-caption">Parish Seal</div>
                    <div class="sig-sub">Impressum Paroeciae</div>
                </div>

                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-caption">Chancellor / Diocesan Archivist</div>
                    <div class="sig-sub">
                        Received & Deposited in Archives<br>
                        Curia Dioecesana Hvangensis
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Attestation Modal (No-Print) -->
    <div class="modal-overlay" id="attestModal">
        <div class="modal-card">
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: white; margin-top: 0; display: flex; align-items: center; gap: 8px;">
                <ion-icon name="ribbon-outline" style="color: #f59e0b;"></ion-icon> Close & Attest Volume (Can. 535 §4)
            </h3>
            <p style="font-size: 0.85rem; color: #94a3b8; line-height: 1.5;">
                By attesting this return, you officially close the sacramental ledger volume for Year <strong><?php echo $selected_year; ?></strong> and transmit the authentic duplicate copy to the Diocesan Archives.
            </p>

            <form method="POST">
                <input type="hidden" name="action" value="attest_volume">
                <input type="hidden" name="parish_id" value="<?php echo $selected_parish_id; ?>">
                <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                <input type="hidden" name="register_type" value="<?php echo $selected_type; ?>">
                <input type="hidden" name="total_count" value="<?php echo $grand_total; ?>">

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 4px; font-weight: 600;">
                        Attesting Priest / Minister *
                    </label>
                    <input type="text" name="attested_by" value="<?php echo h($user_full_name); ?>" required class="control-select" style="width: 100%; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 4px; font-weight: 600;">
                        Attestation Date *
                    </label>
                    <input type="date" name="attested_date" value="<?php echo date('Y-m-d'); ?>" required class="control-select" style="width: 100%; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 4px; font-weight: 600;">
                        Archival Observations / Notes (Optional)
                    </label>
                    <textarea name="notes" rows="2" class="control-select" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Volume audited and checked against baptism counterfoils."></textarea>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="document.getElementById('attestModal').style.display = 'none'" class="btn-action btn-back">
                        Cancel
                    </button>
                    <button type="submit" class="btn-action btn-attest">
                        Solemnly Attest & Transmit
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
