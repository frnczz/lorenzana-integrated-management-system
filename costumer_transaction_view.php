<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}

include "includes/functions.php";
include "db_connect.php";

$order_id = intval($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    header("Location: customers_transactions.php");
    exit;
}

// If the failure_reason column doesn't exist, fall back to NULL so the page doesn't crash.
$has_failure_reason_col = $conn->query("SHOW COLUMNS FROM delivery_assignments LIKE 'failure_reason'")->num_rows > 0;
$failure_reason_select = $has_failure_reason_col ? "(SELECT da.failure_reason FROM delivery_assignments da WHERE da.order_id = so.order_id ORDER BY da.created_at DESC LIMIT 1) AS failure_reason," : "NULL AS failure_reason,";

$order_query = $conn->prepare(
    "SELECT so.*, c.customer_name, c.contact_number, c.address,
        (SELECT da.assignment_id FROM delivery_assignments da WHERE da.order_id = so.order_id ORDER BY da.created_at DESC LIMIT 1) AS assignment_id,
        (SELECT da.proof_of_delivery FROM delivery_assignments da WHERE da.order_id = so.order_id ORDER BY da.created_at DESC LIMIT 1) AS proof_of_delivery,
        {$failure_reason_select}
        (SELECT da.status FROM delivery_assignments da WHERE da.order_id = so.order_id ORDER BY da.created_at DESC LIMIT 1) AS delivery_status,
        (SELECT u.full_name FROM delivery_assignments da
            JOIN users u ON da.driver_id = u.id
            WHERE da.order_id = so.order_id
            ORDER BY da.created_at DESC LIMIT 1
        ) AS courier_name
     FROM sales_orders so
     LEFT JOIN customers c ON so.customer_id = c.customer_id
     WHERE so.order_id = ?"
);
$order_query->bind_param("i", $order_id);
$order_query->execute();
$order = $order_query->get_result()->fetch_assoc();
$order_query->close();

if (!$order) {
    header("Location: customers_transactions.php");
    exit;
}

// Fetch order items
$items_query = $conn->prepare(
    "SELECT oi.*, p.product_name, p.unit
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.product_id
     WHERE oi.order_id = ?"
);
$items_query->bind_param("i", $order_id);
$items_query->execute();
$items_result = $items_query->get_result();
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
$items_query->close();

// Fallback totals
$total_items = 0;
$total_subtotal = 0;
foreach ($items as $item) {
    $total_items += (float)$item['quantity'];
    $total_subtotal += (float)$item['subtotal'];
}

