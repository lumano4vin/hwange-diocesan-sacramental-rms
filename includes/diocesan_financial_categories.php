<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Standard Diocesan Financial Categories (23-Line Double-Entry Cashbook)
 * Aligned with the Diocesan Chancery Administration Standard
 */

function get_diocesan_income_categories() {
    return [
        1 => ['code' => 'diocese', 'name' => 'Diocese', 'is_assessable' => false],
        2 => ['code' => 'bank_interest', 'name' => 'Bank interest', 'is_assessable' => false],
        3 => ['code' => 'pledges', 'name' => 'Tithe/Unsupo/Mutulo', 'is_assessable' => true],
        4 => ['code' => 'collection', 'name' => 'Sunday Collection', 'is_assessable' => true],
        5 => ['code' => 'donation_abroad', 'name' => 'Donation from abroad', 'is_assessable' => false],
        6 => ['code' => 'local_donation', 'name' => 'Local Donation', 'is_assessable' => false],
        7 => ['code' => 'farm_garden_inc', 'name' => 'Farm/Garden', 'is_assessable' => false],
        8 => ['code' => 'income_gen_proj_inc', 'name' => 'Income Gen. Project', 'is_assessable' => false],
        9 => ['code' => 'catechists_fund', 'name' => 'Catechists fund', 'is_assessable' => false],
        10 => ['code' => 'loan_taken', 'name' => 'Loan taken', 'is_assessable' => false],
        11 => ['code' => 'loan_returned', 'name' => 'Loan returned', 'is_assessable' => false],
        12 => ['code' => 'refunds', 'name' => 'Refunds (electricity etc.)', 'is_assessable' => false],
        13 => ['code' => 'religious_congregation', 'name' => 'Religious Congregation', 'is_assessable' => false],
        14 => ['code' => 'sale_of_goods', 'name' => 'Sale of Goods (Repository)', 'is_assessable' => false],
        15 => ['code' => 'rent', 'name' => 'Rent', 'is_assessable' => false],
        16 => ['code' => 'fuel_from_communities', 'name' => 'Fuel from Communities', 'is_assessable' => false],
        17 => ['code' => 'holy_land_inc', 'name' => 'Holy Land', 'is_assessable' => false],
        18 => ['code' => 'charity', 'name' => 'Charity', 'is_assessable' => false],
        19 => ['code' => 'special_collection', 'name' => 'Special Collection', 'is_assessable' => false],
        20 => ['code' => 'lenten_collection', 'name' => 'Lenten Collection', 'is_assessable' => false],
        21 => ['code' => 'special_project', 'name' => 'Special Project', 'is_assessable' => false],
        22 => ['code' => 'peters_penny', 'name' => "Peter's Penny", 'is_assessable' => false],
        23 => ['code' => 'marian_shrine', 'name' => 'Marian Shrine', 'is_assessable' => false],
    ];
}

