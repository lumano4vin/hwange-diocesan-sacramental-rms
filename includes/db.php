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
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'hwange_diocesan_records';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: ''; 
$charset = 'utf8mb4';

$sqlite_file = dirname(__DIR__) . "/database.sqlite";
$dsn_mysql = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 5,
];

$use_cloud = getenv('DB_HOST') ? true : false;

// If we are connecting to a cloud database (Aiven/TiDB), enable SSL
if ($use_cloud) {
    $ssl_verify_key = defined('Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') ? Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT : (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') ? PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT : 1014);
    $ssl_ca_key = defined('Pdo\Mysql::ATTR_SSL_CA') ? Pdo\Mysql::ATTR_SSL_CA : (defined('PDO::MYSQL_ATTR_SSL_CA') ? PDO::MYSQL_ATTR_SSL_CA : 1012);
    
    $options[$ssl_verify_key] = false;
    $options[$ssl_ca_key] = '';
}

try {
    // FORCE PORTABLE MODE locally, but bypass SQLite if we are targeting a cloud MySQL database
    if (file_exists($sqlite_file) && !$use_cloud) {
        $pdo = new PDO("sqlite:" . $sqlite_file, null, null, $options);
    } else {
        $pdo = new PDO($dsn_mysql, $user, $pass, $options);
    }
} catch (\PDOException $e) {
    echo "<h1>Database Connection Error</h1>";
    echo "<p>Could not connect to the database. Please ensure your cloud credentials in the '.env' file are correct and SSL connection is allowed.</p>";
    if (ini_get('display_errors')) {
        echo "<small>Error: " . $e->getMessage() . "</small>";
    }
    exit;
}
// 1.2 Self-Healing Schema Check (Auto-Migrate for Updates)
// Wrapped in a function to prevent hanging on every page load when using remote DB
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

    // Column migration for Inter-Parish Comms
    try {
        $pdo->exec("ALTER TABLE communications ADD COLUMN recipient_parish_id INTEGER DEFAULT NULL");
    } catch (Exception $e) {}

    // Column migration for Parishioners
    try {
        $pdo->exec("ALTER TABLE parishioners ADD COLUMN place_of_residence TEXT");
    } catch (Exception $e) {}

    // Column migration for Receptions (Canonical Baptism Metadata)
    try {
        $pdo->exec("ALTER TABLE receptions ADD COLUMN previous_baptism_church VARCHAR(255)");
        $pdo->exec("ALTER TABLE receptions ADD COLUMN previous_baptism_date DATE");
        $pdo->exec("ALTER TABLE receptions ADD COLUMN previous_baptism_place VARCHAR(255)");
    } catch (Exception $e) {}

    // Prenuptial Investigation (PNI) System
    $pdo->exec("CREATE TABLE IF NOT EXISTS prenuptial_investigations (
        pni_id INTEGER PRIMARY KEY,
        groom_id INTEGER NOT NULL,
        bride_id INTEGER NOT NULL,
        parish_id INTEGER NOT NULL,
        priest_user_id INTEGER,
        investigation_date DATE NOT NULL,
        
        -- Freedom to Marry
        groom_free_to_marry INTEGER DEFAULT 0,
        bride_free_to_marry INTEGER DEFAULT 0,
        groom_previous_marriage INTEGER DEFAULT 0,
        bride_previous_marriage INTEGER DEFAULT 0,
        
        -- Consent & Intentions
        consent_unity INTEGER DEFAULT 0,
        consent_indissolubility INTEGER DEFAULT 0,
        consent_procreation INTEGER DEFAULT 0,
        
        -- Banns of Marriage (Canon 1067)
        banns_date_1 DATE,
        banns_date_2 DATE,
        banns_date_3 DATE,
        banns_parish_id INTEGER,
        
        -- Impediments & Dispensations
        impediments_noted TEXT,
        dispensations_required TEXT,
        
        -- Administrative
        status VARCHAR(20) DEFAULT 'Draft',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (groom_id) REFERENCES parishioners(person_id),
        FOREIGN KEY (bride_id) REFERENCES parishioners(person_id),
        FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
    )");

    // Link marriages to PNI
    try {
        $pdo->exec("ALTER TABLE marriages ADD COLUMN pni_id INTEGER DEFAULT NULL");
        $pdo->exec("ALTER TABLE marriages ADD COLUMN baptism_notified_groom INTEGER DEFAULT 0");
        $pdo->exec("ALTER TABLE marriages ADD COLUMN baptism_notified_bride INTEGER DEFAULT 0");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE deaths ADD COLUMN baptism_notified INTEGER DEFAULT 0");
    } catch (Exception $e) {}

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
        total_collections DECIMAL(10,2) DEFAULT 0, -- Sunday collections
        sacramental_offerings DECIMAL(10,2) DEFAULT 0, -- Stole fees
        other_income DECIMAL(10,2) DEFAULT 0,
        assessment_rate DECIMAL(5,2) DEFAULT 10.00, -- Percentage owed to Diocese
        calculated_levy DECIMAL(10,2),
        status VARCHAR(50) DEFAULT 'Draft', -- Draft, Submitted, Approved, Rejected
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
        reference_number VARCHAR(100), -- Receipt # or Bank Ref
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
        session_id VARCHAR(128) PRIMARY KEY,
        session_data TEXT,
        expires_at INTEGER
    )");
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
// CUSTOM DATABASE SESSION HANDLER (Fixes Vercel Logouts)
// ---------------------------------------------------------
class DatabaseSessionHandler implements SessionHandlerInterface {
    private $pdo;

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
        try {
            $stmt = $this->pdo->prepare("SELECT session_data FROM sessions WHERE session_id = ? AND expires_at > ?");
            $stmt->execute([$id, time()]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return $row['session_data'];
            }
        } catch (Exception $e) {}
        return '';
    }

    public function write($id, $data): bool {
        try {
            $expires = time() + (86400 * 2); // 2 days expiration
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $stmt = $this->pdo->prepare("INSERT INTO sessions (session_id, session_data, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE session_data = VALUES(session_data), expires_at = VALUES(expires_at)");
            } else {
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
}

// Register the Database Session Handler if PDO exists
if (isset($pdo) && session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_set_save_handler(new DatabaseSessionHandler($pdo), true);
}
