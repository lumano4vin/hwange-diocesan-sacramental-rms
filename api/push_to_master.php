<?php
/**
 * Local Proxy to push pending records to the Master Server
 * Avoids CORS issues and securely handles the cURL request.
 */
require_once '../includes/db.php';
require_once '../includes/config_diocese.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!$payload) {
    echo json_encode(['status' => 'error', 'message' => 'Empty payload']);
    exit;
}

if (!defined('MASTER_SYNC_URL') || empty(MASTER_SYNC_URL)) {
    echo json_encode(['status' => 'error', 'message' => 'MASTER_SYNC_URL not defined.']);
    exit;
}

// Prepare cURL
$ch = curl_init(MASTER_SYNC_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Sync-Token: HWN-SYNC-SECURE-2024'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && $response) {
    $res_data = json_decode($response, true);
    if (isset($res_data['status']) && $res_data['status'] === 'success') {
        // Mark as synced locally
        $pdo = getDB();
        $pdo->beginTransaction();
        try {
            if (isset($res_data['processed']['parishioners'])) {
                $ids = $res_data['processed']['parishioners'];
                if (count($ids) > 0) {
                    $inQuery = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare("UPDATE parishioners SET sync_status = 'Synced', last_synced_at = CURRENT_TIMESTAMP WHERE person_id IN ($inQuery)");
                    $stmt->execute($ids);
                }
            }
            // (Repeat for other tables processed)
            
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Successfully pushed and marked as synced locally.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Failed to mark local records synced: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Master rejected sync', 'response' => $res_data]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to connect to Master Server', 'http_code' => $http_code]);
}
?>
