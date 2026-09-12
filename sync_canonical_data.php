<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Canonical Data Synchronization Script
 */

require_once 'includes/db.php';
require_once 'includes/config_diocese.php'; // ensure MASTER_SYNC_URL is loaded

echo "--- Synchronizing Canonical Data ---\n";
echo "Target Cloud Master: " . MASTER_SYNC_URL . "\n\n";

try {
    // 1. Collect Unsynced (Draft) Records
    echo "[SYNC] Gathering pending draft records...\n";
    $payload = [
        'baptisms' => db_fetchAll("SELECT * FROM baptisms WHERE status = 'Draft'"),
        'marriages' => db_fetchAll("SELECT * FROM marriages WHERE status = 'Draft'"),
        'confirmations' => db_fetchAll("SELECT * FROM confirmations WHERE status = 'Draft'"),
        'deaths' => db_fetchAll("SELECT * FROM deaths WHERE status = 'Draft'")
    ];

    $total_records = count($payload['baptisms']) + count($payload['marriages']) + count($payload['confirmations']) + count($payload['deaths']);

    if ($total_records > 0) {
        echo "Found $total_records records to push to the central vault.\n";
        
        // Push to master server via cURL
        $ch = curl_init(MASTER_SYNC_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . (getenv('SYNC_API_KEY') ?: 'hwange_sync_key_123')
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 || $http_code === 201) {
            echo "[SUCCESS] Records successfully pushed to cloud.\n";
            // In a real system, we'd mark them as 'Synced' or fetch their generated cloud IDs here
        } else {
            echo "[ERROR] Cloud server responded with code $http_code. Response: $response\n";
        }
    } else {
        echo "[SYNC] No pending records to push. Local node is up to date.\n";
    }

    echo "\n[SUCCESS] Canonical synchronization cycle complete.\n";

} catch (Exception $e) {
    echo "\n[ERROR] Synchronization failed: " . $e->getMessage() . "\n";
}
