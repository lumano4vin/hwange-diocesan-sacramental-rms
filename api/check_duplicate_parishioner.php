<?php
/**
 * Hwange Diocesan RMS - Duplicate Parishioner Checker API
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Only allow authenticated users
if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

$first_name = trim(upper($_GET['first_name'] ?? ''));
$last_name = trim(upper($_GET['last_name'] ?? ''));
$dob = trim($_GET['dob'] ?? '');

if (empty($first_name) || empty($last_name)) {
    echo json_encode(['duplicates' => []]);
    exit;
}

$pdo = getDB();

// Find potential duplicates matching starting letters to optimize query
$f_start = substr($first_name, 0, 3);
$l_start = substr($last_name, 0, 3);

$sql = "
    SELECT p.person_id, p.first_name, p.other_names, p.last_name, p.dob, p.gender, pa.parish_name, p.status 
    FROM parishioners p
    LEFT JOIN parishes pa ON p.current_parish_id = pa.parish_id
    WHERE p.last_name LIKE ? OR p.first_name LIKE ?
";
$params = ["{$l_start}%", "{$f_start}%"];
$results = db_fetchAll($sql, $params);

$duplicates = [];
$found_ids = [];

foreach ($results as $p) {
    $db_first = upper($p['first_name']);
    $db_last = upper($p['last_name']);
    $db_other = upper($p['other_names'] ?? '');

    // Exact Match
    if ($db_first === $first_name && $db_last === $last_name) {
        if (!in_array($p['person_id'], $found_ids)) {
            $p['match_type'] = 'Exact Match';
            $duplicates[] = $p;
            $found_ids[] = $p['person_id'];
        }
        continue;
    }

    // Similarity Check (Soundex equivalent / typo detection)
    $sim_first = 0; $sim_last = 0; $sim_other = 0;
    similar_text($first_name, $db_first, $sim_first);
    similar_text($last_name, $db_last, $sim_last);
    
    if ($db_other) {
        similar_text($first_name, $db_other, $sim_other);
    }

    // Threshold of 75% similarity for both first and last names
    if (($sim_first > 75 && $sim_last > 75) || ($sim_other > 75 && $sim_last > 75)) {
        if (!in_array($p['person_id'], $found_ids)) {
            $p['match_type'] = 'Similar Name';
            $duplicates[] = $p;
            $found_ids[] = $p['person_id'];
        }
    }
}

// Sort: Exact matches first, then Similar
usort($duplicates, function($a, $b) {
    if ($a['match_type'] == 'Exact Match' && $b['match_type'] != 'Exact Match') return -1;
    if ($a['match_type'] != 'Exact Match' && $b['match_type'] == 'Exact Match') return 1;
    return 0;
});

echo json_encode(['duplicates' => array_slice($duplicates, 0, 10)]); // Max 10 results
exit;
