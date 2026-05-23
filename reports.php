<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include "db_connect.php";
include "includes/functions.php";

// Define modules with display ID and name columns
// Each module is grouped by a logical category for easier navigation.
$modules = [
    'invoice'=>[
        'category'=>'Accounting',
        'table'=>'invoices',
        'date_col'=>'invoice_date',
        'amount_col'=>'amount',
        'id_col'=>'invoice_id',          
        'display_id_col'=>'invoice_number', 
        'name_col'=>'customer_name',       // customer name
        'label'=>'Invoices'
    ],
    'sales'=>[
        'category'=>'Sales',
        'table'=>'sales_orders',
        'date_col'=>'order_date',
        'amount_col'=>'total_amount',
        'id_col'=>'order_id',
        'display_id_col'=>'order_number',
        'name_col'=>'customer_name',
        'label'=>'Sales Orders'
    ],
    'purchase_order'=>[
        'category'=>'Procurement',
        'table'=>'purchase_requests',
        'date_col'=>'created_at',
        'amount_col'=>null,
        'id_col'=>'pr_id',
        'display_id_col'=>'pr_number',
        'name_col'=>'supplier_name',       // supplier name
        'label'=>'Purchase Requests'
    ],
    'batch_report'=>[
        'category'=>'Production',
        'table'=>'production_batches',
        'date_col'=>'created_at',
        'amount_col'=>null,
        'id_col'=>'batch_id',
        'display_id_col'=>'batch_number',
        'name_col'=>'product_name',        // product name
        'label'=>'Production Batches'
    ],
    'production_summary'=>[
        'category'=>'Production',
        'table'=>'production_batches',
        'date_col'=>'batch_date',
        'amount_col'=>'quantity',
        'id_col'=>'batch_id',
        'display_id_col'=>'batch_number',
        'name_col'=>'product_name',
        'label'=>'Production Summary'
    ],
    'rejected_batches'=>[
        'category'=>'Quality Control',
        'table'=>'qc_records',
        'date_col'=>'inspection_date',
        'amount_col'=>null,
        'id_col'=>'qc_id',
        'display_id_col'=>'qc_number', 
        'name_col'=>'batch_number',
        'label'=>'Rejected / Defective Batches'
    ],
    'stock_level'=>[
        'category'=>'Inventory',
        'table'=>'raw_materials',
        'date_col'=>'updated_at',
        'amount_col'=>null,
        'id_col'=>'material_id',
        'display_id_col'=>'material_name',
        'name_col'=>'category',
        'label'=>'Stock Levels'
    ],
    'low_stock'=>[
        'category'=>'Inventory',
        'table'=>'raw_materials',
        'date_col'=>'updated_at',
        'amount_col'=>null,
        'id_col'=>'material_id',
        'display_id_col'=>'material_name',
        'name_col'=>'category',
        'label'=>'Low Stock Items'
    ],
    'inventory_movements'=>[
        'category'=>'Inventory',
        'table'=>'inventory_transactions',
        'date_col'=>'created_at',
        'amount_col'=>'quantity',
        'id_col'=>'transaction_id',
        'display_id_col'=>'transaction_id',
        'name_col'=>'transaction_type',
        'label'=>'Inventory Movements'
    ],
    'delivery_status'=>[
        'category'=>'Logistics',
        'table'=>'delivery_assignments',
        'date_col'=>'created_at',
        'amount_col'=>null,
        'id_col'=>'assignment_id',
        'display_id_col'=>'assignment_number',
        'name_col'=>'status',
        'label'=>'Delivery Status'
    ],
    'returned_inventory'=>[
        'category'=>'Logistics',
        'table'=>'delivery_assignments',
        'date_col'=>'returned_at',
        'amount_col'=>null,
        'id_col'=>'assignment_id',
        'display_id_col'=>'assignment_number',
        'name_col'=>'order_number',
        'label'=>'Returned Deliveries'
    ],
    'user_activity'=>[
        'category'=>'System',
        'table'=>'activity_log',
        'date_col'=>'created_at',
        'amount_col'=>null,
        'id_col'=>'id',
        'display_id_col'=>'action',
        'name_col'=>'user_id',
        'label'=>'User Activity'
    ],
    'statutory_remittances'=>[
        'category'=>'Payroll',
        'table'=>'payroll_breakdown',
        'date_col'=>'payroll_period_end',
        'amount_col'=>'amount',
        'id_col'=>'id',
        'display_id_col'=>'payroll_id',
        'name_col'=>'code',
        'label'=>'Statutory Remittances'
    ]
];

// Handle filters
$moduleFilter = $_GET['module'] ?? 'all';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$groupBy = $_GET['group_by'] ?? 'day';
$groupBy = in_array($groupBy, ['day', 'week', 'month', 'quarter']) ? $groupBy : 'day';

// Optional stat remittance filter (SSS / PhilHealth / Pag-IBIG / Tax etc.)
$remittanceFilter = $_GET['remittance_code'] ?? 'all';
$validRemittanceCodes = ['all','SSS','PHILHEALTH','PAGIBIG','TAX','LATE'];
if (!in_array($remittanceFilter, $validRemittanceCodes)) {
    $remittanceFilter = 'all';
}

function formatDateInput($date) {
    return $date ? date('Y-m-d', strtotime($date)) : '';
}

// Group modules by category for better organization
$modulesByCategory = [];
foreach ($modules as $key => $info) {
    $cat = $info['category'] ?? 'Other';
    if (!isset($modulesByCategory[$cat])) {
        $modulesByCategory[$cat] = [];
    }
    $modulesByCategory[$cat][$key] = $info;
}
ksort($modulesByCategory);

