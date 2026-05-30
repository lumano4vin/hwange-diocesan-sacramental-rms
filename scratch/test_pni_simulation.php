<?php
/**
 * Test Simulation Script for Marriage PNI Unregistered Candidate logic
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set root path
chdir(__DIR__ . '/..');

// Temporarily disable .env to force local SQLite connection
$env_path = 'C:/Users/VINCENT/Documents/hwange diocesan sacramental rms/.env';
$env_backup = 'C:/Users/VINCENT/Documents/hwange diocesan sacramental rms/.env.tmp';
if (file_exists($env_path)) {
    rename($env_path, $env_backup);
}

require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "--- MOCKING USER SESSION ---\n";
// Create mock user session for priest
$_SESSION['user_id'] = 1; // Assuming user ID 1 exists
$_SESSION['role'] = 'priest';
$_SESSION['parish_id'] = 1;

// Fetch an active male parishioner to use as Groom
$groom = db_fetch("SELECT person_id, first_name, last_name FROM parishioners WHERE gender = 'Male' AND status = 'Active' LIMIT 1");
if (!$groom) {
    echo "No active male parishioner found. Creating one...\n";
    $guid = bin2hex(random_bytes(16));
    db_query("INSERT INTO parishioners (first_name, last_name, gender, dob, status, guid, diocese_id) 
              VALUES ('TESTGROOM', 'LASTGROOM', 'Male', '1995-05-05', 'Active', ?, 1)", [$guid]);
    $groom_id = db_fetch("SELECT last_insert_rowid() as id")['id'];
} else {
    $groom_id = $groom['person_id'];
    echo "Found active Groom: ID {$groom_id} ({$groom['first_name']} {$groom['last_name']})\n";
}

// Build mock POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'groom_registration_type' => 'registered',
    'groom_id' => $groom_id,
    
    'bride_registration_type' => 'unregistered',
    'bride_unregistered_first_name' => 'Testbride',
    'bride_unregistered_other_names' => 'Testother',
    'bride_unregistered_last_name' => 'Testlast',
    'bride_unregistered_dob' => '1998-08-08',
    'bride_unregistered_place_of_birth' => 'Test Hospital',
    'bride_unregistered_father_name' => 'Test Father',
    'bride_unregistered_mother_name' => 'Test Mother',
    'bride_unregistered_place_of_baptism' => 'Test Methodist',
    
    'parish_id' => 1,
    'investigation_date' => '2026-05-25',
    'groom_free_to_marry' => 'on',
    'bride_free_to_marry' => 'on',
    'consent_unity' => 'on',
    'consent_indissolubility' => 'on',
    'consent_procreation' => 'on',
    'status' => 'Completed',
    'notes' => 'Test notes of mixed marriage prenuptial statement'
];

// Let's count existing parishioners and PNIs
$parishioners_before = db_fetch("SELECT COUNT(*) as cnt FROM parishioners")['cnt'];
$pnis_before = db_fetch("SELECT COUNT(*) as cnt FROM prenuptial_investigations")['cnt'];

echo "Parishioners count before: {$parishioners_before}\n";
echo "PNI records count before: {$pnis_before}\n";

echo "--- RUNNING marriage_pni_add.php ---\n";

// We define our custom redirect function or capture exit to see results
// Since functions.php defines redirect(), let's override or handle it.
// Wait, we cannot easily override functions.php functions because PHP does not allow re-declaration.
// Let's inspect the code of redirect() in includes/functions.php. It calls exit.
// So we can wrap the execution or run it in a subprocess/catch the output, or simply run it
// knowing it will exit at the end. When it exits, the PHP engine will terminate.
// We can write the verification queries at the beginning of another PHP script,
// or run a verification script *after* this script runs.

register_shutdown_function(function() use ($parishioners_before, $pnis_before) {
    global $groom_id;
    echo "--- SHUTDOWN DETECTED (SCRIPT FINISHED OR EXITED) ---\n";
    
    // Restore .env file if it was renamed
    $env_path = 'C:/Users/VINCENT/Documents/hwange diocesan sacramental rms/.env';
    $env_backup = 'C:/Users/VINCENT/Documents/hwange diocesan sacramental rms/.env.tmp';
    if (file_exists($env_backup)) {
        rename($env_backup, $env_path);
    }

    global $error;
    if (isset($error) && $error !== '') {
        echo "CATCHED ERROR: " . $error . "\n";
    }
    
    // Connect to DB in shutdown handler since global connection might be closed or active
    // SQLite can open another connection
    $db = new SQLite3(dirname(__DIR__) . '/database.sqlite');
    
    $parishioners_after = $db->querySingle("SELECT COUNT(*) FROM parishioners");
    $pnis_after = $db->querySingle("SELECT COUNT(*) FROM prenuptial_investigations");
    
    echo "Parishioners count after: {$parishioners_after}\n";
    echo "PNI records count after: {$pnis_after}\n";
    
    if ($parishioners_after > $parishioners_before) {
        echo "SUCCESS: New parishioner added!\n";
        // Fetch the newly added parishioner (the bride)
        $new_bride = $db->querySingle("SELECT first_name || ' ' || last_name || ' (' || gender || ') DOB: ' || dob || ', POB: ' || place_of_birth || ', Father: ' || father_name || ', Mother: ' || mother_name || ', Baptism: ' || place_of_baptism 
                                       FROM parishioners 
                                       ORDER BY person_id DESC LIMIT 1");
        echo "New Parishioner details: {$new_bride}\n";
    } else {
        echo "FAILED: No new parishioner added.\n";
    }
    
    if ($pnis_after > $pnis_before) {
        echo "SUCCESS: New PNI record added!\n";
        // Fetch the newly added PNI
        $new_pni = $db->querySingle("SELECT 'Groom ID: ' || groom_id || ', Bride ID: ' || bride_id || ', Parish: ' || parish_id || ', Status: ' || status || ', Notes: ' || notes 
                                     FROM prenuptial_investigations 
                                     ORDER BY pni_id DESC LIMIT 1");
        echo "New PNI details: {$new_pni}\n";
        
        // Clean up mock data so we don't pollute the user's database
        $new_pni_id = $db->querySingle("SELECT pni_id FROM prenuptial_investigations ORDER BY pni_id DESC LIMIT 1");
        $new_bride_id = $db->querySingle("SELECT person_id FROM parishioners ORDER BY person_id DESC LIMIT 1");
        
        $db->exec("DELETE FROM prenuptial_investigations WHERE pni_id = {$new_pni_id}");
        $db->exec("DELETE FROM parishioners WHERE person_id = {$new_bride_id}");
        $db->exec("DELETE FROM audit_logs WHERE record_id IN ({$new_pni_id}, {$new_bride_id})");
        
        echo "CLEANUP COMPLETED: Temporary records deleted.\n";
    } else {
        echo "FAILED: No new PNI record added.\n";
    }
});

// Change directory to sacraments/ to make relative includes resolve correctly
chdir('sacraments');
require 'marriage_pni_add.php';
