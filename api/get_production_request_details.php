<?php
// api/get_production_request_details.php
include '../db_connect.php';
header('Content-Type: application/json');

$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
if (!$request_id) {
    echo json_encode(['success' => false, 'error' => 'Missing request_id']);
    exit;
}

// Get production request details
$sql = "SELECT pr.request_id, pr.customer_name, pr.product_id, pr.qty_requested, pr.status, p.product_name, p.fermentation_eligible, p.image_path, p.category_id, COALESCE(p.shelf_life_days, 365) as shelf_life_days
        FROM production_requests pr
        JOIN products p ON pr.product_id = p.product_id
        WHERE pr.request_id = $request_id";
$res = $conn->query($sql);
if (!$res || $res->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Request not found']);
    exit;
}
$row = $res->fetch_assoc();

// Optionally, you can fetch more lines if your system supports multiple products per request

// Return details
$data = [
    'request_id' => $row['request_id'],
    'customer_name' => $row['customer_name'],
    'product_id' => $row['product_id'],
    'qty_requested' => $row['qty_requested'],
    'status' => $row['status'],
    'product_name' => $row['product_name'],
    'fermentation_eligible' => $row['fermentation_eligible'],
    'image_path' => $row['image_path'],
    'category_id' => $row['category_id'],
    'shelf_life_days' => $row['shelf_life_days']
];

echo json_encode(['success' => true, 'data' => $data]);