function get_diocesan_expenditure_categories() {
    return [
        1 => ['code' => 'bank_charges', 'name' => 'Bank charges', 'is_auto_levy' => false],
        2 => ['code' => 'building_repairs', 'name' => 'Building (repairs)', 'is_auto_levy' => false],
        3 => ['code' => 'construction', 'name' => 'Construction', 'is_auto_levy' => false],
        4 => ['code' => 'diocesan_levy', 'name' => 'Diocesan Levy (10%)', 'is_auto_levy' => true],
        5 => ['code' => 'catechists_exp', 'name' => 'Catechists', 'is_auto_levy' => false],
        6 => ['code' => 'farm_garden_exp', 'name' => 'Farm/Garden', 'is_auto_levy' => false],
        7 => ['code' => 'fuel_travel', 'name' => 'Fuel/travel', 'is_auto_levy' => false],
        8 => ['code' => 'toll_fee', 'name' => 'Toll Fee', 'is_auto_levy' => false],
        9 => ['code' => 'general_maintenance', 'name' => 'General Maintenance', 'is_auto_levy' => false],
        10 => ['code' => 'household_needs', 'name' => 'Household Needs', 'is_auto_levy' => false],
        11 => ['code' => 'income_gen_proj_exp', 'name' => 'Income Generating Projects', 'is_auto_levy' => false],
        12 => ['code' => 'staff_food_health', 'name' => 'Staff (Food/Health)', 'is_auto_levy' => false],
        13 => ['code' => 'nssa', 'name' => 'NSSA', 'is_auto_levy' => false],
        14 => ['code' => 'pastoral_work', 'name' => 'Pastoral work: Church Expenses', 'is_auto_levy' => false],
        15 => ['code' => 'phone_postage', 'name' => 'Phone and Postage', 'is_auto_levy' => false],
        16 => ['code' => 'priests_allowance', 'name' => 'Priests Allowance', 'is_auto_levy' => false],
        17 => ['code' => 'salaries_wages', 'name' => 'Salaries and Wages', 'is_auto_levy' => false],
        18 => ['code' => 'holy_land_exp', 'name' => 'Holy Land', 'is_auto_levy' => false],
        19 => ['code' => 'mca', 'name' => 'MCA', 'is_auto_levy' => false],
        20 => ['code' => 'repository_exp', 'name' => 'Repository', 'is_auto_levy' => false],
        21 => ['code' => 'parish_meetings', 'name' => 'Parish Meetings', 'is_auto_levy' => false],
        22 => ['code' => 'internet_wifi', 'name' => 'Internet/Wifi', 'is_auto_levy' => false],
        23 => ['code' => 'vehicle_service_repair', 'name' => 'Vehicle Service or Repair', 'is_auto_levy' => false],
    ];
}

/**
 * The 8 Mandatory Annual Special Diocesan Collections (Occur once a year)
 * 100% of these collections are remitted to the Diocesan Chancery / Holy See.
 * They are NOT subject to the 10% levy and are separate from Parish/Mission collections.
 */
function get_annual_diocesan_special_collections() {
    return [
        'mission_sunday' => [
            'key' => 'mission_sunday',
            'title' => 'Mission Sunday (World Mission Sunday)',
            'season' => 'October',
            'canonical_target' => 'Pontifical Mission Societies (Propaganda Fide)',
            'description' => 'Universal collection supporting mission churches worldwide.'
        ],
        'lenten_collection' => [
            'key' => 'lenten_collection',
            'title' => 'Lenten Collection (Lenten Self-Denial / Sacrifice)',
            'season' => 'Lent (Ash Wednesday to Holy Week)',
            'canonical_target' => 'Diocesan Lenten Appeal',
            'description' => 'Solidarity fund for poor communities and diocesan social works.'
        ],
        'holy_land' => [
            'key' => 'holy_land',
            'title' => 'Holy Land Collection (Good Friday)',
            'season' => 'Good Friday',
            'canonical_target' => 'Custody of the Holy Land (Pro Terra Sancta)',
            'description' => 'Pontifical collection for the preservation of Christian holy sites and communities in the Middle East.'
        ],
        'social_communications' => [
            'key' => 'social_communications',
            'title' => 'Social Communications Collection',
            'season' => 'Ascension Sunday (World Communications Day)',
            'canonical_target' => 'Diocesan Communications Secretariat',
            'description' => 'Support for Catholic radio, publications, and media evangelisation.'
        ],
        'missionary_childhood' => [
            'key' => 'missionary_childhood',
            'title' => 'Missionary Childhood Collections (MCA)',
            'season' => 'Epiphany / National Children\'s Day',
            'canonical_target' => 'Pontifical Society of Missionary Childhood',
            'description' => 'Children helping children initiatives across mission territories.'
        ],
        'peters_pence' => [
            'key' => 'peters_pence',
            'title' => "Peter's Pence (Denarius Sancti Petri)",
            'season' => 'June (Feast of Sts. Peter & Paul)',
            'canonical_target' => 'Holy See (Apostolic Works of the Holy Father)',
            'description' => 'Universal collection for the Pope\'s charitable initiatives worldwide.'
        ],
        'seminary_collection' => [
            'key' => 'seminary_collection',
            'title' => 'Seminary Collection (Seminary Fund)',
            'season' => 'Good Shepherd Sunday (4th Sunday of Easter)',
            'canonical_target' => 'Diocesan Major Seminarians Formation Fund',
            'description' => 'Upkeep and theological/philosophical studies for diocesan seminarians.'
        ],
        'caritas' => [
            'key' => 'caritas',
            'title' => 'Caritas Collection',
            'season' => 'Designated Charity Sunday / Advent',
            'canonical_target' => 'Caritas Hwange (Catholic Development Commission)',
            'description' => 'Disaster relief, food security, and humanitarian assistance in the Diocese.'
        ],
    ];
}

