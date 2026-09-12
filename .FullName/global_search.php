<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Global Search Action - Cross-Parish Discovery
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$q = trim($_GET['q'] ?? '');
$results = [];

if (strlen($q) >= 2) {
    $searchTerm = "%$q%";

    // 1. Search Parishioners (Global)
    $terms = array_filter(explode(' ', $q));
    $conds = [];
    $p_params = [];
    foreach ($terms as $term) {
        $conds[] = "(p.first_name || ' ' || IFNULL(p.other_names, '') || ' ' || p.last_name) LIKE ?";
        $p_params[] = "%$term%";
    }
    if (!empty($conds)) {
        $sql_p = "SELECT p.*, pr.parish_name, 'person' as type 
                  FROM parishioners p
                  LEFT JOIN parishes pr ON p.current_parish_id = pr.parish_id
                  WHERE " . implode(" AND ", $conds) . "
                  LIMIT 10";
        $pars = db_fetchAll($sql_p, $p_params);
        foreach ($pars as $r) {
            $results[] = [
                'title' => $r['first_name'] . ' ' . $r['last_name'],
                'meta' => $r['parish_name'] ?? 'No Parish',
                'type' => 'person',
                'url' => 'parishioners/edit.php?id=' . $r['person_id']
            ];
        }
    }

    // 2. Search Baptisms (Global)
    $conds = [];
    $b_params = [];
    foreach ($terms as $term) {
        $conds[] = "(p.first_name || ' ' || IFNULL(p.other_names, '') || ' ' || p.last_name) LIKE ?";
        $b_params[] = "%$term%";
    }
    if (!empty($conds)) {
        $name_match = "(" . implode(" AND ", $conds) . ")";
        $sql_b = "SELECT b.baptism_id, p.first_name, p.last_name, pr.parish_name, 'baptism' as type 
                  FROM baptisms b
                  JOIN parishioners p ON b.person_id = p.person_id
                  JOIN parishes pr ON b.parish_id = pr.parish_id
                  WHERE $name_match OR b.register_book_number = ?
                  LIMIT 10";
        $b_params[] = $q;
        $baps = db_fetchAll($sql_b, $b_params);
        foreach ($baps as $r) {
            $results[] = [
                'title' => $r['first_name'] . ' ' . $r['last_name'],
                'meta' => $r['parish_name'] . ' (Baptized)',
                'type' => 'baptism',
                'url' => 'sacraments/view_baptism.php?id=' . $r['baptism_id']
            ];
        }
    }

    // 3. Search Marriages (Global)
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
        $sql_m = "SELECT m.marriage_id, gp.first_name as g_f, gp.last_name as g_l, bp.first_name as b_f, bp.last_name as b_l, pr.parish_name, 'marriage' as type 
                  FROM marriages m
                  JOIN parishioners gp ON m.groom_person_id = gp.person_id
                  JOIN parishioners bp ON m.bride_person_id = bp.person_id
                  JOIN parishes pr ON m.parish_id = pr.parish_id
                  WHERE $groom_match OR $bride_match
                  LIMIT 10";
        $mars = db_fetchAll($sql_m, $m_params);
        foreach ($mars as $r) {
            $results[] = [
                'title' => $r['g_f'] . " & " . $r['b_f'] . " (Marriage)",
                'meta' => $r['parish_name'],
                'type' => 'marriage',
                'url' => 'sacraments/view_marriage.php?id=' . $r['marriage_id']
            ];
        }
    }

    // 4. Search Confirmations
    $conds = [];
    $c_params = [];
    foreach ($terms as $term) {
        $conds[] = "(p.first_name || ' ' || IFNULL(p.other_names, '') || ' ' || p.last_name) LIKE ?";
        $c_params[] = "%$term%";
    }
    if (!empty($conds)) {
        $sql_c = "SELECT c.confirmation_id, p.first_name, p.last_name, pr.parish_name, 'confirmation' as type 
                  FROM confirmations c
                  JOIN parishioners p ON c.person_id = p.person_id
                  JOIN parishes pr ON c.parish_id = pr.parish_id
                  WHERE " . implode(" AND ", $conds) . "
                  LIMIT 5";
        $confs = db_fetchAll($sql_c, $c_params);
        foreach ($confs as $r) {
            $results[] = [
                'title' => $r['first_name'] . ' ' . $r['last_name'],
                'meta' => $r['parish_name'] . ' (Confirmed)',
                'type' => 'confirmation',
                'url' => 'sacraments/view_confirmation.php?id=' . $r['confirmation_id']
            ];
        }
    }

    // 5. Search Parishes
    $sql_pa = "SELECT parish_id, parish_name, location, 'parish' as type FROM parishes WHERE parish_name LIKE ? OR location LIKE ? LIMIT 5";
    $p_list = db_fetchAll($sql_pa, [$searchTerm, $searchTerm]);
    foreach ($p_list as $r) {
        $results[] = [
            'title' => $r['parish_name'],
            'meta' => $r['location'],
            'type' => 'parish',
            'url' => 'admin/parishes.php?search=' . $r['parish_name']
        ];
    }

    // 6. Search Clergy
    $sql_u = "SELECT user_id, full_name, role, 'cleric' as type FROM users WHERE full_name LIKE ? AND role != 'guest' LIMIT 5";
    $u_list = db_fetchAll($sql_u, [$searchTerm]);
    foreach ($u_list as $r) {
        $results[] = [
            'title' => $r['full_name'],
            'meta' => ucfirst($r['role']),
            'type' => 'cleric',
            'url' => 'admin/clergy_management.php'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($results);
?>
