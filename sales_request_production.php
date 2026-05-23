<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','sales'])) {
    header("Location: login.php");
    exit;
}

include "includes/functions.php";
include "db_connect.php";

/* Fetch products with stock info */
$products_with_stock = [];
$pq = $conn->query("
    SELECT p.product_id, p.product_name, p.image_path, p.unit_price,
           (SELECT (fg.quantity - COALESCE(fg.reserved_quantity, 0)) FROM finished_goods fg WHERE fg.product_id = p.product_id LIMIT 1) AS available
    FROM products p
    ORDER BY p.product_name
");
if ($pq) {
    while ($r = $pq->fetch_assoc()) {
        $r['available'] = $r['available'] !== null ? (float)$r['available'] : 0;
        $r['unit_price'] = $r['unit_price'] !== null ? (float)$r['unit_price'] : 0;
        $products_with_stock[] = $r;
    }
}

/* Fetch customers for dropdown */
$customers = [];
$cq = $conn->query("SELECT customer_id, customer_name, contact_number, address FROM customers ORDER BY customer_name");
if ($cq) {
    while ($row = $cq->fetch_assoc()) {
        $customers[] = $row;
    }
}

/* Fetch pending orders that might need production */
// Sorting for linked sales orders list
$sort_orders = getSortParams('order_date', ['order_number','customer_name','status','order_date']);
$orders_column_map = [
    'order_number'  => 'so.order_number',
    'customer_name' => 'c.customer_name',
    'status'        => 'so.status',
    'order_date'    => 'so.order_date'
];
$orders_order_by = isset($orders_column_map[$sort_orders['column']]) ? $orders_column_map[$sort_orders['column']] : 'so.order_date';

$pending_orders = [];
$orders_query = $conn->query("
    SELECT so.order_id, so.order_number, so.order_date, so.total_amount, c.customer_name, so.status
    FROM sales_orders so
    LEFT JOIN customers c ON so.customer_id = c.customer_id
    WHERE so.status IN ('Pending', 'Confirmed')
    ORDER BY $orders_order_by " . $sort_orders['order'] . "
    LIMIT 20
");
if ($orders_query) {
    while ($row = $orders_query->fetch_assoc()) {
        $pending_orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request Production | LORINIMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container .select2-selection--single { height: 38px !important; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px !important; }
.select2-results__option img {
    margin-right: 8px;
    width: 40px; height: 40px; object-fit: contain;
    border: 1px solid #ccc; border-radius: 4px;
}
.product-hover-preview img { max-width:120px; max-height:100px; display:none; border:1px solid #ccc; border-radius:4px; margin-top:5px; }

.request-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.order-link-section {
    background: #f0f9ff;
    border: 2px solid #3b82f6;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.order-link-section h4 {
    margin-top: 0;
    color: #1e40af;
}

.order-item {
    padding: 10px;
    background: white;
    border-radius: 6px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid #dbeafe;
}

.order-item:hover {
    background: #eff6ff;
}

.link-order-btn {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.link-order-btn:hover {
    background: #2563eb;
}

@media (max-width: 768px) {
    .request-form-grid {
        grid-template-columns: 1fr;
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

<div class="page-header">
    <h2>Request Production</h2>
    <p>Create a production request for products that need to be manufactured. Requests appear as one row per customer order in Production.</p>
    <br>
    <p style="margin-top:8px;">
        <a href="production_requests.php" class="btn" style="text-decoration:none;">View Production Requests →</a>
    </p>
</div>
<br>
<?php showMessage(); ?>

<!-- Link to Existing Order -->
<?php if (count($pending_orders) > 0): ?>
<div class="card order-link-section">
    <h4>📋 Link to Existing Order</h4>
    <p style="color: var(--text-muted); margin-bottom: 15px;">Select an existing order to create a production request for its items.</p>
    <div id="pending_orders_list">
        <?php foreach ($pending_orders as $order): ?>
            <div class="order-item">
                <div>
                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                    <br><small style="color: var(--text-muted);">
                        <?php echo htmlspecialchars($order['customer_name']); ?> • 
                        <?php echo formatDate($order['order_date']); ?> • 
                        ₱<?php echo number_format($order['total_amount'], 2); ?>
                    </small>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <button type="button" class="link-order-btn" onclick="loadOrderItems(<?php echo $order['order_id']; ?>)">
                        Use This Order
                    </button>
                    <button type="button" class="link-order-btn delete-order-btn" style="background:#dc2626;" data-id="<?php echo $order['order_id']; ?>">
                        Delete
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h3>New Production Request</h3>
    <form method="POST" action="api/request_production.php" id="productionForm">
        <div class="request-form-grid">
            <div>
                <label>Customer Name</label>
                <select id="customerSelect" name="customer_id" style="width:100%; padding:8px;" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['customer_id']; ?>"><?= htmlspecialchars($c['customer_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="customer_name" id="customer_name_hidden" value="">
            </div>
            <div>
                <label>Link to Sales Order (Optional)</label>
                <select name="sales_order_id" id="sales_order_id" style="width:100%; padding:8px;">
                    <option value="">-- No Order Link --</option>
                    <?php foreach ($pending_orders as $order): ?>
                        <option value="<?php echo $order['order_id']; ?>">
                            <?php echo htmlspecialchars($order['order_number']); ?> - <?php echo htmlspecialchars($order['customer_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h4 style="margin:20px 0 10px 0; color: var(--text-primary);">Products to Produce (add multiple)</h4>
        <table class="order-lines-table" style="width:100%; margin-bottom:10px;">
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="width:120px;">Requested Qty</th>
                    <th style="width:100px;">Current Stock</th>
                    <th style="width:150px;">Reason/Priority</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody id="requestLinesBody">
                <tr class="request-line-row">
                    <td>
                        <select name="product_id[]" class="product-select" style="width:100%; padding:8px;" required>
                            <option value="">-- Select Product --</option>
                            <?php foreach ($products_with_stock as $p): ?>
                                <option value="<?= (int)$p['product_id']; ?>"
                                    data-available="<?= (float)$p['available']; ?>"
                                    data-unit-price="<?= (float)$p['unit_price']; ?>"
                                    data-image="assets/images/products/<?= htmlspecialchars($p['image_path'] ?? ''); ?>">
                                    <?php 
                                    $priceDisplay = $p['unit_price'] > 0 ? '₱' . number_format($p['unit_price'], 2) : 'N/A';
                                    echo htmlspecialchars($p['product_name']); 
                                    ?> - <?php echo $priceDisplay; ?> (Stock: <?php echo number_format($p['available'],0); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="product-hover-preview"><img src="" alt=""></div>
                    </td>
                    <td><input type="number" name="requested_qty[]" min="0.01" step="0.01" class="line-qty" style="width:100%; padding:8px;" required></td>
                    <td class="line-stock" style="color: var(--text-muted); font-size: 12px;">-</td>
                    <td>
                        <select name="reason[]" style="width:100%; padding:8px;">
                            <option value="Customer Order">Customer Order</option>
                            <option value="Low Stock">Low Stock</option>
                            <option value="High Demand">High Demand</option>
                            <option value="Scheduled Production">Scheduled Production</option>
                            <option value="Other">Other</option>
                        </select>
                    </td>
                    <td><button type="button" class="btn btn-remove-line" style="padding:6px 10px;">✕</button></td>
                </tr>
            </tbody>
        </table>
        <p><button type="button" id="addRequestLine" class="btn" style="margin-bottom:15px;">+ Add product line</button></p>

        <div style="text-align:right;">
            <button type="submit" class="btn btn-primary">Send Production Request</button>
        </div>
    </form>
</div>

<!-- Recent Production Requests (grouped per customer order) -->
<div class="card">
    <h3>Recent Production Requests</h3>
    <?php
    // Sorting for grouped production requests table
    $sort_req = getSortParams('created_at', ['request_group','customer_name','status','priority','created_at']);
    $req_column_map = [
        'request_group' => 'pr.request_group_id',
        'customer_name' => 'pr.customer_name',
        'status'        => 'pr.status',
        'priority'      => 'pr.priority',
        'created_at'    => 'pr.created_at'
    ];
    $req_order_by = isset($req_column_map[$sort_req['column']]) ? $req_column_map[$sort_req['column']] : 'pr.created_at';

    $has_group = @$conn->query("SHOW COLUMNS FROM production_requests LIKE 'request_group_id'")->num_rows > 0;
    $has_from_production = @$conn->query("SHOW COLUMNS FROM sales_orders LIKE 'from_production_request'")->num_rows > 0;

    $recent_requests_raw = $conn->query("
        SELECT 
            pr.request_id,
            pr.customer_name,
            pr.requested_qty,
            pr.status,
            pr.priority,
            pr.created_at,
            pr.product_id,
            " . ($has_group ? "pr.request_group_id" : "NULL AS request_group_id") . ",
            p.product_name
        FROM production_requests pr
        JOIN products p ON pr.product_id = p.product_id
        ORDER BY $req_order_by " . $sort_req['order'] . "
        LIMIT 50
    ");

    // Group by request_group_id (or single request)
    $grouped = [];
    $requestIdToGroup = [];
    if ($recent_requests_raw) {
        while ($row = $recent_requests_raw->fetch_assoc()) {
            $gid = $has_group && !empty($row['request_group_id'])
                ? $row['request_group_id']
                : ('REQ-' . (int)$row['request_id']);
            if (!isset($grouped[$gid])) {
                $grouped[$gid] = [
                    'group_key'       => $gid,
                    'customer_name'   => $row['customer_name'],
                    'created_at'      => $row['created_at'],
                    'priority'        => $row['priority'],
                    'status'          => $row['status'],
                    'all_completed'   => true,
                    'lines'           => [],
                    'pending_items'   => [],
                    'pending_batches' => [],
                    'request_ids'     => [],
                    'product_names'   => []
                ];
            }

            $rowStatus = trim($row['status'] ?? '');
            // Track which request IDs belong to this group for later batch status checks
            $grouped[$gid]['request_ids'][] = (int)$row['request_id'];
            $grouped[$gid]['product_names'][(int)$row['request_id']] = $row['product_name'];
            $requestIdToGroup[(int)$row['request_id']] = $gid;

            // Overall group status: Completed only if all lines are completed;
            // defaults to the first non-completed status encountered.
            if (strcasecmp($rowStatus, 'Completed') !== 0) {
                $grouped[$gid]['status'] = $row['status'];
                $grouped[$gid]['all_completed'] = false;
                $grouped[$gid]['pending_items'][] = $row['product_name'] . ' (' . $row['status'] . ')';
            }

            $grouped[$gid]['lines'][] = $row;
        }

        // Cross-check production batch statuses for requests in these groups.
        if (!empty($requestIdToGroup)) {
            $requestIds = array_keys($requestIdToGroup);
            $in_clause = implode(',', array_map('intval', $requestIds));
            $batchQ = $conn->query("SELECT request_id, batch_number, status FROM production_batches WHERE request_id IN ($in_clause)");
            if ($batchQ) {
                while ($b = $batchQ->fetch_assoc()) {
                    $rid = (int)$b['request_id'];
                    if (!isset($requestIdToGroup[$rid])) continue;
                    $gid = $requestIdToGroup[$rid];
                    $batchStatus = trim($b['status'] ?? '');
                    if (strcasecmp($batchStatus, 'Completed') !== 0) {
                        $grouped[$gid]['all_completed'] = false;
                        $prodName = $grouped[$gid]['product_names'][$rid] ?? 'Product';
                        $grouped[$gid]['pending_batches'][] = $prodName . ' (Batch ' . $b['batch_number'] . ': ' . $batchStatus . ')';
                    }
                }
            }
        }
    }

    // Map request_group to any auto-created sales order from production_request
    $delivery_links = [];
    if ($has_from_production && !empty($grouped)) {
        $group_ids = [];
        foreach ($grouped as $g) {
            $group_ids[] = $g['group_key'];
        }
        $in_clause = implode(',', array_map(function($v) use ($conn) {
            return "'" . $conn->real_escape_string($v) . "'";
        }, $group_ids));

        $soq = $conn->query("
            SELECT so.order_id, so.request_group_id, so.status, so.delivery_person_id
            FROM sales_orders so
            WHERE so.from_production_request = 1
            AND so.request_group_id IN ($in_clause)
        ");
        if ($soq) {
            while ($so = $soq->fetch_assoc()) {
                $delivery_links[$so['request_group_id']] = [
                    'order_id' => (int)$so['order_id'],
                    'status' => $so['status'],
                    'delivery_person_id' => $so['delivery_person_id'] ?? null
                ];
            }
        }
    }

    $status_styles = [
        'Pending'       => 'background:#fef3c7; color:#92400e;',
        'In Progress'   => 'background:#dbeafe; color:#1e40af;',
        'For Inspection'=> 'background:#ede9fe; color:#5b21b6;',
        'Completed'     => 'background:#d1fae5; color:#065f46;',
    ];
    ?>
    <table class="modern-table" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th style="padding:12px; text-align:left; border-bottom:2px solid var(--border-color);">
                    <?php echo sortHeader('request_group', 'Request Group', $sort_req); ?>
                </th>
                <th style="padding:12px; text-align:left; border-bottom:2px solid var(--border-color);">
                    <?php echo sortHeader('customer_name', 'Customer', $sort_req); ?>
                </th>
                <th style="padding:12px; text-align:left; border-bottom:2px solid var(--border-color);">Products</th>
                <th style="padding:12px; text-align:left; border-bottom:2px solid var(--border-color);">Total Qty</th>
                <th style="padding:12px; text-align:left; border-bottom:2px solid var(--border-color);">
                    <?php echo sortHeader('status', 'Prod. Status', $sort_req); ?>
                </th>
                <th style="padding:12px; text-align:left; border-bottom:2px solid var(--border-color);">
                    <?php echo sortHeader('priority', 'Priority', $sort_req); ?>
                </th>
                <th style="padding:12px; text-align:left; border-bottom:2px solid var(--border-color);">
                    <?php echo sortHeader('created_at', 'Date', $sort_req); ?>
                </th>
                <th style="padding:12px; text-align:left; border-bottom:2px solid var(--border-color);">Delivery</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($grouped)): ?>
                <?php foreach ($grouped as $gid => $g): ?>
                    <?php
                        $status = $g['status'];
                        $lines = $g['lines'];
                        $total_qty = 0;
                        $product_list = [];
                        foreach ($lines as $ln) {
                            $total_qty += (float)$ln['requested_qty'];
                            $product_list[] = htmlspecialchars($ln['product_name']) . ' (' . number_format($ln['requested_qty'],2) . ')';
                        }
                        $prod_status_style = $status_styles[$status] ?? 'background:#f3f4f6; color:#374151;';
                        $delivery_info = $delivery_links[$gid] ?? null;
                        $all_completed = !empty($g['all_completed']);
                        $pendingReasons = [];
                        if (!empty($g['pending_items'])) {
                            $pendingReasons[] = 'Request status: ' . implode(', ', $g['pending_items']);
                        }
                        if (!empty($g['pending_batches'])) {
                            $pendingReasons[] = 'Batch status: ' . implode(', ', $g['pending_batches']);
                        }
                        $pendingMessage = !empty($pendingReasons)
                            ? implode(' | ', $pendingReasons)
                            : 'Waiting for all products to complete...';
                    ?>
                    <tr style="border-bottom:1px solid var(--border-color);">
                        <td style="padding:12px;"><?php echo htmlspecialchars($gid); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($g['customer_name']); ?></td>
                        <td style="padding:12px;">
                            <?php echo implode('<br>', $product_list); ?>
                        </td>
                        <td style="padding:12px;"><?php echo number_format($total_qty, 2); ?></td>
                        <td style="padding:12px;">
                            <span style="padding:4px 10px; border-radius:6px; font-size:12px; font-weight:600; <?php echo $prod_status_style; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($g['priority'] ?? 'Normal'); ?></td>
                        <td style="padding:12px;"><?php echo formatDate($g['created_at']); ?></td>
                        <td style="padding:12px;">
                            <?php if ($all_completed): ?>
                                <?php if (!empty($delivery_info) && !empty($delivery_info['delivery_person_id'])): ?>
                                    <span style="background:#d1fae5;color:#065f46;padding:6px 10px;border-radius:6px;font-size:12px;font-weight:600;">
                                        Delivery Assigned ✓
                                    </span>
                                <?php else: ?>
                                    <?php if (!empty($delivery_info['order_id']) && empty($delivery_info['delivery_person_id'])): ?>
                                        <a href="sales_delivery.php?order_id=<?php echo urlencode($delivery_info['order_id']); ?>" 
                                           class="btn btn-sm btn-primary assign-delivery-btn">
                                            Assign Delivery
                                        </a>
                                    <?php elseif (!empty($delivery_info['delivery_person_id'])): ?>
                                        <span style="background:#d1fae5;color:#065f46;padding:6px 10px;border-radius:6px;font-size:12px;font-weight:600;">
                                            Delivery Assigned ✓
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size:12px; color:#6b7280;" title="No delivery order found for this production request.">
                                            No delivery order available
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size:12px; color:#92400e;" title="<?php echo htmlspecialchars($pendingMessage); ?>">
                                    In production / QC
                                </span>
                            <?php endif; ?>
                            </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);">No production requests found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <p style="text-align:right; margin-top:15px;">
        <a href="production_requests.php" class="btn">View All Requests →</a>
    </p>
</div>

<!-- New Customer Modal -->
<div id="newCustomerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div class="modal-content" style="background:#fff; padding:20px; border-radius:8px; max-width:400px; width:90%;">
        <h3>Add New Customer</h3>
        <input type="hidden" id="newCustomerName">
        <div>
            <label>Customer Name:</label>
            <input type="text" id="modalCustomerName" readonly style="width:100%; padding:6px; margin-top:4px; margin-bottom:10px;">
        </div>
        <div>
            <label>Contact Number:</label>
            <input type="text" id="modalCustomerContact" style="width:100%; padding:6px; margin-top:4px; margin-bottom:10px;">
        </div>
        <div>
            <label>Address:</label>
            <textarea id="modalCustomerAddress" rows="2" style="width:100%; padding:6px; margin-top:4px; margin-bottom:10px;"></textarea>
        </div>
        <div style="text-align:right;">
            <button id="cancelNewCustomer" class="btn" style="margin-right:5px;">Cancel</button>
            <button id="saveNewCustomer" class="btn">Save</button>
        </div>
    </div>
</div>

</div>
<?php include "layouts/footer.php"; ?>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="assets/js/sidebar.js"></script>
<script>
(function() {
    var tbody = document.getElementById('requestLinesBody');
    var optionsHtml = document.querySelector('.request-line-row select[name="product_id[]"]').innerHTML;

    function updateStockDisplay(row) {
        var sel = row.querySelector('select[name="product_id[]"]');
        var opt = sel && sel.options[sel.selectedIndex];
        var stockCell = row.querySelector('.line-stock');
        if (stockCell && opt && opt.value) {
            var avail = parseFloat(opt.getAttribute('data-available')) || 0;
            stockCell.textContent = 'Avail: ' + avail;
            if (avail < 10) {
                stockCell.style.color = '#dc2626';
                stockCell.style.fontWeight = '600';
            } else {
                stockCell.style.color = 'var(--text-muted)';
                stockCell.style.fontWeight = 'normal';
            }
        } else if (stockCell) {
            stockCell.textContent = '-';
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

    // Load order items into form
    window.loadOrderItems = function(orderId) {
        $.get('api/get_order_items.php?order_id=' + orderId, function(data) {
            if (data.success && data.items) {
                // Set customer
                $('#customerSelect').val(data.customer_id).trigger('change');
                $('#sales_order_id').val(orderId);
                
                // Clear existing lines except first
                tbody.innerHTML = '';
                
                // Add items from order
                data.items.forEach(function(item) {
                    var tr = document.createElement('tr');
                    tr.className = 'request-line-row';
                    tr.innerHTML = '<td><select name="product_id[]" class="product-select" style="width:100%; padding:8px;" required>' + optionsHtml + '</select><div class="product-hover-preview"><img src="" alt=""></div></td>' +
                        '<td><input type="number" name="requested_qty[]" min="0.01" step="0.01" class="line-qty" value="' + item.quantity + '" style="width:100%; padding:8px;" required></td>' +
                        '<td class="line-stock" style="color: var(--text-muted); font-size: 12px;">-</td>' +
                        '<td><select name="reason[]" style="width:100%; padding:8px;"><option value="Customer Order" selected>Customer Order</option><option value="Low Stock">Low Stock</option><option value="High Demand">High Demand</option><option value="Scheduled Production">Scheduled Production</option><option value="Other">Other</option></select></td>' +
                        '<td><button type="button" class="btn btn-remove-line" style="padding:6px 10px;">✕</button></td>';
                    tbody.appendChild(tr);
                    
                    // Set product
                    var productSelect = tr.querySelector('.product-select');
                    productSelect.value = item.product_id;
                    
                    // Initialize
                    initSelect2(productSelect);
                    productSelect.addEventListener('change', function() { updateStockDisplay(tr); });
                    updateStockDisplay(tr);
                    
                    // Remove button
                    tr.querySelector('.btn-remove-line').addEventListener('click', function() {
                        if (tbody.querySelectorAll('.request-line-row').length > 1) tr.remove();
                    });
                });
                
                alert('Order items loaded! Review and submit the production request.');
            } else {
                alert('Error loading order items.');
            }
        }, 'json');
    };

    // Initialize product lines
    document.querySelectorAll('.product-select').forEach(function(sel) {
        initSelect2(sel);
        var row = sel.closest('.request-line-row');
        sel.addEventListener('change', function() { updateStockDisplay(row); });
        updateStockDisplay(row);
    });
    
    tbody.querySelectorAll('.request-line-row').forEach(function(row) {
        row.querySelector('.btn-remove-line').addEventListener('click', function() {
            if (tbody.querySelectorAll('.request-line-row').length > 1) row.remove();
        });
    });

    // Add new product line
    document.getElementById('addRequestLine').addEventListener('click', function() {
        var tr = document.createElement('tr');
        tr.className = 'request-line-row';
        tr.innerHTML = '<td><select name="product_id[]" class="product-select" style="width:100%; padding:8px;">' + optionsHtml + '</select><div class="product-hover-preview"><img src="" alt=""></div></td>' +
            '<td><input type="number" name="requested_qty[]" min="0.01" step="0.01" class="line-qty" style="width:100%; padding:8px;"></td>' +
            '<td class="line-stock" style="color: var(--text-muted); font-size: 12px;">-</td>' +
            '<td><select name="reason[]" style="width:100%; padding:8px;"><option value="Customer Order">Customer Order</option><option value="Low Stock">Low Stock</option><option value="High Demand">High Demand</option><option value="Scheduled Production">Scheduled Production</option><option value="Other">Other</option></select></td>' +
            '<td><button type="button" class="btn btn-remove-line" style="padding:6px 10px;">✕</button></td>';
        tbody.appendChild(tr);

        tr.querySelector('.btn-remove-line').addEventListener('click', function() {
            if (tbody.querySelectorAll('.request-line-row').length > 1) tr.remove();
        });

        var sel = tr.querySelector('.product-select');
        initSelect2(sel);
        sel.addEventListener('change', function() { updateStockDisplay(tr); });
    });

    // CUSTOMER SELECT2 & MODAL
    $('#newCustomerModal').hide();
    $('#newCustomerName').val('');

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

    $('#customer_name_hidden').val('');

    $('#customerSelect').on('select2:select', function(e){
        var data = e.params.data;
        if(data.newOption){
            $('#newCustomerName').val(data.text);
            $('#modalCustomerName').val(data.text);
            $('#modalCustomerContact').val('');
            $('#modalCustomerAddress').val('');
            $('#newCustomerModal').fadeIn();
            $('#customer_name_hidden').val(data.text);
        } else {
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
    $('#saveNewCustomer').click(function(){
        var name = $('#newCustomerName').val();
        var contact = $('#modalCustomerContact').val();
        var address = $('#modalCustomerAddress').val();
        $.post('api/add_customer.php', {customer_name:name, contact_number:contact, address:address}, function(res){
            if(res.success){
                var newOption = new Option(res.customer_name, res.customer_id, true, true);
                $('#customerSelect').append(newOption).trigger('change');
                $('#customer_name_hidden').val(res.customer_name);
                $('#newCustomerModal').fadeOut();
            } else {
                alert(res.message || 'Error adding customer.');
            }
        },'json');
    });

    // Delete an existing order from the pending list
    $(document).on('click', '.delete-order-btn', function() {
        var btn = $(this);
        var orderId = btn.data('id');
        if (!orderId) return;
        if (!confirm('Delete this order? This cannot be undone.')) return;

        btn.prop('disabled', true).text('Deleting...');

        $.post('api/delete_sales_order.php', { order_id: orderId }, function(res) {
            if (res && res.success) {
                // Remove from the UI list
                btn.closest('.order-item').remove();
                // Also remove from the order dropdown if present
                $('#sales_order_id option[value="' + orderId + '"]').remove();
            } else {
                btn.prop('disabled', false).text('Delete');
                alert(res.message || 'Unable to delete order.');
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).text('Delete');
            alert('Request failed. Please try again.');
        });
    });

    // Prevent double-click when assigning delivery
    $(document).on('click', '.assign-delivery-btn', function() {
        $(this).text('Opening...').prop('disabled', true);
    });

})();
</script>
</body>
</html>
