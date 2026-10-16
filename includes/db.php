<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Database Connection Utility (PDO)
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

// 1.1 Load environment variables from .env if it exists
$env_file = dirname(__DIR__) . '/.env';
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

$host = getenv('DB_HOST') ?: '127.0.0.1';
$ip = $host;
$dns_resolvable = true;

if ($host !== 'localhost' && $host !== '127.0.0.1') {
    $fail_cache = sys_get_temp_dir() . '/db_dns_failed.txt';
    // Fast-fail known decommissioned host or cached DNS failures (1 hour cache)
    if ($host === 'mysql-3614de40-lumano4vin-1607.f.aivencloud.com' || (file_exists($fail_cache) && abs(time() - filemtime($fail_cache)) < 3600)) {
        $dns_resolvable = false;
    } else {
        $resolved = gethostbyname($host);
        if ($resolved === $host) {
            // DNS resolution failed (e.g. expired cloud database domain)
            @file_put_contents($fail_cache, time());
            $dns_resolvable = false;
        } else {
            @unlink($fail_cache);
            $ip = $resolved;
        }
    }
}
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'hwange_diocesan_records';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: ''; 
$charset = 'utf8mb4';

// Check primary and fallback SQLite file locations
$sqlite_file = dirname(__DIR__) . "/database.sqlite";
if (!file_exists($sqlite_file) && file_exists(dirname(__DIR__) . "/data/fallback.sqlite")) {
    $sqlite_file = dirname(__DIR__) . "/data/fallback.sqlite";
}

/**
 * Optional Cloud Vault Sync for Serverless SQLite (configured via RMS_CLOUD_GIST_ID and RMS_CLOUD_TOKEN env vars)
 */
function rms_cloud_http($method, $url, $headers = [], $body = null, $timeout = 6) {
    $resp_headers = [];
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$resp_headers) {
            $len = strlen($header);
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $resp_headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return $len;
        });
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $resp_body = @curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $status, 'headers' => $resp_headers, 'body' => $resp_body];
    }
    return ['status' => 0, 'headers' => [], 'body' => false];
}

