<?php
session_start();
header('Content-Type: application/json');
include "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$has_order_items = @$conn->query("SHOW TABLES LIKE 'order_items'")->num_rows > 0;
$has_vehicle_type = @$conn->query("SHOW COLUMNS FROM users LIKE 'vehicle_type'")->num_rows > 0;
$vehicle_col = $has_vehicle_type ? 'COALESCE(NULLIF(TRIM(u.vehicle_type), ""), da.vehicle_info)' : 'da.vehicle_info';

if ($has_order_items) {
    $active = $conn->query("
        SELECT 
            da.assignment_id, 
            da.order_id, 
            da.status AS assignment_status,
            da.vehicle_info,
            so.order_number,
            so.delivery_address,
            so.delivery_date,
            u.full_name AS driver_name, 
            u.id AS driver_id,
            " . $vehicle_col . " AS vehicle_display,
            (SELECT latitude FROM gps_tracking WHERE assignment_id = da.assignment_id ORDER BY timestamp DESC LIMIT 1) AS lat,
            (SELECT longitude FROM gps_tracking WHERE assignment_id = da.assignment_id ORDER BY timestamp DESC LIMIT 1) AS lng,
            (SELECT timestamp FROM gps_tracking WHERE assignment_id = da.assignment_id ORDER BY timestamp DESC LIMIT 1) AS last_update,
            (SELECT GROUP_CONCAT(CONCAT(p.product_name, ' x ', oi.quantity) SEPARATOR ', ')
             FROM order_items oi 
             JOIN products p ON oi.product_id = p.product_id 
             WHERE oi.order_id = da.order_id) AS products_display
        FROM delivery_assignments da
        JOIN sales_orders so ON so.order_id = da.order_id
        JOIN users u ON u.id = da.driver_id
        WHERE da.status IN ('Dispatched', 'On the Way', 'Arrived')
        ORDER BY da.dispatch_time DESC
    ");
} else {
    $active = $conn->query("
        SELECT da.assignment_id, da.order_id, da.status AS assignment_status,
               da.vehicle_info,
               so.order_number,
               so.delivery_address,
               so.delivery_date,
               u.full_name AS driver_name, u.id AS driver_id,
               " . $vehicle_col . " AS vehicle_display,
               (SELECT latitude FROM gps_tracking WHERE assignment_id = da.assignment_id ORDER BY timestamp DESC LIMIT 1) AS lat,
               (SELECT longitude FROM gps_tracking WHERE assignment_id = da.assignment_id ORDER BY timestamp DESC LIMIT 1) AS lng,
               (SELECT timestamp FROM gps_tracking WHERE assignment_id = da.assignment_id ORDER BY timestamp DESC LIMIT 1) AS last_update,
               NULL AS products_display
        FROM delivery_assignments da
        JOIN sales_orders so ON so.order_id = da.order_id
        JOIN users u ON u.id = da.driver_id
        WHERE da.status IN ('Dispatched', 'On the Way', 'Arrived')
        ORDER BY da.dispatch_time DESC
    ");
}
$list = [];
while ($row = $active->fetch_assoc()) {
    $list[] = [
        'assignment_id' => (int)$row['assignment_id'],
        'order_id' => (int)$row['order_id'],
        'order_number' => $row['order_number'],
        'driver_id' => isset($row['driver_id']) ? (int)$row['driver_id'] : null,
        'driver_name' => $row['driver_name'],
        'vehicle' => $row['vehicle_display'] ?? $row['vehicle_info'] ?? null,
        'status' => $row['assignment_status'],
        'delivery_address' => $row['delivery_address'],
        'delivery_date' => $row['delivery_date'],
        'products' => $row['products_display'],
        'lat' => $row['lat'] ? (float)$row['lat'] : null,
        'lng' => $row['lng'] ? (float)$row['lng'] : null,
        'last_update' => $row['last_update'],
    ];
}
echo json_encode(['deliveries' => $list]);
