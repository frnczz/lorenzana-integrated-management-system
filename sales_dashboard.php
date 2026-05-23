<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sales') {
    header("Location: login.php");
    exit;
}

include "includes/functions.php";
include "db_connect.php";

// Get sales metrics
$total_orders = $conn->query("SELECT COUNT(*) as count FROM sales_orders")->fetch_assoc()['count'] ?? 0;
$today_orders = $conn->query("SELECT COUNT(*) as count FROM sales_orders WHERE DATE(order_date) = CURDATE()")->fetch_assoc()['count'] ?? 0;
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM sales_orders WHERE status IN ('Pending', 'Confirmed')")->fetch_assoc()['count'] ?? 0;
$delivered_orders = $conn->query("SELECT COUNT(*) as count FROM sales_orders WHERE status = 'Delivered'")->fetch_assoc()['count'] ?? 0;
$total_revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales_orders WHERE status = 'Delivered'")->fetch_assoc()['total'] ?? 0;
$pending_revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales_orders WHERE status IN ('Pending', 'Confirmed')")->fetch_assoc()['total'] ?? 0;

$recent_orders_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM sales_orders", null, 'orders_page', 'orders_per_page')
    : ['offset'=>0,'per_page'=>10,'total'=>0,'total_pages'=>1,'page'=>1,'prev_page'=>null,'next_page'=>null];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dash-hero { margin-bottom: 24px; }
        .dash-hero h2 { font-size: 1.75rem; font-weight: 700; margin-bottom: 4px; }
        .dash-hero p { color: var(--text-muted); font-size: 0.95rem; }
        
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 28px; }
        
        .stat-card { 
            padding: 22px; 
            border-radius: 12px; 
            color: white; 
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.1);
            transform: translateX(-100%);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card:hover::before {
            transform: translateX(0);
        }
        
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
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
        }
        
        .card h3 {
            margin: 0 0 20px 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        table tbody tr:hover {
            background-color: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }
        
        .action-grid .btn {
            text-decoration: none;
            padding: 12px;
            text-align: center;
            border-radius: 6px;
        }
        
        @media (max-width: 768px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .stat-card .value { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <div class="dash-hero">
                <h2>Sales Dashboard</h2>
                <p>Welcome, <strong><?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'; ?></strong> — Order tracking & customer fulfillment</p>
            </div>

            <?php showMessage(); ?>

            <!-- METRIC CARDS -->
            <div class="stat-grid">
                <div class="stat-card" onclick="window.location.href='sales.php'">
                    <div class="icon">📋</div>
                    <div class="label">Total Orders</div>
                    <div class="value"><?php echo number_format($total_orders); ?></div>
                    <div class="meta">All time</div>
                </div>
                <div class="stat-card" onclick="window.location.href='sales.php'">
                    <div class="icon">📅</div>
                    <div class="label">Today's Orders</div>
                    <div class="value"><?php echo number_format($today_orders); ?></div>
                    <div class="meta"><?php echo date('M d, Y'); ?></div>
                </div>
                <div class="stat-card" onclick="window.location.href='sales.php?status=Pending'">
                    <div class="icon">⏳</div>
                    <div class="label">Pending Orders</div>
                    <div class="value"><?php echo number_format($pending_orders); ?></div>
                    <div class="meta">Need action</div>
                </div>
                <div class="stat-card" onclick="window.location.href='sales.php?status=Delivered'">
                    <div class="icon">✅</div>
                    <div class="label">Delivered</div>
                    <div class="value"><?php echo number_format($delivered_orders); ?></div>
                    <div class="meta">Completed</div>
                </div>
                <div class="stat-card">
                    <div class="icon">💰</div>
                    <div class="label">Revenue (Delivered)</div>
                    <div class="value" style="font-size: 1.5rem;"><?php echo formatCurrency($total_revenue); ?></div>
                    <div class="meta">Confirmed</div>
                </div>
                <div class="stat-card">
                    <div class="icon">🎯</div>
                    <div class="label">Pending Revenue</div>
                    <div class="value" style="font-size: 1.5rem;"><?php echo formatCurrency($pending_revenue); ?></div>
                    <div class="meta">In process</div>
                </div>
            </div>

            <!-- RECENT ORDERS -->
            <div class="card">
                <h3>📊 Recent Orders</h3>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin-bottom:12px;">' . renderPerPageSelector($conn, $recent_orders_pagination['per_page'], 'orders_per_page', 'orders_page') . '</div>'; ?>
                <?php
                try {
                    $recent_orders = $conn->query(
                        "SELECT so.*, c.customer_name, 
                        (SELECT GROUP_CONCAT(p.product_name SEPARATOR ', ') FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = so.order_id) AS product_name, 
                        da.status as delivery_status 
                        FROM sales_orders so 
                        LEFT JOIN customers c ON so.customer_id = c.customer_id 
                        LEFT JOIN delivery_assignments da ON so.order_id = da.order_id
                        ORDER BY so.created_at DESC LIMIT "
                        . $recent_orders_pagination['offset'] . ", "
                        . $recent_orders_pagination['per_page']
                    );
                } catch (Exception $e) {
                    $recent_orders = null;
                }
                ?>
                <table>
                    <tr>
                        <th>Order No</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td>
                                    <span class="status-badge" style="background: <?php 
                                        echo $order['status'] == 'Delivered' ? 'rgba(16, 185, 129, 0.1)' : 
                                            ($order['status'] == 'Dispatched' ? 'rgba(59, 130, 246, 0.1)' : 
                                            ($order['status'] == 'Confirmed' ? 'rgba(255, 107, 53, 0.1)' : 'rgba(107, 114, 128, 0.1)')); 
                                    ?>; color: <?php 
                                        echo $order['status'] == 'Delivered' ? '#10b981' : 
                                            ($order['status'] == 'Dispatched' ? '#3b82f6' : 
                                            ($order['status'] == 'Confirmed' ? '#FF6B35' : '#6b7280')); 
                                    ?>;">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="customers_transactions.php?order_id=<?php echo $order['order_id']; ?>&customer_id=<?php echo $order['customer_id']; ?>" class="status-badge" style="text-decoration: none; background: #dbeafe; color: #1e40af;">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">
                                <p style="margin: 0;">No orders found.</p>
                                <a href="sales.php" class="btn" style="display: inline-block; margin-top: 10px; text-decoration: none;">Create First Order</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>                <?php if (function_exists('renderPagination')) echo renderPagination($recent_orders_pagination, 'orders_page'); ?>            </div>

            <!-- PENDING ACTIONS -->
            <div class="card">
                <h3>⚠️ Pending Actions</h3>
                <?php
                try {
                    $pending_action_orders = $conn->query("SELECT so.*, c.customer_name, 
                        (SELECT GROUP_CONCAT(p.product_name SEPARATOR ', ') FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = so.order_id) AS product_name 
                        FROM sales_orders so 
                        LEFT JOIN customers c ON so.customer_id = c.customer_id 
                        WHERE so.status IN ('Pending', 'Confirmed')
                        AND NOT EXISTS (SELECT 1 FROM delivery_assignments da WHERE da.order_id = so.order_id)
                        ORDER BY so.order_date ASC LIMIT 5");
                } catch (Exception $e) {
                    $pending_action_orders = null;
                }
                ?>
                <?php if ($pending_action_orders && $pending_action_orders->num_rows > 0): ?>
                    <p style="color: var(--text-muted); margin-bottom: 15px; font-size: 14px;">Orders needing delivery assignment:</p>
                    <table>
                        <tr>
                            <th>Order No</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <?php while ($order = $pending_action_orders->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="sales_delivery.php" class="status-badge" style="text-decoration: none; background: rgba(16, 185, 129, 0.1); color: #10b981;">Assign</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p style="color: #10b981; font-weight: bold; padding: 20px; text-align: center; background: rgba(16, 185, 129, 0.1); border-radius: 8px;">
                        ✓ No pending actions needed!
                    </p>
                <?php endif; ?>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="card">
                <h3>🚀 Quick Actions</h3>
                <div class="action-grid">
                    <a href="sales.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">📦 Manage Orders</a>
                    <a href="sales_products.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">🏷️ View Products</a>
                    <a href="sales_delivery.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">🚚 Track Delivery</a>
                    <a href="reports.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">📊 View Reports</a>
                </div>
            </div>

        </div>

        <?php include "layouts/footer.php"; ?>

    </div>

</div>

<script src="assets/js/sidebar.js"></script>

</body>
</html>
