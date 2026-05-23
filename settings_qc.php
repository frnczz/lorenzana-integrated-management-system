<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'qc'])) {
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
            UPDATE qc_settings
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
$result = $conn->query("SELECT setting_key, setting_value FROM qc_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quality Control Settings | LORINIMS</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
<?php include "layouts/sidebar.php"; ?>
<div class="main">
<?php include "layouts/header.php"; ?>

<div class="content">
<h2>Quality Control Settings</h2>
<p>Configure QC rules and inspection validation.</p>
<?php showMessage(); ?>


<div class="card">
    <form method="POST">
        <h3>Inspection Scoring Rules</h3>
        <table>
            <tr>
                <td>
                    <label><strong>Minimum Pass Score (%)</strong></label>
                    <small style="color:#666; display:block; margin-top:3px;">Minimum percentage score (0-100) required for QC inspection to pass. Items below this are automatically reviewed for conditional or reject status.</small>
                </td>
                <td>
                    <input type="number" min="0" max="100" name="settings[min_pass_score]" style="width:100%; padding:6px;"
                           value="<?= htmlspecialchars($settings['min_pass_score'] ?? 85) ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label><strong>Mandatory Inspection Fields</strong></label>
                    <small style="color:#666; display:block; margin-top:3px;">Comma-separated list of required fields that must be checked during inspection (e.g., Appearance, Weight, Seal).</small>
                </td>
                <td>
                    <input type="text" name="settings[mandatory_fields]" style="width:100%; padding:6px;"
                           value="<?= htmlspecialchars($settings['mandatory_fields'] ?? 'Appearance, Weight, Seal') ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label><strong>Auto-Reject Below Pass Score</strong></label>
                    <small style="color:#666; display:block; margin-top:3px;">When enabled, items scoring below minimum automatically move to rejected status. When disabled, they can be manually reviewed.</small>
                </td>
                <td>
                    <select name="settings[auto_reject]" style="width:100%; padding:6px;">
                        <option value="1" <?= ($settings['auto_reject'] ?? 1) == 1 ? 'selected' : '' ?>>Enabled</option>
                        <option value="0" <?= ($settings['auto_reject'] ?? 1) == 0 ? 'selected' : '' ?>>Disabled</option>
                    </select>
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