$customer_id = intval($order['customer_id'] ?? 0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order View | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
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
        .btn {
            display: inline-block;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            background: #3b82f6;
            color: white;
            font-weight: 600;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-top: 18px;
        }
        .card-box {
            background: #f9fafb;
            border-radius: 10px;
            padding: 18px;
            border: 1px solid #e5e7eb;
        }
        .card-box h4 {
            margin: 0 0 10px 0;
            font-size: 0.95rem;
            color: #374151;
            font-weight: 600;
        }
        .card-box p {
            margin: 0;
            color: #4b5563;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px;">
                <div>
                    <h2>Order Details</h2>
                    <p style="margin:0; color:var(--text-muted);">Order <strong><?php echo htmlspecialchars($order['order_number']); ?></strong> for <strong><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></strong></p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="customers_transactions.php?customer_id=<?php echo $customer_id; ?>" class="btn">Back to Customer</a>
                    <a href="sales_dashboard.php" class="btn" style="background:#10b981;">Dashboard</a>
                </div>
            </div>

            <?php showMessage(); ?>

            <div class="card-grid">
                <div class="card-box">
                    <h4>Customer</h4>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['contact_number'] ?? '-'); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address'] ?? '-'); ?></p>
                </div>
                <div class="card-box">
                    <h4>Order Info</h4>
                    <p><strong>Status:</strong> <span class="status-badge" style="background: <?php echo $order['status'] === 'Delivered' ? 'rgba(16, 185, 129, 0.1)' : ($order['status'] === 'Dispatched' ? 'rgba(59, 130, 246, 0.1)' : ($order['status'] === 'Confirmed' ? 'rgba(255, 107, 53, 0.1)' : 'rgba(107, 114, 128, 0.1)')); ?>; color: <?php echo $order['status'] === 'Delivered' ? '#10b981' : ($order['status'] === 'Dispatched' ? '#3b82f6' : ($order['status'] === 'Confirmed' ? '#FF6B35' : '#6b7280')); ?>;"><?php echo htmlspecialchars($order['status']); ?></span></p>
                    <p><strong>Order Date:</strong> <?php echo formatDate($order['order_date']); ?></p>
                    <p><strong>Delivery Date:</strong> <?php echo formatDate($order['delivery_date'] ?? null); ?></p>
                    <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address'] ?? '-'); ?></p>
                </div>
                    <div class="card-box">
                    <h4>Delivery</h4>
                    <input type="hidden" id="assignmentId" value="<?php echo (int)($order['assignment_id'] ?? 0); ?>" />
                    <p><strong>Courier:</strong> <?php echo htmlspecialchars($order['courier_name'] ?? '-'); ?></p>
                    <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($order['vehicle_info'] ?? '-'); ?></p>
                    <p><strong>Status:</strong> <span id="currentStatus"><?php echo htmlspecialchars($order['delivery_status'] ?? $order['status'] ?? '-'); ?></span></p>
                    <?php if (!empty($order['failure_reason'])): ?>
                        <p><strong>Failure Reason:</strong> <?php echo htmlspecialchars($order['failure_reason']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="card-box">
                    <h4>Proof of Delivery</h4>
                    <?php if (!empty($order['proof_of_delivery'])): ?>
                        <a href="<?php echo htmlspecialchars($order['proof_of_delivery']); ?>" target="_blank" style="display:inline-block;">
                            <img src="<?php echo htmlspecialchars($order['proof_of_delivery']); ?>" alt="Proof of Delivery" style="max-width:100%; max-height:140px; border-radius:8px; border:1px solid var(--border-color);">
                        </a>
                        <p style="margin-top: 8px; color: var(--text-muted); font-size: 12px;">Click to view full proof of delivery.</p>
                    <?php else: ?>
                        <p style="color: var(--text-muted);">No photo uploaded.</p>
                    <?php endif; ?>
                </div>
                <div class="card-box">
                    <h4>Totals</h4>
                    <p><strong>Items:</strong> <?php echo count($items); ?></p>
                    <p><strong>Quantity:</strong> <?php echo number_format($total_items, 2); ?></p>
                    <p><strong>Order Total:</strong> <?php echo formatCurrency($order['total_amount']); ?></p>
                </div>
            </div>

            <div class="card" id="failedReasonCard" style="<?php echo (isset($order['delivery_status']) && strtolower($order['delivery_status']) === 'failed') ? '' : 'display:none;'; ?>">
                <h3>Failure Reason</h3>
                <p style="margin-bottom: 12px; color: var(--text-muted);">Select or update the reason the delivery failed.</p>
                <select id="failureReasonSelect" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 12px;">
                    <?php
                    $failure_reasons = [
                        'Customer unavailable',
                        'Wrong address',
                        'Access denied / locked gate',
                        'Vehicle breakdown',
                        'Weather conditions',
                        'Damaged goods found',
                        'Customer refused delivery',
                        'Incorrect order details',
                        'Delivery delay',
                        'Other'
                    ];
                    $current_reason = trim((string)($order['failure_reason'] ?? ''));
                    foreach ($failure_reasons as $reason):
                    ?>
                    <option value="<?php echo htmlspecialchars($reason); ?>" <?php echo ($current_reason === $reason) ? 'selected' : ''; ?>><?php echo htmlspecialchars($reason); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" style="width: 100%;" onclick="saveFailureReason()">Save Reason</button>
                <p id="failureReasonStatus" style="margin-top: 10px; color: var(--text-muted); font-size: 13px;"></p>
            </div>

            <div class="card">
                <h3>Purchased Items</h3>
                <?php if (count($items) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($item['quantity'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit'] ?? '-'); ?></td>
                                    <td><?php echo formatCurrency($item['unit_price']); ?></td>
                                    <td><?php echo formatCurrency($item['subtotal']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align:right; font-weight:600;">Total</td>
                                <td style="font-weight:600;"><?php echo formatCurrency($total_subtotal); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No items found for this order.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>

<script>
function saveFailureReason() {
    const assignmentId = document.getElementById('assignmentId')?.value || 0;
    const reason = document.getElementById('failureReasonSelect')?.value || '';
    if (!assignmentId) return;

    const formData = new FormData();
    formData.append('assignment_id', assignmentId);
    formData.append('status', 'Failed');
    formData.append('failure_reason', reason);

    fetch('api/update_gps.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            const msgEl = document.getElementById('failureReasonStatus');
            if (data.success) {
                msgEl.textContent = 'Saved.';
                setTimeout(() => { msgEl.textContent = ''; }, 3000);
            } else {
                msgEl.textContent = 'Error: ' + (data.error || 'Unknown');
            }
        })
        .catch(() => {
            document.getElementById('failureReasonStatus').textContent = 'Error saving.';
        });
}
</script>
<script src="assets/js/sidebar.js"></script>
</body>
</html>
