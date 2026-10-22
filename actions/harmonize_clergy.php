<?php
/**
 * Hwange Diocesan RMS - Clergy Canonical Name Harmonization Action
 * Corrects legacy name variations (Mnkmba / Mnkamba -> Nkamba) across all database tables
 * and forces cloud vault synchronization if running in serverless mode.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Allow CLI execution or authenticated user
if (php_sapi_name() !== 'cli') {
    require_login();
}

$pdo = getDB();

$results = [
    'users' => 0,
    'assignments' => 0,
    'parishes' => 0,
    'sacraments' => 0,
];

try {
    // 1. Users table
    $stmt = $pdo->prepare("UPDATE users SET full_name = 'Fr. Nicolas Nkamba', username = 'nicolas_nkamba', email = REPLACE(REPLACE(email, 'mnkmba', 'nkamba'), 'mnkamba', 'nkamba') WHERE full_name LIKE '%Mnkmba%' OR full_name LIKE '%Mnkamba%' OR username LIKE '%mnkmba%' OR username LIKE '%mnkamba%'");
    $stmt->execute();
    $results['users'] = $stmt->rowCount();

    // 2. Parish assignments
    $stmt = $pdo->prepare("UPDATE parish_assignments SET priest_name_manual = 'Fr. Nicolas Nkamba' WHERE priest_name_manual LIKE '%Mnkmba%' OR priest_name_manual LIKE '%Mnkamba%'");
    $stmt->execute();
    $results['assignments'] = $stmt->rowCount();

    // 3. Parishes
    $stmt = $pdo->prepare("UPDATE parishes SET priest_in_charge_id = 18 WHERE parish_id = 16 AND (priest_in_charge_id IS NULL OR priest_in_charge_id = 0)");
    $stmt->execute();
    $results['parishes'] = $stmt->rowCount();

    // 4. Sacrament tables
    $sacrament_cols = [
        'baptisms' => 'minister',
        'confirmations' => 'minister',
        'first_holy_communions' => 'minister',
        'marriages' => 'officiant',
        'receptions' => 'minister',
        'deaths' => 'minister',
        'ordinations_professions' => 'ordaining_bishop',
        'parish_handovers' => 'notes',
        'parish_assignments' => 'notes',
        'clergy_profiles' => 'full_canonical_name',
        'annual_register_returns' => 'canonical_attestation'
    ];
    foreach ($sacrament_cols as $table => $col) {
        try {
            $stmt = $pdo->prepare("UPDATE `$table` SET `$col` = REPLACE(REPLACE(`$col`, 'Mnkmba', 'Nkamba'), 'Mnkamba', 'Nkamba') WHERE `$col` LIKE '%Mnkmba%' OR `$col` LIKE '%Mnkamba%'");
            $stmt->execute();
            $results['sacraments'] += $stmt->rowCount();
        } catch (Exception $e) {}
    }

    // Force Cloud Vault Sync if in serverless environment
    $cloud_synced = false;
    if (!empty($GLOBALS['rms_is_serverless']) || getenv('VERCEL') || getenv('RMS_CLOUD_GIST_ID')) {
        $cloud_synced = rms_sync_db_to_cloud(true);
    }

    if (php_sapi_name() === 'cli') {
        echo "Harmonization Results: " . json_encode($results) . "\n";
        echo "Cloud Synced: " . ($cloud_synced ? 'Yes' : 'No') . "\n";
        exit(0);
    }

    header("Location: " . BASE_URL . "admin/parishes.php?success=" . urlencode("Clergy canonical name harmonized across all records to Fr. Nicolas Nkamba."));
    exit;

} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
    header("Location: " . BASE_URL . "admin/parishes.php?error=" . urlencode("Harmonization error: " . $e->getMessage()));
    exit;
}
