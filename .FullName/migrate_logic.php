<?php
/**
 * Hwange Diocese RMS - Schema Normalization Tool (V2 - High Resilience)
 * Standardizes shorthand columns AND table names for all sacramental modules.
 */

try {
    $db_path = __DIR__ . '/database.sqlite';
    if (!file_exists($db_path)) {
        die("[ERROR] database.sqlite not found!\n");
    }

    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Define standardization mappings for columns
    $col_mappings = [
        'reg_book' => 'register_book_number',
        'reg_page' => 'page_number',
        'reg_entry' => 'entry_number',
        'book_no' => 'register_book_number',
        'entry_no' => 'entry_number'
    ];

    // 2. Define standard table names we WANT to have
    // Format: 'desired_plural_name' => ['possible', 'singular', 'variants']
    $table_targets = [
        'baptisms' => ['baptism'],
        'marriages' => ['marriage'],
        'confirmations' => ['confirmation'],
        'deaths' => ['death'],
        'ordinations_professions' => ['ordination', 'ordinations', 'holy_orders']
    ];

    $changes_made = 0;

    // Get all existing tables
    $existing_tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

    // 3. STEP ONE: Table Normalization (Rename singular to plural if needed)
    foreach ($table_targets as $target => $variants) {
        if (in_array($target, $existing_tables)) {
            // Target exists, skip
            continue;
        }
        foreach ($variants as $variant) {
            if (in_array($variant, $existing_tables)) {
                echo " -> Renaming Table: $variant to $target\n";
                $pdo->exec("ALTER TABLE `$variant` RENAME TO `$target` ");
                $existing_tables[] = $target; // Add to existing list for next step
                $changes_made++;
                break;
            }
        }
    }

    // 4. STEP TWO: Column Normalization
    foreach ($table_targets as $target => $variants) {
        if (!in_array($target, $existing_tables)) continue;

        echo "Checking table: $target...\n";
        $info = $pdo->query("PRAGMA table_info(`$target`)")->fetchAll(PDO::FETCH_ASSOC);
        $cols = array_column($info, 'name');
        
        foreach ($col_mappings as $old => $new) {
            if (in_array($old, $cols) && !in_array($new, $cols)) {
                echo "   -> Normalizing column: $old to $new\n";
                $pdo->exec("ALTER TABLE `$target` RENAME COLUMN `$old` TO `$new` ");
                $changes_made++;
            }
        }
    }

    if ($changes_made === 0) {
        echo "[OK] All tables and columns are already standardized.\n";
    } else {
        echo "[SUCCESS] Performed $changes_made schema updates.\n";
    }

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
