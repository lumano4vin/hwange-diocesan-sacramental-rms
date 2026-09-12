<?php
/**
 * SQLite to MySQL Database Migration Script
 * For Hwange Diocesan Sacramental RMS
 */

// 1. Load env variables
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $value = trim($value, '"\'');
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');

if (!$host || !$user || !$pass) {
    die("Error: DB_HOST, DB_USER, and DB_PASSWORD must be configured in your .env file.\n");
}

echo "Starting migration...\n";
echo "Cloud MySQL Target: $host:$port ($db)\n\n";

try {
    // 2. Connect to local SQLite
    $sqlite_file = __DIR__ . "/database.sqlite";
    if (!file_exists($sqlite_file)) {
        die("Error: database.sqlite file not found in root directory!\n");
    }
    $sqlite_pdo = new PDO("sqlite:" . $sqlite_file);
    $sqlite_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to local SQLite database.\n";

    // 3. Connect to cloud MySQL
    $dsn_mysql = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = '';
    }
    $mysql_pdo = new PDO($dsn_mysql, $user, $pass, $options);
    echo "✓ Connected to cloud MySQL database.\n";

    // 4. Initialize schemas
    echo "Initializing database tables on MySQL...\n";
    $mysql_pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Read and parse schema.sql (remove CREATE DATABASE and USE statements)
    $schema_sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema_sql === false) {
        die("Error: Could not find schema.sql\n");
    }
    
    // Remove database creation and use statements
    $schema_sql = preg_replace('/CREATE DATABASE[^;]*;/i', '', $schema_sql);
    $schema_sql = preg_replace('/USE [^;]*;/i', '', $schema_sql);
    
    // Split queries by semicolon and execute one by one
    // (A simple split is fine since there are no semicolons inside trigger/function strings in this schema.sql)
    $queries = explode(';', $schema_sql);
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                $mysql_pdo->exec($query);
            } catch (Exception $e) {
                // If table already exists or minor error, keep going
                echo "  (Note during schema init: " . $e->getMessage() . ")\n";
            }
        }
    }
    echo "✓ Core tables initialized.\n";

    // Execute self-healing checks from db.php (by overriding getDB and calling connection initialization)
    // We will execute the queries directly to ensure the extra tables exist on MySQL:
    $extra_tables = [
        "dioceses" => "CREATE TABLE IF NOT EXISTS dioceses (
            diocese_id INT AUTO_INCREMENT PRIMARY KEY,
            diocese_name VARCHAR(100) NOT NULL,
            bishop_name VARCHAR(100),
            location VARCHAR(255),
            logo_path VARCHAR(255),
            api_key VARCHAR(64) UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "receptions" => "CREATE TABLE IF NOT EXISTS receptions (
            reception_id INT AUTO_INCREMENT PRIMARY KEY, 
            person_id INT NOT NULL, 
            parish_id INT NOT NULL, 
            date_of_reception DATE DEFAULT NULL, 
            minister VARCHAR(255), 
            previous_denomination VARCHAR(255), 
            godparents_sponsors TEXT, 
            register_book_number VARCHAR(50), 
            page_number VARCHAR(50), 
            entry_number VARCHAR(50), 
            status VARCHAR(20) DEFAULT 'Draft', 
            verification_hash VARCHAR(64), 
            guid VARCHAR(36), 
            diocese_id INT DEFAULT 1, 
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
            FOREIGN KEY (person_id) REFERENCES parishioners(person_id), 
            FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
        )",
        "parish_assignments" => "CREATE TABLE IF NOT EXISTS parish_assignments (
            assignment_id INT AUTO_INCREMENT PRIMARY KEY, 
            parish_id INT NOT NULL, 
            user_id INT, 
            priest_name_manual VARCHAR(255), 
            start_date DATE DEFAULT NULL, 
            end_date DATE, 
            role VARCHAR(50) DEFAULT 'Parish Priest', 
            status VARCHAR(20) DEFAULT 'Active', 
            notes TEXT, 
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
            FOREIGN KEY (parish_id) REFERENCES parishes(parish_id), 
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )",
        "communications" => "CREATE TABLE IF NOT EXISTS communications (
            comm_id INT AUTO_INCREMENT PRIMARY KEY, 
            sender_id INT NOT NULL, 
            parish_id INT NOT NULL, 
            recipient_parish_id INT DEFAULT NULL, 
            subject VARCHAR(255) NOT NULL, 
            message TEXT NOT NULL, 
            type VARCHAR(50) DEFAULT 'Query', 
            status VARCHAR(20) DEFAULT 'Pending', 
            is_read INT DEFAULT 0, 
            admin_response TEXT, 
            replied_at DATETIME, 
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
            FOREIGN KEY (sender_id) REFERENCES users(user_id), 
            FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
        )",
        "prenuptial_investigations" => "CREATE TABLE IF NOT EXISTS prenuptial_investigations (
            pni_id INT AUTO_INCREMENT PRIMARY KEY,
            groom_id INT NOT NULL,
            bride_id INT NOT NULL,
            parish_id INT NOT NULL,
            priest_user_id INT,
            investigation_date DATE DEFAULT NULL,
            groom_free_to_marry INT DEFAULT 0,
            bride_free_to_marry INT DEFAULT 0,
            groom_previous_marriage INT DEFAULT 0,
            bride_previous_marriage INT DEFAULT 0,
            consent_unity INT DEFAULT 0,
            consent_indissolubility INT DEFAULT 0,
            consent_procreation INT DEFAULT 0,
            impediments_noted TEXT,
            dispensations_required TEXT,
            status VARCHAR(20) DEFAULT 'Draft',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (groom_id) REFERENCES parishioners(person_id),
            FOREIGN KEY (bride_id) REFERENCES parishioners(person_id),
            FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
        )",
        "clergy_profiles" => "CREATE TABLE IF NOT EXISTS clergy_profiles (
            cleric_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNIQUE,
            title VARCHAR(50),
            full_canonical_name VARCHAR(255),
            date_of_birth DATE,
            date_of_ordination DATE,
            place_of_ordination VARCHAR(255),
            ordaining_bishop VARCHAR(255),
            incardination_status VARCHAR(50) DEFAULT 'Incardinated',
            religious_congregation VARCHAR(255),
            faculties_status VARCHAR(50) DEFAULT 'Active',
            faculties_expiry DATE,
            special_faculties TEXT,
            health_notes TEXT,
            emergency_contact TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )",
        "parish_financial_submissions" => "CREATE TABLE IF NOT EXISTS parish_financial_submissions (
            submission_id INT AUTO_INCREMENT PRIMARY KEY,
            parish_id INT NOT NULL,
            fiscal_year INT NOT NULL,
            fiscal_month INT NOT NULL,
            total_collections DECIMAL(10,2) DEFAULT 0,
            sacramental_offerings DECIMAL(10,2) DEFAULT 0,
            other_income DECIMAL(10,2) DEFAULT 0,
            assessment_rate DECIMAL(5,2) DEFAULT 10.00,
            calculated_levy DECIMAL(10,2),
            status VARCHAR(50) DEFAULT 'Draft',
            submitted_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parish_id) REFERENCES parishes(parish_id),
            FOREIGN KEY (submitted_by) REFERENCES users(user_id)
        )",
        "financial_payments" => "CREATE TABLE IF NOT EXISTS financial_payments (
            payment_id INT AUTO_INCREMENT PRIMARY KEY,
            parish_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_date DATE NOT NULL,
            payment_method VARCHAR(50),
            reference_number VARCHAR(100),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
        )",
        "parish_handovers" => "CREATE TABLE IF NOT EXISTS parish_handovers (
            handover_id INT AUTO_INCREMENT PRIMARY KEY,
            parish_id INT NOT NULL,
            outgoing_priest_id INT,
            incoming_priest_id INT,
            assignment_id INT,
            status VARCHAR(50) DEFAULT 'Pending',
            registry_status_notes TEXT,
            outgoing_sign_date DATETIME,
            incoming_accept_date DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parish_id) REFERENCES parishes(parish_id),
            FOREIGN KEY (outgoing_priest_id) REFERENCES users(user_id),
            FOREIGN KEY (incoming_priest_id) REFERENCES users(user_id)
        )",
        "annual_reports" => "CREATE TABLE IF NOT EXISTS annual_reports (
            report_id INT AUTO_INCREMENT PRIMARY KEY,
            parish_id INT NOT NULL,
            report_year INT NOT NULL,
            report_data TEXT NOT NULL,
            submitted_by INT,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'Final',
            UNIQUE KEY (parish_id, report_year)
        )"
    ];

    foreach ($extra_tables as $tblName => $tblSql) {
        try {
            $mysql_pdo->exec($tblSql);
        } catch (Exception $e) {
            echo "  (Note during extra table init ($tblName): " . $e->getMessage() . ")\n";
        }
    }
    
    // Apply migrations and extra columns check to MySQL
    $migrations = [
        "ALTER TABLE parishioners MODIFY COLUMN dob DATE DEFAULT NULL",
        "ALTER TABLE baptisms MODIFY COLUMN date_of_baptism DATE DEFAULT NULL",
        "ALTER TABLE baptisms MODIFY COLUMN minister VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE confirmations MODIFY COLUMN date_of_confirmation DATE DEFAULT NULL",
        "ALTER TABLE marriages MODIFY COLUMN date_of_marriage DATE DEFAULT NULL",
        "ALTER TABLE sacraments_notations MODIFY COLUMN event_date DATE DEFAULT NULL",
        "ALTER TABLE deaths MODIFY COLUMN date_of_death DATE DEFAULT NULL",
        "ALTER TABLE ordinations_professions MODIFY COLUMN event_date DATE DEFAULT NULL",
        "ALTER TABLE first_holy_communions MODIFY COLUMN date_of_communion DATE DEFAULT NULL",
        "ALTER TABLE receptions MODIFY COLUMN date_of_reception DATE DEFAULT NULL",
        "ALTER TABLE parish_assignments MODIFY COLUMN start_date DATE DEFAULT NULL",
        "ALTER TABLE prenuptial_investigations MODIFY COLUMN investigation_date DATE DEFAULT NULL",
        "ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL",
        "ALTER TABLE parishioners MODIFY COLUMN gender VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE parishioners MODIFY COLUMN status VARCHAR(50) DEFAULT 'Active'",
        "ALTER TABLE baptisms MODIFY COLUMN status VARCHAR(50) DEFAULT 'Valid'",
        "ALTER TABLE sacraments_notations MODIFY COLUMN sacrament_type VARCHAR(50) NOT NULL",
        "ALTER TABLE ordinations_professions MODIFY COLUMN record_type VARCHAR(50) NOT NULL",
        "ALTER TABLE parishes ADD COLUMN diocese_id INT DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN reset_token_expiry DATETIME DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(32) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0",
        "ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'",
        "ALTER TABLE users ADD COLUMN diocese_id INT DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) DEFAULT 0",
        "ALTER TABLE users ADD COLUMN deanery_id INT DEFAULT NULL",
        "ALTER TABLE parishioners ADD COLUMN diocese_id INT DEFAULT NULL",
        "ALTER TABLE parishioners ADD COLUMN scc_name VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE parishioners ADD COLUMN address TEXT DEFAULT NULL",
        "ALTER TABLE parishioners ADD COLUMN guid VARCHAR(36) DEFAULT NULL",
        "ALTER TABLE parishioners ADD COLUMN place_of_residence TEXT DEFAULT NULL",
        "ALTER TABLE parishioners ADD COLUMN place_of_baptism VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE parishioners ADD COLUMN sync_status VARCHAR(20) DEFAULT 'Pending'",
        "ALTER TABLE parishioners ADD COLUMN last_synced_at TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE baptisms ADD COLUMN guid VARCHAR(36) DEFAULT NULL",
        "ALTER TABLE baptisms ADD COLUMN diocese_id INT DEFAULT 1",
        "ALTER TABLE baptisms ADD COLUMN place_of_baptism VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE baptisms ADD COLUMN sync_status VARCHAR(20) DEFAULT 'Pending'",
        "ALTER TABLE baptisms ADD COLUMN last_synced_at TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE confirmations ADD COLUMN status VARCHAR(20) DEFAULT 'Active'",
        "ALTER TABLE confirmations ADD COLUMN guid VARCHAR(36) DEFAULT NULL",
        "ALTER TABLE confirmations ADD COLUMN diocese_id INT DEFAULT 1",
        "ALTER TABLE confirmations ADD COLUMN sync_status VARCHAR(20) DEFAULT 'Pending'",
        "ALTER TABLE confirmations ADD COLUMN last_synced_at TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE marriages ADD COLUMN status VARCHAR(20) DEFAULT 'Active'",
        "ALTER TABLE marriages ADD COLUMN guid VARCHAR(36) DEFAULT NULL",
        "ALTER TABLE marriages ADD COLUMN diocese_id INT DEFAULT 1",
        "ALTER TABLE marriages ADD COLUMN sync_status VARCHAR(20) DEFAULT 'Pending'",
        "ALTER TABLE marriages ADD COLUMN last_synced_at TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE marriages ADD COLUMN pni_id INT DEFAULT NULL",
        "ALTER TABLE marriages ADD COLUMN baptism_notified_groom INT DEFAULT 0",
        "ALTER TABLE marriages ADD COLUMN baptism_notified_bride INT DEFAULT 0",
        "ALTER TABLE audit_logs ADD COLUMN old_data TEXT DEFAULT NULL",
        "ALTER TABLE audit_logs ADD COLUMN new_data TEXT DEFAULT NULL",
        "ALTER TABLE deaths ADD COLUMN verification_hash VARCHAR(64) DEFAULT NULL",
        "ALTER TABLE deaths ADD COLUMN status VARCHAR(20) DEFAULT 'Active'",
        "ALTER TABLE deaths ADD COLUMN guid VARCHAR(36) DEFAULT NULL",
        "ALTER TABLE deaths ADD COLUMN diocese_id INT DEFAULT 1",
        "ALTER TABLE deaths ADD COLUMN sync_status VARCHAR(20) DEFAULT 'Pending'",
        "ALTER TABLE deaths ADD COLUMN last_synced_at TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE deaths ADD COLUMN baptism_notified INT DEFAULT 0",
        "ALTER TABLE ordinations_professions ADD COLUMN verification_hash VARCHAR(64) DEFAULT NULL",
        "ALTER TABLE ordinations_professions ADD COLUMN status VARCHAR(20) DEFAULT 'Active'",
        "ALTER TABLE ordinations_professions ADD COLUMN sync_status VARCHAR(20) DEFAULT 'Pending'",
        "ALTER TABLE ordinations_professions ADD COLUMN last_synced_at TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE first_holy_communions ADD COLUMN verification_hash VARCHAR(64) DEFAULT NULL",
        "ALTER TABLE first_holy_communions ADD COLUMN status VARCHAR(20) DEFAULT 'Valid'",
        "ALTER TABLE first_holy_communions ADD COLUMN guid VARCHAR(36) DEFAULT NULL",
        "ALTER TABLE first_holy_communions ADD COLUMN diocese_id INT DEFAULT 1",
        "ALTER TABLE communications ADD COLUMN recipient_parish_id INT DEFAULT NULL",
        "ALTER TABLE receptions ADD COLUMN previous_baptism_church VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE receptions ADD COLUMN previous_baptism_date DATE DEFAULT NULL",
        "ALTER TABLE receptions ADD COLUMN previous_baptism_place VARCHAR(255) DEFAULT NULL"
    ];
    foreach ($migrations as $mig) {
        try {
            $mysql_pdo->exec($mig);
        } catch (Exception $e) {}
    }
    echo "✓ Extra tables and column schemas validated.\n\n";
 
    // 5. Transfer Data Table by Table
    $tables = [
        'dioceses',
        'parishes',
        'users',
        'parishioners',
        'baptisms',
        'confirmations',
        'marriages',
        'sacraments_notations',
        'audit_logs',
        'deaths',
        'ordinations_professions',
        'first_holy_communions',
        'receptions',
        'parish_assignments',
        'communications',
        'prenuptial_investigations',
        'clergy_profiles',
        'parish_financial_submissions',
        'financial_payments',
        'parish_handovers',
        'annual_reports'
    ];

    foreach ($tables as $table) {
        echo "Migrating table: $table...\n";
        
        // Truncate table on MySQL to avoid duplicates
        try {
            $mysql_pdo->exec("TRUNCATE TABLE `$table` ;");
        } catch (Exception $e) {
            // Fallback if truncate fails (e.g. foreign keys or not empty)
            $mysql_pdo->exec("DELETE FROM `$table` ;");
        }

        // Fetch all rows from SQLite
        $stmt_sqlite = $sqlite_pdo->query("SELECT * FROM `$table` ;");
        $rows = $stmt_sqlite->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) === 0) {
            echo "  (0 records to transfer)\n";
            continue;
        }

        // Prepare insert query for MySQL
        $columns = array_keys($rows[0]);
        $colNames = implode('`, `', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        
        $sql_mysql = "INSERT INTO `$table` (`$colNames`) VALUES ($placeholders)";
        $stmt_mysql = $mysql_pdo->prepare($sql_mysql);

        $mysql_pdo->beginTransaction();
        $count = 0;
        foreach ($rows as $row) {
            // Bind parameters, sanitizing empty strings for numeric/date fields
            $values = [];
            foreach ($row as $colName => $val) {
                if ($val === '') {
                    $is_numeric_or_date = false;
                    $colNameLower = strtolower($colName);
                    if (strpos($colNameLower, '_id') !== false || 
                        strpos($colNameLower, 'date') !== false || 
                        strpos($colNameLower, 'time') !== false || 
                        strpos($colNameLower, 'year') !== false || 
                        strpos($colNameLower, 'month') !== false || 
                        strpos($colNameLower, '_at') !== false || 
                        strpos($colNameLower, 'expiry') !== false || 
                        strpos($colNameLower, 'login') !== false || 
                        $colNameLower === 'dob' ||
                        $colNameLower === 'user_id' || 
                        $colNameLower === 'parish_id' || 
                        $colNameLower === 'person_id' ||
                        $colNameLower === 'groom_id' ||
                        $colNameLower === 'bride_id' ||
                        $colNameLower === 'sender_id' ||
                        $colNameLower === 'recipient_parish_id' ||
                        $colNameLower === 'pni_id' ||
                        $colNameLower === 'two_factor_enabled' ||
                        $colNameLower === 'must_change_password' ||
                        $colNameLower === 'is_read' ||
                        $colNameLower === 'total_collections' ||
                        $colNameLower === 'sacramental_offerings' ||
                        $colNameLower === 'other_income' ||
                        $colNameLower === 'calculated_levy' ||
                        $colNameLower === 'amount') {
                        $is_numeric_or_date = true;
                    }
                    if ($is_numeric_or_date) {
                        $values[] = null;
                    } else {
                        $values[] = '';
                    }
                } else {
                    $values[] = $val;
                }
            }
            $stmt_mysql->execute($values);
            $count++;
        }
        $mysql_pdo->commit();
        echo "  ✓ Successfully copied $count records.\n";
    }

    $mysql_pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "\n🎉 Database migration completed successfully!\n";

} catch (Exception $e) {
    if (isset($mysql_pdo) && $mysql_pdo->inTransaction()) {
        $mysql_pdo->rollBack();
    }
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
