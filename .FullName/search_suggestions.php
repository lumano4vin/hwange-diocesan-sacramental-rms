<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Unified Autocomplete Backend
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all'; 
$results = [];

if (strlen($q) >= 2) {
    $searchTerm = "%$q%";

    // 1. Parishioners
    if ($type === 'all' || $type === 'person') {
        $terms = array_filter(explode(' ', $q));
        $conds = [];
        $p_params = [];
        foreach ($terms as $term) {
            $conds[] = "(p.first_name || ' ' || IFNULL(p.other_names, '') || ' ' || p.last_name) LIKE ?";
            $p_params[] = "%$term%";
        }
        if (!empty($conds)) {
            $sql = "SELECT p.person_id, p.first_name, p.last_name, pr.parish_name 
                    FROM parishioners p
                    LEFT JOIN parishes pr ON p.current_parish_id = pr.parish_id
                    WHERE " . implode(" AND ", $conds) . "
                    LIMIT 5";
            $data = db_fetchAll($sql, $p_params);
            foreach ($data as $r) {
                $results[] = [
                    'id' => $r['person_id'],
                    'title' => $r['first_name'] . ' ' . $r['last_name'],
                    'sub' => $r['parish_name'] ?? 'No Parish',
                    'category' => 'Parishioner',
                    'url' => '../parishioners/view.php?id=' . $r['person_id'],
                    'icon' => 'person-outline'
                ];
            }
        }
    }

    // 2. Parishes
    if ($type === 'all' || $type === 'parish') {
        $sql = "SELECT parish_id, parish_name, location FROM parishes WHERE parish_name LIKE ? OR location LIKE ? LIMIT 5";
        $data = db_fetchAll($sql, [$searchTerm, $searchTerm]);
        foreach ($data as $r) {
            $results[] = [
                'id' => $r['parish_id'],
                'title' => $r['parish_name'],
                'sub' => $r['location'],
                'category' => 'Parish',
                'url' => '../admin/parishes.php?search=' . urlencode($r['parish_name']),
                'icon' => 'business-outline'
            ];
        }
    }

    // 3. Baptisms
    if ($type === 'all' || $type === 'baptism') {
        $terms = array_filter(explode(' ', $q));
        $conds = [];
        $b_params = [];
        foreach ($terms as $term) {
            $conds[] = "(p.first_name || ' ' || IFNULL(p.other_names, '') || ' ' || p.last_name) LIKE ?";
            $b_params[] = "%$term%";
        }
        if (!empty($conds)) {
            $name_match = "(" . implode(" AND ", $conds) . ")";
            $sql = "SELECT b.baptism_id, p.first_name, p.last_name, pr.parish_name 
                    FROM baptisms b
                    JOIN parishioners p ON b.person_id = p.person_id
                    JOIN parishes pr ON b.parish_id = pr.parish_id
                    WHERE $name_match OR b.register_book_number = ?
                    LIMIT 5";
            $b_params[] = $q;
            $data = db_fetchAll($sql, $b_params);
            foreach ($data as $r) {
                $results[] = [
                    'id' => $r['baptism_id'],
                    'title' => $r['first_name'] . ' ' . $r['last_name'],
                    'sub' => $r['parish_name'] . ' (Baptism)',
                    'category' => 'Sacrament',
                    'url' => '../sacraments/view_baptism.php?id=' . $r['baptism_id'],
                    'icon' => 'water-outline'
                ];
            }
        }
    }

    // 4. Marriages
    if ($type === 'all' || $type === 'marriage') {
        $terms = array_filter(explode(' ', $q));
        $groom_conds = [];
        $bride_conds = [];
        $m_params = [];
        foreach ($terms as $term) {
            $groom_conds[] = "(gp.first_name || ' ' || IFNULL(gp.other_names, '') || ' ' || gp.last_name) LIKE ?";
            $bride_conds[] = "(bp.first_name || ' ' || IFNULL(bp.other_names, '') || ' ' || bp.last_name) LIKE ?";
            $m_params[] = "%$term%";
        }
        if (!empty($groom_conds)) {
            $groom_match = "(" . implode(" AND ", $groom_conds) . ")";
            $bride_match = "(" . implode(" AND ", $bride_conds) . ")";
            foreach ($terms as $term) {
                $m_params[] = "%$term%";
            }
            $sql = "SELECT m.marriage_id, gp.first_name as g_f, bp.first_name as b_f, pr.parish_name 
                    FROM marriages m
                    JOIN parishioners gp ON m.groom_person_id = gp.person_id
                    JOIN parishioners bp ON m.bride_person_id = bp.person_id
                    JOIN parishes pr ON m.parish_id = pr.parish_id
                    WHERE $groom_match OR $bride_match
                    LIMIT 5";
            $data = db_fetchAll($sql, $m_params);
            foreach ($data as $r) {
                $results[] = [
                    'id' => $r['marriage_id'],
                    'title' => $r['g_f'] . " & " . $r['b_f'],
                    'sub' => $r['parish_name'] . ' (Marriage)',
                    'category' => 'Sacrament',
                    'url' => '../sacraments/view_marriage.php?id=' . $r['marriage_id'],
                    'icon' => 'heart-outline'
                ];
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($results);