// --- KPI Metrics / Dashboard values ---
$totalRevenue = (float)($conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM invoices WHERE status = 'Paid'")->fetch_assoc()['total'] ?? 0);
$totalOrders = (int)($conn->query("SELECT COUNT(*) AS c FROM sales_orders")->fetch_assoc()['c'] ?? 0);
$completedDeliveries = (int)($conn->query("SELECT COUNT(*) AS c FROM delivery_assignments WHERE status = 'Delivered'")->fetch_assoc()['c'] ?? 0);
$returnedDeliveries = 0;
$hasReturnedFlag = $conn->query("SHOW COLUMNS FROM delivery_assignments LIKE 'returned_to_inventory'")->num_rows > 0;
if ($hasReturnedFlag) {
    $returnedDeliveries = (int)($conn->query("SELECT COUNT(*) AS c FROM delivery_assignments WHERE status = 'Failed' AND returned_to_inventory = 1")->fetch_assoc()['c'] ?? 0);
}
$lowStockItems = (int)($conn->query("SELECT COUNT(*) AS c FROM raw_materials WHERE min_stock_level>0 AND quantity <= min_stock_level")->fetch_assoc()['c'] ?? 0);

// --- Chart data ---
$year = date('Y');

// Revenue vs Expenses per month
$revenueByMonth = [];
$expenseByMonth = [];
$res = $conn->query("SELECT DATE_FORMAT(invoice_date, '%Y-%m') AS m, COALESCE(SUM(amount),0) AS total FROM invoices WHERE status = 'Paid' AND YEAR(invoice_date) = $year GROUP BY m ORDER BY m");
while($r = $res ? $res->fetch_assoc() : null) {
    $revenueByMonth[$r['m']] = (float)$r['total'];
}
$res = $conn->query("SELECT DATE_FORMAT(expense_date, '%Y-%m') AS m, COALESCE(SUM(amount),0) AS total FROM expenses WHERE YEAR(expense_date) = $year GROUP BY m ORDER BY m");
while($r = $res ? $res->fetch_assoc() : null) {
    $expenseByMonth[$r['m']] = (float)$r['total'];
}
$months = array_unique(array_merge(array_keys($revenueByMonth), array_keys($expenseByMonth)));
sort($months);
$chartMonths = $months;
$chartRevenue = array_map(function($m) use ($revenueByMonth) { return $revenueByMonth[$m] ?? 0; }, $months);
$chartExpenses = array_map(function($m) use ($expenseByMonth) { return $expenseByMonth[$m] ?? 0; }, $months);

// Production per product (top 10)
$prodLabels = [];
$prodData = [];
$res = $conn->query("SELECT p.product_name, COALESCE(SUM(pb.quantity),0) AS total FROM production_batches pb LEFT JOIN products p ON pb.product_id = p.product_id GROUP BY pb.product_id, p.product_name ORDER BY total DESC LIMIT 10");
while($r = $res ? $res->fetch_assoc() : null) {
    $prodLabels[] = $r['product_name'];
    $prodData[] = (float)$r['total'];
}

// Delivery status breakdown
$deliveryLabels = [];
$deliveryData = [];
$res = $conn->query("SELECT status, COUNT(*) AS c FROM delivery_assignments GROUP BY status");
while($r = $res ? $res->fetch_assoc() : null) {
    $deliveryLabels[] = $r['status'];
    $deliveryData[] = (int)$r['c'];
}

// Module icons (compact dashboard)
$moduleIcons = [
    'invoice' => '📄',
    'sales' => '🛒',
    'stock_level' => '📦',
    'low_stock' => '⚠️',
    'inventory_movements' => '🔄',
    'delivery_status' => '🚚',
    'purchase_order' => '🛒',
    'batch_report' => '🏭',
    'production_summary' => '🏭',
    'rejected_batches' => '🧪',
    'user_activity' => '👤',
    'statutory_remittances' => '💰',
    'system_summary' => '📑'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Central Reports | LORINIMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* Filter design */
.filter {
    margin-bottom: 18px;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    background: #f8fafc;
    padding: 14px 16px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}
.filter label { font-weight: 600; font-size: 0.93rem; }
.filter select, .filter input { padding: 8px 10px; border-radius: 8px; border: 1px solid #d1d5db; background: white; }
.filter .actions { margin-left: auto; display:flex; gap:8px; }

/* KPI cards */
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px;margin-bottom:20px;}
.kpi-card{background:white;padding:16px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.05);border:1px solid rgba(0,0,0,0.06);}
.kpi-card h4{margin:0 0 10px;font-size:0.95rem;color:#334155;}
.kpi-value{font-size:24px;font-weight:700;color:#ff6b35;}
.kpi-meta{font-size:0.85rem;color:#64748b;margin-top:8px;}

/* Compact module dashboard */
.module-dashboard{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,0.04);margin-bottom:20px;}
.module-dashboard-header{padding:14px 16px;border-bottom:1px solid #f1f5f9;font-weight:700;color:#1f2937;display:flex;align-items:center;justify-content:space-between;}
.module-dashboard-list{list-style:none;margin:0;padding:0;}
.module-dashboard-item{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-bottom:1px solid #f1f5f9;}
.module-dashboard-item:last-child{border-bottom:none;}
.module-dashboard-left{display:flex;align-items:center;gap:10px;flex:1;}
.module-icon{font-size:18px;}
.module-label{font-weight:600;color:#1f2937;}
.module-count{font-weight:700;color:#ff6b35;min-width:64px;text-align:right;}
.module-action{margin-left:12px;}
.module-action a{padding:6px 12px;border-radius:8px;background:#3b82f6;color:#fff;text-decoration:none;font-size:12px;}
.module-action a:hover{background:#2563eb;}

/* Tables */
.reports-container { max-width:1200px; margin:0 auto; }
table { width: 100%; border-collapse: collapse; margin-bottom: 28px; background: #fff; border-radius: 10px; overflow: hidden; }
th, td { border-bottom: 1px solid #f1f1f1; padding: 12px 10px; text-align: left; }
th { background: linear-gradient(90deg,#FF7A45,#FF6B35); color: white; position: sticky; top: 0; z-index: 1; }
tbody tr:nth-child(even){background:#f9fafb;}
tbody tr:hover{background:#fff7ed;}
.badge{padding:4px 8px;border-radius:6px;font-size:12px;font-weight:600;display:inline-block;}
.badge-success{background:#d1fae5;color:#065f46;}
.badge-warning{background:#fef3c7;color:#92400e;}
.badge-danger{background:#fee2e2;color:#991b1b;}

.btn { padding:8px 12px; background:#10b981; color:white; border:none; cursor:pointer; text-decoration:none; border-radius:8px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease; }
.btn:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(0,0,0,0.18); background: #0ea676; }

.chart-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(260px,1fr)); gap:18px; margin-bottom:24px; }
.chart-card { background:white; border-radius:12px; padding:16px; box-shadow:0 4px 10px rgba(0,0,0,0.05); border:1px solid rgba(0,0,0,0.06); }
.chart-card h4 { margin:0 0 12px; font-size:1rem; color:#1f2937; }
.chart-card canvas { width:100% !important; height:260px !important; }

.export-all { display:flex; gap:10px; align-items:center; justify-content:flex-end; margin-bottom:18px; position: relative; z-index: 2; } 
.btn { padding:8px 12px; background:#10b981; color:white; border:none; cursor:pointer; text-decoration:none; border-radius:8px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease; position: relative; z-index: 2; }
</style>
</head>
<body>
<div class="wrapper">
<?php include "layouts/sidebar.php"; ?>
<div class="main">
<?php include "layouts/header.php"; ?>

<div class="content">
<h2>📊 Central Reports</h2>
<div class="reports-container">
    <div class="filter">
        <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; width:100%; align-items:center;">
                    <label style="min-width:160px;">Module:
                <select name="module" id="module-select">
                    <option value="all" <?php if($moduleFilter=='all') echo 'selected'; ?>>All</option>
                    <option value="system_summary" <?php if($moduleFilter=='system_summary') echo 'selected'; ?>>System Summary</option>
                    <?php foreach($modulesByCategory as $category => $items): ?>
                    <optgroup label="<?php echo htmlspecialchars($category); ?>">
                        <?php foreach($items as $key=>$info): ?>
                        <option value="<?php echo $key; ?>" <?php if($moduleFilter==$key) echo 'selected'; ?>><?php echo $info['label']; ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Start Date:
                <input type="date" name="start_date" value="<?php echo formatDateInput($startDate); ?>">
            </label>
            <label>End Date:
                <input type="date" name="end_date" value="<?php echo formatDateInput($endDate); ?>">
            </label>
            <label>Group By:
                <select name="group_by">
                    <option value="day" <?php if($groupBy=='day') echo 'selected'; ?>>Daily</option>
                    <option value="week" <?php if($groupBy=='week') echo 'selected'; ?>>Weekly</option>
                    <option value="month" <?php if($groupBy=='month') echo 'selected'; ?>>Monthly</option>
                    <option value="quarter" <?php if($groupBy=='quarter') echo 'selected'; ?>>Quarterly</option>
                </select>
            </label>
            <label id="remittance-filter" style="display:none;">
                Remittance:
                <select name="remittance_code">
                    <option value="all" <?php if($remittanceFilter=='all') echo 'selected'; ?>>All</option>
                    <option value="SSS" <?php if($remittanceFilter=='SSS') echo 'selected'; ?>>SSS</option>
                    <option value="PHILHEALTH" <?php if($remittanceFilter=='PHILHEALTH') echo 'selected'; ?>>PhilHealth</option>
                    <option value="PAGIBIG" <?php if($remittanceFilter=='PAGIBIG') echo 'selected'; ?>>Pag-IBIG</option>
                    <option value="TAX" <?php if($remittanceFilter=='TAX') echo 'selected'; ?>>Withholding Tax</option>
                    <option value="LATE" <?php if($remittanceFilter=='LATE') echo 'selected'; ?>>Late Penalty</option>
                </select>
            </label>
            <div class="actions">
                <button type="submit" class="btn">Filter</button>
                <button type="button" class="btn" onclick="location.href='reports.php'">Clear</button>
            </div>
        </form>
    </div>


    <div class="kpi-grid">
        <div class="kpi-card">
            <h4>Total Revenue</h4>
            <div class="kpi-value">₱<?php echo number_format($totalRevenue,2); ?></div>
            <div class="kpi-meta">Paid invoices</div>
        </div>
        <div class="kpi-card">
            <h4>Total Orders</h4>
            <div class="kpi-value"><?php echo number_format($totalOrders); ?></div>
            <div class="kpi-meta">Sales orders</div>
        </div>
        <div class="kpi-card">
            <h4>Delivered</h4>
            <div class="kpi-value"><?php echo number_format($completedDeliveries); ?></div>
            <div class="kpi-meta">Completed deliveries</div>
        </div>
        <div class="kpi-card">
            <h4>Returned</h4>
            <div class="kpi-value"><?php echo number_format($returnedDeliveries); ?></div>
            <div class="kpi-meta">Failed deliveries returned</div>
        </div>
        <div class="kpi-card">
            <h4>Low Stock Items</h4>
            <div class="kpi-value"><?php echo number_format($lowStockItems); ?></div>
            <div class="kpi-meta">Raw materials below min</div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <h4>Revenue vs Expenses (<?php echo $year; ?>)</h4>
            <canvas id="revenueExpensesChart"></canvas>
        </div>
        <div class="chart-card">
            <h4>Production (Top Products)</h4>
            <canvas id="productionChart"></canvas>
        </div>
        <div class="chart-card">
            <h4>Delivery Status</h4>
            <canvas id="deliveryChart"></canvas>
        </div>
        <div class="chart-card">
            <h4>Sales Trend (<?php echo ucfirst($groupBy); ?>)</h4>
            <canvas id="salesTrendChart"></canvas>
        </div>
    </div>

    <div class="module-dashboard">
        <div class="module-dashboard-header">
            <span>📊 Report Modules</span>
            <span style="font-size:0.85rem;color:#64748b;">Select to view details per module</span>
        </div>
        <ul class="module-dashboard-list">
            <?php foreach($modules as $key=>$info):
                $count = 0;
                $icon = $moduleIcons[$key] ?? '📌';
                if ($key === 'returned_inventory') {
                    // Only show returned deliveries if our schema supports the flag
                    $hasReturnedFlag = $conn->query("SHOW COLUMNS FROM delivery_assignments LIKE 'returned_to_inventory'")->num_rows > 0;
                    if ($hasReturnedFlag) {
                        $count = (int)($conn->query("SELECT COUNT(*) as c FROM delivery_assignments WHERE status = 'Failed' AND returned_to_inventory = 1")->fetch_assoc()['c'] ?? 0);
                    }
                } else {
                    $count = $conn->query("SELECT COUNT(*) as c FROM " . $info['table'])->fetch_assoc()['c'] ?? 0;
                }
            ?>
            <li class="module-dashboard-item">
                <div class="module-dashboard-left">
                    <span class="module-icon"><?php echo $icon; ?></span>
                    <span class="module-label"><?php echo htmlspecialchars($info['label']); ?></span>
                </div>
                <div class="module-count"><?php echo number_format($count); ?></div>
                <div class="module-action"><a href="reports.php?module=<?php echo urlencode($key); ?>">View</a></div>
            </li>
            <?php endforeach; ?>
            <li class="module-dashboard-item">
                <div class="module-dashboard-left">
                    <span class="module-icon"><?php echo $moduleIcons['system_summary']; ?></span>
                    <span class="module-label">System Summary</span>
                </div>
                <div class="module-count">-</div>
                <div class="module-action"><a href="reports.php?module=system_summary">View</a></div>
            </li>
        </ul>
    </div>

<?php
function dateCondition($col, $start, $end) {
    $conds = [];
    if($start) $conds[] = "$col >= '". $start ."'";
    if($end) $conds[] = "$col <= '". $end ."'";
    return $conds ? ' AND '.implode(' AND ',$conds) : '';
}

// Monthly Analytics
$year = date('Y');
?>
<div class="analytics">
<h3>📈 Monthly Analytics (<?php echo $year; ?>)</h3>
<table>
<tr>
    <th>Module</th>
    <th>Total Records</th>
    <th>Total Amount (if applicable)</th>
</tr>
<?php
foreach($modules as $mod => $info){
    // Statutory remittances use payroll dates and breakdown amounts
    if ($mod === 'statutory_remittances') {
        global $remittanceFilter;
        $codeCond = '';
        if ($remittanceFilter !== 'all') {
            $codeCond = " AND pb.code = '" . $conn->real_escape_string($remittanceFilter) . "'";
        }
        $sql = "SELECT COUNT(*) AS total_records, COALESCE(SUM(pb.amount),0) AS total_amount " .
               "FROM payroll_breakdown pb " .
               "JOIN payroll p ON pb.payroll_id = p.payroll_id " .
               "WHERE YEAR(p.payroll_period_end) = $year" . $codeCond;
    } else {
        $sql = "SELECT COUNT(*) AS total_records";
        if($info['amount_col']) $sql .= ", SUM(".$info['amount_col'].") AS total_amount";
        $sql .= " FROM ".$info['table']." WHERE YEAR(".$info['date_col'].") = $year";
    }

    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : ['total_records' => 0, 'total_amount' => 0];
    echo "<tr>
        <td>{$info['label']}</td>
        <td>{$row['total_records']}</td>
        <td>".($info['amount_col'] ? '₱'.number_format($row['total_amount']??0,2) : '-')."</td>
    </tr>";
}
?>
</table>
</div>

<?php
// Sales aggregation summary (Daily/Weekly/Monthly/Quarterly)
switch($groupBy){
    case 'week':
        $salesDateFmt = "YEARWEEK(%s,1)";
        break;

    case 'month':
        $salesDateFmt = "DATE_FORMAT(%s,'%Y-%m')";
        break;

    case 'quarter':
        $salesDateFmt = "CONCAT(YEAR(%s),'-Q',QUARTER(%s))";
        break;

    default:
        $salesDateFmt = "DATE(%s)";
}
if ($groupBy === 'quarter') {
    $salesGroup = sprintf($salesDateFmt, 'order_date','order_date');
} else {
    $salesGroup = sprintf($salesDateFmt, 'order_date');
}

$salesSql = "
SELECT 
    $salesGroup AS period,
    COUNT(*) AS total_orders,
    COALESCE(SUM(total_amount),0) AS total_sales
FROM sales_orders
WHERE 1=1 " . dateCondition('order_date', $startDate, $endDate) . "
GROUP BY period
ORDER BY period DESC
";
$salesRes = $conn->query($salesSql);

$salesRows = [];
$salesLabels = [];
$salesTotals = [];
if ($salesRes) {
    while ($row = $salesRes->fetch_assoc()) {
        $salesRows[] = $row;
        $salesLabels[] = $row['period'];
        $salesTotals[] = (float)$row['total_sales'];
    }
}

echo "<div class='analytics'>";
echo "<div style='display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; margin-bottom:12px;'>";
echo "<h3 style='margin:0;'>🛒 Sales Summary (" . ucfirst($groupBy) . ")</h3>";
echo "</div>";
echo "<table id='salesSummaryTable'>";
echo "<thead><tr><th>Period</th><th>Total Orders</th><th>Total Sales</th></tr></thead>";
echo "<tbody>";

if (!empty($salesRows)) {
    foreach ($salesRows as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['period']) . "</td>";
        echo "<td>" . number_format($row['total_orders']) . "</td>";
        echo "<td>₱" . number_format($row['total_sales'], 2) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='3' style='text-align:center; padding: 16px; color: var(--text-muted);'>No sales data</td></tr>";
}

echo "</tbody>";
echo "</table>";
echo "</div>";

// Render module table function
function renderModuleTable($conn, $module, $info, $startDate, $endDate){
    $dateCond = dateCondition($info['date_col'],$startDate,$endDate);

    // Ensure returned inventory reporting is only shown if the schema supports it
    if ($module === 'returned_inventory') {
        $hasReturnedFlag = $conn->query("SHOW COLUMNS FROM delivery_assignments LIKE 'returned_to_inventory'")->num_rows > 0;
        if (!$hasReturnedFlag) {
            echo "<div style='padding:16px; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; margin-bottom:20px;'>Returned inventory reporting is not enabled. Please run the database migration to add return tracking columns.</div>";
            return;
        }
    }

    // Build SQL per module to include names where necessary
    switch($module){
        case 'payroll':
            $sql = "SELECT p.*, e.employee_number, e.first_name, e.last_name 
                    FROM ".$info['table']." p
                    LEFT JOIN employees e ON p.employee_id = e.employee_id
                    WHERE 1=1 $dateCond
                    ORDER BY ".$info['date_col']." DESC";
            break;

        case 'invoice':
            $sql = "SELECT i.*, c.customer_name 
                    FROM ".$info['table']." i
                    LEFT JOIN customers c ON i.customer_id = c.customer_id
                    WHERE 1=1 $dateCond
                    ORDER BY ".$info['date_col']." DESC";
            break;

        case 'sales':
            $sql = "SELECT so.*, c.customer_name 
                    FROM ".$info['table']." so
                    LEFT JOIN customers c ON so.customer_id = c.customer_id
                    WHERE 1=1 $dateCond
                    ORDER BY ".$info['date_col']." DESC";
            break;

        case 'purchase_order':
            $sql = "SELECT pr.*, s.supplier_name 
                    FROM ".$info['table']." pr
                    LEFT JOIN suppliers s ON pr.supplier_id = s.supplier_id
                    WHERE 1=1 $dateCond
                    ORDER BY ".$info['date_col']." DESC";
            break;

        case 'batch_report':
            $sql = "SELECT pb.*, p.product_name 
                    FROM ".$info['table']." pb
                    LEFT JOIN products p ON pb.product_id = p.product_id
                    WHERE 1=1 $dateCond
                    ORDER BY ".$info['date_col']." DESC";
            break;

        case 'production_summary':
            $sql = "SELECT pb.product_id, p.product_name, COUNT(*) AS batch_count, COALESCE(SUM(pb.quantity),0) AS total_quantity 
                    FROM ".$info['table']." pb
                    LEFT JOIN products p ON pb.product_id = p.product_id
                    WHERE 1=1 $dateCond
                    GROUP BY pb.product_id
                    ORDER BY total_quantity DESC";
            break;

        case 'rejected_batches':
            $sql = "SELECT qc.*, pb.product_id, pb.batch_number AS production_batch_number, p.product_name 
                    FROM ".$info['table']." qc
                    LEFT JOIN production_batches pb ON qc.batch_number = pb.batch_number
                    LEFT JOIN products p ON pb.product_id = p.product_id
                    WHERE 1=1 AND (qc.approval_status != 'Approved' OR qc.test_result != 'Passed') $dateCond
                    ORDER BY ".$info['date_col']." DESC";
            break;

        case 'stock_level':
        case 'low_stock':
            $stockCond = '';
            if($module === 'low_stock'){
                $stockCond = ' AND quantity <= min_stock_level';
            }
            $sql = "SELECT * FROM ".$info['table']." WHERE 1=1 $dateCond $stockCond ORDER BY quantity ASC";
            break;

        case 'inventory_movements':
            $sql = "SELECT it.*, rm.material_name, p.product_name
                    FROM ".$info['table']." it
                    LEFT JOIN raw_materials rm ON it.item_type = 'raw_material' AND it.item_id = rm.material_id
                    LEFT JOIN products p ON it.item_type = 'product' AND it.item_id = p.product_id
                    WHERE 1=1 $dateCond
                    ORDER BY ".$info['date_col']." DESC";
            break;

        case 'returned_inventory':
            $sql = "SELECT da.*, so.order_number 
                    FROM ".$info['table']." da
                    LEFT JOIN sales_orders so ON da.order_id = so.order_id
                    WHERE 1=1 AND da.status = 'Failed' AND da.returned_to_inventory = 1 $dateCond
                    ORDER BY ".$info['date_col']." DESC";
            break;

        case 'delivery_status':
            $sql = "SELECT da.*, so.order_number 
                    FROM ".$info['table']." da
                    LEFT JOIN sales_orders so ON da.order_id = so.order_id
                    WHERE 1=1 $dateCond
                    ORDER BY ".$info['date_col']." DESC";
            break;

        case 'user_activity':
            $sql = "SELECT al.*, u.username 
                    FROM ".$info['table']." al
                    LEFT JOIN users u ON al.user_id = u.id
                    WHERE 1=1 $dateCond
                    ORDER BY ".$info['date_col']." DESC";
            break;

        case 'statutory_remittances':
            global $remittanceFilter;
            $codeCond = '';
            if ($remittanceFilter !== 'all') {
                $codeCond = " AND pb.code = '" . $conn->real_escape_string($remittanceFilter) . "'";
            }
            $sql = "SELECT pb.*, p.payroll_ref, p.payroll_period_end, e.employee_number, e.first_name, e.last_name 
                    FROM ".$info['table']." pb
                    LEFT JOIN payroll p ON pb.payroll_id = p.payroll_id
                    LEFT JOIN employees e ON p.employee_id = e.employee_id
                    WHERE 1=1 " . dateCondition('p.payroll_period_end', $startDate, $endDate) . $codeCond . " 
                    ORDER BY p.payroll_period_end DESC, pb.id DESC";
            break;

        case 'qc_report':
            // QC table already has inspector_name, no join
            $sql = "SELECT * FROM ".$info['table']." qc WHERE 1=1 $dateCond ORDER BY ".$info['date_col']." DESC";
            break;

        default:
            $sql = "SELECT * FROM ".$info['table']." WHERE 1=1 $dateCond ORDER BY ".$info['date_col']." DESC";
    }

    $res = $conn->query($sql);
    if($res && $res->num_rows>0){
        echo "<h3>{$info['label']}</h3>";
        echo "<table>";
        echo "<thead><tr>";
        echo "<th>ID</th><th>Name</th><th>Date</th>";
        if($info['amount_col']) echo "<th>Amount</th>";
        echo "<th>Action</th></tr></thead>";
        echo "<tbody>";

        while($row = $res->fetch_assoc()){
            // Determine display ID and Name
            $displayId = $row[$info['display_id_col']] ?? $row[$info['id_col']] ?? 'N/A';
            $displayName = 'N/A';

            switch($module){
                case 'payroll':
                    $displayId = htmlspecialchars($row['employee_number'].' - '.$row['first_name'].' '.$row['last_name']);
                    $displayName = htmlspecialchars($row['first_name'].' '.$row['last_name']);
                    break;
                case 'statutory_remittances':
                    $displayId = htmlspecialchars($row['payroll_ref'] ?? ('#' . ($row['payroll_id'] ?? 'N/A')));
                    $displayName = htmlspecialchars(trim(($row['employee_number'] ?? '') . ' ' . ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
                    $displayName .= '<div style="font-size:11px;color:#555;">' . htmlspecialchars($row['code'] ?? '') . ' - ' . htmlspecialchars($row['description'] ?? '') . '</div>';
                    break;
                case 'invoice':
                    $displayId = $row['invoice_number'] ?? $row[$info['id_col']] ?? 'N/A';
                    $displayName = htmlspecialchars($row['customer_name'] ?? 'N/A');
                    break;
                case 'sales':
                    $displayId = $row['order_number'] ?? $row[$info['id_col']] ?? 'N/A';
                    $displayName = htmlspecialchars($row['customer_name'] ?? 'N/A');
                    break;
                case 'purchase_order':
                    $displayId = $row['pr_number'] ?? $row[$info['id_col']] ?? 'N/A';
                    $displayName = htmlspecialchars($row['supplier_name'] ?? 'N/A');
                    break;
                case 'batch_report':
                    $displayId = $row['batch_number'] ?? $row[$info['id_col']] ?? 'N/A';
                    $displayName = htmlspecialchars($row['product_name'] ?? 'N/A');
                    break;
                case 'production_summary':
                    $displayId = htmlspecialchars($row['product_name'] ?? 'N/A');
                    $displayName = 'Batches: ' . number_format($row['batch_count'] ?? 0) . ' | Qty: ' . number_format($row['total_quantity'] ?? 0, 2);
                    break;
                case 'rejected_batches':
                    $displayId = htmlspecialchars($row['qc_number'] ?? 'N/A');
                    $displayName = htmlspecialchars(($row['product_name'] ?? '') . ' / Batch: ' . ($row['production_batch_number'] ?? '')) .
                        '<div style="font-size:11px;color:#555;">Result: ' . htmlspecialchars($row['test_result'] ?? '') .
                        ' | Status: ' . htmlspecialchars($row['approval_status'] ?? '') . '</div>';
                    break;
                case 'stock_level':
                case 'low_stock':
                    $displayId = htmlspecialchars($row['material_name'] ?? 'N/A');
                    $displayName = htmlspecialchars($row['category'] ?? '') .
                        '<div style="font-size:11px;color:#555;">Qty: ' . number_format($row['quantity'] ?? 0, 2) . ' ' . htmlspecialchars($row['unit'] ?? '') .
                        ' | Min: ' . number_format($row['min_stock_level'] ?? 0, 2) .
                        ($row['expiry_date'] ? ' | Exp: ' . htmlspecialchars($row['expiry_date']) : '') . '</div>';
                    break;
                case 'inventory_movements':
                    $displayId = htmlspecialchars($row['transaction_id'] ?? 'N/A');
                    $itemName = $row['material_name'] ?? $row['product_name'] ?? '';
                    $displayName = htmlspecialchars($row['transaction_type'] ?? '') .
                        '<div style="font-size:11px;color:#555;">' . htmlspecialchars($itemName) .
                        ' | Qty: ' . number_format($row['quantity'] ?? 0, 2) . '</div>';
                    break;
                case 'returned_inventory':
                    $displayId = htmlspecialchars($row['assignment_number'] ?? $row['assignment_id'] ?? 'N/A');
                    $displayName = htmlspecialchars($row['order_number'] ?? '') .
                        '<div style="font-size:11px;color:#555;">Status: ' . htmlspecialchars($row['status'] ?? '') .
                        (isset($row['failure_reason']) && $row['failure_reason'] ? ' | Reason: ' . htmlspecialchars($row['failure_reason']) : '') .
                        (isset($row['returned_at']) && $row['returned_at'] ? ' | Returned: ' . htmlspecialchars($row['returned_at']) : '') .
                        '</div>';
                    break;
                case 'delivery_status':
                    $displayId = htmlspecialchars($row['assignment_number'] ?? $row['assignment_id'] ?? 'N/A');
                    $displayName = htmlspecialchars($row['order_number'] ?? '') .
                        '<div style="font-size:11px;color:#555;">Status: ' . htmlspecialchars($row['status'] ?? '') . '</div>';
                    break;
                case 'user_activity':
                    $displayId = htmlspecialchars($row['action'] ?? 'N/A');
                    $displayName = htmlspecialchars($row['username'] ?? '') .
                        '<div style="font-size:11px;color:#555;">' . htmlspecialchars($row['entity_type'] ?? '') .
                        ' #' . htmlspecialchars($row['entity_id'] ?? '') . '</div>';
                    break;
                case 'qc_report':
                    $displayId = $row['qc_number'] ?? $row['batch_number'] ?? $row[$info['id_col']] ?? 'N/A';
                    $displayName = htmlspecialchars($row['inspector_name'] ?? 'N/A');
                    break;
                default:
                    $displayId = htmlspecialchars($row[$info['display_id_col']] ?? $row[$info['id_col']] ?? 'N/A');
                    $displayName = htmlspecialchars($row[$info['name_col']] ?? 'N/A');
            }

            $date = $row[$info['date_col']] ?? '';
            $amount = $info['amount_col'] ? ($row[$info['amount_col']]??0) : null;

            // Determine actions (PDF export where available)
            $actionHtml = '<span style="color:#999;">—</span>';
            $pdfTypes = [
                'invoice' => 'invoice',
                'sales' => 'sales_receipt',
                'purchase_order' => 'purchase_order',
                'po' => 'po',
                'grn' => 'grn',
                'qc_report' => 'qc_report',
                'batch_report' => 'batch_report',
                'payroll' => 'payroll',
                'system_summary' => 'system_summary',
            ];
            if (isset($pdfTypes[$module])) {
                $recordId = $row[$info['id_col']] ?? ($row['id'] ?? '');
                $type = $pdfTypes[$module];
                $actionHtml = '<a href="api/generate_pdf.php?type='.urlencode($type).'&id='.urlencode($recordId).'" target="_blank" class="btn">📄 View PDF</a>';
            }

            echo "<tr>
                <td>$displayId</td>
                <td>$displayName</td>
                <td>".formatDate($date)."</td>";
            if($info['amount_col']) echo "<td>₱".number_format($amount,2)."</td>";
            echo "<td>$actionHtml</td>
            </tr>";
        }
        echo "</tbody>";
        echo "</table>";
    }
}

// Render tables
if ($moduleFilter === 'system_summary') {
    renderSystemSummary($conn, $startDate, $endDate, $groupBy);
} elseif($moduleFilter=='all'){
    foreach($modules as $mod => $info){
        renderModuleTable($conn,$mod,$info,$startDate,$endDate);
    }
}else{
    if(isset($modules[$moduleFilter])){
        renderModuleTable($conn,$moduleFilter,$modules[$moduleFilter],$startDate,$endDate);
    }
}

function renderSystemSummary($conn, $startDate, $endDate, $groupBy) {
    switch($groupBy){
        case 'week':
            $dateFmt = "YEARWEEK(%s,1)";
            break;

        case 'month':
            $dateFmt = "DATE_FORMAT(%s, '%Y-%m')";
            break;

        case 'quarter':
            $dateFmt = "CONCAT(YEAR(%s),'-Q',QUARTER(%s))";
            break;

        default:
            $dateFmt = "DATE(%s)";
    }
    if ($groupBy === 'quarter') {
        $invoiceGroup = sprintf($dateFmt, 'invoice_date', 'invoice_date');
        $expenseGroup = sprintf($dateFmt, 'expense_date', 'expense_date');
        $deliveryGroup = sprintf($dateFmt, 'created_at', 'created_at');
    } else {
        $invoiceGroup = sprintf($dateFmt, 'invoice_date');
        $expenseGroup = sprintf($dateFmt, 'expense_date');
        $deliveryGroup = sprintf($dateFmt, 'created_at');
    }

    $dateCondInvoices = dateCondition('invoice_date', $startDate, $endDate);
    $dateCondExpenses = dateCondition('expense_date', $startDate, $endDate);
    $dateCondDeliveries = dateCondition('created_at', $startDate, $endDate);

    // Summary per period
    $invoiceSql = "SELECT $invoiceGroup AS period, COUNT(*) AS invoice_count, COALESCE(SUM(amount),0) AS invoice_total " .
                  "FROM invoices WHERE status = 'Paid' $dateCondInvoices GROUP BY period ORDER BY period DESC";
    $expenseSql = "SELECT $expenseGroup AS period, COUNT(*) AS expense_count, COALESCE(SUM(amount),0) AS expense_total " .
                  "FROM expenses WHERE 1=1 $dateCondExpenses GROUP BY period ORDER BY period DESC";
    $deliverySql = "SELECT $deliveryGroup AS period, COUNT(*) AS deliveries_completed " .
                   "FROM delivery_assignments WHERE status = 'Delivered' $dateCondDeliveries GROUP BY period ORDER BY period DESC";

    $invoicesRes = $conn->query($invoiceSql);
    $expensesRes = $conn->query($expenseSql);
    $deliveriesRes = $conn->query($deliverySql);

    $summary = [];

    while($row = $invoicesRes ? $invoicesRes->fetch_assoc() : null) {
        $period = $row['period'] ?? '';
        if (!$period) continue;
        $summary[$period]['invoice_count'] = (int)$row['invoice_count'];
        $summary[$period]['invoice_total'] = (float)$row['invoice_total'];
    }
    while($row = $expensesRes ? $expensesRes->fetch_assoc() : null) {
        $period = $row['period'] ?? '';
        if (!$period) continue;
        $summary[$period]['expense_count'] = (int)$row['expense_count'];
        $summary[$period]['expense_total'] = (float)$row['expense_total'];
    }
    while($row = $deliveriesRes ? $deliveriesRes->fetch_assoc() : null) {
        $period = $row['period'] ?? '';
        if (!$period) continue;
        $summary[$period]['deliveries_completed'] = (int)$row['deliveries_completed'];
    }

    // Ensure periods are sorted descending
    krsort($summary);

    echo "<div class=\"analytics\"><h3>📑 System Summary (" . ucfirst($groupBy) . ")</h3>";
    echo "<div style='margin-bottom: 12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;'>";
    echo "<a href='api/generate_pdf.php?type=system_summary&start_date=" . urlencode($startDate) . "&end_date=" . urlencode($endDate) . "&group_by=" . urlencode($groupBy) . "' target='_blank' class='btn export-btn'>📄 Export PDF</a>";
    echo "</div>";

    echo "<table><tr><th>" . ($groupBy === 'month' ? 'Month' : 'Date') . "</th><th>Invoices (Paid)</th><th>Income</th><th>Expenses</th><th>Deliveries Completed</th></tr>";
    if (empty($summary)) {
        echo "<tr><td colspan=5 style='text-align:center; padding: 16px; color: var(--text-muted);'>No data found for the selected range.</td></tr>";
    } else {
        foreach ($summary as $period => $values) {
            $invoiceCount = $values['invoice_count'] ?? 0;
            $invoiceTotal = $values['invoice_total'] ?? 0;
            $expenseTotal = $values['expense_total'] ?? 0;
            $expenseCount = $values['expense_count'] ?? 0;
            $deliveries = $values['deliveries_completed'] ?? 0;
            echo "<tr>";
            echo "<td>" . htmlspecialchars($period) . "</td>";
            echo "<td>" . number_format($invoiceCount) . "</td>";
            echo "<td>₱" . number_format($invoiceTotal,2) . "</td>";
            echo "<td>₱" . number_format($expenseTotal,2) . "</td>";
            echo "<td>" . number_format($deliveries) . "</td>";
            echo "</tr>";
        }
    }
    echo "</table></div>";

    // Detailed lists
    echo "<div class='analytics'><h3>Detailed Transactions</h3>";

    // Paid invoices list
    $invoiceListSql = "SELECT i.*, c.customer_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.customer_id WHERE i.status = 'Paid' $dateCondInvoices ORDER BY i.invoice_date DESC";
    $invoiceListRes = $conn->query($invoiceListSql);
    echo "<h4>Paid Invoices</h4>";
    echo "<table><tr><th>Invoice #</th><th>Date</th><th>Customer</th><th>Amount</th><th>Status</th></tr>";
    if ($invoiceListRes && $invoiceListRes->num_rows > 0) {
        while ($row = $invoiceListRes->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['invoice_number'] ?? $row['invoice_id']) . "</td>";
            echo "<td>" . formatDate($row['invoice_date']) . "</td>";
            echo "<td>" . htmlspecialchars($row['customer_name'] ?? '') . "</td>";
            echo "<td>₱" . number_format($row['amount'] ?? 0, 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['status'] ?? '') . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan=5 style='text-align:center; padding: 16px; color: var(--text-muted);'>No paid invoices found.</td></tr>";
    }
    echo "</table>";

    // Expenses list
    $expenseListSql = "SELECT * FROM expenses WHERE 1=1 $dateCondExpenses ORDER BY expense_date DESC";
    $expenseListRes = $conn->query($expenseListSql);
    echo "<h4>Expenses</h4>";
    echo "<table><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th></tr>";
    if ($expenseListRes && $expenseListRes->num_rows > 0) {
        while ($row = $expenseListRes->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . formatDate($row['expense_date']) . "</td>";
            echo "<td>" . htmlspecialchars($row['category'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['description'] ?? '') . "</td>";
            echo "<td>₱" . number_format($row['amount'] ?? 0, 2) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan=4 style='text-align:center; padding: 16px; color: var(--text-muted);'>No expenses found.</td></tr>";
    }
    echo "</table>";

    // Deliveries list
    $deliveryListSql = "SELECT da.*, so.order_number FROM delivery_assignments da LEFT JOIN sales_orders so ON da.order_id = so.order_id WHERE da.status = 'Delivered' $dateCondDeliveries ORDER BY da.created_at DESC";
    $deliveryListRes = $conn->query($deliveryListSql);
    echo "<h4>Completed Deliveries</h4>";
    echo "<table><tr><th>Date</th><th>Delivery #</th><th>Order #</th><th>Status</th></tr>";
    if ($deliveryListRes && $deliveryListRes->num_rows > 0) {
        while ($row = $deliveryListRes->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . formatDate($row['created_at'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['assignment_number'] ?? $row['assignment_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['order_number'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['status'] ?? '') . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan=4 style='text-align:center; padding: 16px; color: var(--text-muted);'>No completed deliveries found.</td></tr>";
    }
    echo "</table></div>";
}
?>

</div>

</div>
<?php include "layouts/footer.php"; ?>
</div>
<script src="assets/js/sidebar.js"></script>
<script>
function printTable(title, tableEl) {
    var html = '<html><head><title>' + title + '</title>';
    html += '<style>body{font-family:system-ui, sans-serif; margin:20px;} table{width:100%; border-collapse:collapse; margin-top:10px;} th,td{border:1px solid #ccc; padding:8px; text-align:left;} th{background:#f3f4f6;}</style>';
    html += '</head><body><h1>' + title + '</h1>';
    html += tableEl.outerHTML;
    html += '</body></html>';

    var w = window.open('', '_blank');
    if (!w) return;

    w.document.write(html);
    w.document.close();

    w.onload = function(){
        w.print();
        setTimeout(function(){
            w.close();
        },100);
    };
}

function renderChartOrPlaceholder(ctx, config, emptyMessage) {
    if (!ctx) return;
    var labels = (config && config.data && config.data.labels) || [];
    if (!labels.length) {
        var card = ctx.closest('.chart-card');
        if (card) {
            card.innerHTML = '<div style="padding:40px;text-align:center;color:#64748b;">' + emptyMessage + '</div>';
        }
        return;
    }

    new Chart(ctx.getContext('2d'), config);
}

// Attach export, print, and search controls to module tables after DOM ready
document.addEventListener('DOMContentLoaded', function(){
    var moduleSelect = document.getElementById('module-select');
    var remittanceFilterLabel = document.getElementById('remittance-filter');

    function toggleRemittanceFilter() {
        if (moduleSelect && remittanceFilterLabel) {
            remittanceFilterLabel.style.display = moduleSelect.value === 'statutory_remittances' ? 'block' : 'none';
        }
    }

    if (moduleSelect) {
        moduleSelect.addEventListener('change', toggleRemittanceFilter);
        toggleRemittanceFilter();
    }

    document.querySelectorAll('.content > h3').forEach(function(h3){
        var table = h3.nextElementSibling;
        if (!table || table.tagName.toLowerCase() !== 'table') return;
        var wrapper = document.createElement('div');
        wrapper.className = 'table-actions';
        var search = document.createElement('input');
        search.placeholder = 'Search...';
        search.className = 'table-search';
        search.oninput = function(){
            var q = this.value.toLowerCase();
            var rows = table.querySelectorAll('tbody tr');
            if (!rows.length) {
                rows = table.querySelectorAll('tr');
            }
            rows.forEach(function(tr){
                tr.style.display = tr.innerText.toLowerCase().indexOf(q) === -1 ? 'none' : '';
            });
        };
        var printBtn = document.createElement('button');
        printBtn.type = 'button';
        printBtn.className = 'btn export-btn';
        printBtn.textContent = 'Print / PDF';
        printBtn.onclick = function(){ printTable((h3.textContent||'report').trim(), table); };
        wrapper.appendChild(search);
        wrapper.appendChild(printBtn);
        h3.parentNode.insertBefore(wrapper, table);
    });

    // Initialize charts after tables are built
    initReportsCharts();

});


function initReportsCharts() {
    // Revenue vs Expenses
    try {
        var ctx = document.getElementById('revenueExpensesChart');
        renderChartOrPlaceholder(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartMonths); ?>,
                datasets: [
                    {
                        label: 'Revenue',
                        data: <?php echo json_encode($chartRevenue); ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.15)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Expenses',
                        data: <?php echo json_encode($chartExpenses); ?>,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245,158,11,0.15)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { callback: function(v){ return '₱' + v; } } } }
            }
        }, 'No revenue/expense data found for the selected period.');
    } catch (e) { console.warn(e); }

    // Production per product
    try {
        var ctx2 = document.getElementById('productionChart');
        renderChartOrPlaceholder(ctx2, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($prodLabels); ?>,
                datasets: [{
                    label: 'Production Quantity',
                    data: <?php echo json_encode($prodData); ?>,
                    backgroundColor: 'rgba(59,130,246,0.7)',
                    borderColor: 'rgba(37,99,235,0.9)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        }, 'No production data available.');
    } catch (e) { console.warn(e); }

    // Delivery status pie
    try {
        var ctx3 = document.getElementById('deliveryChart');
        renderChartOrPlaceholder(ctx3, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($deliveryLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($deliveryData); ?>,
                    backgroundColor: ['#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6'],
                    borderWidth: 1
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        }, 'No delivery status data available.');
    } catch (e) { console.warn(e); }

    // Sales trend
    try {
        var ctx4 = document.getElementById('salesTrendChart');
        renderChartOrPlaceholder(ctx4, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($salesLabels ?? []); ?>,
                datasets: [{
                    label: 'Total Sales',
                    data: <?php echo json_encode($salesTotals ?? []); ?>,
                    backgroundColor: 'rgba(16,185,129,0.7)',
                    borderColor: 'rgba(5,150,105,0.9)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: function(v){ return '₱' + v; } } } }
            }
        }, 'No sales data available for the selected period.');
    } catch (e) { console.warn(e); }
}
</script>
</body>
</html>