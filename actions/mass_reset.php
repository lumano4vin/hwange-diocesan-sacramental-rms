<?php
/**
 * Hwange Diocese RMS - Mass Credential Reset Utility
 * 
 * This script resets all passwords (except Fr. Vincent) to a unified default
 * and generates a distribution report for the Bishop's office.
 */

// We use the root level includes as we'll run this from the root
require_once 'includes/db.php';
require_once 'includes/functions.php';

$default_pass = "Hwange2026!";
$hash = password_hash($default_pass, PASSWORD_DEFAULT);
$excluded_user = "vincent_lumano";

try {
    $pdo = getDB();
    $users = $pdo->query("SELECT user_id, username, full_name, email, role FROM users")->fetchAll(PDO::FETCH_ASSOC);

    $distribution_report = "===================================================================\n";
    $distribution_report .= "   HWANGE DIOCESAN RMS - CREDENTIALS DISTRIBUTION LIST (MAY 2026)\n";
    $distribution_report .= "===================================================================\n\n";
    $distribution_report .= "CONFIDENTIAL: Please copy the individual messages below and send them\n";
    $distribution_report .= "to the respective clergy members.\n\n";

    $updated_count = 0;
    $skipped_count = 0;

    foreach ($users as $user) {
        if ($user['username'] === $excluded_user) {
            $skipped_count++;
            continue;
        }

        // 1. Update Database
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 1 WHERE user_id = ?");
        $stmt->execute([$hash, $user['user_id']]);
        $updated_count++;

        // 2. Add to Report
        $distribution_report .= "-------------------------------------------------------------------\n";
        $distribution_report .= "TO: " . $user['full_name'] . "\n";
        $distribution_report .= "EMAIL: " . ($user['email'] ?: "No email on record") . "\n\n";
        $distribution_report .= "Dear Father,\n\n";
        $distribution_report .= "The Hwange Diocesan Sacramental Records Management System (RMS) is now entering its final production testing phase. Please find your access credentials below:\n\n";
        $distribution_report .= "Portal URL: http://127.0.0.1:8000 (Local Diocesan Server)\n";
        $distribution_report .= "Username: " . $user['username'] . "\n";
        $distribution_report .= "Temporary Password: " . $default_pass . "\n\n";
        $distribution_report .= "Upon your first login, the system will prompt you to create a new, secure password. Please ensure you have extracted the system files before launching the portal.\n\n";
        $distribution_report .= "Blessings,\n";
        $distribution_report .= "Diocesan Chancery\n";
        $distribution_report .= "-------------------------------------------------------------------\n\n";
    }

    // Write the report
    $report_path = 'docs/CREDENTIALS_DISTRIBUTION.txt';
    file_put_contents($report_path, $distribution_report);

    echo "SUCCESS: Reset $updated_count accounts. Skipped $skipped_count (Vincent).\n";
    echo "Report generated at: $report_path\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
