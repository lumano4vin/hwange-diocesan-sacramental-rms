<?php
/**
 * Test Simulation Script for Baptism Unregistered Candidate logic
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
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'priest';
$_SESSION['parish_id'] = 1;

// Build mock POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'registration_type' => 'unregistered',
    'unregistered_first_name' => 'Testbaby',
    'unregistered_other_names' => 'Testmiddle',
    'unregistered_last_name' => 'Testlast',
    'unregistered_gender' => 'Male',
    'unregistered_dob' => '2026-02-02',
    'unregistered_place_of_birth' => 'Test Clinic',
    'unregistered_father_name' => 'Test Father',
    'unregistered_mother_name' => 'Test Mother',
    'unregistered_mother_maiden_name' => 'Test Maiden',
    
    'parish_id' => 1,
    'date_of_baptism' => '2026-05-25',
    'place_of_baptism' => 'ST. IGNATIUS',
    'minister' => 'FR. J. MOYO',
    'godparents' => 'GODPARENT ONE, GODPARENT TWO',
    'witnesses' => 'WITNESS ONE',
    'register_book_number' => 'Volume I',
    'page_number' => '45',
    'entry_number' => '2026/012',
    'status' => 'Valid'
];

// Count before
$parishioners_before = db_fetch("SELECT COUNT(*) as cnt FROM parishioners")['cnt'];
$baptisms_before = db_fetch("SELECT COUNT(*) as cnt FROM baptisms")['cnt'];

echo "Parishioners count before: {$parishioners_before}\n";
echo "Baptisms count before: {$baptisms_before}\n";

echo "--- RUNNING baptism_add.php ---\n";

register_shutdown_function(function() use ($parishioners_before, $baptisms_before) {
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
    
    $db = new SQLite3(dirname(__DIR__) . '/database.sqlite');
    
    $parishioners_after = $db->querySingle("SELECT COUNT(*) FROM parishioners");
    $baptisms_after = $db->querySingle("SELECT COUNT(*) FROM baptisms");
    
    echo "Parishioners count after: {$parishioners_after}\n";
    echo "Baptisms count after: {$baptisms_after}\n";
    
    if ($parishioners_after > $parishioners_before) {
        echo "SUCCESS: New child registered as parishioner!\n";
        $new_person = $db->querySingle("SELECT first_name || ' ' || last_name || ' (' || gender || ') DOB: ' || dob || ', Mother Maiden: ' || mother_maiden_name 
                                       FROM parishioners 
                                       ORDER BY person_id DESC LIMIT 1");
        echo "Details: {$new_person}\n";
    } else {
        echo "FAILED: No new parishioner added.\n";
    }
    
    if ($baptisms_after > $baptisms_before) {
        echo "SUCCESS: New Baptism record added!\n";
        $new_bap = $db->querySingle("SELECT 'Bap ID: ' || baptism_id || ', Person ID: ' || person_id || ', Minister: ' || minister || ', Book: ' || register_book_number || ', Page: ' || page_number || ', Entry: ' || entry_number 
                                     FROM baptisms 
                                     ORDER BY baptism_id DESC LIMIT 1");
        echo "Details: {$new_bap}\n";
        
        // Cleanup
        $new_bap_id = $db->querySingle("SELECT baptism_id FROM baptisms ORDER BY baptism_id DESC LIMIT 1");
        $new_person_id = $db->querySingle("SELECT person_id FROM parishioners ORDER BY person_id DESC LIMIT 1");
        
        $db->exec("DELETE FROM baptisms WHERE baptism_id = {$new_bap_id}");
        $db->exec("DELETE FROM parishioners WHERE person_id = {$new_person_id}");
        $db->exec("DELETE FROM audit_logs WHERE record_id IN ({$new_bap_id}, {$new_person_id})");
        
        echo "CLEANUP COMPLETED: Temporary records deleted.\n";
    } else {
        echo "FAILED: No new baptism record added.\n";
    }
});

chdir('sacraments');
require 'baptism_add.php';
