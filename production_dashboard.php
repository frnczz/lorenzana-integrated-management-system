<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'production') {
    header("Location: login.php");
    exit;
}

include "includes/functions.php";
include "db_connect.php";
include "includes/low_stock_alerts.php"; // provides $low_stock_fg and $low_stock_items for finished goods

// Near-expiry finished goods (next 30 days)
$near_expiry_finished_goods = [];
$near_exp_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM finished_goods WHERE expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)", null, 'near_exp_page', 'near_exp_per_page')
    : ['offset'=>0,'per_page'=>20,'total'=>0,'total_pages'=>1,'page'=>1,'prev_page'=>null,'next_page'=>null];
$near_expiry_fg_query = "SELECT fg.*, p.product_name FROM finished_goods fg " .
                      "LEFT JOIN products p ON fg.product_id = p.product_id " .
                      "WHERE fg.expiry_date IS NOT NULL " .
                      "  AND fg.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) " .
                      "ORDER BY fg.expiry_date ASC LIMIT " . $near_exp_pagination['offset'] . ", " . $near_exp_pagination['per_page'];
$near_expiry_fg_result = $conn->query($near_expiry_fg_query);
if ($near_expiry_fg_result) {
    while ($row = $near_expiry_fg_result->fetch_assoc()) {
        $near_expiry_finished_goods[] = $row;
    }
}

// Get production metrics
$total_batches = $conn->query("SELECT COUNT(*) as count FROM production_batches")->fetch_assoc()['count'] ?? 0;
$today_batches = $conn->query("SELECT COUNT(*) as count FROM production_batches WHERE DATE(batch_date) = CURDATE()")->fetch_assoc()['count'] ?? 0;
$processing_batches = $conn->query("SELECT COUNT(*) as count FROM production_batches WHERE status = 'Processing'")->fetch_assoc()['count'] ?? 0;
$completed_batches = $conn->query("SELECT COUNT(*) as count FROM production_batches WHERE status = 'Completed'")->fetch_assoc()['count'] ?? 0;
$total_quantity = $conn->query("SELECT COALESCE(SUM(quantity), 0) as total FROM production_batches WHERE DATE(batch_date) = CURDATE()")->fetch_assoc()['total'] ?? 0;
$pending_requests = $conn->query("SELECT COUNT(*) as count FROM production_requests WHERE status = 'pending'")->fetch_assoc()['count'] ?? 0;

$pending_requests_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM production_requests WHERE status = 'pending'", null, 'pending_req_page', 'pending_req_per_page')
    : ['offset'=>0,'per_page'=>5,'total'=>0,'total_pages'=>1,'page'=>1,'prev_page'=>null,'next_page'=>null];

