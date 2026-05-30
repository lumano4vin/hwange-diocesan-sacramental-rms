<?php
/**
 * Dynamic Credentials List & Distribution Generator
 * 
 * Generates docs/CREDENTIALS_LIST.txt and docs/CREDENTIALS_DISTRIBUTION.txt
 * reflecting current database state, assignments, and the production HTTPS environment link.
 */

require_once 'includes/db.php';

try {
    $pdo = getDB();
    $default_pass = "Hwange2026!";
    $prod_url = "https://hwange-diocesan-sacramental-rms.vercel.app";

    // 1. Fetch all users sorted by full_name
    $users = db_fetchAll("SELECT * FROM users ORDER BY full_name ASC");

    // Build the CREDENTIALS_LIST.txt content
    $list_content = "===================================================================\n";
    $list_content .= "   HWANGE DIOCESAN RMS - CLERGY & STAFF CREDENTIALS LIST\n";
    $list_content .= "   Generated: May 2026 • Environment: HTTPS Production\n";
    $list_content .= "===================================================================\n\n";
    $list_content .= "This document lists the access credentials for all active diocesan and parochial accounts. \n";
    $list_content .= "All default accounts use the temporary password 'Hwange2026!' and require a password change on first login.\n";
    $list_content .= "Active accounts currently working on the portal (preserved) are flagged accordingly.\n\n";

    $list_content .= sprintf("%-30s | %-20s | %-15s | %-20s\n", "FULL NAME", "USERNAME", "ROLE", "PASSWORD STATUS");
    $list_content .= str_repeat("-", 90) . "\n";

    foreach ($users as $u) {
        $status = "Hwange2026!";
        if ($u['username'] === 'admin') {
            $status = "[PRESERVED - ADMIN]";
        } elseif ($u['username'] === 'stanislaus_lumano') {
            $status = "[PRESERVED - ACTIVE]";
        } elseif ($u['username'] === 'vincent_lumano') {
            $status = "[PRESERVED - DEV/ADMIN]";
        }
        
        $role = ucfirst($u['role']);
        $list_content .= sprintf("%-30s | %-20s | %-15s | %-20s\n", $u['full_name'], $u['username'], $role, $status);
    }

    $list_content .= "\n" . str_repeat("-", 90) . "\n\n";
    $list_content .= "DETAILED INDIVIDUAL MESSAGES FOR CLERGY:\n\n";

    // Build the CREDENTIALS_DISTRIBUTION.txt content
    $dist_content = "===================================================================\n";
    $dist_content .= "   HWANGE DIOCESAN RMS - CREDENTIALS DISTRIBUTION LIST (MAY 2026)\n";
    $dist_content .= "   Environment: HTTPS Production\n";
    $dist_content .= "===================================================================\n\n";
    $dist_content .= "CONFIDENTIAL: Please copy the individual messages below and send them\n";
    $dist_content .= "to the respective clergy members.\n\n";

    foreach ($users as $u) {
        // Fetch current parish assignment name
        $parish_name = "Diocesan Chancery";
        if ($u['parish_id']) {
            $parish = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$u['parish_id']]);
            if ($parish) {
                $parish_name = $parish['parish_name'];
            }
        } else {
            if ($u['role'] === 'priest' && $u['username'] === 'linos_dube') {
                $parish_name = "Unassigned / Off-Register";
            } elseif ($u['role'] === 'priest') {
                $parish_name = "Unassigned";
            }
        }

        $email = $u['email'] ?: "No email on record";
        $pass_desc = $default_pass;
        if ($u['username'] === 'admin') {
            $pass_desc = "[Preserved - Central Admin]";
        } elseif ($u['username'] === 'vincent_lumano') {
            $pass_desc = "[Preserved - Developer Account]";
        } elseif ($u['username'] === 'stanislaus_lumano') {
            $pass_desc = "[Preserved - Use your existing password]";
        }

        $msg = "-------------------------------------------------------------------\n";
        $msg .= "TO: " . $u['full_name'] . "\n";
        $msg .= "PARISH/ASSIGNMENT: " . $parish_name . "\n";
        $msg .= "EMAIL: " . $email . "\n\n";
        $msg .= "Dear Father,\n\n";
        $msg .= "The Hwange Diocesan Sacramental Records Management System (RMS) is now live for production testing. Please find your access credentials below:\n\n";
        $msg .= "Portal URL: " . $prod_url . "\n";
        $msg .= "Username: " . $u['username'] . "\n";
        $msg .= "Password: " . $pass_desc . "\n\n";
        
        if (in_array($u['username'], ['admin', 'vincent_lumano'])) {
            $msg .= "Central Administrator/Developer credentials bypass standard reset cycles. Please access the portal using the link above.\n\n";
        } elseif ($u['username'] === 'stanislaus_lumano') {
            $msg .= "Use the password you have already configured for your parish portal. Please access the portal using the link above.\n\n";
        } else {
            $msg .= "Upon your first login, the system will prompt you to create a new, secure password. Please access the portal using the link above.\n\n";
        }

        $msg .= "Blessings,\n";
        $msg .= "Diocesan Chancery\n";
        $msg .= "-------------------------------------------------------------------\n\n";

        $dist_content .= $msg;
        $list_content .= $msg;
    }

    file_put_contents('docs/CREDENTIALS_LIST.txt', $list_content);
    file_put_contents('docs/CREDENTIALS_DISTRIBUTION.txt', $dist_content);

    echo "[SUCCESS] Generated docs/CREDENTIALS_LIST.txt\n";
    echo "[SUCCESS] Generated docs/CREDENTIALS_DISTRIBUTION.txt\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
