<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Sacramental Records Hub - Premium Navigation
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

// Fetch Pending Verification Counts for badges
$pending_bap = db_fetch("SELECT COUNT(*) as count FROM baptisms WHERE status = 'Draft'")['count'];
$pending_mar = db_fetch("SELECT COUNT(*) as count FROM marriages WHERE status = 'Draft'")['count'];
$pending_cnf = db_fetch("SELECT COUNT(*) as count FROM confirmations WHERE status = 'Draft'")['count'];
$pending_dth = db_fetch("SELECT COUNT(*) as count FROM deaths WHERE status = 'Draft'")['count'];
$pending_ord = db_fetch("SELECT COUNT(*) as count FROM ordinations_professions WHERE status = 'Draft'")['count'];

$total_pending = $pending_bap + $pending_mar + $pending_cnf + $pending_dth + $pending_ord;

// Fetch a combined list of recent pending items for the queue
$pending_queue = [];
if ($total_pending > 0) {
    $sql_queue = "
        SELECT 'Baptism' as type, b.baptism_id as id, p.first_name || ' ' || p.last_name as name, b.date_of_baptism as date, pr.parish_name, 'view_baptism.php' as link
        FROM baptisms b JOIN parishioners p ON b.person_id = p.person_id JOIN parishes pr ON b.parish_id = pr.parish_id WHERE b.status = 'Draft'
        UNION ALL
        SELECT 'Marriage' as type, m.marriage_id as id, p1.first_name || ' & ' || p2.first_name as name, m.date_of_marriage as date, pr.parish_name, 'view_marriage.php' as link
        FROM marriages m JOIN parishioners p1 ON m.groom_person_id = p1.person_id JOIN parishioners p2 ON m.bride_person_id = p2.person_id JOIN parishes pr ON m.parish_id = pr.parish_id WHERE m.status = 'Draft'
        UNION ALL
        SELECT 'Confirmation' as type, c.confirmation_id as id, p.first_name || ' ' || p.last_name as name, c.date_of_confirmation as date, pr.parish_name, 'view_confirmation.php' as link
        FROM confirmations c JOIN parishioners p ON c.person_id = p.person_id JOIN parishes pr ON c.parish_id = pr.parish_id WHERE c.status = 'Draft'
        ORDER BY date DESC LIMIT 10";
    $pending_queue = db_fetchAll($sql_queue);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sacramental Records - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.3">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .sacrament-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 2rem; 
            margin-top: 2rem; 
        }
        .sacrament-card { 
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none; 
            text-align: center; 
            padding: 2.5rem 2rem; 
            position: relative; 
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.4) 0%, rgba(15, 23, 42, 0.7) 100%);
            border: 1px solid rgba(255,255,255,0.05);
            overflow: hidden;
            border-radius: 1.25rem;
        }
        .sacrament-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 4px;
            background: transparent;
            transition: background 0.3s;
        }
        .sacrament-card:hover { 
            transform: translateY(-8px); 
            border-color: var(--accent);
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.9) 100%);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }
        .sacrament-card:hover::before { background: var(--accent); }
        
        .sacrament-card .icon { 
            font-size: 3rem; 
            margin-bottom: 1.5rem; 
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(255,255,255,0.03);
            transition: transform 0.3s;
        }
        .sacrament-card:hover .icon { transform: scale(1.1) rotate(5deg); }
        
        .icon.baptism { color: #38bdf8; }
        .icon.confirmation { color: #fbbf24; }
        .icon.marriage { color: #f472b6; }
        .icon.death { color: #94a3b8; }
        .icon.ordination { color: #34d399; }

        /* Sacrament Specific Card Themes (Enhanced Base State) */
        .sacrament-card.card-baptism { 
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.08) 0%, rgba(15, 23, 42, 0.8) 100%); 
            border-color: rgba(56, 189, 248, 0.2); 
        }
        .sacrament-card.card-baptism:hover { border-color: #38bdf8; background: linear-gradient(135deg, rgba(56, 189, 248, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%); }
        .sacrament-card.card-baptism:hover::before { background: #38bdf8; }

        .sacrament-card.card-confirmation { 
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.08) 0%, rgba(15, 23, 42, 0.8) 100%); 
            border-color: rgba(251, 191, 36, 0.2); 
        }
        .sacrament-card.card-confirmation:hover { border-color: #fbbf24; background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%); }
        .sacrament-card.card-confirmation:hover::before { background: #fbbf24; }

        .sacrament-card.card-marriage { 
            background: linear-gradient(135deg, rgba(244, 114, 182, 0.08) 0%, rgba(15, 23, 42, 0.8) 100%); 
            border-color: rgba(244, 114, 182, 0.2); 
        }
        .sacrament-card.card-marriage:hover { border-color: #f472b6; background: linear-gradient(135deg, rgba(244, 114, 182, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%); }
        .sacrament-card.card-marriage:hover::before { background: #f472b6; }

        .sacrament-card.card-death { 
            background: linear-gradient(135deg, rgba(148, 163, 184, 0.1) 0%, rgba(15, 23, 42, 0.8) 100%); 
            border-color: rgba(148, 163, 184, 0.2); 
        }
        .sacrament-card.card-death:hover { border-color: #94a3b8; background: linear-gradient(135deg, rgba(148, 163, 184, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%); }
        .sacrament-card.card-death:hover::before { background: #94a3b8; }

        .sacrament-card.card-ordination { 
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.08) 0%, rgba(15, 23, 42, 0.8) 100%); 
            border-color: rgba(52, 211, 153, 0.2); 
        }
        .sacrament-card.card-ordination:hover { border-color: #34d399; background: linear-gradient(135deg, rgba(52, 211, 153, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%); }
        .sacrament-card.card-ordination:hover::before { background: #34d399; }
        
        .sacrament-card h3 { color: white; margin-bottom: 0.75rem; font-family: 'Outfit', sans-serif; font-size: 1.5rem; letter-spacing: -0.5px; }
        .sacrament-card p { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 0; }
        
        .pending-badge {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 0.3rem 0.75rem;
            border-radius: 2rem;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .verification-queue { margin-top: 4rem; animation: fadeInUp 0.6s ease-out; }
        @keyframes fadeInUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .queue-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-left: 0.5rem;
        }
        .queue-header h3 { font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 0.75rem; font-size: 1.5rem; }
        .queue-header .count { background: rgba(56, 189, 248, 0.1); color: var(--accent); padding: 0.35rem 1rem; border-radius: 2rem; font-size: 0.85rem; font-weight: 600; }

        .queue-table { width: 100%; border-collapse: collapse; }
        .queue-table th { text-align: left; padding: 1.25rem 1rem; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .queue-table td { padding: 1.25rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.95rem; }
        
        .queue-type-tag { padding: 0.25rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .type-baptism { background: rgba(56, 189, 248, 0.1); color: #38bdf8; }
        .type-marriage { background: rgba(244, 114, 182, 0.1); color: #f472b6; }
        .type-confirmation { background: rgba(251, 191, 36, 0.1); color: #fbbf24; }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <!-- Header (Unified) -->
            <?php 
                $header_title = "Sacramental Hub";
                $header_subtitle = "Securely manage and verify the holy sacraments of the Diocese.";
                include '../includes/header.php'; 
            ?>

            <div class="sacrament-grid">
                
                <!-- Baptisms -->
                <a href="baptisms.php" class="sacrament-card card-baptism">
                    <?php if ($pending_bap > 0): ?><span class="pending-badge"><?php echo $pending_bap; ?> Pending</span><?php endif; ?>
                    <div class="icon" style="background: #fb923c;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22C12 22 19 18 19 12C19 11 18.5 10.5 18 10C18 7 16 4 12 4C8 4 6 7 6 10C5.5 10.5 5 11 5 12C5 18 12 22 12 22Z" fill="#fffef0" />
                            <path d="M12 4V22M12 4C14 4 16 6 16 9M12 4C10 4 8 6 8 9M12 4C14.5 4 17.5 7 17.5 11M12 4C9.5 4 6.5 7 6.5 11" stroke="#fb923c" stroke-width="0.5"/>
                        </svg>
                    </div>
                    <h3>Baptisms</h3>
                    <p>Archival records of Christian initiation and rebirth in the Holy Spirit.</p>
                </a>

                <!-- Confirmations -->
                <a href="confirmations.php" class="sacrament-card card-confirmation">
                    <?php if ($pending_cnf > 0): ?><span class="pending-badge"><?php echo $pending_cnf; ?> Pending</span><?php endif; ?>
                    <div class="icon" style="background: #10b981;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 18C12 18 19 14 19 10C19 6 16 3 12 3C8 3 5 6 5 10C5 14 12 18 12 18Z" fill="white" />
                            <path d="M12 12C12.5523 12 13 11.5523 13 11C13 10.4477 12.5523 10 12 10C11.4477 10 11 10.4477 11 11C11 11.5523 11.4477 12 12 12Z" fill="#ef4444" />
                            <path d="M8 10L5 8M16 10L19 8M12 18V21M9 19L12 18L15 19" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3>Confirmations</h3>
                    <p>Records of the sealing with the Gift of the Holy Spirit.</p>
                </a>

                <!-- Marriages -->
                <a href="marriages.php" class="sacrament-card card-marriage">
                    <?php if ($pending_mar > 0): ?><span class="pending-badge"><?php echo $pending_mar; ?> Pending</span><?php endif; ?>
                    <div class="icon" style="background: #334155;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 21C12 21 20 16 20 10C20 6.5 17 4 14 4C12.5 4 11.5 4.5 11 5C10.5 4.5 9.5 4 8 4C5 4 2 6.5 2 10C2 16 12 21 12 21Z" fill="#ef4444" />
                            <circle cx="10" cy="11" r="3.5" stroke="#fbbf24" stroke-width="2" />
                            <circle cx="14" cy="11" r="3.5" stroke="#fbbf24" stroke-width="2" />
                        </svg>
                    </div>
                    <h3>Marriages</h3>
                    <p>Canonical records of the Holy Covenant of Matrimony.</p>
                </a>

                <!-- Deaths -->
                <a href="deaths.php" class="sacrament-card card-death">
                    <?php if ($pending_dth > 0): ?><span class="pending-badge"><?php echo $pending_dth; ?> Pending</span><?php endif; ?>
                    <div class="icon" style="background: #94a3b8;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22V14M12 14C12 14 19 12 19 8C19 4 16 2 12 2C8 2 5 4 5 8C5 12 12 14 12 14Z" fill="rgba(255,255,255,0.2)" stroke="white" stroke-width="2"/>
                            <path d="M8 7L11 9L8 11" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3>Deaths</h3>
                    <p>Records of Christian burial and the transition to eternal life.</p>
                </a>

                 <!-- Ordinations -->
                 <a href="ordinations.php" class="sacrament-card card-ordination">
                    <?php if ($pending_ord > 0): ?><span class="pending-badge"><?php echo $pending_ord; ?> Pending</span><?php endif; ?>
                    <div class="icon" style="background: #facc15;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8 4V16C8 18 10 20 12 20C14 20 16 18 16 16V4" stroke="#059669" stroke-width="4" stroke-linecap="round"/>
                            <path d="M10 8H14M10 14H14" stroke="white" stroke-width="1"/>
                        </svg>
                    </div>
                    <h3>Holy Orders</h3>
                    <p>Records of Diaconate, Priesthood, and Religious Professions.</p>
                </a>

            </div>

            <!-- Pending Verification Queue -->
            <?php if ($total_pending > 0 && $_SESSION['role'] !== 'secretary'): ?>
            <div class="verification-queue">
                <div class="queue-header">
                    <h3><ion-icon name="shield-checkmark-outline" style="color: var(--accent);"></ion-icon> Canonical Verification Queue</h3>
                    <span class="count"><?php echo $total_pending; ?> Records Awaiting Review</span>
                </div>
                <div class="card bg-card no-padding overflow-hidden">
                    <table class="queue-table">
                        <thead>
                            <tr>
                                <th>Sacrament</th>
                                <th>Candidate / Couple</th>
                                <th>Celebration Date</th>
                                <th>Parish / Mission</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_queue as $item): ?>
                            <tr>
                                <td><span class="queue-type-tag type-<?php echo strtolower($item['type']); ?>"><?php echo h($item['type']); ?></span></td>
                                <td><strong><?php echo h($item['name']); ?></strong></td>
                                <td style="color: var(--text-muted);"><?php echo date('d M Y', strtotime($item['date'])); ?></td>
                                <td style="color: var(--text-muted);"><?php echo h($item['parish_name']); ?></td>
                                <td>
                                    <a href="<?php echo $item['link']; ?>?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm" style="padding: 0.5rem 1rem; border-radius: 6px;">
                                        Review & Verify
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
