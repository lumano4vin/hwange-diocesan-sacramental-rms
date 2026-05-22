<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Pastoral Reminders & Sacramental Milestone Tracker
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$parish_id = $_SESSION['parish_id'];
$role = $_SESSION['role'];

// 1. Logic: Find individuals baptized at least 7 years ago who have NO confirmation recorded
$age_threshold = 7;
$target_date = date('Y-m-d', strtotime("-$age_threshold years"));

$sql_confirm_reminders = "
    SELECT p.person_id, p.first_name, p.last_name, p.dob, b.date_of_baptism, pr.parish_name
    FROM parishioners p
    JOIN baptisms b ON p.person_id = b.person_id
    JOIN parishes pr ON b.parish_id = pr.parish_id
    LEFT JOIN confirmations c ON p.person_id = c.person_id
    WHERE b.date_of_baptism <= ?
    AND c.confirmation_id IS NULL
";

// If not global admin, filter by parish
$params = [$target_date];
if (!in_array($role, ['admin', 'chancellor'])) {
    $sql_confirm_reminders .= " AND b.parish_id = ?";
    $params[] = $parish_id;
}

$sql_confirm_reminders .= " ORDER BY b.date_of_baptism ASC LIMIT 50";
$reminders = db_fetchAll($sql_confirm_reminders, $params);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pastoral Reminders - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.3">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php 
                $header_title = "Pastoral Reminders";
                $header_subtitle = "Automated milestone tracker to ensure no parishioner misses a sacrament.";
                include '../includes/header.php'; 
            ?>


            <!-- Reminder Categories -->
            <div class="reminder-grid" style="display: grid; grid-template-columns: 1fr; gap: 2rem; margin-top: 2rem;">
                
                <!-- Confirmation Gap Analysis -->
                <div class="card bg-card">
                    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
                        <h3 style="display: flex; align-items: center; gap: 10px;">
                            <ion-icon name="flame-outline" style="color: #fbbf24;"></ion-icon>
                            Confirmation Readiness (Baptized 7+ Years Ago)
                        </h3>
                        <span class="status-badge draft"><?php echo count($reminders); ?> Flagged</span>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (empty($reminders)): ?>
                            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                                <ion-icon name="checkmark-done-circle" style="font-size: 3rem; color: var(--success);"></ion-icon>
                                <p>All sacramental milestones appear to be up to date for this parish.</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: rgba(255,255,255,0.02);">
                                        <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase;">Parishioner</th>
                                        <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase;">Baptism Date</th>
                                        <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase;">Age at Baptism</th>
                                        <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase;">Current Parish</th>
                                        <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reminders as $r): ?>
                                    <tr>
                                        <td style="padding: 1.2rem;">
                                            <strong><?php echo h($r['first_name'] . ' ' . $r['last_name']); ?></strong>
                                        </td>
                                        <td style="padding: 1.2rem; color: #fbbf24; font-weight: 600;">
                                            <?php echo date('d M Y', strtotime($r['date_of_baptism'])); ?>
                                        </td>
                                        <td style="padding: 1.2rem; color: var(--text-muted);">
                                            <?php echo get_age($r['dob'], $r['date_of_baptism']); ?>
                                        </td>
                                        <td style="padding: 1.2rem; color: var(--text-muted);"><?php echo h($r['parish_name']); ?></td>
                                        <td style="padding: 1.2rem;">
                                            <a href="add_confirmation.php?person_id=<?php echo $r['person_id']; ?>" class="btn btn-primary btn-sm" style="padding: 0.5rem 1rem; font-size: 0.75rem; border-radius: 6px;">
                                                Register Confirmation
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Strategic Advice Card -->
                <div class="card notice-card" style="background: rgba(251, 191, 36, 0.05); border-left: 5px solid #fbbf24;">
                    <div class="card-body" style="display: flex; align-items: start; gap: 1.5rem; padding: 25px;">
                        <ion-icon name="bulb-outline" style="font-size: 2.5rem; color: #fbbf24;"></ion-icon>
                        <div>
                            <h4 style="color: #fbbf24; margin-bottom: 8px; font-size: 1.1rem;">Pastoral Growth Tip</h4>
                            <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">
                                The individuals flagged above are clinically ready for <b>First Holy Communion</b> and <b>Confirmation</b>. 
                                Parish priests are encouraged to initiate catechetical preparation for these candidates to ensure their full initiation into the Church.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
