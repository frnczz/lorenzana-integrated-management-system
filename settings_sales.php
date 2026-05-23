<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'sales'])) {
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
            UPDATE sales_settings
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
$result = $conn->query("SELECT setting_key, setting_value FROM sales_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sales Settings | LORINIMS</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
<?php include "layouts/sidebar.php"; ?>
<div class="main">
<?php include "layouts/header.php"; ?>

<div class="content">
<h2>Sales Settings</h2>
<p>Configure pricing and sales policies.</p>
<?php showMessage(); ?>


<div class="card">
    <form method="POST">
        <h3>Pricing and Discount Configuration</h3>
        <table>
            <tr>
                <td>
                    <label><strong>Default Product Price (₱)</strong></label>
                    <small style="color:#666; display:block; margin-top:3px;">Standard price in Philippine Pesos used when adding products without an existing price. Can be overridden per product.</small>
                </td>
                <td>
                    <input type="number" step="0.01" name="settings[default_price]" style="width:100%; padding:6px;"
                           value="<?= htmlspecialchars($settings['default_price'] ?? 100.00) ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label><strong>Maximum Discount (%)</strong></label>
                    <small style="color:#666; display:block; margin-top:3px;">Maximum percentage discount (0-100) allowed on sales orders. Prevents excessive discounting without approval.</small>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" max="100" name="settings[max_discount]" style="width:100%; padding:6px;"
                           value="<?= htmlspecialchars($settings['max_discount'] ?? 10) ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label><strong>VAT Rate (%)</strong></label>
                    <small style="color:#666; display:block; margin-top:3px;">Value-Added Tax percentage applied to all sales orders and invoices. Standard is 12% in the Philippines.</small>
                </td>
                <td>
                    <input type="number" step="0.01" name="settings[vat_rate]" style="width:100%; padding:6px;"
                           value="<?= htmlspecialchars($settings['vat_rate'] ?? 12) ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label><strong>Allowed Payment Terms</strong></label>
                    <small style="color:#666; display:block; margin-top:3px;">Comma-separated list of accepted payment options (e.g., Cash, 30 Days, 60 Days, Check).</small>
                </td>
                <td>
                    <input type="text" name="settings[payment_terms]" style="width:100%; padding:6px;"
                           value="<?= htmlspecialchars($settings['payment_terms'] ?? 'Cash, 30 Days') ?>">
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
