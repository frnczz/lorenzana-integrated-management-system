<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'sales'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit;
}

$order_id = intval($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit;
}

$has_coords = @$conn->query("SHOW COLUMNS FROM sales_orders LIKE 'delivery_lat'")->num_rows > 0;
if (!$has_coords) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT delivery_lat, delivery_lng FROM sales_orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

header('Content-Type: application/json');
// Treat 0,0 as invalid/missing coordinates (common default placeholder), and only return when we have meaningful values.
if ($row 
    && isset($row['delivery_lat'], $row['delivery_lng'])
    && is_numeric($row['delivery_lat'])
    && is_numeric($row['delivery_lng'])
    && abs((float)$row['delivery_lat']) > 0.000001
    && abs((float)$row['delivery_lng']) > 0.000001
) {
    echo json_encode(['success' => true, 'lat' => $row['delivery_lat'], 'lng' => $row['delivery_lng']]);
} else {
    echo json_encode(['success' => false]);
}
