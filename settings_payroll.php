<?php
session_start();

// =========================
// ACCESS CONTROL
// =========================
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'accounting'])) {
    header("Location: login.php");
    exit;
}

// =========================
// INCLUDE DB AND FUNCTIONS
// =========================
include "db_connect.php";
include "includes/functions.php";

// =========================
// SAVE SETTINGS
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $conn->prepare("
            UPDATE payroll_settings
            SET setting_value = ?
            WHERE setting_key = ?
        ");
        $stmt->bind_param("ds", $value, $key); // double, string
        $stmt->execute();
        $stmt->close();
    }
    setMessage('Settings saved successfully.', 'success');
}

// =========================
// LOAD SETTINGS
// =========================
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM payroll_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Settings | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>

        <div class="content">
            <h2>Payroll Settings</h2>
            <p>Configure late penalties and statutory deduction rates.</p>
            <?php showMessage(); ?>


            <div class="card">
                <form method="POST">
                    <h3>Late Penalty</h3>
                    <table>
                        <tr>
                            <td>
                                <label><strong>Grace Minutes</strong></label>
                                <small style="color:#666; display:block; margin-top:3px;">Number of minutes an employee can be late before penalty applies (e.g., 15 minutes grace period).</small>
                            </td>
                            <td>
                                <input type="number" name="settings[late_grace_minutes]" style="width:100%; padding:6px;"
                                       value="<?= htmlspecialchars($settings['late_grace_minutes'] ?? 0) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><strong>Penalty per Minute (₱)</strong></label>
                                <small style="color:#666; display:block; margin-top:3px;\">Amount deducted from salary per minute of lateness, after grace period expires. Used to calculate late penalties.</small>
                            </td>
                            <td>
                                <input type="number" step="0.01" name="settings[late_penalty_per_minute]" style="width:100%; padding:6px;"
                                       value="<?= htmlspecialchars($settings['late_penalty_per_minute'] ?? 0) ?>">
                            </td>
                        </tr>
                    </table>
                    <br>
                    <br>
                    <h3>Statutory Deductions</h3>
                    <table>
                        <tr>
                            <td>
                                <label><strong>PhilHealth Rate</strong></label>
                                <small style="color:#666; display:block; margin-top:3px;\">Philippine Health Insurance Corporation contribution rate (decimal, e.g., 0.025 = 2.5%). Employee portion deducted from salary.</small>
                            </td>
                            <td>
                                <input type="number" step="0.0001" name="settings[philhealth_rate]" style="width:100%; padding:6px;"
                                       value="<?= htmlspecialchars($settings['philhealth_rate'] ?? 0) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><strong>Pag-IBIG Rate</strong></label>
                                <small style="color:#666; display:block; margin-top:3px;\">Home Development Mutual Fund contribution rate (decimal, e.g., 0.02 = 2%). Employee portion deducted from salary.</small>
                            </td>
                            <td>
                                <input type="number" step="0.0001" name="settings[pagibig_rate]" style="width:100%; padding:6px;"
                                       value="<?= htmlspecialchars($settings['pagibig_rate'] ?? 0) ?>">
                            </td>
                        </tr>
                    </table>

                    <div style="text-align:right; margin-top:15px;">
                        <button type="submit" class="btn">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
</body>
</html>
