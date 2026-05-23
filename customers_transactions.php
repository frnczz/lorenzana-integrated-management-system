<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Sort params for orders, invoices, payments
$sort_ord = getSortParams('order_date', ['order_number', 'order_date', 'item_count', 'total_amount', 'status']);
$sort_inv = getSortParams('invoice_date', ['invoice_number', 'invoice_date', 'due_date', 'amount', 'status']);
$sort_pay = getSortParams('payment_date', ['payment_number', 'payment_date', 'amount', 'payment_method', 'invoice_number']);
$col_ord = ['order_number' => 'so.order_number', 'order_date' => 'so.order_date', 'item_count' => 'item_count', 'total_amount' => 'so.total_amount', 'status' => 'so.status'];
$col_inv = ['invoice_number' => 'i.invoice_number', 'invoice_date' => 'i.invoice_date', 'due_date' => 'i.due_date', 'amount' => 'i.amount', 'status' => 'i.status'];
$col_pay = ['payment_number' => 'p.payment_number', 'payment_date' => 'p.payment_date', 'amount' => 'p.amount', 'payment_method' => 'p.payment_method', 'invoice_number' => 'i.invoice_number'];

// Get customer and order ID from query string
$customer_id = intval($_GET['customer_id'] ?? 0);
$order_id = intval($_GET['order_id'] ?? 0);

// Redirect to the single-order view page if an order ID is provided
if ($order_id > 0) {
    header("Location: costumer_transaction_view.php?order_id=" . $order_id);
    exit;
}

$selected_customer = null;

if ($customer_id > 0) {
    $customer_query = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $customer_query->bind_param("i", $customer_id);
    $customer_query->execute();
    $selected_customer = $customer_query->get_result()->fetch_assoc();
    $customer_query->close();
}

// Fetch all customers for dropdown
$customers = [];
$customers_query = $conn->query("SELECT customer_id, customer_name, contact_number FROM customers ORDER BY customer_name");
if ($customers_query) {
    while ($row = $customers_query->fetch_assoc()) {
        $customers[] = $row;
    }
}

// Fetch transactions if customer is selected
$orders = [];
$invoices = [];
$payments = [];
$total_orders = 0;
$total_invoiced = 0;
$total_paid = 0;
$total_outstanding = 0;

