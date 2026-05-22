<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Helper Functions & Session Management
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2FA Security Engine
require_once 'totp_helper.php';
require_once __DIR__ . '/config_diocese.php';

/**
 * Convert a datetime string into a human-readable "time ago" string
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

/**
 * Calculate age at a specific point in time (canonical accuracy)
 * Returns a string (e.g. "24 years" or "3 months") or null
 */
function get_age($dob, $reference_date = null) {
    if (!$dob || $dob === '0000-00-00') return null;
    
    try {
        $birthDate = new DateTime($dob);
        $targetDate = $reference_date ? new DateTime($reference_date) : new DateTime();
        
        if ($targetDate < $birthDate) return "Infant"; 

        $diff = $targetDate->diff($birthDate);
        
        if ($diff->y >= 18) {
            return $diff->y . ($diff->y == 1 ? " year" : " years");
        } elseif ($diff->y >= 1) {
            $parts = [];
            $parts[] = $diff->y . ($diff->y == 1 ? " year" : " years");
            if ($diff->m > 0) {
                $parts[] = $diff->m . ($diff->m == 1 ? " month" : " months");
            }
            return implode(', ', $parts);
        } elseif ($diff->m > 0) {
            $parts = [];
            $parts[] = $diff->m . ($diff->m == 1 ? " month" : " months");
            if ($diff->d > 0) {
                $parts[] = $diff->d . ($diff->d == 1 ? " day" : " days");
            }
            return implode(', ', $parts);
        } else {
            return $diff->d . ($diff->d == 1 ? " day" : " days");
        }
    } catch (Exception $e) {
        return null;
    }
}
/**
 * Format a date for certificate display with superscript ordinal suffixes
 * Example: 25th -> 25<sup>TH</sup>
 */
function format_certificate_date($date_string) {
    if (!$date_string || $date_string === '0000-00-00') return '-';
    $time = strtotime($date_string);
    $day = date('j', $time);
    $suffix = strtoupper(date('S', $time));
    $month = strtoupper(date('F', $time));
    $year = date('Y', $time);
    return "{$day}<sup>{$suffix}</sup> OF {$month}, {$year}";
}

/**
 * Calculate the relative path to the root directory
 */
function get_base_url() {
    $script_path = $_SERVER['PHP_SELF'];
    $folders = ['/sacraments/', '/dashboard/', '/admin/', '/parishioners/', '/actions/', '/profile/', '/auth/'];
    foreach ($folders as $folder) {
        if (strpos($script_path, $folder) !== false) {
            return '../';
        }
    }
    return '';
}

/**
 * Check if a user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Require a user to be logged in, else redirect to login
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: ../index.php?error=unauthorized");
        exit;
    }
}

/**
 * Require a specific role for access
 */
function require_role($role) {
    require_login();
    $user_role = strtolower($_SESSION['role'] ?? '');
    // Observers can access anything that a priest/deacon can view, but require_role is usually for mutations
    if ($user_role !== strtolower($role) && $user_role !== 'admin' && $user_role !== 'chancellor') {
        header("Location: " . get_base_url() . "dashboard/index.php?error=access_denied");
        exit;
    }
}

/**
 * Check if the current user can EDIT records
 */
function can_edit() {
    $role = strtolower($_SESSION['role'] ?? '');
    return in_array($role, ['admin', 'chancellor', 'priest', 'deacon', 'secretary']);
}

/**
 * Require editing rights
 */
function require_editor() {
    require_login();
    if (!can_edit()) {
        header("Location: " . get_base_url() . "dashboard/observer.php?error=read_only");
        exit;
    }
}

/**
 * Check if the current user is a Diocesan Admin
 */
function is_admin() {
    $role = strtolower($_SESSION['role'] ?? '');
    return $role === 'admin' || $role === 'chancellor';
}

/**
 * Check if the current user is a Cleric (Priest/Deacon)
 */
function is_cleric() {
    $role = strtolower($_SESSION['role'] ?? '');
    return $role === 'priest' || $role === 'deacon' || $role === 'admin' || $role === 'chancellor';
}

/**
 * Require Admin access for the current page
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        header("Location: " . get_base_url() . "dashboard/index.php?error=access_denied");
        exit;
    }
}

// Aliases for backward compatibility if any
function isDiocesanAdmin() { return is_admin(); }
function isAdmin() { return is_admin(); }
function requireLogin() { require_login(); }

/**
 * Log an action to the audit trail
 */
