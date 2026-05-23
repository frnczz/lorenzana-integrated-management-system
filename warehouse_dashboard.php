<?php
session_start();

// Check if user is logged in and is warehouse staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warehouse') {
    header("Location: login.php");
    exit;
}

include "includes/functions.php";
include "db_connect.php";

// Load warehouse settings
$low_stock_threshold = floatval(getWarehouseSetting($conn, 'low_stock_threshold', 50, true));
$expiry_warning_days = intval(getWarehouseSetting($conn, 'expiry_warning_days', 30, true));
$stock_method = getWarehouseSetting($conn, 'stock_method', 'FIFO');

// Load shared low stock data (aligned with inventory_summary, inventory_raw_materials, inventory_items)
include "includes/low_stock_alerts.php";

// Get inventory metrics
$raw_materials_count = $conn->query("SELECT COUNT(*) as count FROM raw_materials")->fetch_assoc()['count'] ?? 0;

// Calculate low stock items (matching inventory.php logic)
$low_stock_raw = $conn->query("SELECT COUNT(*) as count FROM raw_materials WHERE quantity <= min_stock_level AND quantity > 0")->fetch_assoc()['count'] ?? 0;
$low_stock_fg = $conn->query("SELECT COUNT(DISTINCT product_id) as count FROM finished_goods WHERE qc_approved = 1 AND (quantity - COALESCE(reserved_quantity, 0)) <= $low_stock_threshold AND (quantity - COALESCE(reserved_quantity, 0)) > 0")->fetch_assoc()['count'] ?? 0;
$low_stock = $low_stock_raw + $low_stock_fg;

