<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parish Data Restoration Utility
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page (Admin or Chancellor)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chancellor') {
    header("Location: index.php?error=access_denied");
    exit;
}

$success_count = 0;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_restore'])) {
    // Official 36 Parishes, Missions & Seminary of the Diocese of Hwange
    $parishes_data = [
        ['St. Ignatius Cathedral', 'Hwange', 'Hwange Urban'],
        ['Holy Family', 'Hwange', 'Hwange Urban'],
        ['Our Lady of Peace', 'Hwange', 'Victoria Falls'],
        ['Mary Help of Christians', 'Don Bosco, Hwange', 'Empumalanga'],
        ['Ss. Peter and Paul', 'Hwange', 'Hwange Urban'],
        ['St. Charles Lwanga', 'Hwange', 'Empumalanga'],
        ['St. Francis Xavier', 'Dete', 'Dete'],
        ['St. Francis of Assisi', 'Cross Dete', 'Dete'],
        ['St. George', 'Hwange', 'Hwange Urban'],
        ['St. Joachim', 'Hwange', 'Hwange Urban'],
        ['St. Joseph', 'Hwange', 'Hwange Urban'],
        ['St. Josephine Bakhita', 'Victoria Falls', 'Victoria Falls'],
        ['St. Kizito', 'Victoria Falls', 'Victoria Falls'],
        ['St. Monica', 'Hwange', 'Hwange Urban'],
        ['St. Teresa', 'Hwange', 'Empumalanga'],
        ['All Souls', 'Binga', 'Binga'],
        ['Divine Mercy', 'Lubimbi', 'Dete'],
        ['Holy Cross', 'Lusulu', 'Binga'],
        ['Mary Immaculate', 'Gomoza', 'Lupane'],
        ['Our Lady of Fatima', 'Fatima', 'Lupane'],
        ['Sacred Heart', 'Jambezi', 'Jambezi'],
        ['St. Augustine', 'Mzola', 'Lupane'],
        ['St. Cecilia', 'Tshongokwe', 'Lupane'],
        ['St. Faustine', 'Matetsi', 'Jambezi'],
        ['St. John the Baptist (Dandanda)', 'Dandanda', 'Lupane'],
        ['St. John the Baptist (Makwa)', 'Makwa', 'Makwa'],
        ['St. John Vianney', 'Kariangwe', 'Binga'],
        ['St. Luke', 'Chisuma', 'Victoria Falls'],
        ['St. Mathew', 'Dambwamkulu', 'Makwa'],
        ['St. Mark', 'Nagangala', 'Binga'],
        ['St. Martin de Porres', 'Jotsholo', 'Lupane'],
        ['St. Mary', 'Lukosi', 'Empumalanga'],
        ['St. Michael', 'Kasibo', 'Makwa'],
        ['St. Padre Pio', 'Siacilaba', 'Binga'],
        ['St. Therese', 'Kamativi', 'Dete'],
        ['Immaculate Conception of the Minor Seminary', 'Chezhou-Dete', 'Dete']
    ];

    try {
        global $pdo;
        $pdo->beginTransaction();

        // Safely upsert each parish without wiping existing IDs or priest assignments
        foreach ($parishes_data as $row) {
            [$p_name, $p_loc, $p_deanery] = $row;
            $existing = db_fetch("SELECT parish_id FROM parishes WHERE parish_name = ?", [$p_name]);
            if ($existing) {
                db_query("UPDATE parishes SET location = ?, deanery = ? WHERE parish_id = ?", [$p_loc, $p_deanery, $existing['parish_id']]);
            } else {
                db_query("INSERT INTO parishes (parish_name, location, deanery) VALUES (?, ?, ?)", [$p_name, $p_loc, $p_deanery]);
            }
            $success_count++;
        }
        
        $pdo->commit();
        log_audit($_SESSION['user_id'], 'RESTORE', 'parishes', 0, "Verified/restored $success_count official parishes and missions.");
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restore Parish Data - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout" id="app-layout">
        
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            
            <?php 
                $header_title = "System Data Restoration";
                $header_subtitle = "Initialize or restore the official canonical structure of the Diocese.";
                include '../includes/header.php'; 
            ?>


            <div class="card bg-card" style="max-width: 600px; margin: 0 auto;">
                <?php if ($success_count > 0): ?>
                    <div class="alert alert-success" style="text-align: center; padding: 2rem;">
                        <ion-icon name="checkmark-circle" style="font-size: 3rem; margin-bottom: 1rem;"></ion-icon>
                        <h3>Success!</h3>
                        <p><?php echo $success_count; ?> official parishes and missions have been restored.</p>
                        <div style="margin-top: 1.5rem;">
                            <a href="index.php" class="btn btn-primary">Go to Dashboard</a>
                        </div>
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-error">
                        <h3>Restoration Error</h3>
                        <p><?php echo h($error); ?></p>
                    </div>
                <?php else: ?>
                    <div class="restore-content" style="text-align: center; padding: 1rem;">
                        <ion-icon name="cloud-download-outline" style="font-size: 4rem; color: var(--accent); margin-bottom: 1rem;"></ion-icon>
                        <h3>Restore Official Data?</h3>
                        <p style="color: var(--text-muted); margin-bottom: 2rem;">
                            This action will populate the database with the 35 official parishes and missions of the Hwange Diocese. 
                            Existing parish records will be reset to ensure canonical consistency.
                        </p>
                        
                        <form method="POST">
                            <input type="hidden" name="confirm_restore" value="1">
                            <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                                Confirm Restoration
                            </button>
                        </form>
                        
                        <div style="margin-top: 1.5rem;">
                            <a href="index.php" style="color: var(--text-muted); font-size: 0.9rem;">Cancel and Return</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <style>
        .btn-large { padding: 1rem 2rem; font-size: 1.1rem; font-weight: 700; border-radius: 0.75rem; }
    </style>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