if ($selected_customer) {
    // Get orders
    $ob_ord = isset($col_ord[$sort_ord['column']]) ? $col_ord[$sort_ord['column']] : 'so.order_date';
    $orders_query = $conn->prepare("
        SELECT so.order_id, so.order_number, so.order_date, so.status, so.total_amount,
               COUNT(oi.item_id) as item_count
        FROM sales_orders so
        LEFT JOIN order_items oi ON so.order_id = oi.order_id
        WHERE so.customer_id = ?
        GROUP BY so.order_id
        ORDER BY " . $ob_ord . " " . $sort_ord['order'] . "
    ");
    $orders_query->bind_param("i", $customer_id);
    $orders_query->execute();
    $orders_result = $orders_query->get_result();
    while ($row = $orders_result->fetch_assoc()) {
        $orders[] = $row;
        $total_orders += (float)$row['total_amount'];
    }
    $orders_query->close();
    
    // Get invoices
    $ob_inv = isset($col_inv[$sort_inv['column']]) ? $col_inv[$sort_inv['column']] : 'i.invoice_date';
    $invoices_query = $conn->prepare("
        SELECT i.invoice_id, i.invoice_number, i.invoice_date, i.due_date, i.amount, 
               i.status, i.approval_status, i.payment_terms,
               COALESCE(SUM(p.amount), 0) as paid_amount,
               (i.amount - COALESCE(SUM(p.amount), 0)) as outstanding
        FROM invoices i
        LEFT JOIN payments p ON i.invoice_id = p.invoice_id
        WHERE i.customer_id = ?
        GROUP BY i.invoice_id
        ORDER BY " . $ob_inv . " " . $sort_inv['order'] . "
    ");
    $invoices_query->bind_param("i", $customer_id);
    $invoices_query->execute();
    $invoices_result = $invoices_query->get_result();
    while ($row = $invoices_result->fetch_assoc()) {
        $invoices[] = $row;
        $total_invoiced += (float)$row['amount'];
        $total_paid += (float)$row['paid_amount'];
        $total_outstanding += (float)$row['outstanding'];
    }
    $invoices_query->close();
    
    // Get payments
    $ob_pay = isset($col_pay[$sort_pay['column']]) ? $col_pay[$sort_pay['column']] : 'p.payment_date';
    $payments_query = $conn->prepare("
        SELECT p.payment_id, p.payment_number, p.payment_date, p.payment_method, 
               p.amount, p.reference_number, p.notes,
               i.invoice_number
        FROM payments p
        JOIN invoices i ON p.invoice_id = i.invoice_id
        WHERE i.customer_id = ?
        ORDER BY " . $ob_pay . " " . $sort_pay['order'] . "
    ");
    $payments_query->bind_param("i", $customer_id);
    $payments_query->execute();
    $payments_result = $payments_query->get_result();
    while ($row = $payments_result->fetch_assoc()) {
        $payments[] = $row;
    }
    $payments_query->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Transactions | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .customer-selector {
            margin-bottom: 30px;
        }
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
        .summary-card:nth-child(4) { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .tab:hover {
            color: #374151;
        }
        .tab.active {
            color: #FF6B35;
            border-bottom-color: #FF6B35;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
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
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C5A 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
        }
        table tr:hover {
            background-color: #f9fafb;
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
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-approved { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Customer Transactions</h2>
            <p>View all orders, invoices, and payments for a customer.</p>
            <?php showMessage(); ?>
            
            <!-- Customer Selector -->
            <div class="card customer-selector">
                <h3>Select Customer</h3>
                <form method="GET" action="">
                    <select name="customer_id" id="customer_select" style="width:100%; padding:12px; font-size:16px;" onchange="this.form.submit()">
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo $c['customer_id']; ?>" <?php echo ($customer_id == $c['customer_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['customer_name']); ?> 
                                <?php if ($c['contact_number']): ?>
                                    - <?php echo htmlspecialchars($c['contact_number']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <?php if ($selected_customer): ?>
                <!-- Customer Info -->
                <div class="card">
                    <h3>Customer Information</h3>
                    <table>
                        <tr>
                            <td style="width:150px;"><strong>Name:</strong></td>
                            <td><?php echo htmlspecialchars($selected_customer['customer_name']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Contact:</strong></td>
                            <td><?php echo htmlspecialchars($selected_customer['contact_number'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Address:</strong></td>
                            <td><?php echo htmlspecialchars($selected_customer['address'] ?? '-'); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>Total Orders</h3>
                        <p><?php echo count($orders); ?></p>
                        <small>₱<?php echo number_format($total_orders, 2); ?></small>
                    </div>
                    <div class="summary-card">
                        <h3>Total Invoiced</h3>
                        <p><?php echo count($invoices); ?></p>
                        <small>₱<?php echo number_format($total_invoiced, 2); ?></small>
                    </div>
                    <div class="summary-card">
                        <h3>Total Paid</h3>
                        <p><?php echo count($payments); ?></p>
                        <small>₱<?php echo number_format($total_paid, 2); ?></small>
                    </div>
                    <div class="summary-card">
                        <h3>Outstanding</h3>
                        <p>₱<?php echo number_format($total_outstanding, 2); ?></p>
                        <small><?php echo count(array_filter($invoices, function($inv) { return $inv['outstanding'] > 0; })); ?> unpaid</small>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="card">
                    <div class="tabs">
                        <button class="tab active" onclick="showTab('orders')">Orders (<?php echo count($orders); ?>)</button>
                        <button class="tab" onclick="showTab('invoices')">Invoices (<?php echo count($invoices); ?>)</button>
                        <button class="tab" onclick="showTab('payments')">Payments (<?php echo count($payments); ?>)</button>
                    </div>
                    
                    <!-- Orders Tab -->
                    <div id="orders" class="tab-content active">
                        <h3>Order History</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo sortHeader('order_number', 'Order #', $sort_ord, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th><?php echo sortHeader('order_date', 'Date', $sort_ord, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th><?php echo sortHeader('item_count', 'Items', $sort_ord, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th><?php echo sortHeader('total_amount', 'Amount', $sort_ord, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th><?php echo sortHeader('status', 'Status', $sort_ord, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th>Invoice</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($orders) > 0): ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                            <td><?php echo formatDate($order['order_date']); ?></td>
                                            <td><?php echo $order['item_count']; ?> item(s)</td>
                                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                    <?php echo htmlspecialchars($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($order['status'] === 'Delivered'): ?>
                                                    <?php
                                                    $inv_check = $conn->query("SELECT invoice_id, invoice_number FROM invoices WHERE order_id = " . $order['order_id']);
                                                    if ($inv_check && $inv_row = $inv_check->fetch_assoc()):
                                                    ?>
                                                        <a href="accounting_invoices.php?view=<?php echo $inv_row['invoice_id']; ?>" target="_blank">
                                                            <?php echo htmlspecialchars($inv_row['invoice_number']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <button onclick="generateInvoice(<?php echo $order['order_id']; ?>)" class="btn" style="padding:4px 12px; font-size:12px;">
                                                            Generate Invoice
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted);">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="costumer_transaction_view.php?order_id=<?php echo $order['order_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px; text-decoration:none;">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No orders found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Invoices Tab -->
                    <div id="invoices" class="tab-content">
                        <h3>Invoice History</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo sortHeader('invoice_number', 'Invoice #', $sort_inv, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th><?php echo sortHeader('invoice_date', 'Date', $sort_inv, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th><?php echo sortHeader('due_date', 'Due Date', $sort_inv, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th><?php echo sortHeader('amount', 'Amount', $sort_inv, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th>Paid</th>
                                    <th>Outstanding</th>
                                    <th><?php echo sortHeader('status', 'Status', $sort_inv, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th>Payment Terms</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($invoices) > 0): ?>
                                    <?php foreach ($invoices as $inv): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                            <td><?php echo formatDate($inv['invoice_date']); ?></td>
                                            <td><?php echo formatDate($inv['due_date']); ?></td>
                                            <td>₱<?php echo number_format($inv['amount'], 2); ?></td>
                                            <td>₱<?php echo number_format($inv['paid_amount'], 2); ?></td>
                                            <td><strong>₱<?php echo number_format($inv['outstanding'], 2); ?></strong></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($inv['status']); ?>">
                                                    <?php echo htmlspecialchars($inv['status']); ?>
                                                </span>
                                                <?php if ($inv['approval_status'] !== 'Approved'): ?>
                                                    <br><small style="color: #f59e0b;"><?php echo $inv['approval_status']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($inv['payment_terms']); ?></td>
                                            <td>
                                                <a href="api/generate_pdf.php?type=invoice&id=<?php echo $inv['invoice_id']; ?>" target="_blank" class="btn" style="padding:4px 12px; font-size:12px; text-decoration:none;">
                                                    📄 View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" style="text-align:center; padding:30px; color:var(--text-muted);">No invoices found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Payments Tab -->
                    <div id="payments" class="tab-content">
                        <h3>Payment History</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo sortHeader('payment_number', 'Payment #', $sort_pay, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th><?php echo sortHeader('payment_date', 'Date', $sort_pay, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th><?php echo sortHeader('invoice_number', 'Invoice #', $sort_pay, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th><?php echo sortHeader('payment_method', 'Method', $sort_pay, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th><?php echo sortHeader('amount', 'Amount', $sort_pay, $customer_id ? ['customer_id' => $customer_id] : []); ?></th>
                                    <th>Reference</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($payments) > 0): ?>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($payment['payment_number']); ?></strong></td>
                                            <td><?php echo formatDate($payment['payment_date']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['invoice_number']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                            <td><strong>₱<?php echo number_format($payment['amount'], 2); ?></strong></td>
                                            <td><?php echo htmlspecialchars($payment['reference_number'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($payment['notes'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No payments found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <p style="text-align:center; padding:40px; color:var(--text-muted);">
                        Please select a customer to view their transaction history.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>

<script src="assets/js/sidebar.js"></script>
<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}

function generateInvoice(orderId) {
    if (!confirm('Generate invoice for this order?')) return;
    
    // Open invoice generation modal or redirect
    window.location.href = 'accounting_invoices.php?auto_generate=' + orderId;
}
</script>
</body>
</html>
