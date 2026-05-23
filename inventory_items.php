<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Load sort parameters for Raw Materials table
$sort_raw = getSortParams('material_name', ['material_name', 'category', 'quantity', 'unit', 'expiry_date', 'warehouse_location']);

// Map columns for raw materials
$raw_column_map = [
    'material_name' => 'material_name',
    'category' => 'category',
    'quantity' => 'quantity',
    'unit' => 'unit',
    'expiry_date' => 'expiry_date',
    'warehouse_location' => 'warehouse_location'
];

$raw_order_by = isset($raw_column_map[$sort_raw['column']]) ? $raw_column_map[$sort_raw['column']] : 'material_name';

// Load sort parameters for Finished Goods table
$sort_fg = getSortParams('product_name', ['product_name', 'warehouse_location', 'total_quantity', 'total_stock', 'total_reserved']);

// Load sort parameters for Pending QC table
$sort_qc = getSortParams('batch_date', ['batch_number', 'product_name', 'quantity', 'batch_date', 'warehouse_location', 'status']);

$qc_column_map = [
    'batch_number' => 'pb.batch_number',
    'product_name' => 'p.product_name',
    'quantity' => 'pb.quantity',
    'batch_date' => 'pb.batch_date',
    'warehouse_location' => 'pb.warehouse_location',
    'status' => 'pb.status'
];

$qc_order_by = isset($qc_column_map[$sort_qc['column']]) ? $qc_column_map[$sort_qc['column']] : 'pb.batch_date';

// Load near-expiry settings
$near_expiry_days = intval(getSetting($conn, 'warehouse_settings', 'expiry_warning_days', 30, true));

// Load sort parameters for Near Expiry Finished Goods
$sort_near_exp = getSortParams('expiry_date', ['product_name', 'available_qty', 'expiry_date', 'warehouse_location', 'batch_number']);

// Pagination for tables on this page
$raw_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM raw_materials", null, 'raw_page', 'raw_per_page')
    : ['offset'=>0,'per_page'=>25,'total'=>0,'total_pages'=>1,'page'=>1,'prev_page'=>null,'next_page'=>null];

$finished_goods_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(DISTINCT fg.product_id) as c FROM finished_goods fg", null, 'fg_page', 'fg_per_page')
    : ['offset'=>0,'per_page'=>25,'total'=>0,'total_pages'=>1,'page'=>1,'prev_page'=>null,'next_page'=>null];

