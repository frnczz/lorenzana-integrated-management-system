<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_service.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    header("Location: ../login.php");
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0);
if ($order_id <= 0) {
    $_SESSION['error'] = "Invalid order.";
    header("Location: ../sales.php");
    exit;
}

$conn->begin_transaction();
try {
    $order = $conn->query("SELECT order_id, order_number, status FROM sales_orders WHERE order_id = $order_id")->fetch_assoc();
    if (!$order) {
        throw new Exception("Order not found.");
    }
    if ($order['status'] === 'Delivered') {
        throw new Exception("Cannot delete a delivered order.");
    }

    $items = [];
    $items_q = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $order_id");
    while ($row = $items_q->fetch_assoc()) {
        $items[] = ['product_id' => (int)$row['product_id'], 'quantity' => (float)$row['quantity']];
    }
    if (!empty($items)) {
        try {
            processInventoryEvent($conn, 'SALES_RELEASE', ['items' => $items]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    $conn->query("UPDATE sales_orders SET status = 'Cancelled' WHERE order_id = $order_id");
    if (function_exists('logActivity')) {
        logActivity($conn, (int)$_SESSION['user_id'], 'cancel', 'order', $order_id, "Order " . $order['order_number'] . " cancelled");
    }
    $conn->commit();
    $_SESSION['success'] = "Order " . $order['order_number'] . " has been cancelled and stock reservation released.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
}
header("Location: ../sales.php");
exit;
