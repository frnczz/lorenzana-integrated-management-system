<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'warehouse'])) {
    header("Location: login.php");
    exit;
}

include "db_connect.php";
include "includes/functions.php";

// Determine available failed deliveries
$has_returned_flag = $conn->query("SHOW COLUMNS FROM delivery_assignments LIKE 'returned_to_inventory'")->num_rows > 0;

$failedQuery = "SELECT da.*, so.order_number, c.customer_name, c.contact_number
    FROM delivery_assignments da
    LEFT JOIN sales_orders so ON da.order_id = so.order_id
    LEFT JOIN customers c ON so.customer_id = c.customer_id
    WHERE da.status = 'Failed'";
if ($has_returned_flag) {
    $failedQuery .= " AND COALESCE(da.returned_to_inventory,0) = 0";
}
$failedQuery .= " ORDER BY da.created_at DESC";
$failedRes = $conn->query($failedQuery);
$failedDeliveries = $failedRes ? $failedRes->fetch_all(MYSQLI_ASSOC) : [];

$selectedAssignmentId = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$selectedAssignment = null;
$order_items = [];
$order = null;

if ($selectedAssignmentId > 0) {
    $stmt = $conn->prepare("SELECT da.*, so.order_number, so.order_id, so.customer_id, c.customer_name, c.contact_number
        FROM delivery_assignments da
        LEFT JOIN sales_orders so ON da.order_id = so.order_id
        LEFT JOIN customers c ON so.customer_id = c.customer_id
        WHERE da.assignment_id = ? LIMIT 1");
    $stmt->bind_param('i', $selectedAssignmentId);
    $stmt->execute();
    $selectedAssignment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($selectedAssignment && !empty($selectedAssignment['order_id'])) {
        $order = [
            'order_id' => $selectedAssignment['order_id'],
            'order_number' => $selectedAssignment['order_number'],
            'customer_name' => $selectedAssignment['customer_name'],
            'contact_number' => $selectedAssignment['contact_number']
        ];

        $oi_stmt = $conn->prepare("SELECT oi.product_id, oi.quantity, p.product_name, p.unit
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?");
        $oi_stmt->bind_param('i', $selectedAssignment['order_id']);
        $oi_stmt->execute();
        $oi_res = $oi_stmt->get_result();
        while ($row = $oi_res->fetch_assoc()) {
            $order_items[] = $row;
        }
        $oi_stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Returns | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 8px 20px rgba(0,0,0,0.06); margin-bottom: 20px; }
        .card h3 { margin-top: 0; }
        .btn { display: inline-block; padding: 10px 16px; background: #4f46e5; border: none; border-radius: 8px; color: white; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #4338ca; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #eceff4; text-align: left; }
        th { background: #f5f7ff; font-weight: 600; }
        .input-number { width: 100px; padding: 8px 10px; border-radius: 8px; border: 1px solid #d1d5db; }
        .badge {display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;}
        .badge-yes{background:#d1fae5;color:#065f46;}
        .badge-no{background:#fee2e2;color:#991b1b;}
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <div style="text-align: center; margin-bottom: 20px;">
                <?php include "layouts/logo.php"; ?>
            </div>

            <div class="card">
                <h3>Process Returned Deliveries</h3>
                <p>Select a failed delivery assignment below to load the order items and enter returned quantities.</p>
                <form method="GET" action="inventory_returns.php" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                    <label style="flex:1; min-width:280px;">
                        Return Order:
                        <select name="assignment_id" style="width:100%; padding:10px; border-radius:8px; border:1px solid #d1d5db;">
                            <option value="0">-- Select a failed delivery --</option>
                            <?php foreach ($failedDeliveries as $fd): ?>
                                <?php
                                    $label = sprintf("#%s - Order %s - %s", $fd['assignment_number'] ?? $fd['assignment_id'], $fd['order_number'] ?? 'N/A', $fd['customer_name'] ?? '');
                                    if (!empty($fd['failure_reason'])) {
                                        $label .= ' (' . $fd['failure_reason'] . ')';
                                    }
                                ?>
                                <option value="<?php echo (int)$fd['assignment_id']; ?>" <?php echo ((int)$fd['assignment_id'] === $selectedAssignmentId) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" class="btn">Load Order</button>
                    <a href="inventory_return.php" class="btn" style="background:#10b981;">Quick Return Form</a>
                </form>
            </div>

            <?php if ($selectedAssignment && $order): ?>
                <div class="card">
                    <h3>Return Items for Order <?php echo htmlspecialchars($order['order_number']); ?></h3>
                    <p>
                        Customer: <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                        <?php if (!empty($order['contact_number'])): ?>
                            (<?php echo htmlspecialchars($order['contact_number']); ?>)
                        <?php endif; ?>
                    </p>
                    <form method="POST" action="process_inventory_return.php">
                        <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>">
                        <input type="hidden" name="assignment_id" value="<?php echo (int)$selectedAssignmentId; ?>">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Ordered Qty</th>
                                    <th>Qty Returned</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item):
                                    $pid = (int)$item['product_id'];
                                    $orderedQty = (float)$item['quantity'];
                                    $unit = $item['unit'] ?? 'pcs';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo number_format($orderedQty, 2); ?> <?php echo htmlspecialchars($unit); ?></td>
                                    <td>
                                        <input type="number" name="qty[<?php echo $pid; ?>]" value="<?php echo number_format($orderedQty, 2); ?>" min="0" max="<?php echo $orderedQty; ?>" step="0.01" class="input-number">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div style="margin-top:18px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                            <button type="submit" class="btn">Save Returned Inventory</button>
                            <a href="inventory_returns.php" class="btn" style="background:#6b7280;">Clear Selection</a>
                        </div>
                    </form>
                </div>
            <?php elseif ($selectedAssignmentId > 0): ?>
                <div class="card" style="background:#fef2f2; border:1px solid #fecaca;">
                    <p style="margin:0; font-weight:600; color:#991b1b;">No matching failed delivery found for that selection.</p>
                </div>
            <?php endif; ?>

            <div class="card">
                <h3>Failed Deliveries</h3>
                <p style="margin-top:0; margin-bottom:12px; color:#475569;">These are deliveries marked <strong>Failed</strong>. Select one above to process a return.</p>
                <table>
                    <thead>
                        <tr>
                            <th>Assignment</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Failure Reason</th>
                            <th>Returned</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($failedDeliveries)): ?>
                            <tr><td colspan="7" style="text-align:center; padding: 18px; color:#6b7280;">No failed deliveries found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($failedDeliveries as $fd): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fd['assignment_number'] ?? $fd['assignment_id']); ?></td>
                                    <td><?php echo htmlspecialchars($fd['order_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($fd['customer_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fd['failure_reason'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($has_returned_flag && isset($fd['returned_to_inventory']) && $fd['returned_to_inventory']): ?>
                                            <span class="badge badge-yes">Yes</span>
                                        <?php else: ?>
                                            <span class="badge badge-no">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($fd['created_at'] ?? ''); ?></td>
                                    <td><a class="btn" href="inventory_returns.php?assignment_id=<?php echo (int)$fd['assignment_id']; ?>">Return</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
</body>
</html>