$near_expiry_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(DISTINCT fg.product_id) as c FROM finished_goods fg WHERE fg.expiry_date IS NOT NULL AND fg.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $near_expiry_days DAY)", null, 'near_exp_page', 'near_exp_per_page')
    : ['offset'=>0,'per_page'=>25,'total'=>0,'total_pages'=>1,'page'=>1,'prev_page'=>null,'next_page'=>null];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Items | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Enhanced table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #5568d3;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #374151;
        }
        table tbody tr {
            transition: all 0.2s ease;
        }
        table tbody tr:hover {
            background-color: #f8f9ff;
            box-shadow: inset 0 0 0 1px #e0e7ff;
        }
        table tbody tr:last-child td {
            border-bottom: none;
        }
        table a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        table a:hover {
            text-decoration: underline;
        }
        .card {
            margin-bottom: 25px;
            border-radius: 10px;
            overflow: hidden;
        }
        .card h3 {
            margin: 0 0 20px 0;
            padding: 15px 0;
            border-bottom: 2px solid #f0f0f0;
            font-size: 18px;
            color: #1f2937;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Inventory Items & Records</h2>
            <p>View raw materials inventory, finished goods, and pending QC batches.</p>
            <?php showMessage(); ?>

            <div class="card" style="background: linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%); border-left: 4px solid #f59e0b; margin-bottom: 20px;">
                <p style="margin: 0; color: #92400e;"><strong>📌 Note:</strong> To add or manage raw materials, go to <a href="inventory_raw_materials.php" style="color: #b45309; text-decoration: underline;">Raw Materials Management</a>.</p>
            </div>

            <!-- RAW MATERIALS INVENTORY -->
            <div class="card">
                <h3>Raw Materials Inventory</h3>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin-bottom:12px;">' . renderPerPageSelector($conn, $raw_pagination['per_page'], 'raw_per_page', 'raw_page') . '</div>'; ?>
                <?php
                $raw_materials_result = $conn->query("SELECT * FROM raw_materials ORDER BY " . $raw_order_by . " " . $sort_raw['order'] . " LIMIT " . $raw_pagination['offset'] . ", " . $raw_pagination['per_page']);
                ?>
                <table>
                    <tr>
                        <th><?php echo sortHeader('material_name', 'Item Name', $sort_raw); ?></th>
                        <th><?php echo sortHeader('category', 'Category', $sort_raw); ?></th>
                        <th><?php echo sortHeader('quantity', 'Quantity', $sort_raw); ?></th>
                        <th><?php echo sortHeader('unit', 'Unit', $sort_raw); ?></th>
                        <th><?php echo sortHeader('expiry_date', 'Expiry Date', $sort_raw); ?></th>
                        <th><?php echo sortHeader('warehouse_location', 'Location', $sort_raw); ?></th>
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
                                <td><?php echo ($item['quantity'] <= $item['min_stock_level']) ? '<span style="color:#dc2626;font-weight:bold;">Low Stock</span>' : '<span style="color:#10b981;">Available</span>'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted);">No raw materials found.</td></tr>
                    <?php endif; ?>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($raw_pagination, 'raw_page'); ?>
            </div>

            <!-- FINISHED GOODS INVENTORY (ONLY QC-APPROVED) -->
            <div class="card">
                <h3>Finished Goods Inventory</h3>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin-bottom:12px;">' . renderPerPageSelector($conn, $finished_goods_pagination['per_page'], 'fg_per_page', 'fg_page') . '</div>'; ?>
                <?php
                // Fetch finished goods with sorting (matches Inventory Summary totals)
                // total_quantity = available (quantity minus reserved), total_stock = physical stock (quantity)
                $finished_goods_result = $conn->query("
                    SELECT 
                        COALESCE(p.product_name, 'Unknown Product') AS product_name,
                        GROUP_CONCAT(DISTINCT COALESCE(fg.warehouse_location, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas') SEPARATOR ', ') AS warehouse_location,
                        SUM(GREATEST(fg.quantity - COALESCE(fg.reserved_quantity, 0), 0)) AS total_quantity,
                        SUM(fg.quantity) AS total_stock,
                        SUM(COALESCE(fg.reserved_quantity, 0)) AS total_reserved
                    FROM finished_goods fg
                    LEFT JOIN products p ON fg.product_id = p.product_id
                    GROUP BY fg.product_id, p.product_name
                    ORDER BY COALESCE(p.product_name, 'Unknown Product') " . $sort_fg['order'] . "
                    LIMIT " . $finished_goods_pagination['offset'] . ", " . $finished_goods_pagination['per_page']
                );
                if (!$finished_goods_result) {
                    echo "<p style='color:red;'>SQL Error: " . $conn->error . "</p>";
                }
                ?>
                <table>
                    <tr>
                        <th><?php echo sortHeader('product_name', 'Product Name', $sort_fg); ?></th>
                        <th><?php echo sortHeader('total_quantity', 'Available', $sort_fg); ?></th>
                        <th><?php echo sortHeader('total_reserved', 'Reserved', $sort_fg); ?></th>
                        <th><?php echo sortHeader('total_stock', 'Total Stock', $sort_fg); ?></th>
                        <th><?php echo sortHeader('warehouse_location', 'Location', $sort_fg); ?></th>
                        <th>Status</th>
                    </tr>
                    <?php if ($finished_goods_result && $finished_goods_result->num_rows > 0): ?>
                        <?php while ($item = $finished_goods_result->fetch_assoc()): ?>
                            <?php
                            $available_qty = (float)$item['total_quantity'];
                            $total_stock = (float)$item['total_stock'];
                            $reserved = (float)$item['total_reserved'];
                            
                            if ($available_qty <= 0) {
                                $status_text = 'Out of Stock';
                                $status_color = '#dc2626';
                            } elseif ($available_qty < 5) { // low stock threshold
                                $status_text = 'Low Stock';
                                $status_color = '#f6c23e';
                            } else {
                                $status_text = 'In Stock';
                                $status_color = '#10b981';
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name'] ?? 'Unknown Product'); ?></td>
                                <td><strong><?php echo number_format($available_qty, 2); ?></strong></td>
                                <td><strong><?php echo number_format($reserved, 2); ?></strong></td>
                                <td><?php echo number_format($total_stock, 2); ?></td>
                                <td><?php echo htmlspecialchars(formatLocation($item['warehouse_location'] ?? null)); ?></td>
                                <td>
                                    <span style="color: <?php echo $status_color; ?>; font-weight:bold;">
                                        <?php echo $status_text; ?>
                                    </span>
                                    <?php if ($available_qty <= 0 && $reserved > 0): ?>
                                        <br><small style="color: #6b7280;">(All stock reserved)</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted);">No QC-approved finished goods found. Products will appear here after QC approval.</td>
                        </tr>
                    <?php endif; ?>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($finished_goods_pagination, 'fg_page'); ?>
            </div>

            <!-- PENDING QC BATCHES -->
            <div class="card">
                <h3>Pending QC / Production Batches</h3>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">
                    These batches are waiting for QC inspection. Once approved, they will be added to Finished Goods Inventory and become available in product dropdowns.
                </p>
                <?php
                $pending_qc_batches_result = $conn->query("
                    SELECT pb.batch_id, pb.batch_number, p.product_name, pb.quantity, pb.batch_date, pb.warehouse_location, pb.status,
                        COALESCE(qc.approval_status, 'Pending') AS qc_status,
                        qc.qc_number,
                        CASE 
                            WHEN qc.approval_status = 'Rejected' THEN 'Rejected'
                            WHEN qc.approval_status = 'For Re-inspection' THEN 'For Re-inspection'
                            WHEN qc.approval_status = 'Approved' THEN 'Approved (Processing)'
                            ELSE 'Pending QC'
                        END AS display_status
                    FROM production_batches pb
                    LEFT JOIN products p ON pb.product_id = p.product_id
                    LEFT JOIN qc_records qc ON pb.batch_number = qc.batch_number
                    -- Show any batches that are not yet completed (including those rejected by QC)
                    WHERE pb.status != 'Completed'
                    ORDER BY " . $qc_order_by . " " . $sort_qc['order']
                );
                ?>
                <table>
                    <tr>
                        <th><?php echo sortHeader('batch_number', 'Batch No', $sort_qc); ?></th>
                        <th><?php echo sortHeader('product_name', 'Product Name', $sort_qc); ?></th>
                        <th><?php echo sortHeader('quantity', 'Quantity', $sort_qc); ?></th>
                        <th><?php echo sortHeader('batch_date', 'Date', $sort_qc); ?></th>
                        <th><?php echo sortHeader('warehouse_location', 'Location', $sort_qc); ?></th>
                        <th><?php echo sortHeader('status', 'Batch Status', $sort_qc); ?></th>
                        <th>QC Status</th>
                        <th>QC Number</th>
                    </tr>
                    <?php if($pending_qc_batches_result && $pending_qc_batches_result->num_rows > 0): ?>
                        <?php while($b = $pending_qc_batches_result->fetch_assoc()): ?>
                            <?php
                            $qc_status = $b['qc_status'];
                            $status_color = '#6b7280'; // default gray
                            if ($qc_status === 'Approved') {
                                $status_color = '#10b981'; // green
                            } elseif ($qc_status === 'Rejected') {
                                $status_color = '#dc2626'; // red
                            } elseif ($qc_status === 'For Re-inspection') {
                                $status_color = '#f59e0b'; // orange
                            }
                            ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['batch_number']); ?></td>
                            <td><?php echo htmlspecialchars($b['product_name']); ?></td>
                            <td><?php echo number_format($b['quantity'],2); ?></td>
                            <td><?php echo formatDate($b['batch_date']); ?></td>
                            <td><?php echo htmlspecialchars(formatLocation($b['warehouse_location'] ?? null)); ?></td>
                            <td><?php echo htmlspecialchars($b['status']); ?></td>
                            <td><span style="color: <?php echo $status_color; ?>; font-weight:bold;"><?php echo htmlspecialchars($b['display_status']); ?></span></td>
                            <td><?php echo htmlspecialchars($b['qc_number'] ?? '-'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center;padding:20px;color:var(--text-muted);">No batches pending QC. All batches have been processed.</td></tr>
                    <?php endif; ?>
                </table>
            </div>

        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>

<script src="assets/js/sidebar.js"></script>
</body>
</html>