/**
 * Get localized text for Sacramental Certificates
 * Supports: English (en), Tonga (to), Ndebele (nd), Shona (sh)
 */
function get_certificate_text($lang = 'en') {
    $texts = [
        'en' => [
            'diocese' => get_diocese_branding(),
            'country' => get_country_branding(),
            'cert_baptism' => 'Certificate of Baptism',
            'cert_marriage' => 'Certificate of Holy Matrimony',
            'cert_confirmation' => 'Certificate of Confirmation',
            'cert_communion' => 'Certificate of First Holy Communion',
            'cert_death' => 'Certificate of Christian Burial',
            'certify' => 'This is to certify that',
            'born_at' => 'Born at',
            'on_date' => 'on the',
            'baptized' => 'The Sacrament of Baptism',
            'confirmed' => 'The Sacrament of Confirmation',
            'married' => 'The Sacrament of Holy Matrimony',
            'communion' => 'The Sacrament of First Holy Communion',
            'buried' => 'Given Christian Burial',
            'in_parish' => 'in the Parish of',
            'minister' => 'Minister of Sacraments',
            'officiant' => 'Officiant',
            'godparents' => 'Godparent',
            'sponsors' => 'Sponsors',
            'witnesses' => 'Witnesses',
            'father' => 'Father',
            'mother' => 'Mother',
            'spouse' => 'Spouse',
            'date_issue' => 'Date of Issue',
            'given_at' => 'Given at the Diocesan Office, Hwange this',
            'priest' => 'Parish Priest / Administrator',
            'seal_text' => 'Official Diocesan Archival Seal - ZCRE Verified',
            'canonical_ref' => 'Registry Reference',
            'full_name' => 'Full Name',
            'born_on' => 'Born on the',
            'solemnly_received' => 'solemnly received',
            'groom' => 'Groom (Husband)',
            'bride' => 'Bride (Wife)',
            'and' => 'and',
            'joined_in' => 'were solemnly joined in',
            'confirmand' => 'Confirmand',
            'prev_baptized' => 'having been previously Baptized,',
            'communion_name' => 'Full Name of Parishioner',
            'prepared_catechized' => 'Having been duly prepared and catechized,',
            'deceased_name' => 'Full Name of Deceased',
            'departed' => 'departed this life',
            'was_solemnly' => 'and was solemnly',
        ],
        'to' => [ // Tonga
            'diocese' => 'Diyoziisi ya Katolika ya Hwange',
            'country' => 'Cisi ca Zimbabwe',
            'cert_baptism' => 'Cisumbya ca Lulubatizo',
            'cert_marriage' => 'Cisumbya ca Cikwati Cisalala',
            'cert_confirmation' => 'Cisumbya ca Kusungidwa',
            'cert_communion' => 'Cisumbya ca Komuniyo Yakusaanguna',
            'certify' => 'Oolu ndilusalazyu lwakuti',
            'born_at' => 'Wakazyalilwa ku',
            'on_date' => 'mubuzuba bwa',
            'baptized' => 'Wakalubatizigwa',
            'confirmed' => 'Wakasungidwa',
            'married' => 'Wakasunganyigwa mu Cikwati',
            'communion' => 'Wakatambula Komuniyo Yakusaanguna',
            'in_parish' => 'mu Parishi ya',
            'minister' => 'Mupaili',
            'godparents' => 'Muzyali wa Kumuuya',
            'father' => 'Bauso',
            'mother' => 'Banyina',
            'priest' => 'Mupaili uulangania Parishi',
        ],
        'nd' => [ // Ndebele
            'diocese' => 'Idayozisi yeKhatholika yeHwange',
            'country' => 'Ilizwe laseZimbabwe',
            'cert_baptism' => 'Isitifiketi soBhabhatizo',
            'cert_marriage' => 'Isitifiketi somTshado oNgcwele',
            'cert_confirmation' => 'Isitifiketi sokuQiniseka',
            'certify' => 'Lokhu kuyafakaza ukuthi',
            'born_at' => 'Wazalelwa e',
            'on_date' => 'ngelanga lika',
            'baptized' => 'Wabhabhatizwa',
            'confirmed' => 'Waqiniswa',
            'married' => 'Wahlanganiswa emTshadweni',
            'in_parish' => 'ePharishi le',
            'minister' => 'Umphristi',
            'father' => 'Uyise',
            'mother' => 'Unina',
        ],
        'sh' => [ // Shona
            'diocese' => 'Dhiyozisi yeKatorike yeHwange',
            'country' => 'Nyika yeZimbabwe',
            'cert_baptism' => 'Chitupa choRubhabhatidzo',
            'cert_marriage' => 'Chitupa choMuchato Unoyera',
            'certify' => 'Izvi ndezvokupupura kuti',
            'born_at' => 'Akazvarirwa pa',
            'on_date' => 'nezuva ra',
            'baptized' => 'Akabhabhatidzwa',
            'in_parish' => 'muParishi ye',
            'minister' => 'Muprista',
            'father' => 'Baba',
            'mother' => 'Amai',
        ],
        'nb' => [ // Nambya (Authentic Diocesan)
            'diocese' => 'Diyoziisi yeKhatholika yeHwange',
            'country' => 'Ilizwe leZimbabwe',
            'cert_baptism' => 'Chitupa chombhabhatiso',
            'cert_marriage' => 'Chitupa chokulobolana kuchena',
            'certify' => 'Yozu zunosumikija kuti',
            'born_at' => 'Wakazwalilwa ku',
            'on_date' => 'Nonsi wa',
            'baptized' => 'Wakabhabhatiswa',
            'in_parish' => 'Kugubungano lyoku',
            'minister' => 'Unshingili/Untebuji',
            'father' => 'Dade',
            'mother' => 'Amai',
        ],
        'cw' => [ // Chewa
            'diocese' => 'Dayosizi ya Katolika ya Hwange',
            'country' => 'Dziko la Zimbabwe',
            'cert_baptism' => 'Setifikeiti ya Ubatizo',
            'cert_marriage' => 'Setifikeiti ya Ukwati Woyera',
            'certify' => 'Izi ndi zotsimikizira kuti',
            'born_at' => 'Anabadwira ku',
            'on_date' => 'pa tsiku la',
            'baptized' => 'Anabatizidwa',
            'in_parish' => 'mu Parishi ya',
            'minister' => 'Mbusa',
            'father' => 'Atate',
            'mother' => 'Amayi',
        ],
        'la' => [ // Latin (Traditional Church Language)
            'diocese' => 'Dioecesis Hwangeensis',
            'country' => 'Zimbabuia',
            'cert_baptism' => 'Testimonium Baptismi',
            'cert_marriage' => 'Testimonium Matrimonii',
            'cert_confirmation' => 'Testimonium Confirmationis',
            'cert_communion' => 'Testimonium Primae Communionis',
            'cert_death' => 'Testimonium Sepulturae Christianae',
            'certify' => 'His litteris testamur',
            'born_at' => 'Natum/am in',
            'on_date' => 'die',
            'baptized' => 'Sacramentum Baptismi recepisse',
            'confirmed' => 'Sacramentum Confirmationis recepisse',
            'married' => 'Sacramentum Matrimonii contraxisse',
            'communion' => 'Sacramentum Primae Communionis recepisse',
            'buried' => 'Sepultura Christiana donatum/am esse',
            'in_parish' => 'in Paroecia',
            'minister' => 'Minister Sacramentorum',
            'officiant' => 'Officians',
            'godparents' => 'Patrini',
            'sponsors' => 'Sponsores',
            'witnesses' => 'Testes',
            'father' => 'Pater',
            'mother' => 'Mater',
            'spouse' => 'Sponsus/Sponsa',
            'date_issue' => 'Datum die',
            'given_at' => 'Datum ex officio dioecesano, Hwange, die',
            'priest' => 'Parochus / Administrator',
            'seal_text' => 'Sigillum Archivi Dioecesani - ZCRE Verificatum',
            'canonical_ref' => 'Referentia Archivi',
            'full_name' => 'Nomen Integrum',
            'born_on' => 'Natus/a die',
            'solemnly_received' => 'sollemniter suscepit',
            'groom' => 'Sponsus',
            'bride' => 'Sponsa',
            'and' => 'et',
            'joined_in' => 'in matrimonio coniuncti sunt in',
            'confirmand' => 'Confirmandus/a',
            'prev_baptized' => 'antea baptizatus/a,',
            'communion_name' => 'Nomen Communicantis',
            'prepared_catechized' => 'Debite praeparatus/a et catechizatus/a,',
            'deceased_name' => 'Nomen Defuncti',
            'departed' => 'ex hac vita migravit',
            'was_solemnly' => 'et sollemniter',
        ]
    ];

    // Fallback to English if language or specific key is missing
    $merged = array_merge($texts['en'], $texts[$lang] ?? []);
    
    // Inject Dynamic Branding if not already provided by the language array
    if ($lang !== 'la' && $lang !== 'nb' && $lang !== 'to' && $lang !== 'nd' && $lang !== 'sh' && $lang !== 'cw') {
        $merged['diocese'] = get_diocese_branding();
        $merged['country'] = get_country_branding();
    }
    
    return $merged;
}