/**
 * Fetch Custom Financial Categories for a parish (plus any global diocesan ones)
 */
function get_parish_custom_categories($parish_id = null, $type = null) {
    global $pdo;
    if (!$pdo) return [];

    $sql = "SELECT * FROM parish_financial_custom_categories WHERE is_active = 1";
    $params = [];

    if ($parish_id !== null) {
        $sql .= " AND (parish_id = ? OR parish_id IS NULL)";
        $params[] = (int)$parish_id;
    }

    if ($type !== null) {
        $sql .= " AND category_type = ?";
        $params[] = strtolower($type);
    }

    $sql .= " ORDER BY category_type ASC, category_name ASC";

    try {
        return db_fetchAll($sql, $params);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get structured categories dictionary for Daily Ledger entry dropdown
 */
function get_all_daily_ledger_categories($parish_id = null) {
    $inc_cats = get_diocesan_income_categories();
    $exp_cats = get_diocesan_expenditure_categories();
    $spec_cats = get_annual_diocesan_special_collections();
    $custom_cats = get_parish_custom_categories($parish_id);

    $grouped = [
        'income' => [],
        'expenditure' => [],
        'special' => []
    ];

    // 1. Standard 23 Income Lines
    foreach ($inc_cats as $num => $cat) {
        $grouped['income'][] = [
            'code' => 'inc_' . $num,
            'line_num' => $num,
            'name' => '#' . $num . ' ' . $cat['name'],
            'raw_name' => $cat['name'],
            'is_assessable' => $cat['is_assessable'] ?? false,
            'is_custom' => false
        ];
    }

    // Custom Incomes
    foreach ($custom_cats as $cc) {
        if ($cc['category_type'] === 'income') {
            $grouped['income'][] = [
                'code' => 'custom_inc_' . $cc['category_id'],
                'line_num' => null,
                'name' => '[Custom] ' . $cc['category_name'],
                'raw_name' => $cc['category_name'],
                'is_assessable' => (bool)$cc['is_assessable'],
                'is_custom' => true,
                'category_id' => $cc['category_id']
            ];
        }
    }

    // 2. Standard 23 Expenditure Lines
    foreach ($exp_cats as $num => $cat) {
        $grouped['expenditure'][] = [
            'code' => 'exp_' . $num,
            'line_num' => $num,
            'name' => '#' . $num . ' ' . $cat['name'],
            'raw_name' => $cat['name'],
            'is_auto_levy' => $cat['is_auto_levy'] ?? false,
            'is_custom' => false
        ];
    }

    // Custom Expenditures
    foreach ($custom_cats as $cc) {
        if ($cc['category_type'] === 'expenditure') {
            $grouped['expenditure'][] = [
                'code' => 'custom_exp_' . $cc['category_id'],
                'line_num' => null,
                'name' => '[Custom] ' . $cc['category_name'],
                'raw_name' => $cc['category_name'],
                'is_custom' => true,
                'category_id' => $cc['category_id']
            ];
        }
    }

    // 3. 8 Annual Special Diocesan Collections
    foreach ($spec_cats as $s_key => $s_meta) {
        $grouped['special'][] = [
            'code' => 'spec_' . $s_key,
            'key' => $s_key,
            'name' => '[Diocesan Appeal] ' . $s_meta['title'],
            'season' => $s_meta['season'],
            'raw_name' => $s_meta['title']
        ];
    }

    return $grouped;
}

/**
 * Sync & Aggregate Daily Ledger Transactions into Monthly 23-line Cashbook
 */
function sync_daily_ledger_to_monthly_return($parish_id, $year, $month) {
    $year_sql = db_year_sql('entry_date');
    $month_sql = db_month_sql('entry_date');

    $entries = db_fetchAll("
        SELECT * FROM parish_daily_ledger
        WHERE parish_id = ? AND {$year_sql} = ? AND {$month_sql} = ?
        ORDER BY entry_date ASC, entry_id ASC
    ", [$parish_id, $year, $month]);

    $result = [
        'income' => array_fill(1, 23, 0.0),
        'expenditure' => array_fill(1, 23, 0.0),
        'special_collections' => [],
        'custom_income' => [],
        'custom_expenditure' => [],
        'cash_at_hand_receipts' => 0.0,
        'cash_at_hand_payments' => 0.0,
        'cash_in_bank_receipts' => 0.0,
        'cash_in_bank_payments' => 0.0,
        'total_income' => 0.0,
        'total_expenditure' => 0.0,
        'entry_count' => count($entries)
    ];

    foreach ($entries as $e) {
        $amt = (float)$e['amount'];
        $type = $e['entry_type']; // 'income' or 'expenditure'
        $code = $e['category_code'];
        $acct = $e['account_type'] ?: 'cash_at_hand';

        // Track cash and bank inflows / outflows
        if ($type === 'income') {
            if ($acct === 'cash_in_bank') {
                $result['cash_in_bank_receipts'] += $amt;
            } else {
                $result['cash_at_hand_receipts'] += $amt;
            }
            $result['total_income'] += $amt;
        } else {
            if ($acct === 'cash_in_bank') {
                $result['cash_in_bank_payments'] += $amt;
            } else {
                $result['cash_at_hand_payments'] += $amt;
            }
            $result['total_expenditure'] += $amt;
        }

        // Map Category Codes
        if (strpos($code, 'inc_') === 0) {
            $line_num = (int)substr($code, 4);
            if ($line_num >= 1 && $line_num <= 23) {
                $result['income'][$line_num] += $amt;
            }
        } elseif (strpos($code, 'exp_') === 0) {
            $line_num = (int)substr($code, 4);
            if ($line_num >= 1 && $line_num <= 23) {
                $result['expenditure'][$line_num] += $amt;
            }
        } elseif (strpos($code, 'spec_') === 0) {
            $spec_key = substr($code, 5);
            if (!isset($result['special_collections'][$spec_key])) {
                $result['special_collections'][$spec_key] = 0.0;
            }
            $result['special_collections'][$spec_key] += $amt;

            // Also mirror standard lines if applicable (Holy Land, MCA, Mission Sunday, Lenten, Peters Pence)
            if ($spec_key === 'holy_land') {
                $result['income'][17] += $amt;
                $result['expenditure'][18] += $amt;
            } elseif ($spec_key === 'lenten_collection') {
                $result['income'][20] += $amt;
            } elseif ($spec_key === 'peters_pence') {
                $result['income'][22] += $amt;
            } elseif ($spec_key === 'missionary_childhood') {
                $result['expenditure'][19] += $amt;
            } elseif ($spec_key === 'mission_sunday') {
                $result['expenditure'][21] += $amt;
            }
        } elseif (strpos($code, 'custom_inc_') === 0) {
            $c_id = (int)substr($code, 11);
            if (!isset($result['custom_income'][$c_id])) {
                $result['custom_income'][$c_id] = 0.0;
            }
            $result['custom_income'][$c_id] += $amt;
        } elseif (strpos($code, 'custom_exp_') === 0) {
            $c_id = (int)substr($code, 11);
            if (!isset($result['custom_expenditure'][$c_id])) {
                $result['custom_expenditure'][$c_id] = 0.0;
            }
            $result['custom_expenditure'][$c_id] += $amt;
        }
    }

    // Calculate 10% Diocesan Levy on Line 3 (Tithe/Unsupo/Mutulo) + Line 4 (Sunday Collection)
    $tithe = $result['income'][3];
    $sunday_coll = $result['income'][4];
    $assessable_base = $tithe + $sunday_coll;
    $result['calculated_levy'] = $assessable_base * 0.10;
    $result['expenditure'][4] = $result['calculated_levy'];

    $result['tithe'] = $tithe;
    $result['sunday_collection'] = $sunday_coll;
    $result['assessable_base'] = $assessable_base;
    $result['net_operating'] = $result['total_income'] - $result['total_expenditure'];

    return $result;
}

