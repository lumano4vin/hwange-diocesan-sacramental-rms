<?php
/**
 * Local API to mark records as successfully synced
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$pdo = getDB();
$pdo->beginTransaction();

try {
    foreach ($data as $table => $ids) {
        $pk = '';
        if ($table === 'parishioners') $pk = 'person_id';
        elseif ($table === 'baptisms') $pk = 'baptism_id';
        elseif ($table === 'confirmations') $pk = 'confirmation_id';
        elseif ($table === 'marriages') $pk = 'marriage_id';
        elseif ($table === 'deaths') $pk = 'death_id';
        elseif ($table === 'ordinations_professions') $pk = 'record_id';
        
        if (empty($pk) || empty($ids)) continue;

        // Secure IN clause
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE $table SET sync_status = 'Synced', last_synced_at = CURRENT_TIMESTAMP WHERE $pk IN ($inQuery)");
        $stmt->execute($ids);
    }
    
    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
