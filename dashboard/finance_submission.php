<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Parish Financial Submission Form
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_login();

$parish_id = $_SESSION['parish_id'];
if (!$parish_id) die("You must be assigned to a parish to submit financial reports.");

$year = date('Y');
$month = date('m');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total_collections = $_POST['total_collections'];
    $sacramental_offerings = $_POST['sacramental_offerings'];
    $other_income = $_POST['other_income'];
    $fiscal_year = $_POST['fiscal_year'];
    $fiscal_month = $_POST['fiscal_month'];
    
    // Calculate 10% Levy
    $calculated_levy = ($total_collections + $sacramental_offerings + $other_income) * 0.10;

    $sql = "INSERT INTO parish_financial_submissions (parish_id, fiscal_year, fiscal_month, total_collections, sacramental_offerings, other_income, calculated_levy, status, submitted_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Submitted', ?)";
    db_query($sql, [$parish_id, $fiscal_year, $fiscal_month, $total_collections, $sacramental_offerings, $other_income, $calculated_levy, $_SESSION['user_id']]);

    set_flash("Financial report for month $fiscal_month has been submitted successfully.");
    redirect("index.php");
}

$header_title = "Financial Remittance";
$header_subtitle = "Submit monthly financial assessment to the Diocesan Chancery.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Remittance - Hwange Diocese</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=1.1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-body" style="padding: 2rem 0; max-width: 600px; margin: 0 auto;">
                <div class="card bg-card" style="padding: 3rem; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05);">
                    <h2 style="font-family: 'Outfit'; color: var(--accent); margin-bottom: 2rem;">Monthly Assessment</h2>
                    
                    <form method="POST" class="premium-form">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                            <div class="form-group">
                                <label class="form-label">Fiscal Year</label>
                                <select name="fiscal_year" class="input-field">
                                    <option value="<?php echo date('Y'); ?>"><?php echo date('Y'); ?></option>
                                    <option value="<?php echo date('Y')-1; ?>"><?php echo date('Y')-1; ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fiscal Month</label>
                                <select name="fiscal_month" class="input-field">
                                    <?php for($m=1; $m<=12; $m++): ?>
                                        <option value="<?php echo $m; ?>" <?php echo $m == date('m') ? 'selected' : ''; ?>>
                                            <?php echo date('F', mktime(0,0,0,$m,1)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Total Sunday Collections ($)</label>
                            <input type="number" step="0.01" name="total_collections" class="input-field" required placeholder="0.00">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Sacramental Offerings / Stole Fees ($)</label>
                            <input type="number" step="0.01" name="sacramental_offerings" class="input-field" required placeholder="0.00">
                        </div>

                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label class="form-label">Other Parish Income ($)</label>
                            <input type="number" step="0.01" name="other_income" class="input-field" required placeholder="0.00">
                        </div>

                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); padding: 1.5rem; border-radius: 1rem; margin-bottom: 2rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.8rem; color: #10b981; font-weight: 700;">CALCULATED DIOCESAN LEVY (10%)</span>
                                <span id="levy-display" style="font-size: 1.5rem; font-weight: 900; color: white;">$0.00</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1.25rem; font-weight: 800;">Submit Financial Report</button>
                    </form>
                    
                    <div style="margin-top: 2rem; padding: 1rem; background: rgba(56, 189, 248, 0.05); border-radius: 12px; border: 1px dashed rgba(56, 189, 248, 0.2);">
                        <p style="font-size: 0.75rem; color: var(--accent); display: flex; align-items: center; gap: 8px;">
                            <ion-icon name="sync-outline"></ion-icon>
                            <strong>ERP Integration Engine:</strong> Data validated for export to Hwange Diocesan Financial System.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js?v=1.6.2"></script>
    <?php include '../includes/privacy_footer.php'; ?>

    <script>
        const inputs = document.querySelectorAll('input[type="number"]');
        const display = document.getElementById('levy-display');

        inputs.forEach(input => {
            input.addEventListener('input', () => {
                let total = 0;
                inputs.forEach(i => total += parseFloat(i.value || 0));
                display.innerText = '$' + (total * 0.10).toFixed(2);
            });
        });
    </script>
</body>
</html>
