<?php
session_start();
include "../db_connect.php";

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','sales'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

// Get order and customer info
$order_query = $conn->prepare("
    SELECT so.order_id, so.order_number, so.customer_id, so.order_date, so.status, so.fulfillment_type,
           so.total_amount, so.delivery_address, so.delivery_date,
           c.customer_name, c.contact_number, c.address AS customer_address
    FROM sales_orders so
    LEFT JOIN customers c ON so.customer_id = c.customer_id
    WHERE so.order_id = ?
");
$order_query->bind_param("i", $order_id);
$order_query->execute();
$order = $order_query->get_result()->fetch_assoc();
$order_query->close();

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Get order items
$items_query = $conn->prepare("
    SELECT oi.product_id, oi.quantity, p.product_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$items_query->bind_param("i", $order_id);
$items_query->execute();
$items_result = $items_query->get_result();
$items = [];

while ($row = $items_result->fetch_assoc()) {
    $items[] = [
        'product_id' => (int)$row['product_id'],
        'product_name' => $row['product_name'],
        'quantity' => (float)$row['quantity']
    ];
}
$items_query->close();

echo json_encode([
    'success' => true,
    'order_id' => (int)$order['order_id'],
    'order_number' => $order['order_number'] ?? '',
    'order_date' => $order['order_date'] ?? null,
    'status' => $order['status'] ?? '',
    'fulfillment_type' => $order['fulfillment_type'] ?? '',
    'total_amount' => isset($order['total_amount']) ? (float)$order['total_amount'] : 0,
    'customer_id' => (int)$order['customer_id'],
    'customer_name' => $order['customer_name'],
    'contact_number' => $order['contact_number'] ?? null,
    'delivery_address' => $order['delivery_address'] ?? null,
    'customer_address' => $order['customer_address'] ?? null,
    'delivery_date' => $order['delivery_date'] ?? null,
    'items' => $items
]);
?>
