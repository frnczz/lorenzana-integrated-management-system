<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dash-hero { margin-bottom: 24px; }
        .dash-hero h2 { font-size: 1.75rem; font-weight: 700; margin-bottom: 4px; }
        .dash-hero p { color: var(--text-muted); font-size: 0.95rem; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 28px; }
        .stat-card { padding: 22px; border-radius: 12px; color: white; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.1); transform: translateX(-100%); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 12px rgba(0,0,0,0.15); }
        .stat-card:hover::before { transform: translateX(0); }
        .stat-card .label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; font-weight: 600; opacity: 0.9; }
        .stat-card .value { font-size: 2rem; font-weight: 700; margin: 10px 0; }
        .stat-card .meta { font-size: 0.85rem; opacity: 0.85; }
        .stat-card .icon { font-size: 2.5rem; margin-bottom: 8px; }
        .stat-card:nth-child(1) { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card:nth-child(2) { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card:nth-child(3) { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card:nth-child(4) { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-card:nth-child(5) { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .stat-card:nth-child(6) { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        .stat-card:nth-child(7) { background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); }
        @media (max-width: 768px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } .stat-card .value { font-size: 1.5rem; } }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <div class="dash-hero">
                <h2>Admin Control Panel</h2>
                <p>Lorenzana Food Corporation Integrated Management System (LORINIMS). Central command center for all modules.</p>
            </div>

            <?php include "db_connect.php"; ?>
            <?php include "includes/functions.php"; showMessage(); ?>
            <?php include "includes/low_stock_alerts.php"; ?>

            <?php
            try {
                $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] ?? 0;
                $total_batches = $conn->query("SELECT COUNT(*) as count FROM production_batches")->fetch_assoc()['count'] ?? 0;
                $total_orders = $conn->query("SELECT COUNT(*) as count FROM sales_orders")->fetch_assoc()['count'] ?? 0;
                $total_invoices = $conn->query("SELECT COUNT(*) as count FROM invoices")->fetch_assoc()['count'] ?? 0;
                $active_deliveries = $conn->query("SELECT COUNT(*) as count FROM delivery_assignments WHERE status IN ('Dispatched', 'On the Way', 'Arrived')")->fetch_assoc()['count'] ?? 0;
                $total_revenue = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'Paid'")->fetch_assoc()['total'] ?? 0;
                $near_expiry_pagination = function_exists('getPagination')
                    ? getPagination(
                        $conn,
                        "SELECT COUNT(*) as c FROM finished_goods f WHERE f.expiry_date IS NOT NULL AND f.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)",
                        null,
                        'near_expiry_admin_page',
                        'near_expiry_admin_per_page'
                    )
                    : ['offset' => 0, 'per_page' => 25];

                $near_expiry_finished_goods = $conn->query("SELECT f.*, p.product_name FROM finished_goods f LEFT JOIN products p ON f.product_id = p.product_id WHERE f.expiry_date IS NOT NULL AND f.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY f.expiry_date ASC LIMIT " . $near_expiry_pagination['offset'] . ", " . $near_expiry_pagination['per_page'] . "")->fetch_all(MYSQLI_ASSOC);
            } catch (Exception $e) {
                $total_users = $total_batches = $total_orders = $total_invoices = $active_deliveries = 0;
                $total_revenue = 0;
                $near_expiry_finished_goods = [];
            }
            ?>

            <!-- Stat Grid (warehouse style) -->
            <div class="stat-grid">
                <div class="stat-card" onclick="window.location.href='users.php'">
                    <div class="icon">👥</div>
                    <div class="label">Total Users</div>
                    <div class="value"><?php echo number_format($total_users); ?></div>
                    <div class="meta">Click to manage</div>
                </div>
                <div class="stat-card" onclick="window.location.href='production_record.php'">
                    <div class="icon">🏭</div>
                    <div class="label">Production Batches</div>
                    <div class="value"><?php echo number_format($total_batches); ?></div>
                    <div class="meta">Click to view</div>
                </div>
                <div class="stat-card" onclick="window.location.href='sales.php'">
                    <div class="icon">💼</div>
                    <div class="label">Sales Orders</div>
                    <div class="value"><?php echo number_format($total_orders); ?></div>
                    <div class="meta">Click to manage</div>
                </div>
                <div class="stat-card" onclick="window.location.href='accounting.php'">
                    <div class="icon">💰</div>
                    <div class="label">Invoices</div>
                    <div class="value"><?php echo number_format($total_invoices); ?></div>
                    <div class="meta">Click to view</div>
                </div>
                <div class="stat-card" onclick="window.location.href='sales.php'">
                    <div class="icon">🚚</div>
                    <div class="label">Active Deliveries</div>
                    <div class="value"><?php echo number_format($active_deliveries); ?></div>
                    <div class="meta">In progress</div>
                </div>
                <div class="stat-card">
                    <div class="icon">📊</div>
                    <div class="label">Revenue</div>
                    <div class="value" style="font-size: 1.4rem;"><?php echo formatCurrency($total_revenue); ?></div>
                    <div class="meta">Total paid</div>
                </div>
                <div class="stat-card" onclick="window.location.href='inventory_summary.php#low-stock-alerts'">
                    <div class="icon">⚠️</div>
                    <div class="label">Low Stock Alerts</div>
                    <div class="value"><?php echo number_format($total_low_stock); ?></div>
                    <div class="meta"><?php echo number_format($low_stock_raw); ?> raw, <?php echo number_format($low_stock_fg); ?> FG</div>
                </div>
            </div>

            <!-- Content cards and tables - tables below -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
                <div class="card">
                    <h3>Today's Activity</h3>
                    <?php
                    try {
                        $recent_batches = $conn->query("SELECT COUNT(*) as count FROM production_batches WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'] ?? 0;
                        $recent_orders = $conn->query("SELECT COUNT(*) as count FROM sales_orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'] ?? 0;
                        $recent_qc = $conn->query("SELECT COUNT(*) as count FROM qc_records WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'] ?? 0;
                        $recent_invoices = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'] ?? 0;
                        $recent_deliveries = $conn->query("SELECT COUNT(*) as count FROM delivery_assignments WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'] ?? 0;
                    } catch (Exception $e) {
                        $recent_batches = $recent_orders = $recent_qc = $recent_invoices = $recent_deliveries = 0;
                    }
                    ?>
                    <div style="margin-top: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid var(--border-color);">
                            <span><strong>Production Batches</strong></span>
                            <span style="font-size: 20px; font-weight: bold; color: #10b981;"><?php echo $recent_batches; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid var(--border-color);">
                            <span><strong>Sales Orders</strong></span>
                            <span style="font-size: 20px; font-weight: bold; color: #3b82f6;"><?php echo $recent_orders; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid var(--border-color);">
                            <span><strong>QC Inspections</strong></span>
                            <span style="font-size: 20px; font-weight: bold; color: #8b5cf6;"><?php echo $recent_qc; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid var(--border-color);">
                            <span><strong>Invoices</strong></span>
                            <span style="font-size: 20px; font-weight: bold; color: #f59e0b;"><?php echo $recent_invoices; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px;">
                            <span><strong>Deliveries Assigned</strong></span>
                            <span style="font-size: 20px; font-weight: bold; color: #06b6d4;"><?php echo $recent_deliveries; ?></span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <h3>Quick Actions</h3>
                    <div style="display: grid; gap: 10px; margin-top: 15px;">
                        <a href="production_record.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px;">🏭 New Production Batch</a>
                        <a href="sales.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px;">💼 New Sales Order</a>
                        <a href="accounting.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px;">💰 Create Invoice</a>
                        <a href="users.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px;">👥 Manage Users</a>
                        <a href="inventory_summary.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px;">📦 View Inventory</a>
                        <a href="procurement.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px;">🛒 Procurement</a>
                    </div>
                </div>
            </div>

            <!-- Low Stock Table (below content) -->
            <?php if ($total_low_stock > 0): ?>
            <div class="card" style="margin-top: 20px; border-left: 4px solid #ef4444;">
                <h3>⚠️ Low Stock & Out of Stock Alerts</h3>
                <?php if (function_exists('renderPerPageSelector') && isset($low_stock_pagination)): ?>
                    <div class="pagination-toolbar" style="margin-bottom:12px;">
                        <?php echo renderPerPageSelector($conn, $low_stock_pagination['per_page'], 'low_stock_page', 'low_stock_per_page'); ?>
                    </div>
                <?php endif; ?>
                <p style="color: var(--text-muted); margin-bottom: 15px;">Data from <a href="inventory_summary.php">Inventory Summary</a>, <a href="inventory_raw_materials.php">Raw Materials</a>, <a href="inventory_items.php">Inventory Items</a>.</p>
                <table>
                    <tr>
                        <th>Type</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Min Threshold</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($low_stock_items as $item): ?>
                    <tr style="background-color: <?php echo (float)$item['quantity'] <= 0 ? '#fef2f2' : '#fffbeb'; ?>;">
                        <td><span style="padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; background: <?php echo $item['item_type'] === 'Raw Material' ? 'rgba(59, 130, 246, 0.1)' : 'rgba(34, 197, 94, 0.1)'; ?>; color: <?php echo $item['item_type'] === 'Raw Material' ? '#2563eb' : '#16a34a'; ?>;"><?php echo htmlspecialchars($item['item_type']); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                        <td style="color: <?php echo (float)$item['quantity'] <= 0 ? '#dc2626' : '#f59e0b'; ?>; font-weight: bold;"><?php echo number_format((float)$item['quantity'], 2); ?></td>
                        <td><?php echo number_format((float)$item['threshold'], 2); ?></td>
                        <td><?php echo htmlspecialchars(formatLocation($item['location'] ?? null)); ?></td>
                        <td><?php if ((float)$item['quantity'] <= 0): ?><span style="padding: 4px 8px; background: #fee2e2; color: #991b1b; border-radius: 4px; font-size: 12px; font-weight: 600;">OUT OF STOCK</span><?php else: ?><span style="padding: 4px 8px; background: #fef3c7; color: #92400e; border-radius: 4px; font-size: 12px; font-weight: 600;">LOW STOCK</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php if (function_exists('renderPagination') && isset($low_stock_pagination)) echo renderPagination($low_stock_pagination, 'low_stock_page', 'low_stock_per_page'); ?>
                <p style="margin-top: 15px;">
                    <a href="inventory_summary.php#low-stock-alerts" class="btn">View Full Inventory Summary</a>
                    <a href="inventory_raw_materials.php" class="btn" style="margin-left: 8px;">Manage Raw Materials</a>
                    <a href="inventory_items.php" class="btn" style="margin-left: 8px;">View Inventory Items</a>
                </p>
            </div>
            <?php endif; ?>

            <?php if (!empty($near_expiry_finished_goods)): ?>
            <div class="card" style="margin-top: 20px; border-left: 4px solid #f59e0b;">
                <h3>⏳ Near Expiry Finished Goods</h3>
                <p style="color: var(--text-muted); margin-bottom: 15px;">Items expiring within the next 30 days.</p>
                <table>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Location</th>
                    </tr>
                    <?php foreach ($near_expiry_finished_goods as $fg): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fg['product_name'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($fg['quantity'], 2); ?></td>
                            <td><?php echo formatDate($fg['expiry_date']); ?></td>
                            <td><?php echo htmlspecialchars(formatLocation($fg['warehouse_location'] ?? null)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>

            <!-- Recent Orders Table (below) -->
            <div class="card" style="margin-top: 20px;">
                <h3>Recent Orders & Deliveries</h3>
                <?php
                $sort = getSortParams('created_at', ['order_number', 'customer_name', 'status', 'created_at', 'order_date']);
                $order_column_map = ['order_number' => 'so.order_number', 'customer_name' => 'c.customer_name', 'status' => 'so.status', 'created_at' => 'so.created_at', 'order_date' => 'so.order_date'];
                $order_by = isset($order_column_map[$sort['column']]) ? $order_column_map[$sort['column']] : 'so.created_at';

                $delivery_pagination = function_exists('getPagination')
                    ? getPagination($conn, "SELECT COUNT(*) AS c FROM delivery_assignments")
                    : ['offset' => 0, 'per_page' => 5, 'total' => 0, 'total_pages' => 1, 'page' => 1, 'prev_page' => null, 'next_page' => null];

                try {
                    $recent_orders_detail = $conn->query("SELECT so.*, c.customer_name,
                        (SELECT GROUP_CONCAT(p.product_name SEPARATOR ', ') FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = so.order_id) AS product_name,
                        da.status as delivery_status,
                        da.proof_of_delivery AS proof_of_delivery
                        FROM sales_orders so LEFT JOIN customers c ON so.customer_id = c.customer_id LEFT JOIN delivery_assignments da ON so.order_id = da.order_id ORDER BY " . $order_by . " " . $sort['order'] . " LIMIT " . $delivery_pagination['offset'] . ", " . $delivery_pagination['per_page']);
                } catch (Exception $e) {
                    $recent_orders_detail = null;
                }
                ?>
                <table>
                    <tr>
                        <th><?php echo sortHeader('order_number', 'Order No', $sort); ?></th>
                        <th><?php echo sortHeader('customer_name', 'Customer', $sort); ?></th>
                        <th>Product</th>
                        <th><?php echo sortHeader('status', 'Status', $sort); ?></th>
                        <th>Delivery</th>
                        <th>Proof</th>
                        <th><?php echo sortHeader('order_date', 'Date', $sort); ?></th>
                    </tr>
                    <?php if ($recent_orders_detail && $recent_orders_detail->num_rows > 0): ?>
                        <?php while ($order = $recent_orders_detail->fetch_assoc()): ?>
                            <tr style="cursor: pointer;" onclick="window.location.href='costumer_transaction_view.php?order_id=<?php echo $order['order_id']; ?>'">
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?></td>
                                <td><span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; background: <?php echo $order['status'] === 'Delivered' ? 'rgba(16, 185, 129, 0.1)' : ($order['status'] === 'Dispatched' ? 'rgba(59, 130, 246, 0.1)' : ($order['status'] === 'Confirmed' ? 'rgba(255, 107, 53, 0.1)' : 'rgba(107, 114, 128, 0.1)')); ?>; color: <?php echo $order['status'] === 'Delivered' ? '#10b981' : ($order['status'] === 'Dispatched' ? '#3b82f6' : ($order['status'] === 'Confirmed' ? '#FF6B35' : '#6b7280')); ?>;"><?php echo htmlspecialchars($order['status']); ?></span></td>
                                <td><?php if (!empty($order['delivery_status'])): ?><span style="padding: 4px 8px; background: rgba(6, 182, 212, 0.1); color: #06b6d4; border-radius: 4px; font-size: 12px; font-weight: 600;"><?php echo htmlspecialchars($order['delivery_status']); ?></span><?php else: ?><span style="color: var(--text-muted); font-size: 12px;">Not assigned</span><?php endif; ?></td>
                                <td>
                                    <?php if (!empty($order['proof_of_delivery'])): ?>
                                        <a href="<?php echo htmlspecialchars($order['proof_of_delivery']); ?>" target="_blank" style="display:inline-block;">
                                            <img src="<?php echo htmlspecialchars($order['proof_of_delivery']); ?>" alt="Proof of Delivery" style="max-width:60px; max-height:40px; border-radius:4px; border:1px solid var(--border-color);">
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 12px;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($order['order_date']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 20px; color: var(--text-muted);">No recent orders found.</td></tr>
                    <?php endif; ?>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($delivery_pagination, 'page'); ?>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js" defer></script>
</body>
</html>
