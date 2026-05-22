<?php
/**
 * ZCRE - Canonical Exchange API (v1 Prototype)
 * Provides record verification and exchange between dioceses.
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$guid = $_GET['guid'] ?? null;
$type = $_GET['type'] ?? 'baptism'; // baptism, marriage, etc.

if (!$guid) {
    echo json_encode(['error' => 'Missing GUID']);
    exit;
}

$allowed_types = [
    'baptism' => ['table' => 'baptisms', 'pk' => 'baptism_id'],
    'marriage' => ['table' => 'marriages', 'pk' => 'marriage_id'],
    'confirmation' => ['table' => 'confirmations', 'pk' => 'confirmation_id'],
    'communion' => ['table' => 'first_holy_communions', 'pk' => 'communion_id'],
    'death' => ['table' => 'deaths', 'pk' => 'death_id']
];

if (!isset($allowed_types[$type])) {
    echo json_encode(['error' => 'Invalid sacrament type']);
    exit;
}

$config = $allowed_types[$type];
$table = $config['table'];

try {
    $pdo = getDB();
    
    // Fetch record with parishioner details
    $stmt = $pdo->prepare("
        SELECT r.*, p.first_name, p.last_name, p.dob, p.place_of_birth, pa.parish_name as celebrating_parish, d.diocese_name
        FROM $table r
        JOIN parishioners p ON r.person_id = p.person_id
        JOIN parishes pa ON r.parish_id = pa.parish_id
        JOIN dioceses d ON pa.diocese_id = d.diocese_id
        WHERE r.guid = ?
    ");
    $stmt->execute([$guid]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        echo json_encode(['error' => 'Record not found in the ' . get_diocese_branding() . ' Registry']);
        exit;
    }

    // Scrub internal IDs for exchange
    unset($record['person_id']);
    unset($record['parish_id']);
    unset($record['diocese_id']);

    echo json_encode([
        'status' => 'Verified',
        'source_diocese' => $DIOCESE_NAME,
        'verification_timestamp' => date('c'),
        'canonical_data' => $record
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => 'API Error: ' . $e->getMessage()]);
}
