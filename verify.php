<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Public Certificate Verification Portal
 * 
 * This page allows authorities to verify the authenticity of a 
 * certificate by scanning the QR code or searching by hash.
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';

$id = $_GET['id'] ?? 0;
$hash = trim($_GET['hash'] ?? '');
$guid = trim($_GET['guid'] ?? '');
$type = strtolower(trim($_GET['type'] ?? 'baptism'));

$record = null;
$verified = false;

// Table & Column mapping
$type_map = [
    'baptism'      => ['table' => 'baptisms', 'id_col' => 'baptism_id', 'date_col' => 'date_of_baptism', 'label' => 'Baptism'],
    'marriage'     => ['table' => 'marriages', 'id_col' => 'marriage_id', 'date_col' => 'date_of_marriage', 'label' => 'Holy Matrimony'],
    'confirmation' => ['table' => 'confirmations', 'id_col' => 'confirmation_id', 'date_col' => 'date_of_confirmation', 'label' => 'Confirmation'],
    'death'        => ['table' => 'deaths', 'id_col' => 'death_id', 'date_col' => 'date_of_death', 'label' => 'Christian Burial'],
    'burial'       => ['table' => 'deaths', 'id_col' => 'death_id', 'date_col' => 'date_of_death', 'label' => 'Christian Burial'],
    'reception'    => ['table' => 'receptions', 'id_col' => 'reception_id', 'date_col' => 'date_of_reception', 'label' => 'Profession of Faith'],
    'communion'    => ['table' => 'first_holy_communions', 'id_col' => 'communion_id', 'date_col' => 'date_of_communion', 'label' => 'First Holy Communion'],
    'ordination'   => ['table' => 'ordinations_professions', 'id_col' => 'record_id', 'date_col' => 'event_date', 'label' => 'Holy Orders / Profession']
];

$config = $type_map[$type] ?? $type_map['baptism'];
$table = $config['table'];
$id_col = $config['id_col'];
$date_col = $config['date_col'];
$type_label = $config['label'];

if (!empty($guid)) {
    $record = db_fetch("SELECT * FROM $table WHERE guid = ?", [$guid]);
}

if (!$record && !empty($id) && !empty($hash)) {
    $record = db_fetch("SELECT * FROM $table WHERE $id_col = ? AND verification_hash = ?", [$id, $hash]);
}

if (!$record && !empty($hash)) {
    $record = db_fetch("SELECT * FROM $table WHERE verification_hash = ?", [$hash]);
}

if (!$record && !empty($guid)) {
    // Search across all tables if type was mismatched
    foreach ($type_map as $alt_type => $alt_cfg) {
        $alt_table = $alt_cfg['table'];
        $alt_rec = db_fetch("SELECT * FROM $alt_table WHERE guid = ?", [$guid]);
        if ($alt_rec) {
            $record = $alt_rec;
            $table = $alt_table;
            $id_col = $alt_cfg['id_col'];
            $date_col = $alt_cfg['date_col'];
            $type_label = $alt_cfg['label'];
            break;
        }
    }
}

if ($record) {
    $verified = true;
    
    // Fetch Person Name(s)
    if ($table === 'marriages') {
        $groom = db_fetch("SELECT first_name, last_name FROM parishioners WHERE person_id = ?", [$record['groom_person_id'] ?? 0]);
        $bride = db_fetch("SELECT first_name, last_name FROM parishioners WHERE person_id = ?", [$record['bride_person_id'] ?? 0]);
        $g_name = $groom ? ($groom['first_name'] . ' ' . $groom['last_name']) : 'Groom';
        $b_name = $bride ? ($bride['first_name'] . ' ' . $bride['last_name']) : 'Bride';
        $record['name'] = "$g_name & $b_name";
    } else {
        $p_id = $record['person_id'] ?? 0;
        $person = db_fetch("SELECT first_name, last_name FROM parishioners WHERE person_id = ?", [$p_id]);
        $record['name'] = $person ? ($person['first_name'] . ' ' . $person['last_name']) : 'Canonical Subject';
    }
    
    // Fetch Parish Name
    $parish = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$record['parish_id'] ?? 0]);
    $record['parish_name'] = $parish['parish_name'] ?? 'Diocese of Hwange';
    $record['event_date_formatted'] = !empty($record[$date_col]) ? date('d F Y', strtotime($record[$date_col])) : 'Recorded';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification - Hwange Diocese</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { --accent: #38bdf8; --success: #22c55e; --error: #ef4444; }
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: white; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .verify-card { background: #1e293b; padding: 40px; border-radius: 24px; width: 100%; max-width: 450px; text-align: center; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .diocese-seal { width: 80px; height: 80px; margin-bottom: 20px; }
        .status-header { margin-bottom: 30px; }
        .status-icon { font-size: 5rem; margin-bottom: 20px; }
        .status-icon.verified { color: var(--success); }
        .status-icon.failed { color: var(--error); }
        .status-title { font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin: 0; }
        .record-details { background: rgba(255,255,255,0.03); border-radius: 16px; padding: 25px; margin-top: 30px; text-align: left; border: 1px solid rgba(255,255,255,0.05); }
        .detail-row { margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; }
        .detail-row:last-child { border-bottom: none; margin-bottom: 0; }
        .label { font-size: 0.7rem; text-transform: uppercase; color: #64748b; letter-spacing: 1px; display: block; margin-bottom: 4px; }
        .value { font-family: 'Outfit', sans-serif; font-size: 1.1rem; color: white; display: block; }
        .official-footer { margin-top: 40px; font-size: 0.75rem; color: #475569; }
    </style>
</head>
<body>
    <div class="verify-card">
        <div class="status-header">
            <?php if ($verified): ?>
                <div class="status-icon verified"><ion-icon name="checkmark-circle"></ion-icon></div>
                <h1 class="status-title">Authentic Record</h1>
                <p style="color: #94a3b8; font-size: 0.9rem; margin-top: 8px;">The Hwange Diocese Registry confirms this canonical document as authentic.</p>
            <?php else: ?>
                <div class="status-icon failed"><ion-icon name="alert-circle"></ion-icon></div>
                <h1 class="status-title">Verification Failed</h1>
                <p style="color: #94a3b8; font-size: 0.9rem; margin-top: 8px;">The provided digital fingerprint does not match any record in the Diocesan Registry.</p>
            <?php endif; ?>
        </div>

        <?php if ($verified): ?>
            <div class="record-details">
                <div class="detail-row">
                    <span class="label">Sacrament Type</span>
                    <span class="value"><?php echo h($type_label); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Canonical Name(s)</span>
                    <span class="value"><?php echo h($record['name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Parish / Mission</span>
                    <span class="value"><?php echo h($record['parish_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Celebration Date</span>
                    <span class="value"><?php echo h($record['event_date_formatted']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Canonical Status</span>
                    <span class="value" style="color: var(--success); font-weight: 800;"><?php echo strtoupper($record['status']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="official-footer">
            <p>© <?php echo date('Y'); ?> Catholic Diocese of Hwange - Zimbabwe</p>
            <p>Archives & Chancery Division - Digital Registry Services</p>
        </div>
    </div>
</body>
</html>
