<?php
/**
 * Add Parish Asset / Property
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$pdo = getDB();
$parishes = $pdo->query("SELECT id, name FROM parishes ORDER BY name")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = $_POST['item_name'] ?? '';
    $category = $_POST['category'] ?? '';
    $parish_id = $_POST['parish_id'] ?? null;
    $qty = $_POST['quantity'] ?? 1;
    $date = $_POST['acquisition_date'] ?? null;
    $value = $_POST['estimated_value'] ?? null;
    $status = $_POST['current_status'] ?? 'Good';
    $desc = $_POST['description'] ?? '';

    if ($item_name && $parish_id) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO parish_assets (parish_id, item_name, category, description, quantity, acquisition_date, estimated_value, current_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$parish_id, $item_name, $category, $desc, $qty, $date, $value, $status]);
            
            $new_id = $pdo->lastInsertId();
            logAction("Added Parish Asset", "parish_assets", $new_id);
            
            $success = "Asset added successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in required fields (Item Name, Parish).";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Asset - Hwange SRMS</title>
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
                    <li class="nav-item"><a href="minutes_list.php" class="nav-link"><i class="fa-solid fa-file-lines"></i> Minutes</a></li>
                    <li class="nav-item"><a href="assets_list.php" class="nav-link active"><i class="fa-solid fa-boxes-stacked"></i> Assets</a></li>
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link" style="color: #f56565;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header>
                <div class="page-title">
                    <h1>Add Parish Asset</h1>
                    <p style="color: var(--text-muted);">Inventory management for parish property and assets.</p>
                </div>
            </header>

            <?php if ($success): ?>
                <div style="background: var(--success); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                    <?= e($success) ?> <a href="assets_list.php" style="color: white; font-weight: 700; margin-left: 10px;">View Inventory</a>
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
                            <label>Item Name / Asset Name *</label>
                            <input type="text" name="item_name" placeholder="e.g. Parish Hilux, Sound Mixer, 50 Benches" required>
                        </div>
                        <div class="input-group">
                            <label>Category</label>
                            <select name="category">
                                <option value="General">General</option>
                                <option value="Furniture">Furniture</option>
                                <option value="Electronics">Electronics</option>
                                <option value="Vehicle">Vehicle</option>
                                <option value="Property/Land">Property/Land</option>
                                <option value="Liturgical">Liturgical (Vestments, etc.)</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="input-group">
                            <label>Parish / Location *</label>
                            <select name="parish_id" required>
                                <option value="">-- Select Parish --</option>
                                <?php foreach ($parishes as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" value="1" min="1">
                        </div>
                        <div class="input-group">
                            <label>Current Status</label>
                            <select name="current_status">
                                <option value="Excellent">Excellent</option>
                                <option value="Good" selected>Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
                                <option value="Broken">Broken</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="input-group">
                            <label>Acquisition Date</label>
                            <input type="date" name="acquisition_date">
                        </div>
                        <div class="input-group">
                            <label>Estimated Value (USD)</label>
                            <input type="number" step="0.01" name="estimated_value" placeholder="0.00">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Item Description / Serial Number</label>
                        <textarea name="description" rows="3" placeholder="Additional details about the asset..."></textarea>
                    </div>

                    <div style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem; text-align: right;">
                        <button type="submit" class="btn btn-primary">Add Asset</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
