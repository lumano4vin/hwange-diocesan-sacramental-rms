<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * CSV Report Export Utility
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page (Admin Only)
require_role('admin');

$year = $_GET['year'] ?? date('Y');
$type = $_GET['type'] ?? 'annual';

if ($type === 'annual') {
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Hwange_Diocese_Annual_Report_' . $year . '.csv');

    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    // Column headers for Vatican/Diocesan requirements
    fputcsv($output, ['Hwange Diocese Sacramental Report', 'Year: ' . $year]);
    fputcsv($output, []);
    fputcsv($output, ['Deanery', 'Parish', 'Baptisms', 'Confirmations', 'Marriages', 'Ordinations', 'Deaths']);

    // Fetch Parishes
    $parishes = db_fetchAll("SELECT parish_id, parish_name, deanery FROM parishes ORDER BY deanery ASC, parish_name ASC");

    foreach ($parishes as $p) {
        $pid = $p['parish_id'];
        
        $bap = db_fetch("SELECT COUNT(*) as c FROM baptisms WHERE parish_id = ? AND YEAR(date_of_baptism) = ?", [$pid, $year])['c'];
        $mrg = db_fetch("SELECT COUNT(*) as c FROM marriages WHERE parish_id = ? AND YEAR(date_of_marriage) = ?", [$pid, $year])['c'];
        $cnf = db_fetch("SELECT COUNT(*) as c FROM confirmations WHERE parish_id = ? AND YEAR(date_of_confirmation) = ?", [$pid, $year])['c'];
        $ord = db_fetch("SELECT COUNT(*) as c FROM ordinations_professions WHERE parish_id = ? AND YEAR(event_date) = ?", [$pid, $year])['c'];
        $dth = db_fetch("SELECT COUNT(*) as c FROM deaths WHERE parish_id = ? AND YEAR(date_of_death) = ?", [$pid, $year])['c'];

        fputcsv($output, [
            $p['deanery'],
            $p['parish_name'],
            $bap,
            $cnf,
            $mrg,
            $ord,
            $dth
        ]);
    }

    // Add Totals Row
    $total_bap = db_fetch("SELECT COUNT(*) as c FROM baptisms WHERE YEAR(date_of_baptism) = ?", [$year])['c'];
    $total_mrg = db_fetch("SELECT COUNT(*) as c FROM marriages WHERE YEAR(date_of_marriage) = ?", [$year])['c'];
    $total_cnf = db_fetch("SELECT COUNT(*) as c FROM confirmations WHERE YEAR(date_of_confirmation) = ?", [$year])['c'];
    $total_ord = db_fetch("SELECT COUNT(*) as c FROM ordinations_professions WHERE YEAR(event_date) = ?", [$year])['c'];
    $total_dth = db_fetch("SELECT COUNT(*) as c FROM deaths WHERE YEAR(date_of_death) = ?", [$year])['c'];

    fputcsv($output, []);
    fputcsv($output, [
        'DIOCESAN TOTALS',
        '',
        $total_bap,
        $total_cnf,
        $total_mrg,
        $total_ord,
        $total_dth
    ]);

    fclose($output);
}
?>
