<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'warehouse', 'delivery', 'driver'])) {
    header("Location: login.php");
    exit;
}

include "db_connect.php";
include "includes/functions.php";

// Basic validation
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    die('Invalid or missing order ID.');
}

// Fetch order details
$order_stmt = $conn->prepare("SELECT so.*, c.customer_name, c.contact_number FROM sales_orders so LEFT JOIN customers c ON so.customer_id = c.customer_id WHERE so.order_id = ? LIMIT 1");
$order_stmt->bind_param('i', $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result ? $order_result->fetch_assoc() : null;
$order_stmt->close();

if (!$order) {
    die('Order not found.');
}

// Fetch delivery assignment (failed) for this order if exists
$assignment = null;
$assignment_stmt = $conn->prepare("SELECT * FROM delivery_assignments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
$assignment_stmt->bind_param('i', $order_id);
$assignment_stmt->execute();
$assignment_result = $assignment_stmt->get_result();
if ($assignment_result) {
    $assignment = $assignment_result->fetch_assoc();
}
$assignment_stmt->close();

// Fetch order items
$item_stmt = $conn->prepare("SELECT oi.product_id, oi.quantity, p.product_name, p.unit FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?");
$item_stmt->bind_param('i', $order_id);
$item_stmt->execute();
$item_result = $item_stmt->get_result();
$order_items = [];
while ($row = $item_result->fetch_assoc()) {
    $order_items[] = $row;
}
$item_stmt->close();

// Prepare failure indication message
$failed_message = '';
if ($assignment && isset($assignment['status']) && strtolower($assignment['status']) === 'failed') {
    $failed_message = 'This delivery has been marked as Failed. Please accept returned items into inventory.';
} elseif ($assignment && isset($assignment['status'])) {
    $failed_message = 'Delivery status is currently: ' . htmlspecialchars($assignment['status']) . '. If you are returning items, please proceed below.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return Inventory | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }
        .card h3 {
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eceff4;
            text-align: left;
        }
        table th {
            background: #f5f7ff;
            font-weight: 600;
        }
        .btn {
            display: inline-block;
            padding: 10px 16px;
            background: #4f46e5;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover {
            background: #4338ca;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            background: #fef3c7;
            color: #92400e;
            margin-bottom: 16px;
            border: 1px solid #fcd34d;
        }
        .input-number {
            width: 100px;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
        }
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
                <h3>Return Failed Delivery Items to Inventory</h3>
                <?php if ($failed_message): ?>
                    <div class="alert"><?php echo $failed_message; ?></div>
                <?php endif; ?>
                <p>
                    Order <strong><?php echo htmlspecialchars($order['order_number'] ?? ('#' . $order_id)); ?></strong> for
                    <strong><?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown Customer'); ?></strong>
                </p>

                <?php if (empty($order_items)): ?>
                    <p>No items found for this order.</p>
                <?php else: ?>
                    <form method="POST" action="process_inventory_return.php">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        <?php if (!empty($assignment['assignment_id'])): ?>
                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['assignment_id']; ?>">
                        <?php endif; ?>
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
                                $productId = (int)$item['product_id'];
                                $orderedQty = (float)$item['quantity'];
                                $defaultQty = $orderedQty;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo number_format($orderedQty, 2); ?> <?php echo htmlspecialchars($item['unit'] ?? 'pcs'); ?></td>
                                    <td>
                                        <input class="input-number" type="number" min="0" max="<?php echo $orderedQty; ?>" step="0.01"
                                               name="qty[<?php echo $productId; ?>]" value="<?php echo $defaultQty; ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div style="margin-top: 20px; display: flex; gap: 12px; align-items: center;">
                            <button type="submit" class="btn">Return to Inventory</button>
                            <a href="driver_gps.php" class="btn" style="background:#6b7280;">Back to Delivery</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
</body>
</html>