/**
 * Log an action to the audit trail
 */
function log_audit($user_id, $action, $table, $record_id, $details = '') {
    $sql = "INSERT INTO audit_logs (user_id, action_type, table_name, record_id, details) 
            VALUES (?, ?, ?, ?, ?)";
    db_query($sql, [$user_id, $action, $table, $record_id, $details]);
}

/**
 * Redirect to a specific page
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Sanitize and force uppercase for canonical data
 */
function upper($string) {
    if (is_array($string)) return $string;
    return mb_strtoupper(trim((string)$string), 'UTF-8');
}

/**
 * Sanitize output data (Aliases for legacy)
 */
function h($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}
function e($string) { return h($string); }

/**
 * Log an action (Legacy Alias)
 */
function logAction($details, $table = 'system', $record_id = 0) {
    $user_id = $_SESSION['user_id'] ?? 0;
    log_audit($user_id, 'Action', $table, $record_id, $details);
}

/**
 * Flash messages (for success/error alerts)
 */
function set_flash($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Get SQL fragment for parish identification (Isolation)
 * Returns AND parish_id = ? if not admin
 */
function get_parish_filter(&$params, $table_prefix = '') {
    if (is_admin()) {
        return "";
    }
    $params[] = $_SESSION['parish_id'];
    $column = !empty($table_prefix) ? "$table_prefix.parish_id" : "parish_id";
    return " AND $column = ? ";
}

/**
 * Check if the user has specific access to a record from a parish
 * OBSOLETE: Use has_record_permission() for more granular status-aware checks
 */
function has_record_access($record_parish_id, $action = 'view') {
    if (!is_logged_in()) return false;
    
    $user_role = strtolower($_SESSION['role'] ?? '');
    $user_parish_id = $_SESSION['parish_id'] ?? null;

    // Admins and Chancellors have full access to everything
    if ($user_role === 'admin' || $user_role === 'chancellor') {
        return true;
    }

    // Parish owners
    if ($record_parish_id == $user_parish_id && !empty($user_parish_id)) {
        return true;
    }

    // Cross-parish view for clergy
    if ($action === 'view') {
        return (strpos($user_role, 'priest') !== false || strpos($user_role, 'deacon') !== false);
    }

    return false;
}

/**
 * Granular Permission Engine for Sacramental Records
 * @param array $record The database record row
 * @param string $action 'view', 'edit', 'print', 'verify', 'delete'
 */
function has_record_permission($record, $action = 'view') {
    if (!is_logged_in()) return false;
    
    $user_role = strtolower($_SESSION['role'] ?? '');
    $user_parish_id = $_SESSION['parish_id'] ?? null;
    $record_parish_id = $record['parish_id'] ?? null;
    $status = $record['status'] ?? 'Verified';

    // 1. Super Admins
    if ($user_role === 'admin' || $user_role === 'chancellor') return true;

    // 2. Ownership Check (Same Parish)
    $is_owner = ($record_parish_id == $user_parish_id && !empty($user_parish_id));

    if (!$is_owner) {
        // Cross-parish rules: Priests/Deacons can view anything in the Diocese for verification
        if ($action === 'view') {
           return (strpos($user_role, 'priest') !== false || strpos($user_role, 'deacon') !== false);
        }
        return false;
    }

    // 3. Parish-Specific Rules
    switch ($action) {
        case 'view':
        case 'print':
            return true;

        case 'edit':
            // RULE: Verified records are LOCKED for Secretaries. 
            // Only Priests, Deacons or Admins can unlock/modify.
            if ($status === 'Verified') {
                return ($user_role === 'priest' || $user_role === 'deacon');
            }
            return true; // Everyone in parish can edit 'Draft' records

        case 'verify':
            // RULE: Only clergy can verify canonical data
            return ($user_role === 'priest' || $user_role === 'deacon');

        case 'delete':
            // RULE: No one at the parish level can permanently delete.
            // This ensures canonical persistence. Only Diocesan Admin (Chancery) can hard-delete.
            return ($user_role === 'admin' || $user_role === 'chancellor');

        default:
            return false;
    }
}

function display_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo "<div class='alert alert-{$flash['type']}'>{$flash['message']}</div>";
    }
}

