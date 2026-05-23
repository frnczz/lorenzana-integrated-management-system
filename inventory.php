<?php
session_start();

// Allow admin and warehouse roles to access
if (!isset($_SESSION['user_id']) || 
   ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Management | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="wrapper">

    <!-- Sidebar -->
    <?php include "layouts/sidebar.php"; ?>

    <!-- Main Area -->
    <div class="main">

        <!-- Header -->
        <?php include "layouts/header.php"; ?>

        <!-- Content -->
        <div class="content">

            <h2>Inventory Management Module</h2>
            <p>Monitor and manage raw materials and finished goods in real time.</p>

            <?php include "includes/functions.php"; showMessage(); ?>
            <?php include "db_connect.php"; ?>

            <!-- Inventory Summary -->
            <div class="card">
                <h3>Inventory Summary</h3>
                <?php
                // Get summary data
                $raw_materials_count = $conn->query("SELECT COUNT(*) as count FROM raw_materials")->fetch_assoc()['count'];
                $raw_materials_total = $conn->query("SELECT SUM(quantity) as total FROM raw_materials")->fetch_assoc()['total'] ?? 0;
                
                $finished_goods_count = $conn->query("SELECT COUNT(*) as count FROM finished_goods WHERE quantity > 0")->fetch_assoc()['count'];
                $finished_goods_total = $conn->query("SELECT SUM(quantity) as total FROM finished_goods")->fetch_assoc()['total'] ?? 0;
                
                $low_stock = $conn->query("SELECT COUNT(*) as count FROM raw_materials WHERE quantity <= min_stock_level AND quantity > 0")->fetch_assoc()['count'];
                
                $near_expiry = $conn->query("SELECT COUNT(*) as count FROM raw_materials WHERE expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['count'];
                ?>
                <table>
                    <tr>
                        <th>Total Raw Materials</th>
                        <th>Finished Goods</th>
                        <th>Low Stock Items</th>
                        <th>Expired / Near Expiry</th>
                    </tr>
                    <tr>
                        <td><?php echo $raw_materials_count; ?> items<br><small><?php echo number_format($raw_materials_total, 2); ?> total</small></td>
                        <td><?php echo $finished_goods_count; ?> items<br><small><?php echo number_format($finished_goods_total, 2); ?> total</small></td>
                        <td><?php echo $low_stock; ?></td>
                        <td><?php echo $near_expiry; ?></td>
                    </tr>
                </table>
            </div>

            <!-- Add / Update Inventory -->
            <div class="card">
                <h3>Add / Update Inventory Item</h3>
                <form method="POST" action="api/save_inventory.php" data-loading-message="Saving inventory..." data-loading-subtext="Adding or updating inventory item.">
                    <table>
                        <tr>
                            <td>Item Name</td>
                            <td><input type="text" name="item_name" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Category</td>
                            <td>
                                <select name="category" style="width:100%; padding:8px;" required>
                                    <option value="Raw Material">Raw Material</option>
                                    <option value="Finished Product">Finished Product</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Quantity</td>
                            <td><input type="number" name="quantity" step="0.01" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Unit</td>
                            <td>
                                <select name="unit" style="width:100%; padding:8px;" required>
                                    <option value="kg">kg</option>
                                    <option value="liters">liters</option>
                                    <option value="pcs">pcs</option>
                                    <option value="boxes">boxes</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Expiry Date</td>
                            <td><input type="date" name="expiry_date" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td>Warehouse Location <small style="color: var(--text-muted); font-size: 12px;">(Required)</small></td>
                            <td>
                                <select name="warehouse_location" style="width:100%; padding:8px; border: 2px solid var(--border-color); border-radius: var(--border-radius-sm); font-size: 14px; transition: all var(--transition-fast);" required onfocus="this.style.borderColor='#FF6B35'; this.style.boxShadow='0 0 0 3px rgba(255, 107, 53, 0.1)';" onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none';">
                                    <option value="">-- Select Warehouse Location --</option>
                                    <option value="Lot 6720 Brgy San Joaquin Sto Tomas Batangas">Lot 6720 Brgy San Joaquin Sto Tomas Batangas</option>
                                    <option value="Royal GoldCraft Warehouse 4, Lower MagsaysayRoad, Brgy. San Antonio San Pedro 4023 Laguna">Royal GoldCraft Warehouse 4, Lower MagsaysayRoad, Brgy. San Antonio San Pedro 4023 Laguna</option>
                                </select>
                                <small style="color: var(--text-muted); display: block; margin-top: 5px; font-size: 12px;">Select the warehouse where this item is stored</small>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Save Item</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Inventory Records Table -->
            <div class="card">
                <h3>Raw Materials Inventory</h3>
                <?php
                $raw_materials_query = "SELECT * FROM raw_materials ORDER BY material_name";
                $raw_materials_result = $conn->query($raw_materials_query);
                ?>
                <table>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Expiry Date</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                    <?php if ($raw_materials_result && $raw_materials_result->num_rows > 0): ?>
                        <?php while ($item = $raw_materials_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['material_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category'] ?? 'Raw Material'); ?></td>
                                <td><?php echo number_format($item['quantity'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td><?php echo formatDate($item['expiry_date']); ?></td>
                                <td><?php echo htmlspecialchars(formatLocation($item['warehouse_location'] ?? null)); ?></td>
                                <td>
                                    <?php
                                    if ($item['quantity'] <= $item['min_stock_level']) {
                                        echo '<span style="color: #dc2626; font-weight: bold;">Low Stock</span>';
                                    } else {
                                        echo '<span style="color: #10b981;">Available</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: var(--text-muted);">No raw materials found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Finished Goods Table -->
            <div class="card">
                <h3>Finished Goods Inventory</h3>
                <?php
                $finished_goods_query = "SELECT fg.*, p.product_name FROM finished_goods fg 
                                         LEFT JOIN products p ON fg.product_id = p.product_id 
                                         ORDER BY p.product_name";
                $finished_goods_result = $conn->query($finished_goods_query);
                ?>
                <table>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                    <?php if ($finished_goods_result && $finished_goods_result->num_rows > 0): ?>
                        <?php while ($item = $finished_goods_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($item['quantity'], 2); ?></td>
                                <td><?php echo formatDate($item['expiry_date']); ?></td>
                                <td><?php echo htmlspecialchars(formatLocation($item['warehouse_location'] ?? null)); ?></td>
                                <td>
                                    <?php
                                    if ($item['quantity'] > 0) {
                                        echo '<span style="color: #10b981;">In Stock</span>';
                                    } else {
                                        echo '<span style="color: #dc2626;">Out of Stock</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: var(--text-muted);">No finished goods found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Near Expiry Finished Goods -->
            <div class="card">
                <h3>Near Expiry Finished Goods</h3>
                <?php
                $near_expiry_fg_query = "SELECT fg.*, p.product_name FROM finished_goods fg \
                                       LEFT JOIN products p ON fg.product_id = p.product_id \
                                       WHERE fg.expiry_date IS NOT NULL \
                                         AND fg.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) \
                                       ORDER BY fg.expiry_date ASC \
                                       LIMIT 20";
                $near_expiry_fg_result = $conn->query($near_expiry_fg_query);
                ?>
                <table>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Location</th>
                    </tr>
                    <?php if ($near_expiry_fg_result && $near_expiry_fg_result->num_rows > 0): ?>
                        <?php while ($fg = $near_expiry_fg_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fg['product_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($fg['quantity'], 2); ?></td>
                                <td><?php echo formatDate($fg['expiry_date']); ?></td>
                                <td><?php echo htmlspecialchars(formatLocation($fg['warehouse_location'] ?? null)); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted);">No near-expiry finished goods found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

        </div>

        <!-- Footer -->
        <?php include "layouts/footer.php"; ?>

    </div>

</div>

<script src="assets/js/sidebar.js"></script>

</body>
</html>
