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
$hash = $_GET['hash'] ?? '';
$type = $_GET['type'] ?? 'baptism';

$record = null;
$verified = false;

if (!empty($id) && !empty($hash)) {
    // Determine table based on type
    $table = 'baptisms';
    $id_col = 'baptism_id';
    
    if ($type === 'marriage') { $table = 'marriages'; $id_col = 'marriage_id'; }
    if ($type === 'confirmation') { $table = 'confirmations'; $id_col = 'confirmation_id'; }
    if ($type === 'death') { $table = 'deaths'; $id_col = 'death_id'; }

    $sql = "SELECT * FROM $table WHERE $id_col = ? AND verification_hash = ?";
    $record = db_fetch($sql, [$id, $hash]);
    
    if ($record) {
        $verified = true;
        
        // Fetch Person Name
        $p_id = $record['person_id'] ?? ($record['groom_person_id'] ?? 0);
        $person = db_fetch("SELECT first_name, last_name FROM parishioners WHERE person_id = ?", [$p_id]);
        $record['name'] = $person['first_name'] . ' ' . $person['last_name'];
        
        // Fetch Parish Name
        $parish = db_fetch("SELECT parish_name FROM parishes WHERE parish_id = ?", [$record['parish_id']]);
        $record['parish_name'] = $parish['parish_name'];
    }
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
                    <span class="value"><?php echo strtoupper($type); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Full Name</span>
                    <span class="value"><?php echo h($record['name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Parish / Mission</span>
                    <span class="value"><?php echo h($record['parish_name']); ?></span>
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
