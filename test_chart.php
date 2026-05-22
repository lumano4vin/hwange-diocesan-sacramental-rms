<?php
require 'includes/db.php';
$chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month_str = date('Y-m', strtotime("-$i months"));
    $chart_data[$month_str] = [
        'month' => $month_str,
        'b_count' => 0,
        'm_count' => 0,
        'c_count' => 0,
        'o_count' => 0,
        'd_count' => 0
    ];
}

$six_months_ago = date('Y-m-d H:i:s', strtotime('-6 months'));
$trends = db_query("
    SELECT created_at, table_name
    FROM audit_logs
    WHERE action_type = 'CREATE' 
      AND created_at >= ?
", [$six_months_ago]);

while ($row = $trends->fetch()) {
    $month = date('Y-m', strtotime($row['created_at']));
    if (isset($chart_data[$month])) {
        if ($row['table_name'] === 'baptisms') $chart_data[$month]['b_count']++;
        if ($row['table_name'] === 'marriages') $chart_data[$month]['m_count']++;
        if ($row['table_name'] === 'confirmations') $chart_data[$month]['c_count']++;
        if ($row['table_name'] === 'ordinations_professions') $chart_data[$month]['o_count']++;
        if ($row['table_name'] === 'deaths') $chart_data[$month]['d_count']++;
    }
}
$chart_data = array_values($chart_data);
echo json_encode($chart_data);
?>
