<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Check if order_items table exists
$has_order_items = false;
$check = @$conn->query("SHOW TABLES LIKE 'order_items'");
if ($check && $check->num_rows > 0) $has_order_items = true;

// Fetch products with available stock
$products_with_stock = [];
$pq = $conn->query("SELECT p.product_id, p.product_name, p.unit, p.image_path, p.unit_price,
    (SELECT (fg.quantity - COALESCE(fg.reserved_quantity, 0)) FROM finished_goods fg WHERE fg.product_id = p.product_id LIMIT 1) AS available
    FROM products p ORDER BY p.product_name");
if ($pq) {
    while ($r = $pq->fetch_assoc()) {
        $r['available'] = $r['available'] !== null ? (float)$r['available'] : 0;
        $r['unit_price'] = $r['unit_price'] !== null ? (float)$r['unit_price'] : 0;
        $products_with_stock[] = $r;
    }
}

// Sort params for various tables
$sort_orders = getSortParams('order_date', ['order_number', 'customer_name', 'order_date', 'total_amount', 'status']);
$sort_customers = getSortParams('customer_name', ['customer_id', 'customer_name', 'contact_number', 'address']);
$column_map_orders = ['order_number' => 'so.order_number', 'customer_name' => 'c.customer_name', 'order_date' => 'so.order_date', 'total_amount' => 'so.total_amount', 'status' => 'so.status'];
$column_map_customers = ['customer_id' => 'customer_id', 'customer_name' => 'customer_name', 'contact_number' => 'contact_number', 'address' => 'address'];

$order_by_pickup = isset($column_map_orders[$sort_orders['column']]) ? $column_map_orders[$sort_orders['column']] : 'so.order_date';
$pickup_orders = [];
$pickup_query = $conn->query("
    SELECT so.*, c.customer_name
    FROM sales_orders so
    LEFT JOIN customers c ON so.customer_id = c.customer_id
    WHERE so.order_number LIKE 'PUP-%' OR so.fulfillment_type IN ('Pickup', 'Customer Pickup')
    ORDER BY " . $order_by_pickup . " " . $sort_orders['order']
);

if ($pickup_query) {
    while ($row = $pickup_query->fetch_assoc()) {
        $pickup_orders[] = $row;
    }
}

// Fetch customers (with sorting)
$order_by_cust = isset($column_map_customers[$sort_customers['column']]) ? $column_map_customers[$sort_customers['column']] : 'customer_name';
$customers = [];
$cq = $conn->query("SELECT customer_id, customer_name, contact_person, contact_number, email, address FROM customers ORDER BY " . $order_by_cust . " " . $sort_customers['order']);
if ($cq) {
    while ($row = $cq->fetch_assoc()) {
        $customers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales & Distribution | LORINIMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container .select2-selection--single { height: 38px !important; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px !important; }
.select2-results__option img {
    margin-right: 8px;
    width: 40px;
    height: 40px;
    object-fit: contain;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.product-hover-preview img { max-width:120px; max-height:100px; display:none; border:1px solid #ccc; border-radius:4px; margin-top:5px; }
/* New Customer Modal */
#newCustomerModal {
    display:none;
    position:fixed;
    top:0; left:0; width:100%; height:100%;
    background:rgba(0,0,0,0.5);
    z-index:9999;
    display:flex;
    align-items:center;
    justify-content:center;
}
#newCustomerModal .modal-content {
    background:#fff;
    padding:20px;
    border-radius:8px;
    max-width:400px;
    width:90%;
}
#newCustomerModal input, #newCustomerModal textarea {
    width:100%; padding:6px; margin-top:4px; margin-bottom:10px;
}
</style>
</head>
<body>
<div class="wrapper">
<?php include "layouts/sidebar.php"; ?>
<div class="main">
<?php include "layouts/header.php"; ?>
<div class="content">
<h2>Customer Orders</h2>
<p>Create and manage customer orders. Add multiple products per order.</p>
<?php showMessage(); ?>

<div class="card" style="margin-bottom:20px; border-left:4px solid var(--primary);">
    <h3 style="margin-top:0;">View orders &amp; delivery</h3>
    <p style="color: var(--text-muted); margin-bottom:14px; font-size:14px;">Open line items, customer contact, and jump to delivery scheduling or the full delivery board.</p>
    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
        <a href="#sales-pending-deliveries" class="btn">Pending deliveries</a>
        <a href="#sales-pickup-orders" class="btn">Pickup orders</a>
        <a href="#sales-all-orders" class="btn">All orders list</a>
        <a href="sales_delivery.php" class="btn" style="background:linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color:#fff; border:none;">Delivery board</a>
    </div>
</div>

<div class="card">
    <h3>Create Customer Order</h3>
    <form method="POST" action="api/save_order.php" id="orderForm">
        <table>
            <tr>
                <td>Customer Name</td>
                <td>
                    <select id="customerSelect" name="customer_id" style="width:100%; padding:8px;" required>
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo (int)$c['customer_id']; ?>"
                                data-address="<?php echo htmlspecialchars($c['address'] ?? '', ENT_QUOTES); ?>"
                                data-contact="<?php echo htmlspecialchars($c['contact_number'] ?? '', ENT_QUOTES); ?>"
                                data-contact-person="<?php echo htmlspecialchars($c['contact_person'] ?? '', ENT_QUOTES); ?>"
                                data-email="<?php echo htmlspecialchars($c['email'] ?? '', ENT_QUOTES); ?>">
                                <?php echo htmlspecialchars($c['customer_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Hidden input to satisfy backend validation -->
                    <input type="hidden" name="customer_name" id="customer_name_hidden" value="">
                </td>
            </tr>
            <tr>
                <td>Order Date</td>
                <td><input type="date" name="order_date" style="width:100%; padding:8px;" value="<?php echo date('Y-m-d'); ?>" required></td>
            </tr>
        </table>

        <h4 style="margin:20px 0 10px 0; color: var(--text-primary);">Products (add multiple)</h4>
        <table class="order-lines-table" style="width:100%; margin-bottom:10px;">
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="width:120px;">Qty</th>
                    <th style="width:100px;">Price</th>
                    <th style="width:100px;">Stock</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody id="orderLinesBody">
                <tr class="order-line-row">
                    <td>
                        <select name="product_id[]" class="product-select" style="width:100%; padding:8px;" required>
                            <option value="">-- Select Product --</option>
                            <?php foreach ($products_with_stock as $p): ?>
                                <option value="<?php echo (int)$p['product_id']; ?>" 
                                    data-available="<?php echo (float)$p['available']; ?>"
                                    data-unit-price="<?php echo (float)$p['unit_price']; ?>"
                                    data-image="assets/images/products/<?php echo htmlspecialchars($p['image_path']); ?>">
                                    <?php 
                                    $priceDisplay = $p['unit_price'] > 0 ? '₱' . number_format($p['unit_price'], 2) : 'N/A';
                                    echo htmlspecialchars($p['product_name']); 
                                    ?> - <?php echo $priceDisplay; ?> (Stock: <?php echo number_format($p['available'], 0); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="product-hover-preview"><img src="" alt=""></div>
                    </td>
                    <td><input type="number" name="quantity[]" min="0.01" step="0.01" class="line-qty" style="width:100%; padding:8px;" required></td>
                    <td class="line-price" style="font-size: 12px; font-weight:600; color:#059669;">-</td>
                    <td class="line-stock" style="color: var(--text-muted); font-size: 12px;">-</td>
                    <td><button type="button" class="btn btn-remove-line" style="padding:6px 10px;">✕</button></td>
                </tr>
            </tbody>
        </table>
        <p><button type="button" id="addOrderLine" class="btn" style="margin-bottom:15px;">+ Add product line</button></p>

        <table>
            <tr>
                <td>Fulfillment Type</td>
                <td>
                    <select name="fulfillment_type" id="fulfillmentType" style="width:100%; padding:8px;" required>
                        <option value="Delivery" selected>Delivery</option>
                        <option value="Pickup">Customer Pickup (Warehouse)</option>
                    </select>
                </td>
            </tr>
            <tr>    
                <td>Order Status</td>
                <td>
                    <select name="status" id="orderStatus" style="width:100%; padding:8px;" required>
                        <option value="Pending">Pending</option>
                    </select>
                </td>
            </tr>
            <tr class="delivery-only">
                <td>Delivery Address</td>
                <td><textarea name="delivery_address" style="width:100%; padding:8px;" rows="3" required></textarea></td>
            </tr>
            <tr class="delivery-only">
                <td>Delivery Date</td>
                <td><input type="date" name="delivery_date" style="width:100%; padding:8px;"></td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:right;">
                    <button type="submit" class="btn">Save Order</button>
                </td>
            </tr>
        </table>

    </form>
</div>

<!-- Finished Customer Orders (from Request Production → QC approved) -->
<?php
$has_from_production = @$conn->query("SHOW COLUMNS FROM sales_orders LIKE 'from_production_request'")->num_rows > 0;
if ($has_from_production):
    $ob_finished = isset($column_map_orders[$sort_orders['column']]) ? $column_map_orders[$sort_orders['column']] : 'so.order_date';
    $finished_orders = $conn->query("
        SELECT so.order_id, so.order_number, so.order_date, so.total_amount, so.status, c.customer_name
        FROM sales_orders so
        LEFT JOIN customers c ON so.customer_id = c.customer_id
        WHERE so.from_production_request = 1
        ORDER BY " . $ob_finished . " " . $sort_orders['order'] . ", so.order_id DESC
        LIMIT 20
    ");
?>
<div class="card" style="margin-top:24px;">
    <h3>Finished Customer Orders (from Production Request)</h3>
    <p style="color: var(--text-muted); margin-bottom: 16px;">Orders fulfilled via Request Production and QC approval. These appear here after a production batch passes QC.</p>
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th><?php echo sortHeader('order_number', 'Order #', $sort_orders); ?></th>
                <th><?php echo sortHeader('customer_name', 'Customer', $sort_orders); ?></th>
                <th><?php echo sortHeader('order_date', 'Date', $sort_orders); ?></th>
                <th><?php echo sortHeader('total_amount', 'Total', $sort_orders); ?></th>
                <th><?php echo sortHeader('status', 'Status', $sort_orders); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($finished_orders && $finished_orders->num_rows > 0): ?>
                <?php while ($fo = $finished_orders->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid var(--border-color);">
                        <td style="padding:10px 12px;"><?php echo htmlspecialchars($fo['order_number']); ?></td>
                        <td style="padding:10px 12px;"><?php echo htmlspecialchars($fo['customer_name'] ?? '-'); ?></td>
                        <td style="padding:10px 12px;"><?php echo formatDate($fo['order_date']); ?></td>
                        <td style="padding:10px 12px;">₱<?php echo number_format($fo['total_amount'], 2); ?></td>
                        <td style="padding:10px 12px;"><span style="padding:4px 8px; border-radius:6px; font-size:12px; font-weight:600; background:#d1fae5; color:#065f46;"><?php echo htmlspecialchars($fo['status']); ?></span></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center; padding:24px; color:var(--text-muted);">No finished orders from production yet. Complete a production request and pass QC to see them here.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card" id="sales-pickup-orders">
    <h3>Customer Pickup Orders</h3>

    <table>
        <thead>
            <tr>
                <th><?php echo sortHeader('order_number', 'Order #', $sort_orders); ?></th>
                <th><?php echo sortHeader('customer_name', 'Customer', $sort_orders); ?></th>
                <th><?php echo sortHeader('order_date', 'Date', $sort_orders); ?></th>
                <th><?php echo sortHeader('total_amount', 'Total', $sort_orders); ?></th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>

        <?php if (count($pickup_orders) > 0): ?>
            <?php foreach ($pickup_orders as $order): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td>
                        <?php $pickupStatus = trim(strtolower($order['status'] ?? '')); ?>
                        <?php if ($pickupStatus === 'picked up'): ?>
                            <span style="font-size:10px; color:#059669;">Picked Up</span>
                        <?php else: ?>
                            <button type="button" class="btn btn-small mark-picked-up" data-id="<?php echo $order['order_id']; ?>" style="padding:10px 10px; font-size:10px;">
                                Mark Picked Up
                            </button>
                            <div style="margin-top:4px; font-size:10px; color:#6b7280;">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="pickup-order-actions" data-order-id="<?php echo $order['order_id']; ?>">
                        <button type="button" class="btn btn-small sales-order-detail-btn" data-order-id="<?php echo (int)$order['order_id']; ?>" style="padding:6px 10px; font-size:11px; margin-bottom:4px;">View order</button><br>
                        <?php $pickupActionsStatus = trim(strtolower($order['status'] ?? '')); ?>
                        <?php if ($pickupActionsStatus === 'picked up'): ?>
                            <!-- Print Receipt -->
                            <a href="api/generate_pdf.php?type=sales_receipt&id=<?php echo $order['order_id']; ?>" 
                               target="_blank" class="btn btn-small" style="padding:10px 10px; font-size:10px;">
                               🧾
                            </a>

                            <!-- Generate Invoice -->
                            <?php if (empty($order['invoice_generated']) || $order['invoice_generated'] == 0): ?>
                                <a href="accounting_invoices.php?auto_generate=<?php echo $order['order_id']; ?>" 
                                   class="btn btn-small" style="padding:10px 10px; font-size:10px;">
                                   📄
                                </a>
                            <?php else: ?>
                                <span style="font-size:10px; color:#6b7280;">invoice generated</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="font-size:10px; color:#6b7280;">Actions available after pickup</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center;">No pickup orders found.</td>
            </tr>
        <?php endif; ?>

        </tbody>
    </table>
</div>

<?php
// Pending Deliveries: orders that need scheduling or are pending dispatch
$pending_deliveries = [];
$order_by_pd = isset($column_map_orders[$sort_orders['column']]) ? $column_map_orders[$sort_orders['column']] : 'so.order_date';
$pd_q = $conn->query("
    SELECT 
        so.order_id, 
        so.order_number, 
        so.order_date, 
        so.total_amount, 
        so.status, 
        c.customer_name,
        CASE WHEN da.assignment_id IS NULL THEN 'Not Assigned' ELSE da.status END as assignment_status
    FROM sales_orders so
    LEFT JOIN customers c ON so.customer_id = c.customer_id
    LEFT JOIN delivery_assignments da ON so.order_id = da.order_id
    WHERE so.fulfillment_type = 'Delivery'
      AND so.status IN ('Pending','Confirmed','Dispatched')
      AND (da.assignment_id IS NULL OR da.status IN ('Pending','Dispatched'))
      AND (so.from_production_request IS NULL OR so.from_production_request = 0)
    ORDER BY " . $order_by_pd . " " . $sort_orders['order'] . "
    LIMIT 20
");
if ($pd_q) {
    while ($r = $pd_q->fetch_assoc()) {
        $pending_deliveries[] = $r;
    }
}
?>
<div class="card" style="margin-top:24px;" id="sales-pending-deliveries">
    <h3>Pending Deliveries</h3>
    <p style="color: var(--text-muted); margin-bottom: 12px;">Orders that require delivery scheduling or are awaiting dispatch.</p>
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th><?php echo sortHeader('order_number', 'Order #', $sort_orders); ?></th>
                <th><?php echo sortHeader('customer_name', 'Customer', $sort_orders); ?></th>
                <th><?php echo sortHeader('order_date', 'Date', $sort_orders); ?></th>
                <th><?php echo sortHeader('total_amount', 'Amount', $sort_orders); ?></th>
                <th>Assignment</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($pending_deliveries) > 0): ?>
                <?php foreach ($pending_deliveries as $pd): ?>
                    <tr>
                        <td style="padding:8px;"><?php echo htmlspecialchars($pd['order_number']); ?></td>
                        <td style="padding:8px;"><?php echo htmlspecialchars($pd['customer_name'] ?? '-'); ?></td>
                        <td style="padding:8px;"><?php echo date('M d, Y', strtotime($pd['order_date'])); ?></td>
                        <td style="padding:8px;">₱<?php echo number_format($pd['total_amount'], 2); ?></td>
                        <td style="padding:8px;"><?php echo htmlspecialchars($pd['assignment_status']); ?></td>
                        <td style="padding:8px;">
                            <button type="button" class="btn btn-small sales-order-detail-btn" data-order-id="<?php echo (int)$pd['order_id']; ?>" style="padding:6px 10px; font-size:11px; margin-bottom:6px; display:block;">View lines</button>
                            <?php if ($pd['assignment_status'] === 'Not Assigned'): ?>
                                <a href="sales_delivery.php?order_id=<?php echo $pd['order_id']; ?>" 
                                   class="btn btn-small open-delivery-modal" 
                                   style="padding:6px 10px; font-size:12px;">
                                    Schedule delivery
                                </a>
                            <?php else: ?>
                                <a href="sales_delivery.php?order_id=<?php echo $pd['order_id']; ?>" 
                                   class="btn btn-small" 
                                   style="padding:6px 10px; font-size:12px; background:#e5e7eb; color:#374151;">
                                    Open delivery
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding:16px; color:var(--text-muted);">No pending deliveries.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- All Orders List -->
<div class="card" style="margin-top:30px;" id="sales-all-orders">
    <h3>All Sales Orders (<?php echo $conn->query("SELECT COUNT(*) as count FROM sales_orders")->fetch_assoc()['count'] ?? 0; ?> total)</h3>
    <p style="color: var(--text-muted); margin-bottom: 16px;">Complete list of all sales orders in the system.</p>
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th><?php echo sortHeader('order_number', 'Order #', $sort_orders); ?></th>
                <th><?php echo sortHeader('customer_name', 'Customer', $sort_orders); ?></th>
                <th><?php echo sortHeader('order_date', 'Date', $sort_orders); ?></th>
                <th><?php echo sortHeader('total_amount', 'Total', $sort_orders); ?></th>
                <th><?php echo sortHeader('status', 'Status', $sort_orders); ?></th>

                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $all_orders_pagination = function_exists('getPagination')
                ? getPagination($conn, "SELECT COUNT(*) as c FROM sales_orders", null, 'all_orders_page', 'all_orders_per_page')
                : ['offset' => 0, 'per_page' => 25];

            $all_orders_query = $conn->query("
                SELECT so.*, c.customer_name
                FROM sales_orders so
                LEFT JOIN customers c ON so.customer_id = c.customer_id
                ORDER BY " . $order_by_pickup . " " . $sort_orders['order'] . ", so.order_id DESC
                LIMIT " . $all_orders_pagination['offset'] . ", " . $all_orders_pagination['per_page'] . "
            ");

            if ($all_orders_query && $all_orders_query->num_rows > 0):
                while ($order = $all_orders_query->fetch_assoc()):
            ?>
                <tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding:10px 12px;"><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                    <td style="padding:10px 12px;"><?php echo htmlspecialchars($order['customer_name'] ?? '-'); ?></td>
                    <td style="padding:10px 12px;"><?php echo formatDate($order['order_date']); ?></td>
                    <td style="padding:10px 12px;">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td style="padding:10px 12px;">
                        <span style="padding:4px 8px; border-radius:6px; font-size:12px; font-weight:600;
                            <?php
                            $status_colors = [
                                'Pending' => 'background:#fef3c7; color:#92400e;',
                                'Confirmed' => 'background:#dbeafe; color:#1e40af;',
                                'Dispatched' => 'background:#fef3c7; color:#92400e;',
                                'Delivered' => 'background:#d1fae5; color:#065f46;',
                                'Picked Up' => 'background:#d1fae5; color:#065f46;',
                                'Cancelled' => 'background:#fee2e2; color:#991b1b;'
                            ];
                            echo $status_colors[$order['status']] ?? 'background:#f3f4f6; color:#374151;';
                            ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </td>
                    <td style="padding:10px 12px;">
                        <button type="button" class="btn btn-small sales-order-detail-btn" data-order-id="<?php echo (int)$order['order_id']; ?>" style="margin-right:4px;">Lines</button>
                        <a href="sales_delivery.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-small">Delivery</a>
                        <?php if (empty($order['invoice_generated']) || $order['invoice_generated'] == 0): ?>
                            <a href="accounting_invoices.php?auto_generate=<?php echo $order['order_id']; ?>" class="btn btn-small" style="margin-left:4px;">📄 Invoice</a>
                        <?php else: ?>
                            <span style="font-size:10px; color:#6b7280; margin-left:4px;">invoiced</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr><td colspan="7" style="text-align:center; padding:24px; color:var(--text-muted);">No orders found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Customers Table -->
<div class="card" style="margin-top:30px;">
    <h3>Existing Customers</h3>
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th><?php echo sortHeader('customer_id', 'ID', $sort_customers); ?></th>
                <th><?php echo sortHeader('customer_name', 'Customer Name', $sort_customers); ?></th>
                <th><?php echo sortHeader('contact_number', 'Contact', $sort_customers); ?></th>
                <th><?php echo sortHeader('address', 'Address', $sort_customers); ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $c): ?>
            <tr>
                <td><?php echo $c['customer_id']; ?></td>
                <td><?php echo htmlspecialchars($c['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($c['contact_number']); ?></td>
                <td><?php echo htmlspecialchars($c['address']); ?></td>
                <td>
                    <button class="btn btn-edit-customer" data-id="<?php echo (int)$c['customer_id']; ?>" data-name="<?php echo htmlspecialchars($c['customer_name'], ENT_QUOTES); ?>" data-contact-person="<?php echo htmlspecialchars($c['contact_person'] ?? '', ENT_QUOTES); ?>" data-contact="<?php echo htmlspecialchars($c['contact_number'] ?? '', ENT_QUOTES); ?>" data-email="<?php echo htmlspecialchars($c['email'] ?? '', ENT_QUOTES); ?>" data-address="<?php echo htmlspecialchars($c['address'] ?? '', ENT_QUOTES); ?>" style="margin-right:6px;">Edit</button>
                    <button class="btn btn-delete-customer" data-id="<?php echo (int)$c['customer_id']; ?>">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- New Customer Modal -->
<div id="newCustomerModal">
    <div class="modal-content">
    <h3>Add New Customer</h3>
    <input type="hidden" id="newCustomerName">
    <input type="hidden" id="editingCustomerId">
        <div>
            <label>Customer Name:</label>
            <input type="text" id="modalCustomerName">
        </div>
        <div>
            <label>Contact Number:</label>
            <input type="text" id="modalCustomerContact">
        </div>
        <div>
            <label>Address:</label>
            <textarea id="modalCustomerAddress" rows="2"></textarea>
        </div>
        <div>
            <label>Contact person (optional):</label>
            <input type="text" id="modalCustomerContactPerson" maxlength="100">
        </div>
        <div>
            <label>Email (optional):</label>
            <input type="email" id="modalCustomerEmail" maxlength="100">
        </div>
        <p style="font-size:12px; color:#64748b; margin:12px 0 4px;">Optional — customer can log in to view orders:</p>
        <div>
            <label>Portal username:</label>
            <input type="text" id="modalPortalUsername" maxlength="100" autocomplete="off" placeholder="Leave blank if not needed">
        </div>
        <div>
            <label>Portal password:</label>
            <input type="password" id="modalPortalPassword" maxlength="128" autocomplete="new-password" placeholder="Min 6 characters if username set">
        </div>
        <div style="text-align:right;">
            <button id="cancelNewCustomer" class="btn" style="margin-right:5px;">Cancel</button>
            <button id="saveNewCustomer" class="btn">Save</button>
        </div>
    </div>
</div>

<div id="salesOrderDetailModal" style="display:none; position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; padding:16px; box-sizing:border-box;">
    <div class="sales-order-detail-inner" style="background:#fff; max-width:540px; width:100%; max-height:88vh; overflow:auto; border-radius:14px; padding:22px 20px; position:relative; box-shadow:0 20px 50px rgba(0,0,0,0.25); border:1px solid var(--border-color);">
        <button type="button" id="salesOrderDetailClose" style="position:absolute; top:10px; right:12px; background:#f1f5f9; border:none; width:36px; height:36px; border-radius:999px; font-size:20px; cursor:pointer; line-height:1;">&times;</button>
        <div id="salesOrderDetailBody" style="padding-right:8px;"></div>
    </div>
</div>

</div>
<?php include "layouts/footer.php"; ?>
</div>
</div>

<script src="assets/js/sidebar.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>

$('#newCustomerModal').hide();  // make sure modal is hidden on page load
$('#newCustomerName').val('');   // clear hidden input

function salesEscapeHtml(t) {
    if (t === undefined || t === null) return '';
    var d = document.createElement('div');
    d.textContent = String(t);
    return d.innerHTML;
}
$(document).on('click', '.sales-order-detail-btn', function() {
    var id = $(this).data('order-id');
    var $body = $('#salesOrderDetailBody');
    $body.html('<p style="color:#64748b;">Loading…</p>');
    $('#salesOrderDetailModal').css('display', 'flex');
    $.getJSON('api/get_order_items.php', { order_id: id })
        .done(function(res) {
            if (!res.success) {
                $body.html('<p style="color:#b91c1c;">' + salesEscapeHtml(res.error || 'Could not load order.') + '</p>');
                return;
            }
            var ft = res.fulfillment_type || '';
            if (ft === 'Pickup') {
                ft = 'Customer pickup';
            }
            var html = '<h3 style="margin:0 0 6px; font-size:1.15rem;">' + salesEscapeHtml(res.order_number) + '</h3>';
            html += '<p style="color:var(--text-muted); font-size:13px; margin:0 0 14px;">' + salesEscapeHtml(res.customer_name || '—') + ' · ' + salesEscapeHtml(res.status || '') + ' · ' + salesEscapeHtml(ft) + '</p>';
            if (res.contact_number) {
                html += '<p style="font-size:13px; margin:0 0 8px;"><strong>Phone:</strong> ' + salesEscapeHtml(res.contact_number) + '</p>';
            }
            html += '<p style="font-size:13px; margin:0 0 6px;"><strong>Delivery / address</strong></p>';
            html += '<p style="font-size:13px; margin:0 0 14px; color:#334155;">' + salesEscapeHtml(res.delivery_address || res.customer_address || '—') + '</p>';
            html += '<p style="font-size:13px; margin:0 0 8px;"><strong>Products</strong></p><ul style="margin:0 0 16px; padding-left:18px; font-size:14px;">';
            if (res.items && res.items.length) {
                res.items.forEach(function(it) {
                    html += '<li>' + salesEscapeHtml(it.product_name) + ' — <strong>' + it.quantity + '</strong></li>';
                });
            } else {
                html += '<li style="color:#64748b;">No line items in database for this order.</li>';
            }
            html += '</ul>';
            html += '<p style="font-size:15px; font-weight:700; margin:0 0 14px;">Total: ₱' + Number(res.total_amount || 0).toFixed(2) + '</p>';
            html += '<div style="display:flex; flex-wrap:wrap; gap:8px;">';
            html += '<a class="btn btn-small" href="sales_delivery.php?order_id=' + id + '">Delivery / schedule</a>';
            html += '<a class="btn btn-small" href="api/generate_pdf.php?type=sales_receipt&id=' + id + '" target="_blank">Receipt PDF</a>';
            html += '</div>';
            $body.html(html);
        })
        .fail(function() {
            $body.html('<p style="color:#b91c1c;">Network error.</p>');
        });
});
$('#salesOrderDetailClose').on('click', function() {
    $('#salesOrderDetailModal').hide();
});
$('#salesOrderDetailModal').on('click', function(e) {
    if (e.target.id === 'salesOrderDetailModal') {
        $(this).hide();
    }
});

(function() {
    var tbody = document.getElementById('orderLinesBody');
    var optionsHtml = document.querySelector('.order-line-row select[name="product_id[]"]').innerHTML;

    function updateStockDisplay(row) {
        var sel = row.querySelector('select[name="product_id[]"]');
        var opt = sel && sel.options[sel.selectedIndex];
        var stockCell = row.querySelector('.line-stock');
        var priceCell = row.querySelector('.line-price');
        var qtyInput = row.querySelector('.line-qty');
        
        if (opt && opt.value) {
            var avail = parseFloat(opt.getAttribute('data-available')) || 0;
            var unitPrice = parseFloat(opt.getAttribute('data-unit-price')) || 0;
            var qty = parseFloat(qtyInput.value) || 0;
            
            if (stockCell) {
                stockCell.textContent = 'Avail: ' + avail;
            }
            
            if (priceCell) {
                if (unitPrice > 0) {
                    var totalPrice = unitPrice * qty;
                    if (qty > 0) {
                        priceCell.innerHTML = '<div style="font-size:11px; color:#6b7280;">₱' + unitPrice.toFixed(2) + '</div><div style="font-size:12px; color:#059669; font-weight:600;">₱' + totalPrice.toFixed(2) + '</div>';
                    } else {
                        priceCell.innerHTML = '<div style="font-size:12px; color:#059669; font-weight:600;">₱' + unitPrice.toFixed(2) + '</div>';
                    }
                } else {
                    priceCell.textContent = 'N/A';
                }
            }
        } else {
            if (stockCell) stockCell.textContent = '-';
            if (priceCell) priceCell.textContent = '-';
        }
    }

    function formatOption(opt) {
        if (!opt.id) return opt.text;
        var img = $(opt.element).data('image');
        if (img) {
            var $container = $('<span style="display:flex; align-items:center;"></span>');
            var $img = $('<img style="width:40px; height:40px; object-fit:contain; margin-right:8px; border:1px solid #ccc; border-radius:4px;">').attr('src', img);
            $container.append($img).append(opt.text);
            return $container;
        }
        return opt.text;
    }

    function initSelect2(select) {
        $(select).select2({
            templateResult: formatOption,
            templateSelection: formatOption,
            width: '100%'
        });

        var previewImg = $(select).closest('td').find('.product-hover-preview img');
        $(select).on('change', function() {
            var img = $(this).find(':selected').data('image');
            if (img) previewImg.attr('src', img).show();
            else previewImg.hide();
        });
    }

    // Initialize existing product lines
    document.querySelectorAll('.product-select').forEach(function(sel) {
        initSelect2(sel);
        var row = sel.closest('.order-line-row');
        sel.addEventListener('change', function() { updateStockDisplay(row); });
        var qtyInput = row.querySelector('.line-qty');
        if (qtyInput) {
            qtyInput.addEventListener('input', function() { updateStockDisplay(row); });
        }
    });
    tbody.querySelectorAll('.order-line-row').forEach(function(row) {
        row.querySelector('.btn-remove-line').addEventListener('click', function() {
            if (tbody.querySelectorAll('.order-line-row').length > 1) row.remove();
        });
    });

    // Add new product line
    document.getElementById('addOrderLine').addEventListener('click', function() {
        var tr = document.createElement('tr');
        tr.className = 'order-line-row';
        tr.innerHTML = '<td><select name="product_id[]" class="product-select" style="width:100%; padding:8px;">' + optionsHtml + '</select><div class="product-hover-preview"><img src="" alt=""></div></td>' +
            '<td><input type="number" name="quantity[]" min="0.01" step="0.01" class="line-qty" style="width:100%; padding:8px;"></td>' +
            '<td class="line-price" style="color: var(--text-muted); font-size: 12px; font-weight:600; color:#059669;">-</td>' +
            '<td class="line-stock" style="color: var(--text-muted); font-size: 12px;">-</td>' +
            '<td><button type="button" class="btn btn-remove-line" style="padding:6px 10px;">✕</button></td>';
        tbody.appendChild(tr);

        tr.querySelector('.btn-remove-line').addEventListener('click', function() {
            if (tbody.querySelectorAll('.order-line-row').length > 1) tr.remove();
        });

        var sel = tr.querySelector('.product-select');
        var qtyInput = tr.querySelector('.line-qty');
        initSelect2(sel);
        sel.addEventListener('change', function() { updateStockDisplay(tr); });
        if (qtyInput) {
            qtyInput.addEventListener('input', function() { updateStockDisplay(tr); });
        }
    });

    // Customer Select2 with tagging
    $('#customerSelect').select2({
        placeholder: "-- Select Customer --",
        width: '100%',
        tags: true,
        createTag: function(params) {
            var term = $.trim(params.term);
            if(term==='') return null;
            return { id: term, text: term, newOption: true };
        },
        templateResult: function(data){
            var $result = $('<span></span>');
            $result.text(data.text);
            if(data.newOption) $result.append(" <em>(Add New)</em>");
            return $result;
        }
    });

    // Autofill delivery address when customer is selected
    $('#customerSelect').on('change', function(){
        var selectedOption = $(this).find(':selected');
        var address = selectedOption.attr('data-address') || '';
        $('textarea[name="delivery_address"]').val(address);
    });

    // Ensure hidden input is empty on page load
    $('#customer_name_hidden').val('');

    // Only show modal when user actually selects a new customer
    $('#customerSelect').on('select2:select', function(e){
        var data = e.params.data;
        if(data.newOption){
            // Show modal for adding new customer
            $('#newCustomerName').val(data.text);
            $('#modalCustomerName').val(data.text);
            $('#modalCustomerContact').val('');
            $('#modalCustomerAddress').val('');
            $('#modalCustomerContactPerson').val('');
            $('#modalCustomerEmail').val('');
            $('#modalPortalUsername').val('');
            $('#modalPortalPassword').val('');
            $('#newCustomerModal').fadeIn();
            $('#customer_name_hidden').val(data.text);
        } else {
            // Existing customer selected
            $('#customer_name_hidden').val($(this).find(':selected').text());
        }
    });

    $('#newCustomerModal').click(function(e){
        if(e.target == this){
            $(this).fadeOut();
            $('#customerSelect').val('').trigger('change');
        }
    });

    $('#cancelNewCustomer').click(function(){
        $('#newCustomerModal').fadeOut();
        $('#customerSelect').val('').trigger('change');
        $('#customer_name_hidden').val('');
    });

    // Save new OR edited customer
    $('#saveNewCustomer').click(function(){
        var editId = $('#editingCustomerId').val();
        var name = $('#newCustomerName').val();
        var contact = $('#modalCustomerContact').val();
        var address = $('#modalCustomerAddress').val();

        if(editId){
            var contactPerson = $('#modalCustomerContactPerson').val();
            var emailVal = $('#modalCustomerEmail').val();
            $.post('api/edit_customer.php', {
                customer_id: editId,
                customer_name: name,
                contact_person: contactPerson,
                contact_number: contact,
                email: emailVal,
                address: address
            }, function(res){
                if(res.success){
                    var $opt = $('#customerSelect option[value="'+editId+'"]');
                    if($opt.length){
                        $opt.text(res.customer_name);
                        $opt.attr('data-address', res.address || '').attr('data-contact', res.contact_number || '')
                            .attr('data-contact-person', res.contact_person || '').attr('data-email', res.email || '');
                    }
                    var $btn = $('.btn-edit-customer[data-id="'+editId+'"]');
                    if($btn.length){
                        var $row = $btn.closest('tr');
                        $row.find('td').eq(1).text(res.customer_name);
                        $row.find('td').eq(2).text(res.contact_number);
                        $row.find('td').eq(3).text(res.address);
                        $btn.attr('data-name', res.customer_name).attr('data-contact', res.contact_number || '')
                            .attr('data-contact-person', res.contact_person || '').attr('data-email', res.email || '')
                            .attr('data-address', res.address || '');
                    }
                    $('#customer_name_hidden').val(res.customer_name);
                    $('#newCustomerModal').fadeOut();
                    $('#editingCustomerId').val('');
                } else {
                    alert(res.message || 'Error updating customer.');
                }
            },'json');
        } else {
            var contactPerson = $('#modalCustomerContactPerson').val();
            var emailVal = $('#modalCustomerEmail').val();
            var portalUser = $('#modalPortalUsername').val();
            var portalPass = $('#modalPortalPassword').val();
            $.post('api/add_customer.php', {
                customer_name: name,
                contact_person: contactPerson,
                contact_number: contact,
                email: emailVal,
                address: address,
                portal_username: portalUser,
                portal_password: portalPass
            }, function(res){
                if(res.success){
                    var newOption = new Option(res.customer_name, res.customer_id, true, true);
                    $(newOption).attr('data-address', address || '')
                        .attr('data-contact', contact || '')
                        .attr('data-contact-person', res.contact_person || '')
                        .attr('data-email', res.email || '');
                    $('#customerSelect').append(newOption).trigger('change');
                    $('#customer_name_hidden').val(res.customer_name);
                    var newRow = '<tr>\n' +
                        '<td style="border:1px solid #ccc; padding:8px;">'+res.customer_id+'</td>' +
                        '<td style="border:1px solid #ccc; padding:8px;">'+res.customer_name+'</td>' +
                        '<td style="border:1px solid #ccc; padding:8px;">'+(contact||'')+'</td>' +
                        '<td style="border:1px solid #ccc; padding:8px;">'+(address||'')+'</td>' +
                        '<td style="border:1px solid #ccc; padding:8px;">' +
                        '<button class="btn btn-edit-customer" data-id="'+res.customer_id+'" data-name="'+res.customer_name.replace(/"/g,'&quot;')+'" data-contact-person="'+(res.contact_person||'').replace(/"/g,'&quot;')+'" data-contact="'+(contact||'').replace(/"/g,'&quot;')+'" data-email="'+(res.email||'').replace(/"/g,'&quot;')+'" data-address="'+(address||'').replace(/"/g,'&quot;')+'" style="margin-right:6px;">Edit</button>' +
                        '<button class="btn btn-delete-customer" data-id="'+res.customer_id+'">Delete</button>' +
                        '</td>\n' +
                        '</tr>';
                    $('.card table tbody').last().append(newRow);
                    $('#newCustomerModal').fadeOut();
                    $('#modalPortalUsername').val('');
                    $('#modalPortalPassword').val('');
                } else {
                    alert(res.message || 'Error adding customer.');
                }
            },'json');
        }
    });

    // Edit customer button
    $(document).on('click', '.btn-edit-customer', function(){
        var id = $(this).data('id');
        var name = $(this).attr('data-name') || '';
        var contact = $(this).attr('data-contact') || '';
        var address = $(this).attr('data-address') || '';
        var cp = $(this).attr('data-contact-person') || '';
        var em = $(this).attr('data-email') || '';
        $('#editingCustomerId').val(id);
        $('#newCustomerName').val(name);
        $('#modalCustomerName').val(name);
        $('#modalCustomerContact').val(contact);
        $('#modalCustomerAddress').val(address);
        $('#modalCustomerContactPerson').val(cp);
        $('#modalCustomerEmail').val(em);
        $('#modalPortalUsername').val('');
        $('#modalPortalPassword').val('');
        $('#newCustomerModal').fadeIn();
    });

    // Delete customer button
    $(document).on('click', '.btn-delete-customer', function(){
        var id = $(this).data('id');
        if(!id) return;
        if(!confirm('Delete this customer? This action cannot be undone.')) return;
        var $btn = $(this);
        $.post('api/delete_customer.php', {customer_id:id}, function(res){
            if(res.success){
                // remove table row
                $btn.closest('tr').remove();
                // remove option from select
                $('#customerSelect option[value="'+id+'"]').remove();
                $('#customerSelect').trigger('change');
            } else {
                alert(res.message || 'Error deleting customer.');
            }
        },'json');
    });

})();
function refreshOrderStatusOptions() {
    var $status = $('#orderStatus');
    var type = $('#fulfillmentType').val();

    if (type === 'Pickup') {
        $status.html(
            '<option value="Ready for Pickup">Ready for Pickup</option>' +
            '<option value="Picked Up">Picked Up</option>'
        );
        $status.val('Ready for Pickup');
    } else {
        $status.html('<option value="Pending">Pending</option>');
        $status.val('Pending');
    }
}

// Toggle delivery fields + status options
$('#fulfillmentType').on('change', function(){
    if($(this).val() === 'Pickup'){
        $('.delivery-only').hide();
        $('textarea[name="delivery_address"]').val('Warehouse Pickup');
    } else {
        $('.delivery-only').show();
    }
    refreshOrderStatusOptions();
});

// Run on page load
if($('#fulfillmentType').val() === 'Pickup'){
    $('.delivery-only').hide();
}
refreshOrderStatusOptions();

// small helper to prompt for a new status and send to server
function changeStatus(button) {
    var orderId = button.getAttribute('data-id');
    var status = prompt('Enter new status (Pending, Confirmed, Dispatched, Delivered, Ready for Pickup, Picked Up):');
    if (!status) return;
    status = status.trim();
    $.post('api/update_order_status.php', { order_id: orderId, status: status }, function(res) {
        if (res.success) {
            location.reload();
        } else {
            alert(res.message || 'Unable to update status');
        }
    }, 'json');
}


// Handle "Mark Picked Up" button click
$(document).on('click', '.mark-picked-up', function() {
    var btn = $(this);
    var orderId = btn.data('id');
    if (!orderId) return;

    btn.prop('disabled', true).text('Processing...');

    $.post('api/mark_picked_up.php', { order_id: orderId }, function(res) {
        if (res && res.success) {
            var row = btn.closest('tr');
            // Update status cell
            row.find('td').eq(4).html('<span style="font-size:10px; color:#059669;">Picked Up</span>');

            // Update actions cell
            var actionsCell = row.find('.pickup-order-actions');
            if (actionsCell.length) {
                var receiptBtn = '<a href="api/generate_pdf.php?type=sales_receipt&id=' + orderId + '" target="_blank" class="btn btn-small" style="padding:10px 10px; font-size:10px;">🧾</a>';
                var invoiceHtml = '';
                if (res.invoice_generated && parseInt(res.invoice_generated) === 1) {
                    invoiceHtml = '<span style="font-size:10px; color:#6b7280;">invoice generated</span>';
                } else {
                    invoiceHtml = '<a href="accounting_invoices.php?auto_generate=' + orderId + '" class="btn btn-small" style="padding:10px 10px; font-size:10px;">📄</a>';
                }
                actionsCell.html(receiptBtn + invoiceHtml);
            }
        } else {
            btn.prop('disabled', false).text('Mark Picked Up');
            alert(res && res.message ? res.message : 'Unable to update order status.');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).text('Mark Picked Up');
        alert('Request failed. Please try again.');
    });
});
</script>
</body>
</html>
