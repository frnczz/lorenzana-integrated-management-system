<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$show_form = isset($_GET['new']) || isset($_GET['id']) || isset($_GET['create_from_pr']);
$po_id = intval($_GET['id'] ?? 0);
$create_from_pr = intval($_GET['create_from_pr'] ?? 0);

// Pagination and sorting setup
$pagination = function_exists('getPagination') ? getPagination($conn, "SELECT COUNT(*) as c FROM purchase_orders", null, 'po_page', 'po_per_page') : ['offset' => 0, 'per_page' => 25, 'total' => 0];
$sort = getSortParams('created_at', ['po_number', 'pr_number', 'supplier_name', 'order_date', 'expected_delivery_date', 'item_count', 'total_qty_ordered', 'total_qty_received', 'total_amount', 'status']);

// Load data for form if needed
$po = null;
$po_items = [];
$pr = null;
$pr_items = [];
$suppliers = [];
$raw_materials = [];
$products = [];

if ($show_form) {
    // Load PR data if creating from PR
    if ($create_from_pr > 0) {
        $pr_query = $conn->prepare("SELECT * FROM purchase_requisitions WHERE pr_id = ? AND status = 'Approved'");
        $pr_query->bind_param("i", $create_from_pr);
        $pr_query->execute();
        $pr = $pr_query->get_result()->fetch_assoc();
        $pr_query->close();
        
        if ($pr) {
            $pr_items_query = $conn->prepare("SELECT * FROM pr_items WHERE pr_id = ?");
            $pr_items_query->bind_param("i", $create_from_pr);
            $pr_items_query->execute();
            $pr_items_result = $pr_items_query->get_result();
            while ($row = $pr_items_result->fetch_assoc()) {
                $pr_items[] = $row;
            }
            $pr_items_query->close();
        }
    }
    
    // Load PO data if editing
    if ($po_id > 0) {
        $po_query = $conn->prepare("SELECT * FROM purchase_orders WHERE po_id = ?");
        $po_query->bind_param("i", $po_id);
        $po_query->execute();
        $po = $po_query->get_result()->fetch_assoc();
        $po_query->close();
        
        if ($po) {
            $items_query = $conn->prepare("SELECT * FROM po_items WHERE po_id = ?");
            $items_query->bind_param("i", $po_id);
            $items_query->execute();
            $items_result = $items_query->get_result();
            while ($row = $items_result->fetch_assoc()) {
                $po_items[] = $row;
            }
            $items_query->close();
        }
    }
    
    // Load dropdowns
    $suppliers_query = $conn->query("SELECT supplier_id, supplier_name, contact_number, payment_terms FROM suppliers WHERE status = 'Active' ORDER BY supplier_name");
    if ($suppliers_query) {
        while ($row = $suppliers_query->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
    
    $materials_query = $conn->query("SELECT material_id, material_name, category, unit FROM raw_materials ORDER BY material_name");
    if ($materials_query) {
        while ($row = $materials_query->fetch_assoc()) {
            $raw_materials[] = $row;
        }
    }
    
    $products_query = $conn->query("SELECT product_id, product_name, unit FROM products ORDER BY product_name");
    if ($products_query) {
        while ($row = $products_query->fetch_assoc()) {
            $products[] = $row;
        }
    }
}

// Fetch purchase orders
$orders = [];
$status_filter = $_GET['status'] ?? '';
$allowed_statuses = ['Open', 'Partially Received', 'Received', 'Closed'];

$where_clause = '';
if (in_array($status_filter, $allowed_statuses)) {
    $stmt = $conn->prepare("
        SELECT po.*, s.supplier_name, pr.pr_number,
               COUNT(poi.po_item_id) as item_count,
               SUM(poi.quantity_ordered) as total_qty_ordered,
               SUM(poi.quantity_received) as total_qty_received
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
        LEFT JOIN purchase_requisitions pr ON po.pr_id = pr.pr_id
        LEFT JOIN po_items poi ON po.po_id = poi.po_id
        WHERE po.status = ?
        GROUP BY po.po_id
        ORDER BY po.created_at DESC
        LIMIT 100
    ");
    $stmt->bind_param("s", $status_filter);
    $stmt->execute();
    $orders_query = $stmt->get_result();
} else {
    $orders_query = $conn->query("
        SELECT po.*, s.supplier_name, pr.pr_number,
               COUNT(poi.po_item_id) as item_count,
               SUM(poi.quantity_ordered) as total_qty_ordered,
               SUM(poi.quantity_received) as total_qty_received
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
        LEFT JOIN purchase_requisitions pr ON po.pr_id = pr.pr_id
        LEFT JOIN po_items poi ON po.po_id = poi.po_id
        GROUP BY po.po_id
        ORDER BY po.created_at DESC
        LIMIT 100
    ");
}

$orders_query = $conn->query("
    SELECT po.*, s.supplier_name, pr.pr_number,
           COUNT(poi.po_item_id) as item_count,
           SUM(poi.quantity_ordered) as total_qty_ordered,
           SUM(poi.quantity_received) as total_qty_received
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN purchase_requisitions pr ON po.pr_id = pr.pr_id
    LEFT JOIN po_items poi ON po.po_id = poi.po_id
    $where_clause
    GROUP BY po.po_id
    ORDER BY po.created_at DESC
    LIMIT 100
");
if ($orders_query) {
    while ($row = $orders_query->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-open { background: #dbeafe; color: #1e40af; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-received { background: #d1fae5; color: #065f46; }
        .status-closed { background: #f3f4f6; color: #6b7280; }
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Purchase Orders</h2>
            <p>Manage purchase orders and track deliveries.</p>
            <?php showMessage(); ?>
            
            <!-- Orders List -->
            <div id="ordersList" style="display:<?php echo $show_form ? 'none' : 'block'; ?>;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <div class="filter-bar">
                        <a href="procurement_orders.php" class="btn <?php echo !$status_filter ? 'btn-primary' : ''; ?>">All</a>
                        <a href="?status=Open" class="btn <?php echo $status_filter === 'Open' ? 'btn-primary' : ''; ?>">Open</a>
                        <a href="?status=Partially Received" class="btn <?php echo $status_filter === 'Partially Received' ? 'btn-primary' : ''; ?>">Partially Received</a>
                        <a href="?status=Received" class="btn <?php echo $status_filter === 'Received' ? 'btn-primary' : ''; ?>">Received</a>
                        <a href="?status=Closed" class="btn <?php echo $status_filter === 'Closed' ? 'btn-primary' : ''; ?>">Closed</a>
                    </div>
                    <button onclick="showForm()" class="btn">+ New Purchase Order</button>
                </div>
                
                <div class="card">
                <h3>Purchase Orders</h3>
                <table style="width:100%;">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                        <th>PR Number</th>
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th>Expected Delivery</th>
                        <th>Items</th>
                        <th>Qty Ordered</th>
                        <th>Qty Received</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($order['po_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['pr_number'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                                    <td><?php echo formatDate($order['order_date']); ?></td>
                                    <td><?php echo $order['expected_delivery_date'] ? formatDate($order['expected_delivery_date']) : '-'; ?></td>
                                    <td><?php echo $order['item_count']; ?></td>
                                    <td><?php echo number_format($order['total_qty_ordered'] ?? 0, 2); ?></td>
                                    <td><?php echo number_format($order['total_qty_received'] ?? 0, 2); ?></td>
                                    <td>₱<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                            <?php echo htmlspecialchars($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:5px;">
                                            <a href="procurement_order_view.php?id=<?php echo $order['po_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px;">View</a>
                                            <?php if ($order['status'] === 'Open' || $order['status'] === 'Partially Received'): ?>
                                                <a href="procurement_receiving.php?po_id=<?php echo $order['po_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px;">Receive</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" style="text-align:center;padding:30px;color:var(--text-muted);">No purchase orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div>
            
            <!-- PO Form -->
            <div id="poForm" style="display:<?php echo $show_form ? 'block' : 'none'; ?>;">
                <?php 
                // Load form data inline
                $po_id = intval($_GET['id'] ?? 0);
                $create_from_pr = intval($_GET['create_from_pr'] ?? 0);
                $po = null;
                $po_items = [];
                $pr = null;
                $pr_items = [];
                
                // If creating from PR, load PR data
                if ($create_from_pr > 0) {
                    $pr_query = $conn->prepare("SELECT * FROM purchase_requisitions WHERE pr_id = ? AND status = 'Approved'");
                    $pr_query->bind_param("i", $create_from_pr);
                    $pr_query->execute();
                    $pr = $pr_query->get_result()->fetch_assoc();
                    $pr_query->close();
                    
                    if ($pr) {
                        $pr_items_query = $conn->prepare("SELECT * FROM pr_items WHERE pr_id = ?");
                        $pr_items_query->bind_param("i", $create_from_pr);
                        $pr_items_query->execute();
                        $pr_items_result = $pr_items_query->get_result();
                        while ($row = $pr_items_result->fetch_assoc()) {
                            $pr_items[] = $row;
                        }
                        $pr_items_query->close();
                    }
                }
                
                // If editing existing PO
                if ($po_id > 0) {
                    $po_query = $conn->prepare("SELECT * FROM purchase_orders WHERE po_id = ?");
                    $po_query->bind_param("i", $po_id);
                    $po_query->execute();
                    $po = $po_query->get_result()->fetch_assoc();
                    $po_query->close();
                    
                    if ($po) {
                        $items_query = $conn->prepare("SELECT * FROM po_items WHERE po_id = ?");
                        $items_query->bind_param("i", $po_id);
                        $items_query->execute();
                        $items_result = $items_query->get_result();
                        while ($row = $items_result->fetch_assoc()) {
                            $po_items[] = $row;
                        }
                        $items_query->close();
                    }
                }
                
                // Fetch suppliers
                $suppliers = [];
                $suppliers_query = $conn->query("SELECT supplier_id, supplier_name, payment_terms FROM suppliers WHERE status = 'Active' ORDER BY supplier_name");
                if ($suppliers_query) {
                    while ($row = $suppliers_query->fetch_assoc()) {
                        $suppliers[] = $row;
                    }
                }
                
                // Fetch raw materials and products
                $raw_materials = [];
                $materials_query = $conn->query("SELECT material_id, material_name, category, unit FROM raw_materials ORDER BY material_name");
                if ($materials_query) {
                    while ($row = $materials_query->fetch_assoc()) {
                        $raw_materials[] = $row;
                    }
                }
                
                $products = [];
                $products_query = $conn->query("SELECT product_id, product_name, unit FROM products ORDER BY product_name");
                if ($products_query) {
                    while ($row = $products_query->fetch_assoc()) {
                        $products[] = $row;
                    }
                }
                ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3><?php echo $po ? 'Edit Purchase Order' : 'New Purchase Order'; ?></h3>
                    <button onclick="hideForm()" class="btn">Cancel</button>
                </div>
                <?php include "procurement_order_form.php"; ?>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
<script>
// Clear the PO creation flag from localStorage when coming back from creation
var urlParams = new URLSearchParams(window.location.search);
var prId = urlParams.get('create_from_pr');
if (prId) {
    var key = 'po_creating_' + prId;
    localStorage.removeItem(key);
}

function showForm() {
    document.getElementById('ordersList').style.display = 'none';
    document.getElementById('poForm').style.display = 'block';
    window.scrollTo(0, 0);
}

function hideForm() {
    document.getElementById('poForm').style.display = 'none';
    document.getElementById('ordersList').style.display = 'block';
    window.location.href = 'procurement_orders.php';
}
</script>
</body>
</html>
