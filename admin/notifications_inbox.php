<?php
/**
 * Notifications Inbox (Inbound Sacramental Handshake)
 * Parishes receive digital notifications of sacraments performed elsewhere.
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$pdo = getDB();
$parish_id = $_SESSION['parish_id'] ?? null;

// Handle Acknowledgement
if (isset($_POST['action']) && $_POST['action'] === 'acknowledge') {
    $notif_id = $_POST['notif_id'] ?? null;
    $parishioner_id = $_POST['parishioner_id'] ?? null;
    $sacrament_info = $_POST['sacrament_info'] ?? '';
    
    if ($notif_id && $parishioner_id) {
        // Add notation to baptism record
        addMarginalNotation($parishioner_id, "Digital Handshake Acknowledgement: " . $sacrament_info);
        
        // Update notification status
        $stmt = $pdo->prepare("UPDATE sacramental_notifications SET status = 'Acknowledged' WHERE id = ?");
        $stmt->execute([$notif_id]);
        $success = "Notification acknowledged and notation added!";
    }
}

// Fetch Inbound Notifications
$stmt = $pdo->prepare("
    SELECT sn.*, pa.name as sender_parish, p.first_name, p.last_name
    FROM sacramental_notifications sn
    JOIN parishes pa ON sn.sender_parish_id = pa.id
    JOIN parishioners p ON sn.parishioner_id = p.id
    WHERE sn.recipient_parish_id = ?
    ORDER BY sn.created_at DESC
");
$stmt->execute([$parish_id]);
$notifications = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sacramental Inbox - <?php echo get_diocese_branding(); ?> SRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="logo-container"><h2><?php echo get_diocese_branding(); ?> SRMS</h2></div>
            <nav>
                <ul class="nav-links">
                    <li class="nav-item"><a href="../dashboard/index.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="notifications_inbox.php" class="nav-link active"><i class="fa-solid fa-inbox"></i> Inbox</a></li>
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link" style="color: #f56565;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header>
                <div class="page-title">
                    <h1>Inbound Sacramental Notifications</h1>
                    <p style="color: var(--text-muted);">Incoming reports from other parishes regarding parishioners baptized here.</p>
                </div>
            </header>

            <?php if (isset($success)): ?>
                <div style="background: var(--success); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 20px;">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <?php if (empty($notifications)): ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 2rem;">Your parish inbox is empty.</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid var(--border-color);">
                                <th style="padding: 1rem 0.5rem; color: var(--text-muted);">From Parish</th>
                                <th style="padding: 1rem 0.5rem; color: var(--text-muted);">Parishioner</th>
                                <th style="padding: 1rem 0.5rem; color: var(--text-muted);">Sacrament</th>
                                <th style="padding: 1rem 0.5rem; color: var(--text-muted);">Status</th>
                                <th style="padding: 1rem 0.5rem; color: var(--text-muted);">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $n): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 1rem 0.5rem; font-weight: 600;"><?= e($n['sender_parish']) ?></td>
                                    <td style="padding: 1rem 0.5rem;"><?= e($n['first_name'] . ' ' . $n['last_name']) ?></td>
                                    <td style="padding: 1rem 0.5rem;">
                                        <span class="badge" style="background: var(--primary-color); color: white;"><?= e($n['sacrament_type']) ?></span>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 5px;">Recorded on: <?= formatDate($n['date_sent']) ?></div>
                                    </td>
                                    <td style="padding: 1rem 0.5rem;">
                                        <?php if ($n['status'] === 'Acknowledged'): ?>
                                            <span style="color: var(--success);"><i class="fa-solid fa-check-double"></i> Acknowledged</span>
                                        <?php else: ?>
                                            <span style="color: var(--danger);"><i class="fa-solid fa-clock"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 1rem 0.5rem;">
                                        <?php if ($n['status'] === 'Sent'): ?>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="acknowledge">
                                                <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                                                <input type="hidden" name="parishioner_id" value="<?= $n['parishioner_id'] ?>">
                                                <input type="hidden" name="sacrament_info" value="<?= e($n['sacrament_type']) ?> performed at <?= e($n['sender_parish']) ?> on <?= $n['date_sent'] ?>">
                                                <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">Acknowledge & Notate</button>
                                            </form>
                                        <?php else: ?>
                                            <a href="../parishioners/view.php?id=<?= $n['parishioner_id'] ?>" style="color: var(--primary-color); font-size: 0.875rem;">View Profile</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div style="margin-top: 2rem; padding: 1.5rem; background: #ebf8ff; border-left: 4px solid #3182ce; border-radius: var(--radius-md);">
                <p style="font-size: 0.875rem; color: #2c5282;">
                    <strong><i class="fa-solid fa-circle-info"></i> How it works:</strong> When any parish in the <?php echo get_diocese_branding(); ?> network registers a marriage or confirmation for a person baptized in your parish, a digital notification is automatically routed here. Acknowledging it will update the person's digital baptismal notation automatically.
                </p>
            </div>
        </main>
    </div>
</body>
</html>
