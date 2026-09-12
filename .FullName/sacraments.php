<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Sacramental Hub - Unified Entry Point for all Sacramental Records
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Protect the page
require_login();

// Header metadata
$header_title = "Sacramental Hub";
$header_subtitle = "Unified access to all canonical records and registries.";

// Fetch counts for badges
$counts = [
    'baptisms' => db_fetch("SELECT COUNT(*) as count FROM baptisms")['count'] ?? 0,
    'confirmations' => db_fetch("SELECT COUNT(*) as count FROM confirmations")['count'] ?? 0,
    'marriages' => db_fetch("SELECT COUNT(*) as count FROM marriages")['count'] ?? 0,
    'communions' => db_fetch("SELECT COUNT(*) as count FROM first_holy_communions")['count'] ?? 0,
    'ordinations' => db_fetch("SELECT COUNT(*) as count FROM ordinations_professions")['count'] ?? 0,
    'receptions' => db_fetch("SELECT COUNT(*) as count FROM receptions")['count'] ?? 0,
    'deaths' => db_fetch("SELECT COUNT(*) as count FROM deaths")['count'] ?? 0,
    'pni' => db_fetch("SELECT COUNT(*) as count FROM prenuptial_investigations")['count'] ?? 0,
];

// Define sacramental modules
$modules = [
    [
        'id' => 'pni',
        'title' => 'Prenuptial Statement',
        'desc' => 'Canonical investigation and "Freedom to Marry" declaration (PNI Form A).',
        'icon' => 'document-text-outline',
        'color' => '#fbbf24',
        'list_url' => 'sacraments/marriage_pni_list.php',
        'add_url' => 'sacraments/marriage_pni_add.php',
        'count' => $counts['pni'],
        'canon' => 'Canon 1067: The conference of bishops is to establish norms concerning the examination of spouses before marriage.'
    ],
    [
        'id' => 'baptisms',
        'title' => 'Baptismal Registry',
        'desc' => 'Canonical records of rebirth in Christ and entry into the Church.',
        'icon' => 'water-outline',
        'color' => '#38bdf8',
        'list_url' => 'sacraments/baptism_list.php',
        'add_url' => 'sacraments/baptism_add.php',
        'count' => $counts['baptisms'],
        'canon' => 'Canon 849: Baptism, the gateway to the sacraments, is the sign and cause of rebirth to life in God.'
    ],
    [
        'id' => 'communions',
        'title' => 'First Holy Communion',
        'desc' => 'Registry of parishioners receiving the Bread of Life for the first time.',
        'icon' => 'restaurant-outline',
        'color' => '#fbbf24',
        'list_url' => 'sacraments/communion_list.php',
        'add_url' => 'sacraments/communion_add.php',
        'count' => $counts['communions'],
        'canon' => 'Canon 897: The Most Holy Eucharist is the most august sacrament, in which Christ the Lord is offered and received.'
    ],
    [
        'id' => 'confirmations',
        'title' => 'Confirmation Registry',
        'desc' => 'Sealing with the Gift of the Holy Spirit and full Christian initiation.',
        'icon' => 'flame-outline',
        'color' => '#ef4444',
        'list_url' => 'sacraments/confirmation_list.php',
        'add_url' => 'sacraments/confirmation_add.php',
        'count' => $counts['confirmations'],
        'canon' => 'Canon 879: Enriched with the gift of the Holy Spirit, they are bound more perfectly to the Church.'
    ],
    [
        'id' => 'marriages',
        'title' => 'Matrimonial Registry',
        'desc' => 'Canonical records of Holy Matrimony between grooms and brides.',
        'icon' => 'heart-outline',
        'color' => '#f472b6',
        'list_url' => 'sacraments/marriage_list.php',
        'add_url' => 'sacraments/marriage_add.php',
        'count' => $counts['marriages'],
        'canon' => 'Canon 1055: The matrimonial covenant establishes a partnership of the whole of life between a man and woman.'
    ],
    [
        'id' => 'deaths',
        'title' => 'Death & Burial Registry',
        'desc' => 'Canonical record of those who have passed from this life to God.',
        'icon' => 'moon-outline',
        'color' => '#94a3b8',
        'list_url' => 'sacraments/burial_list.php',
        'add_url' => 'sacraments/death_add.php',
        'count' => $counts['deaths'],
        'canon' => 'Canon 1176: Deceased members of the Christian faithful must be given ecclesiastical funerals according to the norm of law.'
    ],
    [
        'id' => 'receptions',
        'title' => 'Profession of Faith',
        'desc' => 'Reception into Full Communion for those baptized in other denominations.',
        'icon' => 'shield-checkmark-outline',
        'color' => '#8b5cf6',
        'list_url' => 'sacraments/reception_list.php',
        'add_url' => 'sacraments/reception_add.php',
        'count' => $counts['receptions'],
        'canon' => 'Canon 883: Those who are received into full communion with the Catholic Church have the right to full sacramental participation.'
    ]

];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sacramental Hub - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .sacrament-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
            gap: 2rem; 
            margin-top: 1rem;
        }
        .sacrament-card {
            background: var(--card-bg);
            border-radius: 28px;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .sacrament-card:hover {
            transform: translateY(-10px);
            border-color: var(--accent);
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
        }
        .icon-box {
            width: 56px; height: 56px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; margin-bottom: 1.5rem;
        }
        .canon-quote {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-style: italic;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.05);
            line-height: 1.5;
            display: none; /* Hidden by default */
            animation: slideDown 0.3s ease;
        }
        .canon-quote.show { display: block; }
        .toggle-canon {
            background: none;
            border: none;
            color: var(--accent);
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
            padding: 0;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            <div class="content-body" style="padding: 1.5rem 0;">
                <div class="sacrament-grid">
                    <?php foreach ($modules as $mod): ?>
                        <div class="sacrament-card" onclick="window.location.href='<?php echo $mod['list_url']; ?>'" style="cursor: pointer;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div class="icon-box" style="background: <?php echo $mod['color']; ?>20; color: <?php echo $mod['color']; ?>;">
                                    <ion-icon name="<?php echo $mod['icon']; ?>"></ion-icon>
                                </div>
                                <div style="text-align: right;">
                                    <span style="display: block; font-size: 1.5rem; font-weight: 900; color: white;"><?php echo number_format($mod['count']); ?></span>
                                    <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Records</span>
                                </div>
                            </div>
                            <h3 style="font-family: 'Outfit'; color: white; margin-bottom: 0.5rem;"><?php echo $mod['title']; ?></h3>
                            <p style="font-size: 0.85rem; color: #94a3b8; line-height: 1.6;"><?php echo $mod['desc']; ?></p>
                            <button class="toggle-canon" onclick="event.stopPropagation(); this.nextElementSibling.classList.toggle('show'); this.querySelector('ion-icon').name = this.nextElementSibling.classList.contains('show') ? 'chevron-up-outline' : 'chevron-down-outline';">
                                <ion-icon name="chevron-down-outline"></ion-icon>
                                Canon Law Reference
                            </button>
                            <div class="canon-quote"><?php echo $mod['canon']; ?></div>
                            <div style="margin-top: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding-top: 1.5rem;">
                                <a href="<?php echo $mod['list_url']; ?>" class="btn btn-secondary" style="font-size: 0.75rem;" onclick="event.stopPropagation();">View Registry</a>
                                <a href="<?php echo $mod['add_url']; ?>" class="btn btn-primary" style="font-size: 0.75rem; background: <?php echo $mod['color']; ?>; border-color: <?php echo $mod['color']; ?>; color: #000;" onclick="event.stopPropagation();">New Entry</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php include 'includes/privacy_footer.php'; ?>

            </div>
        </main>
    </div>
    <script src="assets/js/main.js?v=1.6.2"></script>
</body>
</html>
