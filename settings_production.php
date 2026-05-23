<?php
session_start();

// =========================
// ACCESS CONTROL
// =========================
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'production'])) {
    header("Location: login.php");
    exit;
}

include "db_connect.php";
include "includes/functions.php";

// =========================
// SAVE SETTINGS
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save production settings
    if (isset($_POST['settings'])) {
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $conn->prepare("
                UPDATE warehouse_settings
                SET setting_value = ?
                WHERE setting_key = ?
            ");
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Save product shelf life settings
    if (isset($_POST['shelf_life'])) {
        foreach ($_POST['shelf_life'] as $product_id => $shelf_life_days) {
            $shelf_life_days = intval($shelf_life_days);
            $product_id = intval($product_id);
            
            $stmt = $conn->prepare("
                UPDATE products
                SET shelf_life_days = ?
                WHERE product_id = ?
            ");
            $stmt->bind_param("ii", $shelf_life_days, $product_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Fermentation duration days per product (production_settings: one row per product)
    if (isset($_POST['ferm_duration_days']) && is_array($_POST['ferm_duration_days'])) {
        $ferm_ins = $conn->prepare("
            INSERT INTO production_settings (product_id, setting_key, setting_value)
            VALUES (?, 'fermentation_duration_days', ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        foreach ($_POST['ferm_duration_days'] as $pid => $val) {
            $pid = (int)$pid;
            if ($pid <= 0) {
                continue;
            }
            $days = max(0, (int)$val);
            $days_val = (string)$days;
            $ferm_ins->bind_param('is', $pid, $days_val);
            $ferm_ins->execute();
        }
        $ferm_ins->close();
    }

    // Save finished goods stock adjustments (updates inventory quantities)
    if (isset($_POST['finished_stock'])) {
        foreach ($_POST['finished_stock'] as $product_id => $desired_qty) {
            $product_id = intval($product_id);
            $desired_qty = floatval($desired_qty);

            // Determine current available and reserved stock for the product
            $current_available = 0.0;
            $current_reserved = 0.0;
            $stock_res = $conn->query("SELECT 
                    COALESCE(SUM(GREATEST(quantity - COALESCE(reserved_quantity, 0), 0)), 0) AS available_qty,
                    COALESCE(SUM(COALESCE(reserved_quantity, 0)), 0) AS reserved_qty,
                    COALESCE(SUM(quantity), 0) AS total_qty
                FROM finished_goods
                WHERE product_id = {$product_id}");
            if ($stock_res) {
                $row = $stock_res->fetch_assoc();
                $current_available = (float)($row['available_qty'] ?? 0);
                $current_reserved = (float)($row['reserved_qty'] ?? 0);
                $current_total = (float)($row['total_qty'] ?? 0);
            } else {
                $current_total = 0.0;
            }

            // Calculate what the total quantity should be to reach the desired available quantity
            $desired_total_qty = $desired_qty + $current_reserved;
            $delta = $desired_total_qty - $current_total;

            if (abs($delta) < 0.0001) {
                continue;
            }

            // Adjust all finished goods rows for this product proportionally (preserve warehouse allocations)
            $fg_res = $conn->query("SELECT fg_id, quantity FROM finished_goods WHERE product_id = {$product_id}");
            if ($fg_res && $fg_res->num_rows > 0) {
                if ($current_total <= 0) {
                    // If existing stock is zero, set the first row to the desired total quantity (available + reserved) and zero out others
                    $first = $fg_res->fetch_assoc();
                    $stmt = $conn->prepare("UPDATE finished_goods SET quantity = ? WHERE fg_id = ?");
                    $stmt->bind_param("di", $desired_total_qty, $first['fg_id']);
                    $stmt->execute();
                    $stmt->close();

                    while ($row = $fg_res->fetch_assoc()) {
                        $stmt = $conn->prepare("UPDATE finished_goods SET quantity = 0 WHERE fg_id = ?");
                        $stmt->bind_param("i", $row['fg_id']);
                        $stmt->execute();
                        $stmt->close();
                    }
                } else {
                    $ratio = $desired_total_qty / $current_total;
                    while ($row = $fg_res->fetch_assoc()) {
                        $new_qty = max(0, $row['quantity'] * $ratio);
                        $stmt = $conn->prepare("UPDATE finished_goods SET quantity = ? WHERE fg_id = ?");
                        $stmt->bind_param("di", $new_qty, $row['fg_id']);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO finished_goods (product_id, quantity, qc_approved) VALUES (?, ?, 1)");
                $stmt->bind_param("id", $product_id, $desired_total_qty);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    setMessage('Settings saved successfully.', 'success');
}

// =========================
// LOAD SETTINGS
// =========================
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM warehouse_settings LIMIT 100");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Load products with shelf life for display
$products_with_shelf_life = [];
$prod_result = $conn->query("
    SELECT product_id, product_name, shelf_life_days
    FROM products
    ORDER BY product_name
");
if ($prod_result) {
    while ($row = $prod_result->fetch_assoc()) {
        $products_with_shelf_life[] = $row;
    }
}

// Products eligible for fermentation — fermentation duration days for auto status
$fermentation_duration_days = [];
$fr = $conn->query("SELECT product_id, setting_value FROM production_settings WHERE setting_key = 'fermentation_duration_days'");
if ($fr) {
    while ($frow = $fr->fetch_assoc()) {
        $fermentation_duration_days[(string)(int)$frow['product_id']] = max(0, (int)$frow['setting_value']);
    }
}

$products_fermentation_settings = [];
$pferm = $conn->query("
    SELECT product_id, product_name, COALESCE(fermentation_eligible, 1) AS fermentation_eligible
    FROM products
    WHERE COALESCE(fermentation_eligible, 1) = 1
    ORDER BY product_name
");
if ($pferm) {
    while ($row = $pferm->fetch_assoc()) {
        $products_fermentation_settings[] = $row;
    }
}

// Load finished goods stock totals (for editable inventory adjustments)
$finished_goods_stock = [];
$fg_stock_result = $conn->query("
    SELECT p.product_id,
           p.product_name,
           COALESCE(SUM(GREATEST(fg.quantity - COALESCE(fg.reserved_quantity, 0), 0)), 0) as available_quantity,
           COALESCE(SUM(COALESCE(fg.reserved_quantity, 0)), 0) as reserved_quantity,
           COALESCE(SUM(fg.quantity), 0) as total_quantity
    FROM products p
    LEFT JOIN finished_goods fg ON p.product_id = fg.product_id
    GROUP BY p.product_id, p.product_name
    ORDER BY p.product_name
");
if ($fg_stock_result) {
    while ($row = $fg_stock_result->fetch_assoc()) {
        $finished_goods_stock[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Production Settings | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>

        <div class="content">
            <h2>Production Settings</h2>
            <p>Configure production defaults and batch parameters.</p>
            <?php showMessage(); ?>

            <div class="card">
                <form method="POST">
                    <h3>Product Defaults</h3>
                    <table>
                        <tr>
                            <td>
                                <label><strong>Default Batch Size</strong></label>
                                <small style="color:#666; display:block; margin-top:3px;">Standard number of units produced per batch. Used when creating new production batches without specific quantities.</small>
                            </td>
                            <td>
                                <input type="number" min="1" name="settings[default_batch_size]" style="width:100%; padding:6px;"
                                       value="<?= htmlspecialchars($settings['default_batch_size'] ?? 100) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><strong>Estimated Production Time (hours)</strong></label>
                                <small style="color:#666; display:block; margin-top:3px;">Average time in hours required to complete one batch. Helps with production planning and scheduling.</small>
                            </td>
                            <td>
                                <input type="number" step="0.5" min="0.5" name="settings[production_time_hours]" style="width:100%; padding:6px;"
                                       value="<?= htmlspecialchars($settings['production_time_hours'] ?? 8) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><strong>Expected Yield (%)</strong></label>
                                <small style="color:#666; display:block; margin-top:3px;">Percentage of finished products expected from input materials (0-100). Accounts for waste and quality losses.</small>
                            </td>
                            <td>
                                <input type="number" step="0.1" min="0" max="100" name="settings[expected_yield]" style="width:100%; padding:6px;"
                                       value="<?= htmlspecialchars($settings['expected_yield'] ?? 95) ?>">
                            </td>
                        </tr>
                    </table>

                    <h3 style="margin-top: 30px; border-top: 2px solid #e5e7eb; padding-top: 20px;">Fermentation Duration (per product)</h3>
                    <p style="color: #666; margin-bottom: 15px;">Used automatically when recording a batch. Fermentation status is now date-based: <strong>Not Started → Ongoing → Completed</strong> based on production date and duration days (for fermentation-eligible products only).</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width: 220px;">Fermentation duration (days)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products_fermentation_settings as $fp): ?>
                                <?php
                                $pid = (int)$fp['product_id'];
                                $cur_days = $fermentation_duration_days[(string)$pid] ?? 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($fp['product_name']); ?></strong></td>
                                    <td>
                                        <input type="number" min="0" step="1" name="ferm_duration_days[<?php echo $pid; ?>]" style="width: 100%; padding: 8px;" value="<?php echo (int)$cur_days; ?>">
                                        <small style="display:block; color:#64748b; margin-top:4px;">0 = keep as Not Started until manually progressed by date updates.</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($products_fermentation_settings) === 0): ?>
                                <tr><td colspan="2" style="color:#666;">No fermentation-eligible products. Enable fermentation in product management if needed.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <h3 style="margin-top: 30px; border-top: 2px solid #e5e7eb; padding-top: 20px;">Product Shelf Life Configuration</h3>
                    <p style="color: #666; margin-bottom: 15px;">Set how long each product remains shelf-stable after production (in days). This is used to automatically calculate batch expiry dates. Examples: 365 days = 1 year, 730 days = 2 years.</p>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th style="width: 150px;">Shelf Life (Days)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products_with_shelf_life as $prod): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($prod['product_name']); ?></strong></td>
                                <td>
                                    <input type="number" min="0" name="shelf_life[<?php echo $prod['product_id']; ?>]" 
                                           style="width: 100%; padding: 8px;"
                                           value="<?php echo htmlspecialchars($prod['shelf_life_days']); ?>" required>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h3 style="margin-top: 30px; border-top: 2px solid #e5e7eb; padding-top: 20px;">Finished Goods Inventory Adjustment</h3>
                    <p style="color: #666; margin-bottom: 15px;">Adjust finished goods stock quantities directly. Updating values here will update the inventory quantities for each product.</p>

                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Available Stock</th>
                                <th>Reserved</th>
                                <th style="width: 170px;">Set Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($finished_goods_stock as $fg): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($fg['product_name']); ?></strong></td>
                                    <td><?php echo number_format((float)$fg['available_quantity'], 2); ?></td>
                                    <td><?php echo number_format((float)$fg['reserved_quantity'], 2); ?></td>
                                    <td>
                                        <input type="number" min="0" step="0.01" name="finished_stock[<?php echo $fg['product_id']; ?>]"
                                               style="width: 100%; padding: 8px;"
                                               value="<?php echo htmlspecialchars(number_format((float)$fg['available_quantity'], 2, '.', '')); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="text-align:right; margin-top:20px;">
                        <button type="submit" class="btn">Save All Settings</button>
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