/**
 * Handle Session Timeout (Auto-Logout)
 */
function handle_session_timeout($timeout_seconds = 7200) { // 2 hours (for exhaustive archival tasks)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_seconds)) {
        session_unset();
        session_destroy();
        header("Location: ../index.php?error=session_expired");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Get Localized Label for Certificates
 */
function get_localized_label($key, $lang = 'en') {
    $translations = [
        'cert_title' => [
            'en' => 'Certificate of Baptism',
            'nd' => 'Isitifiketi Sokubhabhatidzwa',
            'sh' => 'Chitupa cheRubhabhatidzo',
            'nb' => 'Chitupa chombhabhatiso',
            'to' => 'Chitupa chaKubbapatizigwa',
            'cw' => 'Setifikeiti ya Ubatizo',
            'la' => 'Testimonium Baptismi'
        ],
        'marriage_cert_title' => [
            'en' => 'Certificate of Marriage',
            'nd' => 'Isitifiketi Somtshado',
            'sh' => 'Chitupa cheMuchato',
            'nb' => 'Chitupa chokulobolana kuchena',
            'to' => 'Chitupa chaLukwatano',
            'cw' => 'Setifikeiti ya Ukwati',
            'la' => 'Testimonium Matrimonii'
        ],
        'certify' => [
            'en' => 'This is to certify that',
            'nd' => 'Lokhu kuyafakaza ukuthi',
            'sh' => 'Izvi ndezvokupupura kuti',
            'nb' => 'Yozu zunosumikija kuti',
            'to' => 'Oolu ndilusalazyu lwakuti',
            'cw' => 'Izi ndi zotsimikizira kuti',
            'la' => 'His litteris testamur'
        ],
        'priest' => [
            'en' => 'Parish Priest / Administrator',
            'nd' => 'Umphristi ophetheyo',
            'sh' => 'Muprista anotungamira',
            'nb' => 'Umpristi',
            'to' => 'Mupaili uulangania',
            'cw' => 'Mbusa',
            'la' => 'Parochus / Administrator'
        ],
        'full_name' => [
            'en' => 'Full Name',
            'nd' => 'Ibizo Elipheleleyo',
            'sh' => 'Zita rakazara',
            'nb' => 'Zina Izhele',
            'to' => 'Izina lyoonse',
            'cw' => 'Dzina lathunthu',
            'la' => 'Nomen Integrum'
        ],
        'date_of_birth' => [
            'en' => 'Date of Birth',
            'nd' => 'Usuku Lokuzalwa',
            'sh' => 'Zuva rokuzvarwa',
            'nb' => 'Izhuba lyokuzwalwa',
            'to' => 'Buzuba bwaKuzyalwa',
            'cw' => 'Tsiku loBadwa',
            'la' => 'Dies Nativitatis'
        ],
        'place_of_birth' => [
            'en' => 'Place of Birth',
            'nd' => 'INDAWO YOKUZALWA',
            'sh' => 'Nzvimbo yokuberekwa',
            'nb' => 'Butala bwa Kuzwalwa',
            'to' => 'Busena bwaKuzyalwa',
            'cw' => 'Malo a Badwa',
            'la' => 'Locus Nativitatis'
        ],
        'date_of_baptism' => [
            'en' => 'Date of Baptism',
            'nd' => 'Usuku Lokubhabhathizwa',
            'sh' => 'Zuva rerubhabhatidzo',
            'nb' => 'Izhuba lyoKubhabhatidzwa',
            'to' => 'Buzuba bwaKubbapatizigwa',
            'cw' => 'Tsiku la Ubatizo',
            'la' => 'Dies Baptismi'
        ],
        'register_ref' => [
            'en' => 'Register Ref',
            'nd' => 'Inkomba yeRejista',
            'sh' => 'Inkomba yeRejista',
            'nb' => 'Butala bwe Rejista',
            'to' => 'Inkomba yeRejista',
            'cw' => 'Inkomba yeRejista',
            'la' => 'Referentia Archivi'
        ],
        'date_of_marriage' => [
            'en' => 'Date of Marriage',
            'nd' => 'Usuku loMtshado',
            'sh' => 'Zuva remuchato',
            'nb' => 'Izhuba lyoLukwatano',
            'to' => 'Buzuba bwaLukwatano',
            'cw' => 'Tsiku la Ukwati',
            'la' => 'Dies Matrimonii'
        ],
        'minister_officiant' => [
            'en' => 'Minister of Sacraments/Officiant',
            'nd' => 'Umukhokheli / Umpristi',
            'sh' => 'Mupristi / Mushumiri',
            'nb' => 'Umpristi / Mushumiri',
            'to' => 'Mupaili',
            'cw' => 'Mbusa',
            'la' => 'Minister Sacramentorum'
        ],
        'witnesses' => [
            'en' => 'Witnesses',
            'nd' => 'Abafakazi',
            'sh' => 'Zvapupu',
            'nb' => 'Bakamboni',
            'to' => 'Bakamboni',
            'cw' => 'Mboni',
            'la' => 'Testes'
        ],
        'date_of_confirmation' => [
            'en' => 'Date of Confirmation',
            'nd' => 'Usuku lokuNcinciswa',
            'sh' => 'Zuva reSimbiso',
            'nb' => 'Izhuba lyoZizhibizho',
            'to' => 'Buzuba bwaZizhibizho',
            'cw' => 'Tsiku la Simbiso',
            'la' => 'Dies Confirmationis'
        ],
        'sponsor' => [
            'en' => 'Sponsor',
            'nd' => 'Umsekeli',
            'sh' => 'Muperekedzi',
            'nb' => 'Mukuzhi',
            'to' => 'Mukuzhi',
            'cw' => 'Sponsor',
            'la' => 'Patrinus'
        ],
        'date_of_death' => [
            'en' => 'Date of Death',
            'nd' => 'Usuku lokuFa',
            'sh' => 'Zuva rekufa',
            'nb' => 'Izhuba lyoKufa',
            'to' => 'Buzuba bwaKufwa',
            'cw' => 'Tsiku loMwalira',
            'la' => 'Dies Obitus'
        ],
        'place_of_burial' => [
            'en' => 'Place of Burial',
            'nd' => 'Indawo yokuGatshwa',
            'sh' => 'Nzvimbo yokuvigwa',
            'nb' => 'Butala bwoKuzhalwa',
            'to' => 'Busena bwaKuzhalwa',
            'cw' => 'Malo o Ikhwa',
            'la' => 'Locus Sepulturae'
        ],
        'father' => [
            'en' => 'Father',
            'nd' => 'Uyise',
            'sh' => 'Baba',
            'nb' => 'Dade',
            'to' => 'Ba-tata',
            'cw' => 'Atate',
            'la' => 'Pater'
        ],
        'mother' => [
            'en' => 'Mother',
            'nd' => 'Unina',
            'sh' => 'Mai',
            'nb' => 'Amai',
            'to' => 'Ba-mama',
            'cw' => 'Amayi',
            'la' => 'Mater'
        ],
        'godparents' => [
            'en' => 'Godparents',
            'nd' => 'Abafakazi',
            'sh' => 'Vabereki veMweya',
            'nb' => 'Bazwali boMpepo',
            'to' => 'Bakamboni',
            'cw' => 'Ankhoswe',
            'la' => 'Patrini'
        ],
        'minister' => [
            'en' => 'Minister of Sacraments/Priest',
            'nd' => 'Umpristi',
            'sh' => 'Mupristi',
            'nb' => 'Umpristi',
            'to' => 'Mupaili',
            'cw' => 'Mbusa',
            'la' => 'Sacerdos/Minister'
        ],
        'parish' => [
            'en' => 'Parish/Mission',
            'nd' => 'Parishi',
            'sh' => 'Parish',
            'nb' => 'Parishi',
            'to' => 'Parish',
            'cw' => 'Parishi',
            'la' => 'Paroecia'
        ],
        'notations_title' => [
            'en' => 'Subsequent Sacramental Notations (Canon 535)',
            'nd' => 'Imibhalo Yeluvuko Lwemizila (Canon 535)',
            'sh' => 'Zviziviso zveMweya (Canon 535)',
            'nb' => 'Zizhibizho zhoMuyo (Canon 535)',
            'to' => 'Bakamboni baMuuya (Canon 535)',
            'cw' => 'Zidziwitso za Sakramenti (Canon 535)',
            'la' => 'Adnotationes Sacramentalium (Canon 535)'
        ],
        'official_notice' => [
            'en' => 'This document is an official extract from the Diocesan Parochial Registers.',
            'nd' => 'Leli phepha liyisiqalo esisemthethweni esivela emizileni yeDiocese.',
            'sh' => 'Gwaro iri chiratidzo chechokwadi chinobva mumanyorerwo eDiocese.',
            'nb' => 'Phepala ili nelyechizhibizho choKuZhinji choMuParishi.',
            'to' => 'Pepa eli nelyabukkamboni bwakasilumamba bwaParish.',
            'cw' => 'Chikalata ichi ndi umbono weniweni wochokera m`mabuku a Parish.',
            'la' => 'Hoc documentum est extractum authenticum ex Registris Paroecialibus Dioecesanis.'
        ],
        'no_notations' => [
            'en' => '[No subsequent notations recorded at time of issue]',
            'nd' => '[Akula mi-bhalo eyengeziweyo ngalesi sikhathi]',
            'sh' => '[Hapana zvimwe zvakanyorwa panguva ino]',
            'nb' => '[Akuna zizhibizho zhomuyo panguva iyi]',
            'to' => '[Taakuna bakamboni baMuuya kuciindi niciputudwa]',
            'cw' => '[Palibe zidziwitso zina pakanthawi kano]',
            'la' => '[Nullae adnotationes inscriptae sunt ad praesens]'
        ]
    ];

    return $translations[$key][$lang] ?? ($translations[$key]['en'] ?? $key);
}

/**
 * Generic Audit Log Helper
 */
function log_action($action, $table = null, $id = null, $details = null) {
    $user_id = $_SESSION['user_id'] ?? 0;
    if (!$user_id) return;
    
    db_query("INSERT INTO audit_logs (user_id, action_type, table_name, record_id, details) VALUES (?, ?, ?, ?, ?)", 
        [$user_id, $action, $table, $id, $details]);
}

/**
 * Log access to sensitive canonical records (PNI, Clergy Dossiers, etc.)
 */
function log_sensitive_access($table_name, $record_id, $details = '') {
    log_action('SENSITIVE_VIEW', $table_name, $record_id, "Privileged Data Viewed: " . $details);
}

/**
 * Extract a friendly greeting name from a full name (e.g., "Fr. Vincent Banda" -> "Fr. Banda Vincent", "Vincent Banda" -> "Banda Vincent")
 */
function get_user_greeting_name($full_name) {
    $full_name = trim((string)$full_name);
    if (empty($full_name)) return 'Minister';
    
    // Specially handle Diocesan Administrator role/name
    if (strtolower($full_name) === 'diocesan' || strtolower($full_name) === 'diocesan admin' || strtolower($full_name) === 'diocesan administrator' || strtolower($full_name) === 'admin') {
        return 'Diocesan Administrator';
    }
    
    $parts = explode(' ', $full_name);
    // If it starts with common clerical/religious titles
    $titles = ['fr', 'fr.', 'father', 'msgr', 'msgr.', 'mons.', 'bishop', 'bp', 'bp.', 'sr', 'sr.', 'sister', 'deacon', 'rev', 'rev.', 'rev. fr.'];
    
    $title = '';
    if (count($parts) > 1 && in_array(strtolower($parts[0]), $titles)) {
        $title = $parts[0] . ' ';
        array_shift($parts); // Remove the title from parts
    }
    
    if (strtolower($parts[0]) === 'diocesan') {
        return 'Diocesan Administrator';
    }
    
    // Format other users as "Surname Firstname"
    if (count($parts) > 1) {
        $surname = array_pop($parts); // Extract last name/surname
        $firstname = implode(' ', $parts); // Combine remaining part as firstname
        return $title . $surname . ' ' . $firstname;
    }
    
    return $title . $parts[0];
}

