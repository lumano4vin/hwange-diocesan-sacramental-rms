<?php
/**
 * ZCRE - Global Diocesan Configuration
 * Loads the active diocese identity and branding.
 */
require_once __DIR__ . '/db.php';

// In a federated setup, each server usually has one "Primary" diocese.
// We'll fetch the first diocese record as the default "System Identity".
$primary_diocese = db_fetch("SELECT * FROM dioceses ORDER BY diocese_id ASC LIMIT 1");

if (!$primary_diocese) {
    // Fallback if migration hasn't run or table is empty
    $DIOCESE_NAME = "Zimbabwe Catholic Records";
    $DIOCESE_BISHOP = "Diocesan Administrator";
    $DIOCESE_LOCATION = "Zimbabwe";
    $DIOCESE_LOGO = "assets/img/default_logo.png";
} else {
    $DIOCESE_NAME = $primary_diocese['diocese_name'];
    $DIOCESE_BISHOP = $primary_diocese['bishop_name'];
    $DIOCESE_LOCATION = $primary_diocese['location'];
    $DIOCESE_LOGO = $primary_diocese['logo_path'] ?: "assets/img/diocesan_logo.png";
}

/**
 * Helper to get the full branding title
 */
function get_diocese_branding() {
    global $DIOCESE_NAME;
    return $DIOCESE_NAME;
}

/**
 * Helper to get the country (Standardized for ZCRE)
 */
function get_country_branding() {
    return "Republic of Zimbabwe";
}

// Master Server URL for Auto-Sync Engine
// Change this to the live website when hosted (e.g., https://hwangediocese.org/api/sync.php)
if (!defined('MASTER_SYNC_URL')) {
    define('MASTER_SYNC_URL', 'http://127.0.0.1:8001/api/sync.php'); 
}
