<?php
session_start();
include "../db_connect.php";

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$supplier_id = intval($_GET['supplier_id'] ?? 0);

if ($supplier_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid supplier ID']);
    exit;
}

$pos_query = $conn->prepare("
    SELECT po_id, po_number, total_amount
    FROM purchase_orders
    WHERE supplier_id = ? AND status IN ('Received', 'Partially Received')
    ORDER BY order_date DESC
    LIMIT 20
");
$pos_query->bind_param("i", $supplier_id);
$pos_query->execute();
$pos_result = $pos_query->get_result();
$pos = [];

while ($row = $pos_result->fetch_assoc()) {
    $pos[] = [
        'po_id' => (int)$row['po_id'],
        'po_number' => $row['po_number'],
        'total_amount' => (float)$row['total_amount']
    ];
}
$pos_query->close();

echo json_encode(['success' => true, 'pos' => $pos]);
?>
