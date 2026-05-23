<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}

include "includes/functions.php";
include "db_connect.php";

// Load warehouse settings for thresholds
$fg_low_threshold = floatval(getSetting($conn, 'warehouse_settings', 'low_stock_threshold', 50, true));
$near_expiry_days = intval(getSetting($conn, 'warehouse_settings', 'expiry_warning_days', 30, true));

// Load sort parameters for Low Stock table
$sort_low = getSortParams('quantity', ['material_name', 'category', 'quantity', 'min_stock_level', 'unit']);

// Load sort parameters for Near Expiry table
$sort_exp = getSortParams('expiry_date', ['material_name', 'category', 'quantity', 'expiry_date', 'unit']);

// Map columns for low stock
$low_column_map = [
    'material_name' => 'material_name',
    'category' => 'category',
    'quantity' => 'quantity',
    'min_stock_level' => 'min_stock_level',
    'unit' => 'unit'
];

$low_order_by = isset($low_column_map[$sort_low['column']]) ? $low_column_map[$sort_low['column']] : 'quantity';

// Map columns for expiry
$exp_column_map = [
    'material_name' => 'material_name',
    'category' => 'category',
    'quantity' => 'quantity',
    'expiry_date' => 'expiry_date',
    'unit' => 'unit'
];

$exp_order_by = isset($exp_column_map[$sort_exp['column']]) ? $exp_column_map[$sort_exp['column']] : 'expiry_date';

// --- RAW MATERIALS SUMMARY ---
$raw_count = $conn->query("SELECT COUNT(*) as c FROM raw_materials")->fetch_assoc()['c'] ?? 0;
$raw_total = $conn->query("SELECT SUM(quantity) as t FROM raw_materials")->fetch_assoc()['t'] ?? 0;
$raw_low = $conn->query("SELECT COUNT(*) as c FROM raw_materials WHERE min_stock_level>0 AND quantity<=min_stock_level")->fetch_assoc()['c'] ?? 0;
$near_exp = $conn->query("
    SELECT COUNT(*) as c 
    FROM raw_materials 
    WHERE expiry_date IS NOT NULL 
    AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $near_expiry_days DAY)
")->fetch_assoc()['c'] ?? 0;

// Pagination for tables
$low_stock_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM raw_materials WHERE min_stock_level>0 AND quantity<=min_stock_level", null, 'low_stock_page', 'low_stock_per_page')
    : ['offset'=>0,'per_page'=>25,'total'=>0,'total_pages'=>1,'page'=>1,'prev_page'=>null,'next_page'=>null];

$near_expiry_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM raw_materials WHERE expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $near_expiry_days DAY)", null, 'near_exp_page', 'near_exp_per_page')
    : ['offset'=>0,'per_page'=>25,'total'=>0,'total_pages'=>1,'page'=>1,'prev_page'=>null,'next_page'=>null];

$fg_list_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM finished_goods WHERE quantity > 0", null, 'fg_list_page', 'fg_list_per_page')
    : ['offset'=>0,'per_page'=>25,'total'=>0,'total_pages'=>1,'page'=>1,'prev_page'=>null,'next_page'=>null];

// --- FINISHED GOODS SUMMARY ---
$fg_count = 0;
$fg_total = 0;
$fg_low = 0;

