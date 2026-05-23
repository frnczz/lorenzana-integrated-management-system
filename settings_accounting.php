<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'accounting'])) {
    header("Location: login.php");
    exit;
}

include "db_connect.php";
include "includes/functions.php";

// =========================
// SAVE SETTINGS
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $conn->prepare("
            UPDATE accounting_settings
            SET setting_value = ?
            WHERE setting_key = ?
        ");
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
        $stmt->close();
    }
    setMessage('Settings saved successfully.', 'success');
}

// =========================
// LOAD SETTINGS
// =========================
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM accounting_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Accounting Settings | LORINIMS</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
<?php include "layouts/sidebar.php"; ?>
<div class="main">
<?php include "layouts/header.php"; ?>

<div class="content">
<h2>Accounting Settings</h2>
<p>Configure financial settings and accounting defaults.</p>
<?php showMessage(); ?>

<div class="card">
    <form method="POST">
        <h3>Tax and Financial Configuration</h3>
        <table>
            <tr>
                        <td>
                            <label><strong>VAT Rate (%)</strong></label>
                            <small style="color:#666; display:block; margin-top:3px;">Value-Added Tax percentage applied to invoices. Standard is 12%.</small>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="settings[vat_rate]" style="width:100%; padding:6px;"
                                   value="<?= htmlspecialchars($settings['vat_rate'] ?? 12) ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><strong>Invoice Prefix</strong></label>
                            <small style="color:#666; display:block; margin-top:3px;">Prefix used for invoice numbering (e.g., INV-2026-). The system appends sequential numbers.</small>
                        </td>
                        <td>
                            <input type="text" name="settings[invoice_prefix]" style="width:100%; padding:6px;"
                                   value="<?= htmlspecialchars($settings['invoice_prefix'] ?? 'INV-2026-') ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><strong>Default Revenue Account</strong></label>
                            <small style="color:#666; display:block; margin-top:3px;">Primary account used for recording sales revenue in accounting records.</small>
                        </td>
                        <td>
                            <input type="text" name="settings[default_revenue_account]" style="width:100%; padding:6px;"
                                   value="<?= htmlspecialchars($settings['default_revenue_account'] ?? 'Sales Revenue') ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><strong>Monthly Cut-Off Day</strong></label>
                            <small style="color:#666; display:block; margin-top:3px;">Day of the month when accounting period ends (1-31). Used for period closing and reporting.</small>
                        </td>
                        <td>
                            <input type="number" min="1" max="31" name="settings[cutoff_day]" style="width:100%; padding:6px;"
                                   value="<?= htmlspecialchars($settings['cutoff_day'] ?? 30) ?>">
                        </td>
                    </tr>
        </table>

        <div style="text-align:right;margin-top:15px;">
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
