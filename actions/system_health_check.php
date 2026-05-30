<?php
/**
 * Hwange Diocesan Sacramental Database System
 * System Integrity & Health Check Utility
 */

require_once 'includes/functions.php';

echo "===========================================\n";
echo " HWANGE DIOCESAN SYSTEM - HEALTH CHECK\n";
echo "===========================================\n\n";

$errors = 0;

// 1. Database Check
echo "[1/4] Checking Database Connectivity... ";
if (file_exists('database.sqlite')) {
    try {
        $pdo = new PDO("sqlite:database.sqlite");
        $stmt = $pdo->query("SELECT diocese_name FROM dioceses LIMIT 1");
        $res = $stmt->fetch();
        echo "OK (Branding: " . ($res['diocese_name'] ?? 'Generic') . ")\n";
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        $errors++;
    }
} else {
    echo "FAILED: database.sqlite not found!\n";
    $errors++;
}

// 2. Critical Files Check
echo "[2/4] Verifying Core Infrastructure... ";
$critical_files = [
    'index.php', 
    'includes/functions.php', 
    'dashboard/index.php', 
    'assets/css/style.css',
    'assets/img/seal.png'
];
$missing = [];
foreach ($critical_files as $f) {
    if (!file_exists($f)) $missing[] = $f;
}
if (empty($missing)) {
    echo "OK (All core components present)\n";
} else {
    echo "FAILED (Missing: " . implode(', ', $missing) . ")\n";
    $errors++;
}

// 3. User Registry Check
echo "[3/4] Validating User Accounts... ";
try {
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "OK ($count users registered)\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    $errors++;
}

// 4. Branding & Language Check
echo "[4/4] Testing Multi-Language Engine... ";
$test_nb = get_certificate_text('nb');
if ($test_nb['cert_baptism'] === 'Chitupa chombhabhatiso') {
    echo "OK (Authentic Nambya active)\n";
} else {
    echo "WARNING: Nambya translations might be outdated.\n";
}

echo "\n-------------------------------------------\n";
if ($errors === 0) {
    echo " RESULT: SYSTEM HEALTHY - READY FOR MISSION\n";
} else {
    echo " RESULT: $errors ERRORS FOUND - NEEDS ATTENTION\n";
}
echo "-------------------------------------------\n";
?>
