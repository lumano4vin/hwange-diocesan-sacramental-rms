<?php
/**
 * Hwange Diocese Records Management System
 * Export Vault Table Data to CSV
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user_role = strtolower($_SESSION['role'] ?? '');
$is_admin = ($user_role === 'admin' || $user_role === 'chancellor');
$user_parish_id = (int)($_SESSION['parish_id'] ?? 0);

$table = $_GET['table'] ?? '';
$allowed_tables = ['baptisms', 'marriages', 'confirmations', 'parishioners', 'deaths', 'catechumens', 'parish_daily_ledger', 'parish_financial_submissions', 'parish_assets', 'parish_outstations', 'mass_intentions'];

if (!in_array($table, $allowed_tables)) {
    die("Invalid table specified.");
}

// Non-admins can only export if assigned to a parish
if (!$is_admin && $user_parish_id <= 0) {
    die("Access denied. No parish assignment found.");
}

// Ensure output is clean
ob_clean();
header('Content-Type: text/csv; charset=utf-8');
$prefix_map = [
    'parish_daily_ledger' => 'Daily_Ledger',
    'parish_financial_submissions' => 'Monthly_Cashbook',
    'parish_assets' => 'Temporal_Goods_Assets',
    'parish_outstations' => 'Outstations_MassCentres',
    'mass_intentions' => 'Mass_Intentions_Book'
];
$filename_prefix = $prefix_map[$table] ?? ucfirst($table);
header('Content-Disposition: attachment; filename=Hwange_' . $filename_prefix . '_Export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Base query logic
$sql = "";
$params = [];

if ($table === 'parish_daily_ledger') {
    if ($is_admin) {
        $sql = "SELECT l.entry_id, pr.parish_name, l.entry_date, l.entry_type, l.category_name, l.description, l.amount, l.currency, l.payment_method, l.account_type, l.reference_number, u.full_name as recorded_by, l.created_at
                FROM parish_daily_ledger l
                LEFT JOIN parishes pr ON l.parish_id = pr.parish_id
                LEFT JOIN users u ON l.recorded_by = u.user_id
                ORDER BY l.entry_date DESC, l.entry_id DESC";
    } else {
        $sql = "SELECT l.entry_id, pr.parish_name, l.entry_date, l.entry_type, l.category_name, l.description, l.amount, l.currency, l.payment_method, l.account_type, l.reference_number, u.full_name as recorded_by, l.created_at
                FROM parish_daily_ledger l
                LEFT JOIN parishes pr ON l.parish_id = pr.parish_id
                LEFT JOIN users u ON l.recorded_by = u.user_id
                WHERE l.parish_id = ?
                ORDER BY l.entry_date DESC, l.entry_id DESC";
        $params[] = $user_parish_id;
    }
} elseif ($table === 'parish_financial_submissions') {
    if ($is_admin) {
        $sql = "SELECT s.submission_id, pr.parish_name, s.fiscal_year, s.fiscal_month, s.total_collections as sunday_collection, s.tithe, s.calculated_levy, s.total_income, s.total_expenditure, s.opening_balance, s.closing_balance, s.cash_at_hand, s.cash_in_bank, s.savings, s.investments, s.currency, s.status, u.full_name as submitted_by, s.created_at
                FROM parish_financial_submissions s
                LEFT JOIN parishes pr ON s.parish_id = pr.parish_id
                LEFT JOIN users u ON s.submitted_by = u.user_id
                ORDER BY s.fiscal_year DESC, s.fiscal_month DESC";
    } else {
        $sql = "SELECT s.submission_id, pr.parish_name, s.fiscal_year, s.fiscal_month, s.total_collections as sunday_collection, s.tithe, s.calculated_levy, s.total_income, s.total_expenditure, s.opening_balance, s.closing_balance, s.cash_at_hand, s.cash_in_bank, s.savings, s.investments, s.currency, s.status, u.full_name as submitted_by, s.created_at
                FROM parish_financial_submissions s
                LEFT JOIN parishes pr ON s.parish_id = pr.parish_id
                LEFT JOIN users u ON s.submitted_by = u.user_id
                WHERE s.parish_id = ?
                ORDER BY s.fiscal_year DESC, s.fiscal_month DESC";
        $params[] = $user_parish_id;
    }
} elseif ($table === 'parish_assets') {
    if ($is_admin) {
        $sql = "SELECT a.id, pr.parish_name, a.item_name, a.category, a.serial_or_reg_number, a.location_stored, a.quantity, a.acquisition_date, a.estimated_value, a.currency, a.current_status, a.is_sacred, a.description, a.created_at
                FROM parish_assets a
                LEFT JOIN parishes pr ON a.parish_id = pr.parish_id
                ORDER BY pr.parish_name ASC, a.category ASC, a.item_name ASC";
    } else {
        $sql = "SELECT a.id, pr.parish_name, a.item_name, a.category, a.serial_or_reg_number, a.location_stored, a.quantity, a.acquisition_date, a.estimated_value, a.currency, a.current_status, a.is_sacred, a.description, a.created_at
                FROM parish_assets a
                LEFT JOIN parishes pr ON a.parish_id = pr.parish_id
                WHERE a.parish_id = ?
                ORDER BY a.category ASC, a.item_name ASC";
        $params[] = $user_parish_id;
    }
} elseif ($table === 'parish_outstations') {
    if ($is_admin) {
        $sql = "SELECT o.outstation_id, pr.parish_name, o.outstation_name, o.patron_saint, o.distance_km, o.catechist_name, o.catechist_phone, o.chairperson_name, o.chairperson_phone, o.mass_schedule, o.active_families_count, o.has_church_building, o.notes, o.created_at
                FROM parish_outstations o
                LEFT JOIN parishes pr ON o.parish_id = pr.parish_id
                ORDER BY pr.parish_name ASC, o.outstation_name ASC";
    } else {
        $sql = "SELECT o.outstation_id, pr.parish_name, o.outstation_name, o.patron_saint, o.distance_km, o.catechist_name, o.catechist_phone, o.chairperson_name, o.chairperson_phone, o.mass_schedule, o.active_families_count, o.has_church_building, o.notes, o.created_at
                FROM parish_outstations o
                LEFT JOIN parishes pr ON o.parish_id = pr.parish_id
                WHERE o.parish_id = ?
                ORDER BY o.outstation_name ASC";
        $params[] = $user_parish_id;
    }
} elseif ($table === 'mass_intentions') {
    if ($is_admin) {
        $sql = "SELECT m.intention_id, pr.parish_name, m.donor_name, m.donor_phone, m.intention_text, m.intention_type, m.stipend_amount, m.currency, m.requested_date, m.celebrated_date, u.full_name as celebrant, m.status, m.notes, m.created_at
                FROM mass_intentions m
                LEFT JOIN parishes pr ON m.parish_id = pr.parish_id
                LEFT JOIN users u ON m.assigned_priest_id = u.user_id
                ORDER BY m.requested_date DESC, m.intention_id DESC";
    } else {
        $sql = "SELECT m.intention_id, pr.parish_name, m.donor_name, m.donor_phone, m.intention_text, m.intention_type, m.stipend_amount, m.currency, m.requested_date, m.celebrated_date, u.full_name as celebrant, m.status, m.notes, m.created_at
                FROM mass_intentions m
                LEFT JOIN parishes pr ON m.parish_id = pr.parish_id
                LEFT JOIN users u ON m.assigned_priest_id = u.user_id
                WHERE m.parish_id = ?
                ORDER BY m.requested_date DESC, m.intention_id DESC";
        $params[] = $user_parish_id;
    }
} elseif ($table === 'baptisms') {
    if ($is_admin) {
        $sql = "SELECT b.*, p.first_name, p.last_name, p.dob, p.father_name, p.mother_name, pr.parish_name 
                FROM baptisms b 
                JOIN parishioners p ON b.person_id = p.person_id 
                JOIN parishes pr ON b.parish_id = pr.parish_id";
    } else {
        $sql = "SELECT b.*, p.first_name, p.last_name, p.dob, p.father_name, p.mother_name, pr.parish_name 
                FROM baptisms b 
                JOIN parishioners p ON b.person_id = p.person_id 
                JOIN parishes pr ON b.parish_id = pr.parish_id
                WHERE b.parish_id = ?";
        $params[] = $user_parish_id;
    }
} elseif ($table === 'marriages') {
    if ($is_admin) {
        $sql = "SELECT m.*, g.first_name as groom_first, g.last_name as groom_last, br.first_name as bride_first, br.last_name as bride_last, pr.parish_name 
                FROM marriages m 
                JOIN parishioners g ON m.groom_person_id = g.person_id 
                JOIN parishioners br ON m.bride_person_id = br.person_id 
                JOIN parishes pr ON m.parish_id = pr.parish_id";
    } else {
        $sql = "SELECT m.*, g.first_name as groom_first, g.last_name as groom_last, br.first_name as bride_first, br.last_name as bride_last, pr.parish_name 
                FROM marriages m 
                JOIN parishioners g ON m.groom_person_id = g.person_id 
                JOIN parishioners br ON m.bride_person_id = br.person_id 
                JOIN parishes pr ON m.parish_id = pr.parish_id
                WHERE m.parish_id = ?";
        $params[] = $user_parish_id;
    }
} elseif ($table === 'parishioners') {
    if ($is_admin) {
        $sql = "SELECT p.*, pr.parish_name FROM parishioners p LEFT JOIN parishes pr ON p.current_parish_id = pr.parish_id";
    } else {
        $sql = "SELECT p.*, pr.parish_name FROM parishioners p LEFT JOIN parishes pr ON p.current_parish_id = pr.parish_id WHERE p.current_parish_id = ?";
        $params[] = $user_parish_id;
    }
} else {
    if ($is_admin) {
        $sql = "SELECT t.*, p.first_name, p.last_name, pr.parish_name 
                FROM $table t 
                JOIN parishioners p ON t.person_id = p.person_id 
                JOIN parishes pr ON t.parish_id = pr.parish_id";
    } else {
        $sql = "SELECT t.*, p.first_name, p.last_name, pr.parish_name 
                FROM $table t 
                JOIN parishioners p ON t.person_id = p.person_id 
                JOIN parishes pr ON t.parish_id = pr.parish_id
                WHERE t.parish_id = ?";
        $params[] = $user_parish_id;
    }
}

$stmt = getDB()->prepare($sql);
$stmt->execute($params);

$firstRow = true;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Exclude sensitive internal data
    unset($row['verification_hash'], $row['guid']);
    
    if ($firstRow) {
        fputcsv($output, array_keys($row));
        $firstRow = false;
    }
    fputcsv($output, array_values($row));
}

fclose($output);
exit;
