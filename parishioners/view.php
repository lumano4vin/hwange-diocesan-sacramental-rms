<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parishioner Profile View - Modernized
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page
require_login();

$id = $_GET['id'] ?? null;
if (!$id) die("Parishioner ID required.");

// Fetch Parishioner details with parish info
$parishioner = db_fetch("
    SELECT p.*, pa.parish_name 
    FROM parishioners p 
    LEFT JOIN parishes pa ON p.current_parish_id = pa.parish_id 
    WHERE p.person_id = ?
", [$id]);

if (!$parishioner) die("Parishioner not found in the archives.");

// Fetch Sacramental Records
$baptism = db_fetch("SELECT b.*, pa.parish_name FROM baptisms b JOIN parishes pa ON b.parish_id = pa.parish_id WHERE b.person_id = ?", [$id]);
$confirmation = db_fetch("SELECT c.*, pa.parish_name FROM confirmations c JOIN parishes pa ON c.parish_id = pa.parish_id WHERE c.person_id = ?", [$id]);
$communion = db_fetch("SELECT fc.*, pa.parish_name FROM first_holy_communions fc JOIN parishes pa ON fc.parish_id = pa.parish_id WHERE fc.person_id = ?", [$id]);
$reception = db_fetch("SELECT r.*, pa.parish_name FROM receptions r JOIN parishes pa ON r.parish_id = pa.parish_id WHERE r.person_id = ?", [$id]);
$death = db_fetch("SELECT d.*, pa.parish_name FROM deaths d JOIN parishes pa ON d.parish_id = pa.parish_id WHERE d.person_id = ?", [$id]);

// Calculate Initiation Score
$initiation_score = 0;
if ($baptism || $reception) $initiation_score += 33;
if ($communion) $initiation_score += 33;
if ($confirmation) $initiation_score += 34;

$next_milestone = "";
if (!$baptism && !$reception) $next_milestone = "Baptism or Reception";
elseif (!$communion) $next_milestone = "First Holy Communion";
elseif (!$confirmation) $next_milestone = "Confirmation";

// Marriage Records (Subject can be Groom or Bride)
$marriages = db_fetchAll("
    SELECT m.*, 
           g.first_name as groom_first, g.last_name as groom_last,
           b.first_name as bride_first, b.last_name as bride_last,
           pa.parish_name 
    FROM marriages m 
    JOIN parishioners g ON m.groom_person_id = g.person_id 
    JOIN parishioners b ON m.bride_person_id = b.person_id
    JOIN parishes pa ON m.parish_id = pa.parish_id 
    WHERE m.groom_person_id = ? OR m.bride_person_id = ?
", [$id, $id]);

// Vocations
$vocations = db_fetchAll("SELECT * FROM ordinations_professions WHERE person_id = ? ORDER BY event_date DESC", [$id]);

// Header metadata
$header_title = "Faithful Profile";
$header_subtitle = "Canonical record for " . h($parishioner['first_name'] . ' ' . $parishioner['last_name']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo h($parishioner['first_name']); ?> - Hwange Diocesan RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Header -->
            <?php include '../includes/header.php'; ?>
            
            <div class="content-body" style="padding: 2rem 0;">
                
                <div class="profile-grid" style="display: grid; grid-template-columns: 350px 1fr; gap: 2rem; align-items: start;">
                    
                    <!-- Left Column: Personal Card -->
                    <div class="sticky-top" style="position: sticky; top: 2rem;">
                        <div class="card bg-card" style="padding: 2.5rem; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05); text-align: center; backdrop-filter: blur(20px);">
                            <div class="profile-avatar" style="width: 120px; height: 120px; background: rgba(255,255,255,0.03); border: 2px solid var(--accent); border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: var(--accent); box-shadow: 0 15px 30px rgba(0,0,0,0.3);">
                                <ion-icon name="person-outline"></ion-icon>
                            </div>
                            <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; color: white; margin-bottom: 0.5rem;"><?php echo h($parishioner['first_name'] . ' ' . $parishioner['last_name']); ?></h2>
                            <span style="display: inline-block; background: rgba(251, 191, 36, 0.1); color: var(--accent); padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; margin-bottom: 2rem;">
                                <?php echo h($parishioner['status'] ?: 'Active'); ?>
                            </span>

                            <div class="info-list" style="text-align: left; display: flex; flex-direction: column; gap: 1.25rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 2rem;">
                                <div class="info-item">
                                    <span style="display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Home Parish</span>
                                    <span style="font-weight: 600; color: white;"><?php echo h($parishioner['parish_name'] ?: 'Diocesan Registry (General)'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span style="display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Date of Birth & Age</span>
                                    <span style="font-weight: 600; color: white;">
                                        <?php echo date('d M Y', strtotime($parishioner['dob'])); ?>
                                        <span style="color: var(--accent); margin-left: 8px;">(<?php echo get_age($parishioner['dob']); ?>)</span>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span style="display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Place of Birth</span>
                                    <span style="font-weight: 600; color: white;"><?php echo h($parishioner['place_of_birth'] ?: 'Unknown'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span style="display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Place of Baptism</span>
                                    <span style="font-weight: 600; color: #38bdf8;"><?php echo h($parishioner['place_of_baptism'] ?: 'Not Recorded'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span style="display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Place of Residence</span>
                                    <span style="font-weight: 600; color: var(--accent);"><?php echo h($parishioner['place_of_residence'] ?: 'Not Recorded'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span style="display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Parents</span>
                                    <span style="display: block; font-size: 0.9rem; color: white;">Father: <?php echo h($parishioner['father_name'] ?: '-'); ?></span>
                                    <span style="display: block; font-size: 0.9rem; color: white;">Mother: <?php echo h($parishioner['mother_name'] ?: '-'); ?></span>
                                </div>
                            </div>

                            <div style="margin-top: 2.5rem; display: grid; grid-template-columns: 1fr; gap: 0.75rem;">
                                <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-secondary" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <ion-icon name="create-outline"></ion-icon> Edit Profile
                                </a>
                                <a href="../parishioners.php" class="btn btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <ion-icon name="arrow-back-outline"></ion-icon> Back to Registry
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Sacred Journey Timeline -->
                    <div class="sacramental-history">
                        
                        <!-- Initiation Progress Tracker -->
                        <div class="initiation-tracker card bg-card" style="padding: 1.5rem; border-radius: 1.5rem; margin-bottom: 2rem; border: 1px solid rgba(56, 189, 248, 0.2); background: linear-gradient(135deg, rgba(30, 41, 59, 0.4) 0%, rgba(15, 23, 42, 0.6) 100%);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div>
                                    <h4 style="font-family: 'Outfit'; color: white; margin: 0; font-size: 1.1rem;">Sacramental Initiation Progress</h4>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin: 4px 0 0;">Foundation of the Christian Life (Baptism, Communion, Confirmation)</p>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 1.5rem; font-weight: 900; color: var(--accent);"><?php echo $initiation_score; ?>%</span>
                                </div>
                            </div>
                            <div style="width: 100%; height: 10px; background: rgba(255,255,255,0.05); border-radius: 5px; overflow: hidden; position: relative;">
                                <div style="width: <?php echo $initiation_score; ?>%; height: 100%; background: linear-gradient(90deg, var(--accent) 0%, #fbbf24 100%); border-radius: 5px; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 0 15px rgba(56, 189, 248, 0.3);"></div>
                            </div>
                            <?php if ($next_milestone): ?>
                                <div style="margin-top: 1rem; display: flex; align-items: center; gap: 8px;">
                                    <ion-icon name="flag-outline" style="color: #fbbf24; font-size: 1rem;"></ion-icon>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">Next Milestone: <strong style="color: white;"><?php echo $next_milestone; ?></strong></span>
                                </div>
                            <?php else: ?>
                                <div style="margin-top: 1rem; display: flex; align-items: center; gap: 8px;">
                                    <ion-icon name="checkmark-circle" style="color: #10b981; font-size: 1rem;"></ion-icon>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">Christian Initiation <strong style="color: #10b981;">Fully Complete</strong></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.6rem; color: white; border-left: 4px solid var(--accent); padding-left: 1rem;">Sacred Journey</h3>
                            <div class="timeline-legend" style="display: flex; gap: 1rem; font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">
                                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 8px; height: 8px; border-radius: 50%; background: #38bdf8;"></span> Baptism</span>
                                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 8px; height: 8px; border-radius: 50%; background: #fbbf24;"></span> Communion</span>
                                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 8px; height: 8px; border-radius: 50%; background: #f472b6;"></span> Marriage</span>
                            </div>
                        </div>

                        <?php
                        // 1. Collect all events for the timeline
                        $events = [];

                        // Birth
                        if ($parishioner['dob']) {
                            $events[] = [
                                'date' => $parishioner['dob'],
                                'title' => 'Birth',
                                'type' => 'birth',
                                'icon' => 'baby-outline',
                                'meta' => [
                                    ['label' => 'Place', 'value' => $parishioner['place_of_birth'] ?: 'Unknown'],
                                    ['label' => 'Parents', 'value' => $parishioner['father_name'] . ' & ' . $parishioner['mother_name']]
                                ]
                            ];
                        }

                        // Baptism
                        if ($baptism) {
                            $events[] = [
                                'date' => $baptism['date_of_baptism'],
                                'title' => 'Sacrament of Baptism',
                                'type' => 'baptism',
                                'icon' => 'water-outline',
                                'url' => '../sacraments/baptism_certificate.php?id=' . $baptism['baptism_id'],
                                'meta' => [
                                    ['label' => 'Parish', 'value' => $baptism['parish_name']],
                                    ['label' => 'Place', 'value' => $baptism['place_of_baptism'] ?: 'Parish Church'],
                                    ['label' => 'Age at Sacrament', 'value' => get_age($parishioner['dob'], $baptism['date_of_baptism'])],
                                    ['label' => 'Minister', 'value' => $baptism['minister']],
                                    ['label' => 'Godparents', 'value' => $baptism['godparents']]
                                ]
                            ];
                        }

                        // First Communion
                        if ($communion) {
                            $events[] = [
                                'date' => $communion['date_of_communion'],
                                'title' => 'First Holy Communion',
                                'type' => 'communion',
                                'icon' => 'nutrition-outline',
                                'url' => '../sacraments/communion_certificate.php?id=' . $communion['communion_id'],
                                'meta' => [
                                    ['label' => 'Parish', 'value' => $communion['parish_name']],
                                    ['label' => 'Age at Sacrament', 'value' => get_age($parishioner['dob'], $communion['date_of_communion'])],
                                    ['label' => 'Minister', 'value' => $communion['minister_name']]
                                ]
                            ];
                        }

                        // Confirmation
                        if ($confirmation) {
                            $events[] = [
                                'date' => $confirmation['date_of_confirmation'],
                                'title' => 'Sacrament of Confirmation',
                                'type' => 'confirmation',
                                'icon' => 'flame-outline',
                                'url' => '../sacraments/confirmation_certificate.php?id=' . $confirmation['confirmation_id'],
                                'meta' => [
                                    ['label' => 'Parish', 'value' => $confirmation['parish_name']],
                                    ['label' => 'Age at Sacrament', 'value' => get_age($parishioner['dob'], $confirmation['date_of_confirmation'])],
                                    ['label' => 'Minister', 'value' => $confirmation['minister']],
                                    ['label' => 'Sponsor', 'value' => $confirmation['sponsor']]
                                ]
                            ];
                        }

                        // Marriages
                        foreach ($marriages as $m) {
                            $spouse = ($m['groom_person_id'] == $id) ? $m['bride_first'] . ' ' . $m['bride_last'] : $m['groom_first'] . ' ' . $m['groom_last'];
                            $events[] = [
                                'date' => $m['date_of_marriage'],
                                'title' => 'Holy Matrimony',
                                'type' => 'marriage',
                                'icon' => 'heart-outline',
                                'url' => '../sacraments/marriage_certificate.php?id=' . $m['marriage_id'],
                                'meta' => [
                                    ['label' => 'Spouse', 'value' => $spouse],
                                    ['label' => 'Age at Sacrament', 'value' => get_age($parishioner['dob'], $m['date_of_marriage'])],
                                    ['label' => 'Parish', 'value' => $m['parish_name']],
                                    ['label' => 'Minister', 'value' => $m['minister']]
                                ]
                            ];
                        }

                        // Vocations
                        foreach ($vocations as $v) {
                            $events[] = [
                                'date' => $v['event_date'],
                                'title' => $v['event_type'], // e.g., Diaconate, Priesthood, Profession
                                'type' => 'vocation',
                                'icon' => 'medal-outline',
                                'meta' => [
                                    ['label' => 'Details', 'value' => $v['details']]
                                ]
                            ];
                        }

                        // Death
                        if ($death) {
                            $events[] = [
                                'date' => $death['date_of_death'],
                                'title' => 'Christian Burial',
                                'type' => 'death',
                                'icon' => 'leaf-outline',
                                'meta' => [
                                    ['label' => 'Place', 'value' => $death['place_of_burial']],
                                    ['label' => 'Minister', 'value' => $death['minister']]
                                ]
                            ];
                        }

                        // 2. Sort events by date
                        usort($events, function($a, $b) {
                            return strtotime($a['date']) <=> strtotime($b['date']);
                        });

                        // 3. Render Timeline
                        ?>
                        <div class="journey-timeline">
                            <?php foreach ($events as $event): ?>
                                <div class="timeline-event <?php echo $event['type']; ?>">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                            <div>
                                                <span class="timeline-date"><?php echo date('d M Y', strtotime($event['date'])); ?></span>
                                                <h4 class="timeline-title"><?php echo h($event['title']); ?></h4>
                                            </div>
                                            <?php if (isset($event['url'])): ?>
                                                <a href="<?php echo $event['url']; ?>" target="_blank" class="btn btn-sm btn-secondary" style="font-size: 0.7rem; padding: 4px 10px; border-radius: 8px;">
                                                    <ion-icon name="print-outline"></ion-icon> Extract
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="timeline-meta">
                                            <?php foreach ($event['meta'] as $m): ?>
                                                <div class="timeline-meta-item">
                                                    <ion-icon name="chevron-forward-circle-outline"></ion-icon>
                                                    <span><strong><?php echo h($m['label']); ?>:</strong> <?php echo h($m['value']); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Actionable End Node -->
                            <div class="timeline-event add-event" style="opacity: 1; transform: none;">
                                <div class="timeline-dot" style="background: var(--accent); border-color: white;"></div>
                                <div class="timeline-content" style="background: rgba(251, 191, 36, 0.05); border: 1px dashed var(--accent);">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--accent); color: black; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                            <ion-icon name="add-outline"></ion-icon>
                                        </div>
                                        <div>
                                            <h4 style="color: white; font-family: 'Outfit';">New Canonical Milestone</h4>
                                            <p style="font-size: 0.8rem; color: var(--text-muted);">Record a subsequent sacrament or canonical event for this person.</p>
                                        </div>
                                    </div>
                                    <div style="margin-top: 1rem; display: flex; gap: 10px;">
                                        <?php if (!$baptism): ?><a href="../sacraments/baptism_add.php?person_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">Baptism</a><?php endif; ?>
                                        <?php if (!$confirmation): ?><a href="../sacraments/confirmation_add.php?person_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">Confirmation</a><?php endif; ?>
                                        <a href="../sacraments/marriage_add.php?person_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">Marriage</a>
                                        <a href="../sacraments/death_add.php?person_id=<?php echo $id; ?>" class="btn btn-sm btn-secondary">Burial</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <?php include '../includes/privacy_footer.php'; ?>

            </div>

        </main>
    </div>

    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
