<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "db_connect.php";
include "includes/functions.php";

$conn->query("CREATE TABLE IF NOT EXISTS pagination_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(50) NOT NULL UNIQUE, setting_value VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
$conn->query("INSERT IGNORE INTO pagination_settings (setting_key, setting_value, description) VALUES ('items_per_page', '25', 'Default rows per page'), ('per_page_options', '10,25,50,100,200', 'Dropdown options')");

$allowed_keys = ['items_per_page', 'per_page_options'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings']) && is_array($_POST['settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        if (!in_array($key, $allowed_keys, true)) continue;
        $value = trim((string)$value);
        if ($key === 'items_per_page') $value = (string)max(5, min(500, (int)$value));
        elseif ($key === 'per_page_options') $value = preg_replace('/[^0-9,]/', '', $value);
        $stmt = $conn->prepare("UPDATE pagination_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            $stmt->close();
            $ins = $conn->prepare("INSERT INTO pagination_settings (setting_key, setting_value) VALUES (?, ?)");
            $ins->bind_param("ss", $key, $value);
            $ins->execute();
            $ins->close();
        } else $stmt->close();
    }
    setMessage('Pagination settings saved.', 'success');
    header("Location: settings_pagination.php");
    exit;
}

$settings = [];
$r = $conn->query("SELECT setting_key, setting_value FROM pagination_settings");
if ($r) while ($row = $r->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
$items_per_page = (int)($settings['items_per_page'] ?? 25);
$per_page_options = $settings['per_page_options'] ?? '10,25,50,100,200';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pagination Settings | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
<?php include "layouts/sidebar.php"; ?>
<div class="main">
<?php include "layouts/header.php"; ?>
<div class="content">
<h2>Pagination Settings</h2>
<p>Configure rows per page in tables across the system.</p>
<?php showMessage(); ?>
<div class="card">
    <form method="POST">
        <h3>Table Pagination</h3>
        <table>
            <tr>
                <td style="width:300px;"><label><strong>Default items per page</strong></label><small style="color:#666;display:block;margin-top:3px;">5–500</small></td>
                <td><input type="number" name="settings[items_per_page]" min="5" max="500" value="<?php echo htmlspecialchars($items_per_page); ?>" style="width:120px;padding:8px;"></td>
            </tr>
            <tr>
                <td><label><strong>Per-page options</strong></label><small style="color:#666;display:block;margin-top:3px;">Comma-separated (e.g. 10,25,50,100,200)</small></td>
                <td><input type="text" name="settings[per_page_options]" value="<?php echo htmlspecialchars($per_page_options); ?>" style="width:100%;max-width:300px;padding:8px;"></td>
            </tr>
        </table>
        <div style="margin-top:20px;"><button type="submit" class="btn">Save Settings</button></div>
    </form>
</div>
</div>
<?php include "layouts/footer.php"; ?>
</div>
</div>
<script src="assets/js/sidebar.js"></script>
</body>
</html>
