<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Load accounting settings
$default_vat_rate = getSetting($conn, 'accounting_settings', 'vat_rate', 12, true);
$invoice_prefix = getSetting($conn, 'accounting_settings', 'invoice_prefix', 'INV-2026-');
$auto_generate_order_id = intval($_GET['auto_generate'] ?? 0);
$auto_order = null;
if ($auto_generate_order_id > 0) {
    $order_query = $conn->prepare("
        SELECT so.*, c.customer_name
        FROM sales_orders so
        LEFT JOIN customers c ON so.customer_id = c.customer_id
        WHERE so.order_id = ? AND so.invoice_generated = 0
    ");
    $order_query->bind_param("i", $auto_generate_order_id);
    $order_query->execute();
    $auto_order = $order_query->get_result()->fetch_assoc();
    $order_query->close();
    
    if ($auto_order) {
        // Get order items
        $items_query = $conn->prepare("
            SELECT oi.*, p.product_name, p.unit
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        $items_query->bind_param("i", $auto_generate_order_id);
        $items_query->execute();
        $auto_order['items'] = $items_query->get_result()->fetch_all(MYSQLI_ASSOC);
        $items_query->close();
    }
}

// Sort params for invoices
$sort_acc = getSortParams('invoice_date', ['invoice_number', 'order_number', 'customer_name', 'invoice_date', 'due_date', 'amount', 'status', 'approval_status']);
$column_map_acc = ['invoice_number' => 'i.invoice_number', 'order_number' => 'so.order_number', 'customer_name' => 'c.customer_name', 'invoice_date' => 'i.invoice_date', 'due_date' => 'i.due_date', 'amount' => 'i.amount', 'status' => 'i.status', 'approval_status' => 'i.approval_status'];
$order_by_acc = isset($column_map_acc[$sort_acc['column']]) ? $column_map_acc[$sort_acc['column']] : 'i.invoice_date';

// Fetch invoices with customer info
$invoices = [];
$invoices_query = $conn->query("
    SELECT i.*, c.customer_name, c.contact_number,
           COALESCE(SUM(p.amount), 0) as paid_amount,
           (i.amount - COALESCE(SUM(p.amount), 0)) as outstanding,
           so.order_id, so.order_number
    FROM invoices i
    LEFT JOIN customers c ON i.customer_id = c.customer_id
    LEFT JOIN sales_orders so ON i.order_id = so.order_id
    LEFT JOIN payments p ON i.invoice_id = p.invoice_id
    GROUP BY i.invoice_id
    ORDER BY " . $order_by_acc . " " . $sort_acc['order'] . ", i.invoice_id DESC
    LIMIT 100
");
if ($invoices_query) {
    while ($row = $invoices_query->fetch_assoc()) {
        $invoices[] = $row;
    }
}

// Attach delivered products to invoices (from linked orders)
$orderIds = array_unique(array_filter(array_column($invoices, 'order_id')));
if (!empty($orderIds)) {
    $inClause = implode(',', array_map('intval', $orderIds));
    $items_query = $conn->query(
        "SELECT oi.order_id, p.product_name, oi.quantity, p.unit
         FROM order_items oi
         JOIN products p ON oi.product_id = p.product_id
         WHERE oi.order_id IN ($inClause)"
    );
    $invoiceItems = [];
    if ($items_query) {
        while ($row = $items_query->fetch_assoc()) {
            $invoiceItems[$row['order_id']][] = $row;
        }
    }
    foreach ($invoices as &$inv) {
        $inv['items'] = $invoiceItems[$inv['order_id']] ?? [];
    }
    unset($inv);
}

// Fetch recently invoicable orders (delivered or customer pickups) without invoices
$pending_invoices = [];
$pending_query = $conn->query("
    SELECT so.order_id, so.order_number, so.order_date, so.total_amount, c.customer_name
    FROM sales_orders so
    LEFT JOIN customers c ON so.customer_id = c.customer_id
    WHERE so.invoice_generated = 0
      AND (so.status = 'Delivered' OR so.status = 'Picked Up' OR so.status = 'Ready for Pickup')
    ORDER BY so.order_date DESC
    LIMIT 20
");
if ($pending_query) {
    while ($row = $pending_query->fetch_assoc()) {
        $pending_invoices[] = $row;
    }
}

$total_revenue = $conn->query("SELECT SUM(amount) as total FROM invoices WHERE status = 'Paid'")->fetch_assoc()['total'] ?? 0;
$total_pending = $conn->query("SELECT SUM(amount - COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id = invoices.invoice_id), 0)) as total FROM invoices WHERE status = 'Pending'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Management | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .summary-card p {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .summary-card:nth-child(1) { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .summary-card:nth-child(2) { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .summary-card:nth-child(3) { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .invoice-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .order-items-table {
            width: 100%;
            margin: 15px 0;
        }
        .order-items-table th {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C5A 100%);
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
        }
        .order-items-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        
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
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C5A 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
        }
        table tr:hover {
            background-color: #f9fafb;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-small {
            padding: 4px 12px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Invoice Management</h2>
            <p>Create, approve, and manage customer invoices. Automatic invoice generation from sales orders (including pickups).</p>
            <?php showMessage(); ?>
            
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total Revenue</h3>
                    <p>₱<?php echo number_format($total_revenue, 2); ?></p>
                </div>
                <div class="summary-card">
                    <h3>Pending Invoices</h3>
                    <p><?php echo count(array_filter($invoices, function($inv) { return $inv['status'] === 'Pending'; })); ?></p>
                    <small>₱<?php echo number_format($total_pending, 2); ?></small>
                </div>
                <div class="summary-card">
                    <h3>Pending Approval</h3>
                    <p><?php echo count(array_filter($invoices, function($inv) { return $inv['approval_status'] === 'Pending'; })); ?></p>
                </div>
            </div>
            
            <!-- Manual Invoice Form -->
            <div class="card" style="border-left: 4px solid #8b5cf6;">
                <h3>Create Manual Invoice</h3>
                <?php if ($auto_order): ?>
                    <p style="color:#374151;">Pre-filled from order <?php echo htmlspecialchars($auto_order['order_number']); ?>.</p>
                <?php endif; ?>
                <p style="color: var(--text-muted); margin-top: -8px; margin-bottom: 15px;">Create an invoice without linking to a delivered order (e.g. for manual billing, adjustments).</p>
                <form method="POST" action="api/save_invoice.php">
                    <table>
                        <tr>
                            <td>Customer</td>
                            <td>
                                <select name="customer_id" style="width:100%; padding:8px;" required>
                                    <option value="">-- Select Customer --</option>
                                    <?php
                                    $cust_list = $conn->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name");
                                    if ($cust_list && $cust_list->num_rows > 0) {
                                        while ($c = $cust_list->fetch_assoc()) {
                                            $sel = ($auto_order && $auto_order['customer_id'] == $c['customer_id']) ? ' selected' : '';
                                            echo '<option value="' . (int)$c['customer_id'] . '"' . $sel . '>' . htmlspecialchars($c['customer_name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Order (Optional)</td>
                            <td>
                                <select name="order_id" style="width:100%; padding:8px;">
                                    <option value="">-- No order --</option>
                                    <?php
                                    // Only include orders that haven't been invoiced to avoid duplicates.
                                    $ord_sql = "SELECT order_id, order_number FROM sales_orders WHERE invoice_generated = 0 ORDER BY order_date DESC LIMIT 100";
                                    if (!empty($auto_order) && !empty($auto_order['order_id'])) {
                                        $aid = intval($auto_order['order_id']);
                                        $ord_sql = "SELECT order_id, order_number FROM sales_orders WHERE (invoice_generated = 0 OR order_id = " . $aid . ") ORDER BY order_date DESC LIMIT 100";
                                    }
                                    $ord_list = $conn->query($ord_sql);
                                    if ($ord_list && $ord_list->num_rows > 0) {
                                        while ($o = $ord_list->fetch_assoc()) {
                                            $sel = ($auto_order && $auto_order['order_id'] == $o['order_id']) ? ' selected' : '';
                                            echo '<option value="' . (int)$o['order_id'] . '"' . $sel . '>' . htmlspecialchars($o['order_number']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Invoice Date</td>
                            <td><input type="date" name="invoice_date" style="width:100%; padding:8px;" value="<?php echo date('Y-m-d'); ?>" required></td>
                        </tr>
                        <tr>
                            <td>Due Date</td>
                            <td><input type="date" name="due_date" style="width:100%; padding:8px;" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required></td>
                        </tr>
                        <tr>
                            <td>Amount (₱)</td>
                            <td><input type="number" name="amount" step="0.01" min="0" style="width:100%; padding:8px;" placeholder="0.00" value="<?php echo $auto_order ? number_format($auto_order['total_amount'], 2, '.', '') : ''; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <select name="status" style="width:100%; padding:8px;">
                                    <option value="Pending">Pending</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Overdue">Overdue</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Save Manual Invoice</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
            
            <!-- Auto-Generate Invoice from Order -->
            <?php if ($auto_order): ?>
            <div class="card" style="border: 2px solid #3b82f6;">
                <h3>Generate Invoice from Order: <?php echo htmlspecialchars($auto_order['order_number']); ?></h3>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($auto_order['customer_name']); ?></p>
                <p><strong>Order Date:</strong> <?php echo formatDate($auto_order['order_date']); ?></p>
                
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auto_order['items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?></td>
                                <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; background: #f9fafb;">
                            <td colspan="3" style="text-align: right;">Total:</td>
                            <td>₱<?php echo number_format($auto_order['total_amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
                
                <form method="POST" action="api/auto_generate_invoice.php" id="autoInvoiceForm">
                    <input type="hidden" name="order_id" value="<?php echo $auto_order['order_id']; ?>">
                    <div class="invoice-form-grid">
                        <div>
                            <label>VAT Rate (%)</label>
                            <input type="number" name="vat_rate" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($default_vat_rate); ?>" style="width:100%; padding:8px;">
                        </div>
                        <div>
                            <label>Discount Amount (₱)</label>
                            <input type="number" name="discount_amount" step="0.01" min="0" value="0" style="width:100%; padding:8px;">
                        </div>
                        <div>
                            <label>Payment Terms</label>
                            <select name="payment_terms" style="width:100%; padding:8px;">
                                <option value="Cash">Cash</option>
                                <option value="Credit">Credit</option>
                            </select>
                        </div>
                        <div>
                            <label>Credit Terms (Days)</label>
                            <input type="number" name="due_days" min="0" value="0" style="width:100%; padding:8px;" placeholder="e.g., 15 or 30">
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <label>Notes</label>
                        <textarea name="notes" style="width:100%; padding:8px; min-height:80px;" placeholder="Optional notes..."></textarea>
                    </div>
                    <div style="text-align:right; margin-top:15px;">
                        <button type="button" class="btn" onclick="window.location.href='accounting_invoices.php'">Cancel</button>
                        <button type="submit" class="btn">Generate Invoice</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Pending Orders (Ready for Invoice) -->
            <?php if (count($pending_invoices) > 0 && !$auto_order): ?>
            <div class="card">
                <h3>Orders Ready for Invoice</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_invoices as $order): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo formatDate($order['order_date']); ?></td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <a href="?auto_generate=<?php echo $order['order_id']; ?>" class="btn btn-small">
                                        Generate Invoice
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- All Invoices -->
            <div class="card">
                <h3>All Invoices</h3>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo sortHeader('invoice_number', 'Invoice #', $sort_acc); ?></th>
                            <th><?php echo sortHeader('order_number', 'Order #', $sort_acc); ?></th>
                            <th><?php echo sortHeader('customer_name', 'Customer', $sort_acc); ?></th>
                            <th>Items</th>
                            <th><?php echo sortHeader('invoice_date', 'Date', $sort_acc); ?></th>
                            <th><?php echo sortHeader('due_date', 'Due Date', $sort_acc); ?></th>
                            <th><?php echo sortHeader('amount', 'Amount', $sort_acc); ?></th>
                            <th>Paid</th>
                            <th>Outstanding</th>
                            <th><?php echo sortHeader('status', 'Status', $sort_acc); ?></th>
                            <th><?php echo sortHeader('approval_status', 'Approval', $sort_acc); ?></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($invoices) > 0): ?>
                            <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($inv['order_number'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($inv['customer_name'] ?? 'N/A'); ?></td>
                                    <td style="max-width:260px; white-space:pre-wrap;">
                                        <?php
                                        if (!empty($inv['items'])) {
                                            $lines = array_map(function($it) {
                                                return htmlspecialchars($it['product_name']) . ' x ' . number_format($it['quantity'], 2);
                                            }, $inv['items']);
                                            echo implode("\n", $lines);
                                        } else {
                                            echo '<span style="color:var(--text-muted);">(no items)</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo formatDate($inv['invoice_date']); ?></td>
                                    <td><?php echo formatDate($inv['due_date']); ?></td>
                                    <td>₱<?php echo number_format($inv['amount'], 2); ?></td>
                                    <td>₱<?php echo number_format($inv['paid_amount'], 2); ?></td>
                                    <td><strong>₱<?php echo number_format($inv['outstanding'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($inv['status']); ?>">
                                            <?php echo htmlspecialchars($inv['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($inv['approval_status'] === 'Pending' && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'accounting')): ?>
                                            <button onclick="approveInvoice(<?php echo $inv['invoice_id']; ?>)" class="btn btn-small" style="background:#10b981;">Approve</button>
                                            <button onclick="rejectInvoice(<?php echo $inv['invoice_id']; ?>)" class="btn btn-small" style="background:#dc2626;">Reject</button>
                                        <?php else: ?>
                                            <span class="status-badge status-<?php echo strtolower($inv['approval_status']); ?>">
                                                <?php echo htmlspecialchars($inv['approval_status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="api/generate_pdf.php?type=invoice&id=<?php echo $inv['invoice_id']; ?>" target="_blank" class="btn btn-small">📄 PDF</a>
                                            <?php if ($inv['outstanding'] > 0 && $inv['approval_status'] === 'Approved'): ?>
                                                <button onclick="recordPayment(<?php echo $inv['invoice_id']; ?>, <?php echo $inv['outstanding']; ?>)" class="btn btn-small">💰 Pay</button>
                                            <?php endif; ?>
                                            <a href="customers_transactions.php?customer_id=<?php echo $inv['customer_id']; ?>" class="btn btn-small">👤 Customer</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" style="text-align:center;padding:30px;color:var(--text-muted);">No invoices found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>

<script src="assets/js/sidebar.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Auto-generate invoice form submission
$('#autoInvoiceForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: 'api/auto_generate_invoice.php',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                window.location.href = 'accounting_invoices.php';
            } else {
                alert('Error: ' + response.error);
            }
        },
        error: function(xhr) {
            var msg = 'Error generating invoice. Please try again.';
            try {
                var r = JSON.parse(xhr.responseText);
                if (r && r.error) msg = r.error;
            } catch (e) {}
            alert(msg);
        }
    });
});

function approveInvoice(invoiceId) {
    if (!confirm('Approve this invoice?')) return;
    $.post('api/approve_invoice.php', {invoice_id: invoiceId, action: 'approve'}, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Error: ' + response.error);
        }
    }, 'json');
}

function rejectInvoice(invoiceId) {
    var reason = prompt('Reason for rejection:');
    if (!reason) return;
    $.post('api/approve_invoice.php', {invoice_id: invoiceId, action: 'reject', reason: reason}, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Error: ' + response.error);
        }
    }, 'json');
}

function recordPayment(invoiceId, outstanding) {
    window.location.href = 'accounting_payments.php?invoice_id=' + invoiceId + '&amount=' + outstanding;
}
</script>
</body>
</html>