function rms_pull_db_from_cloud($tmp_sqlite, $meta_file) {
    $gist_id = getenv('RMS_CLOUD_GIST_ID');
    $token = getenv('RMS_CLOUD_TOKEN');
    if (empty($gist_id) || empty($token)) {
        return false;
    }

    $meta = file_exists($meta_file) ? (@json_decode(@file_get_contents($meta_file), true) ?: []) : [];
    $has_local = file_exists($tmp_sqlite) && filesize($tmp_sqlite) > 0;
    $is_post = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
    $age = time() - (int)($meta['checked_at'] ?? 0);

    if ($has_local && !$is_post && $age < 2) {
        return true;
    }

    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/vnd.github+json',
        'User-Agent: HwangeRMS'
    ];
    if ($has_local && !empty($meta['etag'])) {
        $headers[] = 'If-None-Match: ' . $meta['etag'];
    }

    $res = rms_cloud_http('GET', 'https://api.github.com/gists/' . $gist_id, $headers, null, 6);
    if ($res['status'] === 304 && $has_local) {
        $meta['checked_at'] = time();
        @file_put_contents($meta_file, json_encode($meta));
        return true;
    }

    if ($res['status'] === 200 && !empty($res['body'])) {
        $data = @json_decode($res['body'], true);
        $file_info = $data['files']['hwange_rms_db.gz.b64'] ?? null;
        if ($file_info) {
            $b64 = $file_info['content'] ?? '';
            if ((!empty($file_info['truncated']) || empty($b64)) && !empty($file_info['raw_url'])) {
                $raw_res = rms_cloud_http('GET', $file_info['raw_url'], $headers, null, 8);
                if ($raw_res['status'] === 200) {
                    $b64 = $raw_res['body'];
                }
            }
            if (!empty($b64)) {
                $gz = @base64_decode(trim($b64));
                $sqlite_bytes = $gz ? @gzdecode($gz) : false;
                if ($sqlite_bytes && substr($sqlite_bytes, 0, 15) === 'SQLite format 3') {
                    $dl_tmp = $tmp_sqlite . '.dl.' . getmypid();
                    if (@file_put_contents($dl_tmp, $sqlite_bytes) !== false) {
                        @unlink($tmp_sqlite . '-wal');
                        @unlink($tmp_sqlite . '-shm');
                        @unlink($tmp_sqlite . '-journal');
                        if (!@rename($dl_tmp, $tmp_sqlite)) {
                            @copy($dl_tmp, $tmp_sqlite);
                            @unlink($dl_tmp);
                        }
                        clearstatcache(true, $tmp_sqlite);
                        $meta = [
                            'etag' => $res['headers']['etag'] ?? '',
                            'checked_at' => time(),
                            'hash' => @md5_file($tmp_sqlite)
                        ];
                        @file_put_contents($meta_file, json_encode($meta));
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

function rms_sync_db_to_cloud() {
    if (empty($GLOBALS['rms_is_serverless']) || !empty($GLOBALS['rms_db_synced']) || !empty($GLOBALS['rms_skip_cloud_sync'])) {
        return false;
    }
    $gist_id = getenv('RMS_CLOUD_GIST_ID');
    $token = getenv('RMS_CLOUD_TOKEN');
    if (empty($gist_id) || empty($token)) {
        return false;
    }
    $sqlite_file = $GLOBALS['rms_sqlite_path'] ?? '';
    $meta_file = $GLOBALS['rms_meta_path'] ?? '';
    if (empty($sqlite_file) || !file_exists($sqlite_file)) {
        return false;
    }

    clearstatcache(true, $sqlite_file);
    $current_hash = @md5_file($sqlite_file);
    if (!$current_hash || $current_hash === ($GLOBALS['rms_initial_db_hash'] ?? '')) {
        return false;
    }

    $raw = @file_get_contents($sqlite_file);
    if (!$raw || substr($raw, 0, 15) !== 'SQLite format 3') {
        return false;
    }

    $payload = json_encode([
        'files' => [
            'hwange_rms_db.gz.b64' => [
                'content' => base64_encode(gzencode($raw, 6))
            ]
        ]
    ]);

    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/vnd.github+json',
        'Content-Type: application/json',
        'User-Agent: HwangeRMS'
    ];

    $res = rms_cloud_http('PATCH', 'https://api.github.com/gists/' . $gist_id, $headers, $payload, 10);
    if ($res['status'] === 200) {
        $GLOBALS['rms_db_synced'] = true;
        $GLOBALS['rms_initial_db_hash'] = $current_hash;
        if ($meta_file) {
            @file_put_contents($meta_file, json_encode([
                'etag' => $res['headers']['etag'] ?? '',
                'checked_at' => time(),
                'hash' => $current_hash
            ]));
        }
        return true;
    }
    return false;
}

// In serverless / read-only environments (such as Vercel / AWS Lambda), the deployment directory is read-only.
$is_serverless = false;
if (file_exists($sqlite_file)) {
    $is_serverless = (bool)(getenv('VERCEL') || !is_writable($sqlite_file) || !is_writable(dirname($sqlite_file)));
    if ($is_serverless) {
        $tmp_sqlite = sys_get_temp_dir() . '/hwange_database.sqlite';
        $meta_file = sys_get_temp_dir() . '/hwange_db_meta.json';
        $GLOBALS['rms_is_serverless'] = true;
        $GLOBALS['rms_sqlite_path'] = $tmp_sqlite;
        $GLOBALS['rms_meta_path'] = $meta_file;

        rms_pull_db_from_cloud($tmp_sqlite, $meta_file);

        if (!file_exists($tmp_sqlite) || filesize($tmp_sqlite) === 0) {
            @copy($sqlite_file, $tmp_sqlite);
        }
        if (file_exists($tmp_sqlite) && filesize($tmp_sqlite) > 0) {
            $sqlite_file = $tmp_sqlite;
        }
    }
}

$dsn_mysql = "mysql:host=$ip;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 5,
];

$use_cloud = (getenv('DB_HOST') && $dns_resolvable) ? true : false;

// If we are connecting to a cloud database (Aiven/TiDB), enable SSL
if ($use_cloud) {
    $ssl_verify_key = defined('Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') ? Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT : (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') ? PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT : 1014);
    $ssl_ca_key = defined('Pdo\Mysql::ATTR_SSL_CA') ? Pdo\Mysql::ATTR_SSL_CA : (defined('PDO::MYSQL_ATTR_SSL_CA') ? PDO::MYSQL_ATTR_SSL_CA : 1012);
    
    $options[$ssl_verify_key] = false;
    $options[$ssl_ca_key] = '';
}

$connected = false;
$conn_error = null;

// 1. If cloud/remote DB is configured and DNS resolves, try MySQL first
if ($use_cloud) {
    try {
        $pdo = new PDO($dsn_mysql, $user, $pass, $options);
        $connected = true;
    } catch (\PDOException $e) {
        $conn_error = $e->getMessage();
        error_log("Remote MySQL connection failed: " . $conn_error);
    }
}

// 2. Fallback to SQLite if MySQL failed or not configured
if (!$connected && file_exists($sqlite_file)) {
    try {
        $sqlite_options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO("sqlite:" . $sqlite_file, null, null, $sqlite_options);
        $pdo->exec("PRAGMA busy_timeout = 5000");
        if ($is_serverless) {
            $pdo->exec("PRAGMA journal_mode = DELETE");
            $pdo->exec("PRAGMA synchronous = NORMAL");
        } else {
            $pdo->exec("PRAGMA journal_mode = WAL");
        }
        $connected = true;
        if ($use_cloud || getenv('DB_HOST')) {
            if (!defined('DB_FALLBACK_MODE')) define('DB_FALLBACK_MODE', true);
        }
    } catch (\PDOException $e) {
        $conn_error = $e->getMessage();
        error_log("SQLite connection failed: " . $conn_error);
    }
}


// 3. If neither connected, display clear error
if (!$connected || !$pdo) {
    http_response_code(500);
    echo "<h1>Database Connection Error</h1>";
    echo "<p>Could not connect to the primary database or local storage fallback. Please ensure your database service is running or check your connection settings.</p>";
    if (ini_get('display_errors') && $conn_error) {
        echo "<small>Error details: " . htmlspecialchars($conn_error) . "</small>";
    }
    exit;
}

// Register SQLite compatibility helper functions (CONCAT, etc.)
if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
    $pdo->sqliteCreateFunction('CONCAT', function(...$args) {
        return implode('', $args);
    });
}

function db_add_column_if_missing($pdo, $table, $column_def) {
    try {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $column_def");
    } catch (Exception $e) {
        // Column already exists
    }
}

// 1.2 Self-Healing Schema Check (Auto-Migrate for Updates)
// Wrapped in a function and gated by PRAGMA user_version so it does not lock SQLite on every page load
if (!defined('RMS_SCHEMA_VERSION')) define('RMS_SCHEMA_VERSION', 4);

function run_schema_migrations($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS receptions (
        reception_id INTEGER PRIMARY KEY, 
        person_id INT NOT NULL, 
        parish_id INT NOT NULL, 
        date_of_reception DATE NOT NULL, 
        minister VARCHAR(255), 
        previous_denomination VARCHAR(255), 
        godparents_sponsors TEXT, 
        register_book_number VARCHAR(50), 
        page_number VARCHAR(50), 
        entry_number VARCHAR(50), 
        status VARCHAR(20) DEFAULT 'Draft', 
        verification_hash VARCHAR(64), 
        guid VARCHAR(36), 
        diocese_id INTEGER DEFAULT 1, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        FOREIGN KEY (person_id) REFERENCES parishioners(person_id), 
        FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS parish_assignments (
        assignment_id INTEGER PRIMARY KEY, 
        parish_id INTEGER NOT NULL, 
        user_id INTEGER, 
        priest_name_manual VARCHAR(255), 
        start_date DATE NOT NULL, 
        end_date DATE, 
        role VARCHAR(50) DEFAULT 'Parish Priest', 
        status VARCHAR(20) DEFAULT 'Active', 
        notes TEXT, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        FOREIGN KEY (parish_id) REFERENCES parishes(parish_id), 
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS communications (
        comm_id INTEGER PRIMARY KEY, 
        sender_id INTEGER NOT NULL, 
        parish_id INTEGER NOT NULL, 
        recipient_parish_id INTEGER DEFAULT NULL, 
        subject VARCHAR(255) NOT NULL, 
        message TEXT NOT NULL, 
        type VARCHAR(50) DEFAULT 'Query', 
        status VARCHAR(20) DEFAULT 'Pending', 
        is_read INTEGER DEFAULT 0, 
        admin_response TEXT, 
        replied_at DATETIME, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        FOREIGN KEY (sender_id) REFERENCES users(user_id), 
        FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
    )");

    // Column migrations (each isolated in its own try/catch)
    db_add_column_if_missing($pdo, 'communications', 'recipient_parish_id INTEGER DEFAULT NULL');
    db_add_column_if_missing($pdo, 'users', 'two_factor_secret VARCHAR(64) DEFAULT NULL');
    db_add_column_if_missing($pdo, 'users', 'two_factor_enabled TINYINT(1) DEFAULT 0');
    db_add_column_if_missing($pdo, 'parishioners', 'place_of_residence TEXT');
    db_add_column_if_missing($pdo, 'parishioners', 'place_of_baptism TEXT');

    db_add_column_if_missing($pdo, 'receptions', 'previous_baptism_church VARCHAR(255)');
    db_add_column_if_missing($pdo, 'receptions', 'previous_baptism_date DATE');
    db_add_column_if_missing($pdo, 'receptions', 'previous_baptism_place VARCHAR(255)');
    db_add_column_if_missing($pdo, 'receptions', 'verified_by INTEGER DEFAULT NULL');
    db_add_column_if_missing($pdo, 'receptions', 'verified_at DATETIME DEFAULT NULL');

    // Prenuptial Investigation (PNI) System
    $pdo->exec("CREATE TABLE IF NOT EXISTS prenuptial_investigations (
        pni_id INTEGER PRIMARY KEY,
        groom_id INTEGER NOT NULL,
        bride_id INTEGER NOT NULL,
        parish_id INTEGER NOT NULL,
        priest_user_id INTEGER,
        investigation_date DATE NOT NULL,
        groom_free_to_marry INTEGER DEFAULT 0,
        bride_free_to_marry INTEGER DEFAULT 0,
        groom_previous_marriage INTEGER DEFAULT 0,
        bride_previous_marriage INTEGER DEFAULT 0,
        consent_unity INTEGER DEFAULT 0,
        consent_indissolubility INTEGER DEFAULT 0,
        consent_procreation INTEGER DEFAULT 0,
        banns_date_1 DATE,
        banns_date_2 DATE,
        banns_date_3 DATE,
        banns_parish_id INTEGER,
        impediments_noted TEXT,
        dispensations_required TEXT,
        status VARCHAR(20) DEFAULT 'Draft',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (groom_id) REFERENCES parishioners(person_id),
        FOREIGN KEY (bride_id) REFERENCES parishioners(person_id),
        FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
    )");

    db_add_column_if_missing($pdo, 'prenuptial_investigations', 'banns_date_1 DATE');
    db_add_column_if_missing($pdo, 'prenuptial_investigations', 'banns_date_2 DATE');
    db_add_column_if_missing($pdo, 'prenuptial_investigations', 'banns_date_3 DATE');
    db_add_column_if_missing($pdo, 'prenuptial_investigations', 'banns_parish_id INTEGER');

    // Link marriages to PNI and add Civil Record Number & verification columns
    db_add_column_if_missing($pdo, 'marriages', 'pni_id INTEGER DEFAULT NULL');
    db_add_column_if_missing($pdo, 'marriages', 'baptism_notified_groom INTEGER DEFAULT 0');
    db_add_column_if_missing($pdo, 'marriages', 'baptism_notified_bride INTEGER DEFAULT 0');
    db_add_column_if_missing($pdo, 'marriages', 'civil_record_number VARCHAR(100) DEFAULT NULL');
    db_add_column_if_missing($pdo, 'marriages', 'verified_by INTEGER DEFAULT NULL');
    db_add_column_if_missing($pdo, 'marriages', 'verified_at DATETIME DEFAULT NULL');

    // Verification & canonical columns across remaining sacrament tables
    db_add_column_if_missing($pdo, 'baptisms', 'place_of_baptism TEXT');
    db_add_column_if_missing($pdo, 'baptisms', 'verified_by INTEGER DEFAULT NULL');
    db_add_column_if_missing($pdo, 'baptisms', 'verified_at DATETIME DEFAULT NULL');

    db_add_column_if_missing($pdo, 'confirmations', 'verified_by INTEGER DEFAULT NULL');
    db_add_column_if_missing($pdo, 'confirmations', 'verified_at DATETIME DEFAULT NULL');

    db_add_column_if_missing($pdo, 'deaths', 'baptism_notified INTEGER DEFAULT 0');
    db_add_column_if_missing($pdo, 'deaths', 'verified_by INTEGER DEFAULT NULL');
    db_add_column_if_missing($pdo, 'deaths', 'verified_at DATETIME DEFAULT NULL');

    db_add_column_if_missing($pdo, 'first_holy_communions', 'verified_by INTEGER DEFAULT NULL');
    db_add_column_if_missing($pdo, 'first_holy_communions', 'verified_at DATETIME DEFAULT NULL');

    db_add_column_if_missing($pdo, 'ordinations_professions', 'verified_by INTEGER DEFAULT NULL');
    db_add_column_if_missing($pdo, 'ordinations_professions', 'verified_at DATETIME DEFAULT NULL');
    db_add_column_if_missing($pdo, 'ordinations_professions', 'guid VARCHAR(36) DEFAULT NULL');
    db_add_column_if_missing($pdo, 'ordinations_professions', 'diocese_id INTEGER DEFAULT 1');

    db_add_column_if_missing($pdo, 'sacraments_notations', "status VARCHAR(20) DEFAULT 'Verified'");
    db_add_column_if_missing($pdo, 'sacraments_notations', 'verified_by INTEGER DEFAULT NULL');
    db_add_column_if_missing($pdo, 'sacraments_notations', 'verified_at DATETIME DEFAULT NULL');

    // 12. Clergy Personnel Dossier (Chancery Module)
    $pdo->exec("CREATE TABLE IF NOT EXISTS clergy_profiles (
        cleric_id INTEGER PRIMARY KEY,
        user_id INTEGER UNIQUE,
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
    )");

    // 13. Financial Stewardship & Levies (Integrated Accounting Bridge)
    $pdo->exec("CREATE TABLE IF NOT EXISTS parish_financial_submissions (
        submission_id INTEGER PRIMARY KEY,
        parish_id INTEGER NOT NULL,
        fiscal_year INTEGER NOT NULL,
        fiscal_month INTEGER NOT NULL,
        total_collections DECIMAL(10,2) DEFAULT 0,
        sacramental_offerings DECIMAL(10,2) DEFAULT 0,
        other_income DECIMAL(10,2) DEFAULT 0,
        assessment_rate DECIMAL(5,2) DEFAULT 10.00,
        calculated_levy DECIMAL(10,2),
        status VARCHAR(50) DEFAULT 'Draft',
        submitted_by INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parish_id) REFERENCES parishes(parish_id),
        FOREIGN KEY (submitted_by) REFERENCES users(user_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS financial_payments (
        payment_id INTEGER PRIMARY KEY,
        parish_id INTEGER NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        payment_method VARCHAR(50),
        reference_number VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
        session_id VARCHAR(128) PRIMARY KEY,
        session_data TEXT,
        expires_at INTEGER
    )");

    // 14. RCIA / Catechumens Tracking
    $pdo->exec("CREATE TABLE IF NOT EXISTS catechumens (
        catechumen_id INTEGER PRIMARY KEY,
        parish_id INTEGER NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        dob DATE,
        sponsor_name VARCHAR(255),
        enrollment_date DATE,
        expected_baptism_date DATE,
        status VARCHAR(50) DEFAULT 'Inquirer',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
    )");

    // 15. Auxiliary Admin Tables (Assets, Minutes, Password Reset Tickets)
    $pdo->exec("CREATE TABLE IF NOT EXISTS parish_assets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        parish_id INTEGER NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        category VARCHAR(100),
        description TEXT,
        quantity INTEGER DEFAULT 1,
        acquisition_date DATE,
        estimated_value DECIMAL(12,2),
        current_status VARCHAR(50) DEFAULT 'Good',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS meeting_minutes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        parish_id INTEGER NOT NULL,
        title VARCHAR(255) NOT NULL,
        meeting_date DATE NOT NULL,
        content TEXT,
        created_by INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        status VARCHAR(50) DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS parish_handovers (
        handover_id INTEGER PRIMARY KEY AUTOINCREMENT,
        parish_id INTEGER NOT NULL,
        outgoing_priest_id INTEGER,
        incoming_priest_id INTEGER,
        assignment_id INTEGER,
        status VARCHAR(50) DEFAULT 'Pending',
        handover_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS annual_reports (
        report_id INTEGER PRIMARY KEY AUTOINCREMENT,
        parish_id INTEGER NOT NULL,
        report_year INTEGER NOT NULL,
        report_data TEXT NOT NULL,
        submitted_by INTEGER,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(parish_id, report_year)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sacramental_notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sender_parish_id INTEGER NOT NULL,
        recipient_parish_id INTEGER NOT NULL,
        parishioner_id INTEGER NOT NULL,
        sacrament_type VARCHAR(50),
        sacrament_details TEXT,
        status VARCHAR(30) DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS deaneries (
        deanery_id INTEGER PRIMARY KEY AUTOINCREMENT,
        deanery_name VARCHAR(100) NOT NULL UNIQUE
    )");

    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->exec("PRAGMA user_version = " . RMS_SCHEMA_VERSION);
    }
}

/**
 * Get Database Instance (Legacy support)
 */
function getDB() {
    global $pdo;
    return $pdo;
}

/**
 * Convenience function for prepared queries
 */
function db_query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_fetch($sql, $params = []) {
    return db_query($sql, $params)->fetch();
}

function db_fetchAll($sql, $params = []) {
    return db_query($sql, $params)->fetchAll();
}

function db_lastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * Helper to generate database-agnostic Year extraction SQL
 */
function db_year_sql($column) {
    global $pdo;
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        return "YEAR($column)";
    } else {
        return "strftime('%Y', $column)";
    }
}

// ---------------------------------------------------------
// CUSTOM DATABASE SESSION HANDLER (Fixes Vercel & SQLite Lock Logouts)
// ---------------------------------------------------------
class DatabaseSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface {
    private $pdo;
    private $read_failed = false;
    private $had_data = false;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function open($path, $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id) {
        $this->read_failed = false;
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $stmt = $this->pdo->prepare("SELECT session_data FROM sessions WHERE session_id = ? AND expires_at > ?");
                $stmt->execute([$id, time()]);
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $data = (string)$row['session_data'];
                    $this->had_data = ($data !== '');
                    return $data;
                }
                return '';
            } catch (Exception $e) {
                $this->read_failed = true;
                usleep(50000);
            }
        }
        return '';
    }

    public function write($id, $data): bool {
        // Never overwrite a valid session in the DB with empty data if read() failed due to a transient lock
        if ($data === '' && $this->read_failed) {
            return true;
        }
        try {
            $expires = time() + (86400 * 2); // 2 days expiration
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $stmt = $this->pdo->prepare("INSERT INTO sessions (session_id, session_data, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE session_data = VALUES(session_data), expires_at = VALUES(expires_at)");
            } else {
                if ($data === '' && !$this->had_data) {
                    // Avoid writing empty rows if an existing valid row is already stored for this session_id
                    $chk = $this->pdo->prepare("SELECT session_data FROM sessions WHERE session_id = ?");
                    $chk->execute([$id]);
                    $existing = $chk->fetchColumn();
                    if (!empty($existing)) {
                        return true;
                    }
                }
                $stmt = $this->pdo->prepare("REPLACE INTO sessions (session_id, session_data, expires_at) VALUES (?, ?, ?)");
            }
            $stmt->execute([$id, $data, $expires]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function destroy($id): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE session_id = ?");
            $stmt->execute([$id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    #[\ReturnTypeWillChange]
    public function gc($max_lifetime) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE expires_at < ?");
            $stmt->execute([time()]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            return false;
        }
    }

    public function validateId($id): bool {
        // Always accept session IDs so strict_mode does not drop sessions across serverless containers
        return true;
    }

    public function updateTimestamp($id, $data): bool {
        return $this->write($id, $data);
    }
}

// Run schema migrations only when SQLite schema version is older than RMS_SCHEMA_VERSION
if (isset($pdo)) {
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $current_ver = (int)$pdo->query("PRAGMA user_version")->fetchColumn();
            if ($current_ver < RMS_SCHEMA_VERSION) {
                run_schema_migrations($pdo);
                $pdo->exec("PRAGMA user_version = " . RMS_SCHEMA_VERSION);
            }
            if (!empty($is_serverless) && !empty($sqlite_file)) {
                clearstatcache(true, $sqlite_file);
                $GLOBALS['rms_initial_db_hash'] = @md5_file($sqlite_file);
                register_shutdown_function('rms_sync_db_to_cloud');
            }
        } else {
            // For MySQL, ensure sessions table exists so session saves never fail
            $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
                session_id VARCHAR(128) PRIMARY KEY,
                session_data MEDIUMTEXT,
                expires_at INT,
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB");
        }
    } catch (Exception $e) {
        // Silently log or ignore if DB is locked
    }
}

// Register the Database Session Handler if PDO exists
if (isset($pdo) && session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $session_pdo = $pdo;
    if (!empty($is_serverless) && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        try {
            $sess_file = sys_get_temp_dir() . '/hwange_sessions.sqlite';
            $session_pdo = new PDO("sqlite:" . $sess_file, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $session_pdo->exec("PRAGMA busy_timeout = 3000");
            $session_pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
                session_id VARCHAR(128) PRIMARY KEY,
                session_data TEXT,
                expires_at INTEGER
            )");
        } catch (Exception $e) {
            $session_pdo = $pdo;
        }
    }
    session_set_save_handler(new DatabaseSessionHandler($session_pdo), true);
}

