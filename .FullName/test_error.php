<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'includes/db.php';
require 'includes/functions.php';

try {
    $params = [];
    $filter = ""; // fake filter

    $baptisms = db_query("SELECT date_of_baptism as event_date FROM baptisms WHERE status != 'Draft' $filter", $params)->fetchAll();
    $marriages = db_query("SELECT date_of_marriage as event_date FROM marriages WHERE status != 'Draft' $filter", $params)->fetchAll();
    $confirmations = db_query("SELECT date_of_confirmation as event_date FROM confirmations WHERE status != 'Draft' $filter", $params)->fetchAll();
    $ordinations = db_query("SELECT event_date FROM ordinations_professions WHERE status != 'Draft' $filter", $params)->fetchAll();
    $deaths = db_query("SELECT date_of_death as event_date FROM deaths WHERE status != 'Draft' $filter", $params)->fetchAll();

    $all_events = [];
    foreach ($baptisms as $r) { if($r['event_date']){ $m = substr($r['event_date'], 0, 7); $all_events[$m]['b_count'] = ($all_events[$m]['b_count'] ?? 0) + 1; } }
    foreach ($marriages as $r) { if($r['event_date']){ $m = substr($r['event_date'], 0, 7); $all_events[$m]['m_count'] = ($all_events[$m]['m_count'] ?? 0) + 1; } }
    foreach ($confirmations as $r) { if($r['event_date']){ $m = substr($r['event_date'], 0, 7); $all_events[$m]['c_count'] = ($all_events[$m]['c_count'] ?? 0) + 1; } }
    foreach ($ordinations as $r) { if($r['event_date']){ $m = substr($r['event_date'], 0, 7); $all_events[$m]['o_count'] = ($all_events[$m]['o_count'] ?? 0) + 1; } }
    foreach ($deaths as $r) { if($r['event_date']){ $m = substr($r['event_date'], 0, 7); $all_events[$m]['d_count'] = ($all_events[$m]['d_count'] ?? 0) + 1; } }

    krsort($all_events);
    $keys = array_keys($all_events);
    $latest_month = !empty($keys) ? $keys[0] : date('Y-m');

    $chart_data = [];
    for ($i = 5; $i >= 0; $i--) {
        $m = date('Y-m', strtotime($latest_month . "-01 -$i months"));
        $chart_data[] = [
            'month' => $m,
            'b_count' => $all_events[$m]['b_count'] ?? 0,
            'm_count' => $all_events[$m]['m_count'] ?? 0,
            'c_count' => $all_events[$m]['c_count'] ?? 0,
            'o_count' => $all_events[$m]['o_count'] ?? 0,
            'd_count' => $all_events[$m]['d_count'] ?? 0,
        ];
    }
    echo json_encode($chart_data);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
