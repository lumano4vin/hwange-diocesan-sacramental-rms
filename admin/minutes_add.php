<?php
/**
 * Add Meeting Minutes
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$pdo = getDB();
$parishes = $pdo->query("SELECT id, name FROM parishes ORDER BY name")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $m_date = $_POST['meeting_date'] ?? '';
    $content = $_POST['content'] ?? '';
    $parish_id = $_POST['parish_id'] ?? null;
    $created_by = $_SESSION['user_id'];

    if ($title && $m_date && $parish_id) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO meeting_minutes (parish_id, title, meeting_date, content, created_by) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$parish_id, $title, $m_date, $content, $created_by]);
            
            $new_id = $pdo->lastInsertId();
            logAction("Added Meeting Minutes", "meeting_minutes", $new_id);
            
            $success = "Meeting minutes added successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in required fields (Title, Date, Parish).";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Minutes - Hwange SRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="logo-container"><h2>HWANGE SRMS</h2></div>
            <nav>
                <ul class="nav-links">
                    <li class="nav-item"><a href="../dashboard/index.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="minutes_list.php" class="nav-link active"><i class="fa-solid fa-file-lines"></i> Minutes</a></li>
                    <li class="nav-item"><a href="assets_list.php" class="nav-link"><i class="fa-solid fa-boxes-stacked"></i> Assets</a></li>
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link" style="color: #f56565;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header>
                <div class="page-title">
                    <h1>Add Meeting Minutes</h1>
                    <p style="color: var(--text-muted);">Record minutes for parish or diocesan meetings.</p>
                </div>
            </header>

            <?php if ($success): ?>
                <div style="background: var(--success); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                    <?= e($success) ?> <a href="minutes_list.php" style="color: white; font-weight: 700; margin-left: 10px;">View All Minutes</a>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                        <div class="input-group">
                            <label>Meeting Title / Type *</label>
                            <input type="text" name="title" placeholder="e.g. Parish Pastoral Council Meeting" required>
                        </div>
                        <div class="input-group">
                            <label>Meeting Date *</label>
                            <input type="date" name="meeting_date" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Parish / Entity *</label>
                        <select name="parish_id" required>
                            <option value="">-- Select Parish --</option>
                            <?php foreach ($parishes as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Minutes / Content</label>
                        <textarea name="content" rows="15" placeholder="Detailed minutes of the meeting..."></textarea>
                    </div>

                    <div style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem; text-align: right;">
                        <button type="submit" class="btn btn-primary">Save Minutes</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