$recent_batches_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM production_batches", null, 'batch_page', 'batch_per_page')
    : ['offset'=>0,'per_page'=>10,'total'=>0,'total_pages'=>1,'page'=>1,'prev_page'=>null,'next_page'=>null];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Dashboard | LORINIMS</title>
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
        
        .alert-box {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-left: 4px solid #10b981;
        }
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border-left: 4px solid #f59e0b;
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
                <h2>Production Dashboard</h2>
                <p>Welcome, <strong><?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'; ?></strong> — Batch management & production tracking</p>
            </div>

            <?php showMessage(); ?>

            <!-- METRIC CARDS -->
            <div class="stat-grid">
                <div class="stat-card" onclick="window.location.href='production.php'">
                    <div class="icon">🏭</div>
                    <div class="label">Total Batches</div>
                    <div class="value"><?php echo number_format($total_batches); ?></div>
                    <div class="meta">All time</div>
                </div>
                <div class="stat-card" onclick="window.location.href='production.php'">
                    <div class="icon">📅</div>
                    <div class="label">Today's Batches</div>
                    <div class="value"><?php echo number_format($today_batches); ?></div>
                    <div class="meta"><?php echo date('M d, Y'); ?></div>
                </div>
                <div class="stat-card" onclick="window.location.href='production.php?status=Processing'">
                    <div class="icon">⚙️</div>
                    <div class="label">In Processing</div>
                    <div class="value"><?php echo number_format($processing_batches); ?></div>
                    <div class="meta">Active batches</div>
                </div>
                <div class="stat-card" onclick="window.location.href='production.php?status=Completed'">
                    <div class="icon">✅</div>
                    <div class="label">Completed</div>
                    <div class="value"><?php echo number_format($completed_batches); ?></div>
                    <div class="meta">Finished</div>
                </div>
                <div class="stat-card">
                    <div class="icon">📦</div>
                    <div class="label">Today's Output</div>
                    <div class="value"><?php echo number_format($total_quantity, 0); ?></div>
                    <div class="meta">Units produced</div>
                </div>
                <div class="stat-card" onclick="window.location.href='production_requests.php'">
                    <div class="icon">📋</div>
                    <div class="label">Pending Requests</div>
                    <div class="value"><?php echo number_format($pending_requests); ?></div>
                    <div class="meta">Need action</div>
                </div>
            </div>

            <?php if (!empty($low_stock_items) && $low_stock_fg > 0): ?>
            <div class="card" style="margin-top: 20px; border-left: 4px solid #ef4444;">
                <h3>⚠️ Low Stock Finished Goods</h3>
                <p style="color: var(--text-muted); margin-bottom: 15px;">Showing only finished goods that are below the low-stock threshold.</p>
                <table>
                    <tr>
                        <th>Item</th>
                        <th>Current Stock</th>
                        <th>Min Threshold</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($low_stock_items as $item): ?>
                        <?php if (($item['item_type'] ?? '') !== 'Finished Good') continue; ?>
                        <tr style="background-color: <?php echo (float)$item['quantity'] <= 0 ? '#fef2f2' : '#fffbeb'; ?>;">
                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                            <td style="color: <?php echo (float)$item['quantity'] <= 0 ? '#dc2626' : '#f59e0b'; ?>; font-weight: bold;"><?php echo number_format((float)$item['quantity'], 2); ?></td>
                            <td><?php echo number_format((float)$item['threshold'], 2); ?></td>
                            <td><?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></td>
                            <td><span style="padding: 4px 8px; background: <?php echo (float)$item['quantity'] <= 0 ? '#fee2e2' : '#fef3c7'; ?>; color: <?php echo (float)$item['quantity'] <= 0 ? '#991b1b' : '#92400e'; ?>; border-radius: 4px; font-size: 12px; font-weight: 600;"><?php echo (float)$item['quantity'] <= 0 ? 'OUT OF STOCK' : 'LOW STOCK'; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($near_expiry_finished_goods)): ?>
            <div class="card" style="margin-top: 20px; border-left: 4px solid #f59e0b;">
                <h3>⏳ Near Expiry Finished Goods</h3>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin-bottom:12px;">' . renderPerPageSelector($conn, $near_exp_pagination['per_page'], 'near_exp_per_page', 'near_exp_page') . '</div>'; ?>
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
                <?php if (function_exists('renderPagination')) echo renderPagination($near_exp_pagination, 'near_exp_page'); ?>
            </div>
            <?php endif; ?>

            <!-- ALERTS -->
            <?php if ($pending_requests > 0): ?>
                <div class="alert-box alert-warning">
                    <strong>⚠️ Action Required:</strong> You have <?php echo $pending_requests; ?> pending production request(s) waiting for approval.
                </div>
            <?php endif; ?>

            <!-- RECENT BATCHES -->
            <div class="card">
                <h3>🔄 Recent Production Batches</h3>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin-bottom:12px;">' . renderPerPageSelector($conn, $recent_batches_pagination['per_page'], 'batch_per_page', 'batch_page') . '</div>'; ?>
                <?php
                try {
                    $recent_batches = $conn->query("SELECT pb.*, p.product_name FROM production_batches pb 
                                                    LEFT JOIN products p ON pb.product_id = p.product_id 
                                                    ORDER BY pb.created_at DESC LIMIT " . $recent_batches_pagination['offset'] . ", " . $recent_batches_pagination['per_page']);
                } catch (Exception $e) {
                    $recent_batches = null;
                }
                ?>
                <table>
                    <tr>
                        <th>Batch No</th>
                        <th>Product</th>
                        <th>Date</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php if ($recent_batches && $recent_batches->num_rows > 0): ?>
                        <?php while ($batch = $recent_batches->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($batch['batch_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($batch['product_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($batch['batch_date']); ?></td>
                                <td><?php echo number_format($batch['quantity'], 2); ?></td>
                                <td>
                                    <span class="status-badge" style="background: <?php 
                                        echo $batch['status'] == 'Completed' ? 'rgba(16, 185, 129, 0.1)' : 
                                            ($batch['status'] == 'Processing' ? 'rgba(245, 158, 11, 0.1)' : 'rgba(107, 114, 128, 0.1)'); 
                                    ?>; color: <?php 
                                        echo $batch['status'] == 'Completed' ? '#10b981' : 
                                            ($batch['status'] == 'Processing' ? '#f59e0b' : '#6b7280'); 
                                    ?>;">
                                        <?php echo htmlspecialchars($batch['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="production.php" class="status-badge" style="text-decoration: none; background: #dbeafe; color: #1e40af;">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">
                                <p style="margin: 0;">No batches found.</p>
                                <a href="production.php" class="btn" style="display: inline-block; margin-top: 10px; text-decoration: none;">Create First Batch</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($recent_batches_pagination, 'batch_page'); ?>
            </div>

            <!-- PENDING REQUESTS -->
            <div class="card">
                <h3>📋 Pending Production Requests</h3>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin-bottom:12px;">' . renderPerPageSelector($conn, $pending_requests_pagination['per_page'], 'pending_req_per_page', 'pending_req_page') . '</div>'; ?>
                <?php
                try {
                    $requests = $conn->query("
                        SELECT pr.*, p.product_name 
                        FROM production_requests pr
                        LEFT JOIN products p ON pr.product_id = p.product_id
                        WHERE pr.status = 'pending'
                        ORDER BY pr.created_at DESC LIMIT " . $pending_requests_pagination['offset'] . ", " . $pending_requests_pagination['per_page'] . "
                    ");
                } catch (Exception $e) {
                    $requests = null;
                }
                ?>
                <table>
                    <tr>
                        <th>Request ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Reason</th>
                        <th>Action</th>
                    </tr>
                    <?php if ($requests && $requests->num_rows > 0): ?>
                        <?php while ($req = $requests->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($req['request_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($req['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['product_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($req['requested_qty'], 2); ?></td>
                                <td><?php echo htmlspecialchars($req['reason']); ?></td>
                                <td>
                                    <a href="production_requests.php" class="status-badge" style="text-decoration: none; background: rgba(16, 185, 129, 0.1); color: #10b981;">Review</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">
                                <p style="color: #10b981; font-weight: bold;">✓ No pending requests</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($pending_requests_pagination, 'pending_req_page'); ?>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="card">
                <h3>🚀 Quick Actions</h3>
                <div class="action-grid">
                    <a href="production.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">🏭 New Batch</a>
                    <a href="production_requests.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">📋 View Requests</a>
                    <a href="qc.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">✅ Quality Control</a>
                    <a href="inventory.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">📦 Check Inventory</a>
                </div>
            </div>

        </div>

        <?php include "layouts/footer.php"; ?>

    </div>

</div>

<script src="assets/js/sidebar.js"></script>

</body>
</html>
