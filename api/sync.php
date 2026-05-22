<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Master Server - Remote Data Sync API
 * 
 * Receives JSON payloads from offline laptops and merges them into the master database.
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// 1. Basic API Authentication (Token-based)
$headers = getallheaders();
$api_token = $headers['X-Sync-Token'] ?? '';

// Master Sync Token
if (empty($api_token) || $api_token !== 'HWN-SYNC-SECURE-2024') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized Sync Attempt', 'status' => 'failure']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_payload = file_get_contents('php://input');
    $payload = json_decode($raw_payload, true);

    if (!$payload) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON Payload', 'status' => 'failure']);
        exit;
    }

    $pdo = getDB();
    $processed = [];

    // Begin Demo Processing Logic
    // For the ZAOU Demo, we will demonstrate syncing Parishioners using their GUID to avoid duplication.
    if (isset($payload['parishioners'])) {
        $synced_ids = [];
        $stmt_check = $pdo->prepare("SELECT person_id FROM parishioners WHERE guid = ?");
        $stmt_insert = $pdo->prepare("
            INSERT INTO parishioners (first_name, other_names, last_name, gender, dob, place_of_birth, father_name, mother_name, scc_name, current_parish_id, address, status, guid, diocese_id, place_of_residence, place_of_baptism, sync_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Synced')
        ");

        foreach ($payload['parishioners'] as $p) {
            if (empty($p['guid'])) continue;
            
            $stmt_check->execute([$p['guid']]);
            if (!$stmt_check->fetch()) {
                // Not in master DB, insert it
                $stmt_insert->execute([
                    $p['first_name'], $p['other_names'], $p['last_name'], $p['gender'], $p['dob'],
                    $p['place_of_birth'], $p['father_name'], $p['mother_name'], $p['scc_name'],
                    $p['current_parish_id'], $p['address'], $p['status'], $p['guid'],
                    $p['diocese_id'], $p['place_of_residence'], $p['place_of_baptism']
                ]);
            }
            // Mark as successfully processed so the client updates its local status
            $synced_ids[] = $p['person_id']; 
        }
        $processed['parishioners'] = $synced_ids;
    }
    
    // (Other tables like baptisms would follow a similar pattern using verification_hash)
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Data merged successfully into Master Database.',
        'processed' => $processed,
        'server_time' => date('Y-m-d H:i:s')
    ]);
} else {
    echo json_encode([
        'status' => 'online',
        'message' => 'Master Server Sync API is active.',
        'version' => '1.0.0-PRO'
    ]);
}
?>