// Total count (all finished goods)
$fg_count_q = $conn->query("
    SELECT COUNT(DISTINCT fg.product_id) AS c
    FROM finished_goods fg
    WHERE fg.quantity > 0
");
$fg_count = ($fg_count_q) ? (int)$fg_count_q->fetch_assoc()['c'] : 0;

// Total quantity (all finished goods)
$fg_total_q = $conn->query("
    SELECT SUM(fg.quantity - COALESCE(fg.reserved_quantity, 0)) AS t
    FROM finished_goods fg
    WHERE fg.quantity > 0
");
$fg_total = ($fg_total_q) ? (float)$fg_total_q->fetch_assoc()['t'] : 0;

// Low stock (all finished goods)
$fg_low_q = $conn->query("
    SELECT COUNT(DISTINCT fg.product_id) as c
    FROM finished_goods fg
    WHERE (fg.quantity - COALESCE(fg.reserved_quantity,0)) < $fg_low_threshold 
      AND fg.quantity > 0
");
$fg_low = ($fg_low_q && $fg_low_q->num_rows) ? (int)$fg_low_q->fetch_assoc()['c'] : 0;

// --- RAW MATERIALS BY CATEGORY FOR CHART ---
$raw_by_category = $conn->query("
    SELECT 
        COALESCE(category, 'Uncategorized') as category_name,
        SUM(quantity) as total_quantity,
        COUNT(*) as item_count
    FROM raw_materials
    GROUP BY category
    ORDER BY item_count DESC
");

$raw_category_labels = [];
$raw_category_data = [];
$raw_category_colors = [];
$category_palette = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#5a5c69', '#858796', '#3a3b45'];
if($raw_by_category){
    $color_index = 0;
    while($row = $raw_by_category->fetch_assoc()){
        $raw_category_labels[] = $row['category_name'];
        $raw_category_data[] = (int)$row['item_count'];
        $raw_category_colors[] = $category_palette[$color_index % count($category_palette)];
        $color_index++;
    }
}

// --- RAW MATERIALS BY INDIVIDUAL ITEM FOR DETAILED CHART ---
$raw_items = $conn->query("
    SELECT material_name, quantity, min_stock_level, unit
    FROM raw_materials
    ORDER BY quantity DESC
    LIMIT 15
");

$raw_item_labels = [];
$raw_item_data = [];
$raw_item_colors = [];
if($raw_items){
    while($row = $raw_items->fetch_assoc()){
        $raw_item_labels[] = $row['material_name'];
        $raw_item_data[] = (float)$row['quantity'];
        if($row['quantity'] <= $row['min_stock_level']){
            $raw_item_colors[] = '#e74a3b'; // red for low
        } elseif($row['quantity'] < ($row['min_stock_level']*2)){
            $raw_item_colors[] = '#f6c23e'; // yellow for moderate
        } else {
            $raw_item_colors[] = '#1cc88a'; // green for safe
        }
    }
}

// --- FINISHED GOODS PER PRODUCT FOR CHART ---
$fg_batch_result = $conn->query("
    SELECT p.product_name, SUM(fg.quantity - COALESCE(fg.reserved_quantity,0)) as available_qty
    FROM finished_goods fg
    INNER JOIN products p ON fg.product_id = p.product_id
    WHERE fg.quantity > 0
    GROUP BY fg.product_id, p.product_name
    ORDER BY available_qty DESC
    LIMIT 15
");

$fg_labels = [];
$fg_data = [];
$fg_colors = [];
if($fg_batch_result){
    while($row = $fg_batch_result->fetch_assoc()){
        $fg_labels[] = $row['product_name'];
        $fg_data[] = (float)$row['available_qty'];
        if($row['available_qty'] < $fg_low_threshold){
            $fg_colors[] = '#e74a3b'; // red for low
        } elseif($row['available_qty'] < ($fg_low_threshold*3)){
            $fg_colors[] = '#f6c23e'; // yellow for moderate
        } else {
            $fg_colors[] = '#1cc88a'; // green for safe
        }
    }
}

// --- FINISHED GOODS ITEMS LIST (All items, QC-approved or not) ---
$fg_items = $conn->query(
    "SELECT fg.*, p.product_name FROM finished_goods fg LEFT JOIN products p ON fg.product_id = p.product_id ORDER BY p.product_name, fg.fg_id LIMIT " . $fg_list_pagination['offset'] . ", " . $fg_list_pagination['per_page'] . ""
);

$fg_items_list = [];
if ($fg_items) {
    while ($row = $fg_items->fetch_assoc()) {
        $row['available_qty'] = max(0, (float)$row['quantity'] - (float)($row['reserved_quantity'] ?? 0));
        $row['is_low'] = ($row['available_qty'] > 0 && $row['available_qty'] < $fg_low_threshold);
        $row['is_near_expiry'] = false;
        if ($row['expiry_date']) {
            $expiry_ts = strtotime($row['expiry_date']);
            $now_ts = strtotime(date('Y-m-d'));
            $cutoff_ts = strtotime("+{$near_expiry_days} days", $now_ts);
            $row['is_near_expiry'] = ($expiry_ts >= $now_ts && $expiry_ts <= $cutoff_ts);
        }
        $fg_items_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Inventory Summary | LORINIMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.card-small {
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.card-small:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}
.card-small h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    opacity: 0.9;
    font-weight: 500;
}
.card-small p {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
}
.card-small small {
    display: block;
    margin-top: 5px;
    font-size: 14px;
    opacity: 0.8;
}
.card-small:nth-child(1) { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.card-small:nth-child(2) { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.card-small:nth-child(3) { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.card-small:nth-child(4) { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.card-small:nth-child(5) { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

.card {
    padding: 25px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.card h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #1f2937;
    font-size: 20px;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 10px;
}

.chart-container {
    position: relative;
    height: 400px;
    margin-bottom: 20px;
}

.chart-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
table th, table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}
table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #5568d3;
}
table tbody tr {
    transition: all 0.2s ease;
}
table tbody tr:hover {
    background-color: #f8f9ff;
}
table tbody tr:last-child td {
    border-bottom: none;
}
.low-stock {
    background-color: #fef2f2;
    border-left: 4px solid #dc2626;
}
.near-expiry {
    background-color: #fffbeb;
    border-left: 4px solid #f59e0b;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
.status-low { background: #fee2e2; color: #991b1b; }
.status-safe { background: #d1fae5; color: #065f46; }
.status-warning { background: #fef3c7; color: #92400e; }

@media (max-width: 768px) {
    .chart-grid {
        grid-template-columns: 1fr;
    }
    .dashboard {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
</head>
<body>
<div class="wrapper">
<?php include "layouts/sidebar.php"; ?>
<div class="main">
<?php include "layouts/header.php"; ?>
<div class="content">
<h2>Inventory Summary Dashboard</h2>
<p>Comprehensive overview of raw materials and QC-approved finished goods inventory.</p>
<?php showMessage(); ?>

<!-- DASHBOARD CARDS -->
<div class="dashboard">
    <div class="card-small">
        <h3>Total Raw Materials</h3>
        <p><?php echo number_format($raw_count); ?></p>
        <small><?php echo number_format($raw_total, 2); ?> total units</small>
    </div>
    <div class="card-small">
        <h3>Total Finished Goods</h3>
        <p><?php echo number_format($fg_count); ?></p>
        <small><?php echo number_format($fg_total, 2); ?> available units</small>
    </div>
    <div class="card-small">
        <h3>Low Stock Raw</h3>
        <p><?php echo number_format($raw_low); ?></p>
        <small>Items below minimum</small>
    </div>
    <div class="card-small">
        <h3>Low Stock FG</h3>
        <p><?php echo number_format($fg_low); ?></p>
        <small>Products below threshold</small>
    </div>
    <div class="card-small">
        <h3>Near Expiry</h3>
        <p><?php echo number_format($near_exp); ?></p>
        <small>Within <?php echo $near_expiry_days; ?> days</small>
    </div>
</div>

<!-- CHARTS GRID -->
<div class="chart-grid">
    <!-- Raw Materials by Category -->
    <div class="card">
        <h3>Raw Materials by Category</h3>
        <div class="chart-container">
            <canvas id="rawCategoryChart"></canvas>
        </div>
    </div>

    <!-- Raw Materials Top Items -->
    <div class="card">
        <h3>Top Raw Materials (Individual Items)</h3>
        <div class="chart-container">
            <canvas id="rawItemsChart"></canvas>
        </div>
    </div>
</div>

<div class="chart-grid">
    <!-- Finished Goods by Product -->
    <div class="card">
        <h3>Finished Goods by Product (Top 15)</h3>
        <div class="chart-container">
            <canvas id="fgChart"></canvas>
        </div>
    </div>

    <!-- Overall Inventory Comparison -->
    <div class="card">
        <h3>Inventory Overview</h3>
        <div class="chart-container">
            <canvas id="overviewChart"></canvas>
        </div>
    </div>
</div>

<!-- LOW STOCK TABLE -->
<div class="card" id="low-stock-alerts">
<h3>Low Stock Alerts</h3>
<?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin-bottom:12px;">' . renderPerPageSelector($conn, $low_stock_pagination['per_page'], 'low_stock_per_page', 'low_stock_page') . '</div>'; ?>
<?php
$low_list = $conn->query("SELECT material_name, quantity, min_stock_level, unit, category FROM raw_materials WHERE min_stock_level>0 AND quantity<=min_stock_level ORDER BY " . $low_order_by . " " . $sort_low['order'] . " LIMIT " . $low_stock_pagination['offset'] . ", " . $low_stock_pagination['per_page']);
?>
<table>
<thead>
<tr>
<th><?php echo sortHeader('material_name', 'Raw Material', $sort_low); ?></th>
<th><?php echo sortHeader('category', 'Category', $sort_low); ?></th>
<th><?php echo sortHeader('quantity', 'Current Qty', $sort_low); ?></th>
<th><?php echo sortHeader('min_stock_level', 'Min Level', $sort_low); ?></th>
<th>Reorder Qty</th>
<th><?php echo sortHeader('unit', 'Unit', $sort_low); ?></th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php if($low_list && $low_list->num_rows>0): ?>
    <?php while($r=$low_list->fetch_assoc()): 
        $reorder_qty = max($r['min_stock_level']*2 - $r['quantity'],0);
        $status_class = $r['quantity'] <= 0 ? 'status-low' : 'status-warning';
    ?>
    <tr class="low-stock">
        <td><strong><?php echo htmlspecialchars($r['material_name']); ?></strong></td>
        <td><?php echo htmlspecialchars($r['category'] ?? 'Uncategorized'); ?></td>
        <td><?php echo number_format($r['quantity'],2); ?></td>
        <td><?php echo number_format($r['min_stock_level'],2); ?></td>
        <td><strong><?php echo number_format($reorder_qty,2); ?></strong></td>
        <td><?php echo htmlspecialchars($r['unit']); ?></td>
        <td><span class="status-badge <?php echo $status_class; ?>">Low Stock</span></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">✅ No raw materials below minimum level. All stock levels are healthy.</td></tr>
<?php endif; ?>
</tbody>
</table>
<?php if (function_exists('renderPagination')) echo renderPagination($low_stock_pagination, 'low_stock_page'); ?>
</div>

<!-- NEAR EXPIRY TABLE -->
<div class="card" id="near-expiry-alerts">
<h3>Near Expiry Alerts (Next <?php echo $near_expiry_days; ?> Days)</h3>
<?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin-bottom:12px;">' . renderPerPageSelector($conn, $near_expiry_pagination['per_page'], 'near_exp_per_page', 'near_exp_page') . '</div>'; ?>
<?php
$exp_list = $conn->query("SELECT material_name, quantity, expiry_date, unit, category FROM raw_materials WHERE expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $near_expiry_days DAY) ORDER BY " . $exp_order_by . " " . $sort_exp['order'] . " LIMIT " . $near_expiry_pagination['offset'] . ", " . $near_expiry_pagination['per_page']);
?>
<table>
<thead>
<tr>
<th><?php echo sortHeader('material_name', 'Raw Material', $sort_exp); ?></th>
<th><?php echo sortHeader('category', 'Category', $sort_exp); ?></th>
<th><?php echo sortHeader('quantity', 'Qty', $sort_exp); ?></th>
<th><?php echo sortHeader('expiry_date', 'Expiry Date', $sort_exp); ?></th>
<th>Days Remaining</th>
<th><?php echo sortHeader('unit', 'Unit', $sort_exp); ?></th>
</tr>
</thead>
<tbody>
<?php if($exp_list && $exp_list->num_rows>0): ?>
    <?php while($r=$exp_list->fetch_assoc()): 
        $exp_date = new DateTime($r['expiry_date']);
        $today = new DateTime();
        $days_left = $today->diff($exp_date)->days;
    ?>
    <tr class="near-expiry">
        <td><strong><?php echo htmlspecialchars($r['material_name']); ?></strong></td>
        <td><?php echo htmlspecialchars($r['category'] ?? 'Uncategorized'); ?></td>
        <td><?php echo number_format($r['quantity'],2); ?></td>
        <td><strong><?php echo formatDate($r['expiry_date']); ?></strong></td>
        <td><span class="status-badge status-warning"><?php echo $days_left; ?> days</span></td>
        <td><?php echo htmlspecialchars($r['unit']); ?></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6" style="text-align:center; padding:30px; color:var(--text-muted);">✅ No items expiring in the next <?php echo $near_expiry_days; ?> days.</td></tr>
<?php endif; ?>
</tbody>
</table>
<?php if (function_exists('renderPagination')) echo renderPagination($near_expiry_pagination, 'near_exp_page'); ?>
</div>

<!-- FINISHED GOODS TABLE -->
<div class="card" id="finished-goods-list">
<h3>Finished Goods (All Items)</h3>
<?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin-bottom:12px;">' . renderPerPageSelector($conn, $fg_list_pagination['per_page'], 'fg_list_per_page', 'fg_list_page') . '</div>'; ?>
<table>
<thead>
<tr>
    <th>Product</th>
    <th>Available</th>
    <th>Reserved</th>
    <th>Expiry</th>
    <th>Status</th>
    <th>QC</th>
</tr>
</thead>
<tbody>
<?php if(count($fg_items_list) > 0): ?>
    <?php foreach($fg_items_list as $item): ?>
        <?php
            $status = [];
            if ($item['is_low']) {
                $status[] = '<span class="status-badge status-low">Low Stock</span>';
            }
            if ($item['is_near_expiry']) {
                $status[] = '<span class="status-badge status-warning">Near Expiry</span>';
            }
            if (empty($status)) {
                $status[] = '<span class="status-badge status-safe">OK</span>';
            }
        ?>
        <tr <?php echo $item['is_low'] ? 'class="low-stock"' : ($item['is_near_expiry'] ? 'class="near-expiry"' : ''); ?> >
            <td><?php echo htmlspecialchars($item['product_name'] ?? 'Unknown'); ?></td>
            <td><?php echo number_format($item['available_qty'],2); ?></td>
            <td><?php echo number_format($item['reserved_quantity'] ?? 0, 2); ?></td>
            <td><?php echo $item['expiry_date'] ? htmlspecialchars($item['expiry_date']) : '-'; ?></td>
            <td><?php echo implode(' ', $status); ?></td>
            <td><?php echo $item['qc_approved'] ? '✔️' : '❌'; ?></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--text-muted);">No finished goods found.</td></tr>
<?php endif; ?>
</tbody>
</table>
<?php if (function_exists('renderPagination')) echo renderPagination($fg_list_pagination, 'fg_list_page'); ?>
</div>

<?php include "layouts/footer.php"; ?>
</div>
</div>

<script src="assets/js/sidebar.js"></script>
<script>
// Chart.js configuration
Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.plugins.legend.display = true;
Chart.defaults.plugins.legend.position = 'bottom';

// Raw Materials by Category Chart (Pie)
const rawCategoryCtx = document.getElementById('rawCategoryChart').getContext('2d');
new Chart(rawCategoryCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($raw_category_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($raw_category_data); ?>,
            backgroundColor: <?php echo json_encode($raw_category_colors); ?>,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var total = context.dataset.data.reduce((a, b) => a + b, 0);
                        var percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' items (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Raw Materials Individual Items Chart (Bar)
const rawItemsCtx = document.getElementById('rawItemsChart').getContext('2d');
new Chart(rawItemsCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($raw_item_labels); ?>,
        datasets: [{
            label: 'Quantity',
            data: <?php echo json_encode($raw_item_data); ?>,
            backgroundColor: <?php echo json_encode($raw_item_colors); ?>,
            borderColor: <?php echo json_encode($raw_item_colors); ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Quantity: ' + context.parsed.x.toFixed(2);
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

// Finished Goods Chart (Bar)
const fgCtx = document.getElementById('fgChart').getContext('2d');
new Chart(fgCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($fg_labels); ?>,
        datasets:[{
            label:'Available Quantity',
            data: <?php echo json_encode($fg_data); ?>,
            backgroundColor: <?php echo json_encode($fg_colors); ?>,
            borderColor: <?php echo json_encode($fg_colors); ?>,
            borderWidth: 1
        }]
    },
    options:{
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins:{
            legend:{
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Available: ' + context.parsed.x.toFixed(2) + ' units';
                    }
                }
            }
        },
        scales:{
            x:{
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

// Overview Comparison Chart
const overviewCtx = document.getElementById('overviewChart').getContext('2d');
new Chart(overviewCtx, {
    type: 'bar',
    data: {
        labels: ['Raw Materials', 'Finished Goods'],
        datasets: [{
            label: 'Total Quantity',
            data: [<?php echo $raw_total; ?>, <?php echo $fg_total; ?>],
            backgroundColor: ['#4e73df', '#1cc88a'],
            borderColor: ['#4e73df', '#1cc88a'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Total: ' + context.parsed.y.toFixed(2) + ' units';
                    }
                }
            }
        },
        scales: {
            y: {
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
