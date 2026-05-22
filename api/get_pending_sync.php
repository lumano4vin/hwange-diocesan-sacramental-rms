<?php
/**
 * Local API to fetch pending offline records for cloud syncing
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
require_login(); // Ensure only logged-in sessions can trigger sync

$pdo = getDB();
$tables = ['parishioners', 'baptisms', 'confirmations', 'marriages', 'deaths', 'ordinations_professions'];
$payload = [];

try {
    foreach ($tables as $table) {
        $pk = '';
        if ($table === 'parishioners') $pk = 'person_id';
        elseif ($table === 'baptisms') $pk = 'baptism_id';
        elseif ($table === 'confirmations') $pk = 'confirmation_id';
        elseif ($table === 'marriages') $pk = 'marriage_id';
        elseif ($table === 'deaths') $pk = 'death_id';
        elseif ($table === 'ordinations_professions') $pk = 'record_id';
        
        $stmt = $pdo->query("SELECT * FROM $table WHERE sync_status = 'Pending'");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($records) > 0) {
            $payload[$table] = $records;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'has_pending' => count($payload) > 0,
        'payload' => $payload
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
