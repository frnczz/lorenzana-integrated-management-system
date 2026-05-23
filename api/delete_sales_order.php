<?php
ob_start();
header('Content-Type: application/json');
session_start();
@include "../db_connect.php";
ob_end_clean();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order id']);
    exit;
}

$conn->begin_transaction();
try {
    // Delete dependent records first
    $del_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $del_items->bind_param('i', $order_id);
    $del_items->execute();
    $del_items->close();

    $del_assign = $conn->prepare("DELETE FROM delivery_assignments WHERE order_id = ?");
    $del_assign->bind_param('i', $order_id);
    $del_assign->execute();
    $del_assign->close();

    $del = $conn->prepare("DELETE FROM sales_orders WHERE order_id = ?");
    $del->bind_param('i', $order_id);
    $del->execute();
    $affected = $del->affected_rows;
    $del->close();

    if ($affected === 0) {
        throw new Exception('Order not found or could not be deleted.');
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