$out_of_stock = $conn->query("SELECT COUNT(*) as count FROM raw_materials WHERE quantity <= 0")->fetch_assoc()['count'] ?? 0;
$near_expiry = $conn->query("SELECT COUNT(*) as count FROM raw_materials WHERE expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['count'] ?? 0;
$finished_goods_count = $conn->query("SELECT COUNT(DISTINCT product_id) as count FROM finished_goods WHERE qc_approved = 1 AND quantity > 0")->fetch_assoc()['count'] ?? 0;
$total_finished_qty = $conn->query("SELECT COALESCE(SUM(quantity - COALESCE(reserved_quantity, 0)), 0) as total FROM finished_goods WHERE qc_approved = 1")->fetch_assoc()['total'] ?? 0;
$total_raw_materials_qty = $conn->query("SELECT COALESCE(SUM(quantity), 0) as total FROM raw_materials")->fetch_assoc()['total'] ?? 0;

// Get top items for charts
$top_raw_materials = $conn->query("SELECT material_name, quantity, min_stock_level, category FROM raw_materials ORDER BY quantity DESC LIMIT 10");
$raw_labels = [];
$raw_data = [];
$raw_colors = [];
if($top_raw_materials) {
    while($row = $top_raw_materials->fetch_assoc()) {
        $raw_labels[] = $row['material_name'];
        $raw_data[] = (float)$row['quantity'];
        if($row['quantity'] <= $row['min_stock_level']) {
            $raw_colors[] = '#e74a3b';
        } elseif($row['quantity'] < ($row['min_stock_level'] * 2)) {
            $raw_colors[] = '#f6c23e';
        } else {
            $raw_colors[] = '#1cc88a';
        }
    }
}

$top_finished_goods = $conn->query("SELECT p.product_name, SUM(fg.quantity - COALESCE(fg.reserved_quantity, 0)) as available_qty FROM finished_goods fg INNER JOIN products p ON fg.product_id = p.product_id WHERE fg.qc_approved = 1 AND fg.quantity > 0 GROUP BY fg.product_id, p.product_name ORDER BY available_qty DESC LIMIT 10");
$fg_labels = [];
$fg_data = [];
if($top_finished_goods) {
    while($row = $top_finished_goods->fetch_assoc()) {
        $fg_labels[] = $row['product_name'];
        $fg_data[] = (float)$row['available_qty'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warehouse Dashboard | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: 20px;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
        
        table tr:hover {
            background-color: #f9fafb;
        }
        
        .low-stock { background-color: #fef2f2; border-left: 4px solid #dc2626; }
        .near-expiry { background-color: #fffbeb; border-left: 4px solid #f59e0b; }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-low { background: #fee2e2; color: #991b1b; }
        .status-warning { background: #fef3c7; color: #92400e; }
        .status-safe { background: #d1fae5; color: #065f46; }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .chart-grid { grid-template-columns: 1fr; }
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
                <h2>Warehouse Dashboard</h2>
                <p>Welcome, <strong><?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'; ?></strong> — Real-time inventory management & stock monitoring</p>
                <p style="font-size:0.9em; color:#666; margin-top:-10px;"><strong>Warehouse Settings:</strong> Low Stock Alert: <strong><?php echo $low_stock_threshold; ?> units</strong> | Expiry Warning: <strong><?php echo $expiry_warning_days; ?> days</strong> | Stock Method: <strong><?php echo htmlspecialchars($stock_method); ?></strong> | <a href="settings_warehouse.php" style="color:#3b82f6;">Edit</a></p>
            </div>

            <?php showMessage(); ?>

            <!-- METRIC CARDS -->
            <div class="stat-grid">
                <div class="stat-card" onclick="window.location.href='inventory.php'">
                    <div class="icon">📦</div>
                    <div class="label">Raw Materials</div>
                    <div class="value"><?php echo number_format($raw_materials_count); ?></div>
                    <div class="meta"><?php echo number_format($total_raw_materials_qty, 0); ?> units tracked</div>
                </div>
                <div class="stat-card" onclick="window.location.href='inventory.php#finished'">
                    <div class="icon">✅</div>
                    <div class="label">QC-Approved Goods</div>
                    <div class="value"><?php echo number_format($finished_goods_count); ?></div>
                    <div class="meta"><?php echo number_format($total_finished_qty, 0); ?> available units</div>
                </div>
                <div class="stat-card" onclick="document.getElementById('low-stock-alerts').scrollIntoView({behavior: 'smooth'})">
                    <div class="icon">⚠️</div>
                    <div class="label">Low Stock Items</div>
                    <div class="value"><?php echo number_format($low_stock); ?></div>
                    <div class="meta">Need replenishment</div>
                </div>
                <div class="stat-card" onclick="document.getElementById('low-stock-alerts').scrollIntoView({behavior: 'smooth'})">
                    <div class="icon">🔴</div>
                    <div class="label">Out of Stock</div>
                    <div class="value"><?php echo number_format($out_of_stock); ?></div>
                    <div class="meta">Urgent action needed</div>
                </div>
                <div class="stat-card" onclick="document.getElementById('near-expiry').scrollIntoView({behavior: 'smooth'})">
                    <div class="icon">📅</div>
                    <div class="label">Near Expiry</div>
                    <div class="value"><?php echo number_format($near_expiry); ?></div>
                    <div class="meta">Within 30 days</div>
                </div>
                <div class="stat-card">
                    <div class="icon">📊</div>
                    <div class="label">Overall Stock Value</div>
                    <div class="value" style="font-size: 1.5rem;">Optimal</div>
                    <div class="meta">Warehouse status</div>
                </div>
            </div>

            <!-- CHARTS SECTION -->
            <div class="chart-grid">
                <div class="card">
                    <h3>📈 Top Raw Materials (Inventory Levels)</h3>
                    <div class="chart-container">
                        <canvas id="rawMaterialsChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <h3>📦 Top Finished Goods (Stock Levels)</h3>
                    <div class="chart-container">
                        <canvas id="finishedGoodsChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- LOW STOCK ALERTS (data from inventory_summary, inventory_raw_materials, inventory_items) -->
            <div id="low-stock-alerts" class="card">
                <h3>⚠️ Low Stock & Out of Stock Alerts (Raw Materials & Finished Goods)</h3>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">Threshold: <strong><?php echo number_format($low_stock_threshold); ?> units</strong>. <a href="inventory_summary.php">Summary</a> | <a href="inventory_raw_materials.php">Raw Materials</a> | <a href="inventory_items.php">Inventory Items</a></p>
                <?php if (!empty($low_stock_items)): ?>
                    <table>
                        <tr>
                            <th>Type</th>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Threshold</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                        <?php foreach ($low_stock_items as $item): ?>
                            <tr class="low-stock">
                                <td><span style="padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; background: <?php echo $item['item_type'] === 'Raw Material' ? 'rgba(59, 130, 246, 0.1)' : 'rgba(34, 197, 94, 0.1)'; ?>; color: <?php echo $item['item_type'] === 'Raw Material' ? '#2563eb' : '#16a34a'; ?>;"><?php echo htmlspecialchars($item['item_type']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                                <td style="color: <?php echo (float)$item['quantity'] <= 0 ? '#dc2626' : '#f59e0b'; ?>; font-weight: bold;"><?php echo number_format((float)$item['quantity'], 2); ?></td>
                                <td><?php echo number_format((float)$item['threshold'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></td>
                                <td><?php if ((float)$item['quantity'] <= 0): ?><span class="status-badge status-low">OUT OF STOCK</span><?php else: ?><span class="status-badge status-warning">LOW STOCK</span><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p style="color: #10b981; font-weight: bold; padding: 20px; text-align: center; background: rgba(16, 185, 129, 0.1); border-radius: 8px;">
                        ✓ All items are well stocked!
                    </p>
                <?php endif; ?>
            </div>

            <!-- NEAR EXPIRY ALERTS -->
            <div id="near-expiry" class="card">
                <h3>📅 Items Near Expiry (Next 30 Days)</h3>
                <?php
                try {
                    $expiring_items = $conn->query("SELECT material_name, quantity, expiry_date, unit, location, category,
                                                    DATEDIFF(expiry_date, CURDATE()) as days_left
                                                    FROM raw_materials 
                                                    WHERE expiry_date IS NOT NULL 
                                                    AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                                                    ORDER BY expiry_date ASC LIMIT 15");
                } catch (Exception $e) {
                    $expiring_items = null;
                }
                ?>
                <?php if ($expiring_items && $expiring_items->num_rows > 0): ?>
                    <table>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Expiry Date</th>
                            <th>Days Left</th>
                            <th>Unit</th>
                        </tr>
                        <?php while ($item = $expiring_items->fetch_assoc()): ?>
                            <tr class="near-expiry">
                                <td><strong><?php echo htmlspecialchars($item['material_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($item['quantity'], 2); ?></td>
                                <td><strong><?php echo formatDate($item['expiry_date']); ?></strong></td>
                                <td>
                                    <span class="status-badge" style="background: <?php echo $item['days_left'] <= 7 ? '#fee2e2' : '#fef3c7'; ?>; color: <?php echo $item['days_left'] <= 7 ? '#991b1b' : '#92400e'; ?>;">
                                        <?php echo $item['days_left']; ?> days
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p style="color: #10b981; font-weight: bold; padding: 20px; text-align: center; background: rgba(16, 185, 129, 0.1); border-radius: 8px;">
                        ✓ No items expiring in the next 30 days
                    </p>
                <?php endif; ?>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="card">
                <h3>🚀 Quick Actions</h3>
                <div class="action-grid">
                    <a href="inventory.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">📦 Manage Inventory</a>
                    <a href="inventory_raw_materials.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">🏭 Raw Materials</a>
                    <a href="procurement.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">🛒 Request Purchase</a>
                    <a href="inventory_summary.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">📊 Full Summary</a>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <?php include "layouts/footer.php"; ?>

    </div>

</div>

<script src="assets/js/sidebar.js" defer></script>
<script>
// Chart.js configuration
Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.plugins.legend.display = false;

// Raw Materials Chart
const rawCtx = document.getElementById('rawMaterialsChart').getContext('2d');
new Chart(rawCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($raw_labels); ?>,
        datasets: [{
            label: 'Quantity',
            data: <?php echo json_encode($raw_data); ?>,
            backgroundColor: <?php echo json_encode($raw_colors); ?>,
            borderColor: <?php echo json_encode($raw_colors); ?>,
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Stock: ' + context.parsed.x.toFixed(2) + ' units';
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toFixed(0);
                    }
                }
            }
        }
    }
});

// Finished Goods Chart
const fgCtx = document.getElementById('finishedGoodsChart').getContext('2d');
new Chart(fgCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($fg_labels); ?>,
        datasets: [{
            label: 'Available Quantity',
            data: <?php echo json_encode($fg_data); ?>,
            backgroundColor: '#10b981',
            borderColor: '#059669',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Available: ' + context.parsed.x.toFixed(2) + ' units';
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toFixed(0);
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>
