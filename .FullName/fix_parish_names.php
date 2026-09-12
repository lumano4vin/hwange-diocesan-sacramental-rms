<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Duplicate Parish Name Distinction Fix
 */

require_once 'includes/db.php';

try {
    // 1. Update Dandanda
    db_query("UPDATE parishes SET parish_name = 'St. John the Baptist (Dandanda)' WHERE parish_name = 'St. John the Baptist' AND location = 'Dandanda'");
    
    // 2. Update Makwa
    db_query("UPDATE parishes SET parish_name = 'St. John the Baptist (Makwa)' WHERE parish_name = 'St. John the Baptist' AND location = 'Makwa'");

    echo "SUCCESS: Distinguished St. John the Baptist parishes in the database.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
