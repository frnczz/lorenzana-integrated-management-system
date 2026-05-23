<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','sales'])) {
    header("Location: login.php");
    exit;
}

include "db_connect.php";

$assignment_id = intval($_GET['assignment_id'] ?? 0);
if ($assignment_id <= 0) {
    echo "<h2>Invalid delivery receipt request.</h2>";
    exit;
}

$stmt = $conn->prepare(
    "SELECT 
        da.assignment_id,
        da.order_id,
        da.driver_id,
        da.dispatch_time,
        da.vehicle_info,
        so.order_number,
        so.delivery_address,
        c.customer_name,
        c.address AS customer_address,
        u.full_name AS driver_name
    FROM delivery_assignments da
    LEFT JOIN sales_orders so ON da.order_id = so.order_id
    LEFT JOIN customers c ON so.customer_id = c.customer_id
    LEFT JOIN users u ON da.driver_id = u.id
    WHERE da.assignment_id = ?"
);
$stmt->bind_param('i', $assignment_id);
$stmt->execute();
$delivery = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$delivery) {
    echo "<h2>Delivery assignment not found.</h2>";
    exit;
}

// Determine delivery address (override if set, else customer address)
$delivery_address = trim($delivery['delivery_address'] ?? '');
if ($delivery_address === '') {
    $delivery_address = trim($delivery['customer_address'] ?? '');
}

// Load items for the order
$items = [];
$items_stmt = $conn->prepare(
    "SELECT oi.product_id, oi.quantity, p.product_name
     FROM order_items oi
     JOIN products p ON oi.product_id = p.product_id
     WHERE oi.order_id = ?"
);
$items_stmt->bind_param('i', $delivery['order_id']);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
while ($row = $items_result->fetch_assoc()) {
    $items[] = [
        'product_name' => $row['product_name'],
        'quantity' => $row['quantity']
    ];
}
$items_stmt->close();

$deliveredAt = $delivery['dispatch_time'] ? date('F j, Y g:i A', strtotime($delivery['dispatch_time'])) : '';
$driverName = $delivery['driver_name'] ?: 'N/A';
$orderNumber = $delivery['order_number'] ?: 'N/A';
$customerName = $delivery['customer_name'] ?: 'N/A';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Receipt - <?php echo htmlspecialchars($orderNumber); ?></title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            color: #111827;
        }
        .receipt {
            max-width: 800px;
            margin: 32px auto;
            padding: 24px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        h1 {
            margin: 0 0 12px;
            font-size: 22px;
        }
        .meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin: 18px 0;
        }
        .meta div {
            background: #f9fafb;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }
        .meta label {
            display: block;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .meta .value {
            font-weight: 600;
            font-size: 15px;
            color: #111827;
        }
        .items {
            margin-top: 20px;
        }
        .items table {
            width: 100%;
            border-collapse: collapse;
        }
        .items th, .items td {
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            text-align: left;
        }
        .items th {
            background: #f3f4f6;
            font-weight: 700;
        }
        .signature {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-top: 30px;
        }
        .signature div {
            border-top: 1px solid #d1d5db;
            padding-top: 10px;
            font-size: 13px;
            color: #6b7280;
        }
        .print-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 18px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            background: #4f46e5;
            border-radius: 999px;
            color: #ffffff;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #4338ca;
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #111827;
        }
        .btn-secondary:hover {
            background: #d1d5db;
        }

        @media print {
            body { background: white; }
            .receipt { box-shadow: none; margin: 0; }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="print-actions">
        <button class="btn" onclick="window.print();">Print / Save PDF</button>
        <a class="btn btn-secondary" href="sales_delivery.php">Back</a>
    </div>

    <h1>Delivery Receipt</h1>

    <div class="meta">
        <div>
            <label>Order</label>
            <div class="value"><?php echo htmlspecialchars($orderNumber); ?></div>
        </div>
        <div>
            <label>Customer</label>
            <div class="value"><?php echo htmlspecialchars($customerName); ?></div>
        </div>
        <div>
            <label>Driver</label>
            <div class="value"><?php echo htmlspecialchars($driverName); ?></div>
        </div>
        <div>
            <label>Delivered at</label>
            <div class="value"><?php echo htmlspecialchars($deliveredAt); ?></div>
        </div>
        <div style="grid-column: 1 / -1;">
            <label>Delivery Address</label>
            <div class="value"><?php echo nl2br(htmlspecialchars($delivery_address)); ?></div>
        </div>
    </div>

    <div class="items">
        <h2 style="margin:0 0 12px; font-size:18px;">Items Delivered</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 70%;">Item</th>
                    <th style="width: 30%;">Qty</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" style="text-align:center;">No items found for this order.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="signature">
        <div>
            <div style="font-weight:600; color:#111827;">Customer / Recipient Signature</div>
            <div style="padding-top: 18px;">________________________________________</div>
            <div style="margin-top:6px; color:#6b7280;">Printed Name: ___________________________</div>
        </div>
        <div>
            <div style="font-weight:600; color:#111827;">Driver Signature</div>
            <div style="padding-top: 18px;">________________________________________</div>
            <div style="margin-top:6px; color:#6b7280;">Printed Name: ___________________________</div>
        </div>
    </div>
</div>
</body>
</html>
