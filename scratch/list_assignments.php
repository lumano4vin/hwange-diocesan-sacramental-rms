<?php
require_once 'includes/db.php';
$assignments = db_fetchAll('SELECT assignment_id, parish_id, user_id, priest_name_manual, role, status FROM parish_assignments');
foreach ($assignments as $a) {
    echo $a['assignment_id'] . ' | Parish: ' . $a['parish_id'] . ' | User: ' . $a['user_id'] . ' | Name: ' . $a['priest_name_manual'] . ' | Role: ' . $a['role'] . ' | Status: ' . $a['status'] . PHP_EOL;
}
