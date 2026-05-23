<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/inventory_service.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../sales.php");
    exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
$new_status = $_POST['status'] ?? '';

if ($order_id <= 0 || empty($new_status)) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../sales.php");
    exit;
}

$conn->begin_transaction();

try {

    // 1️⃣ Get order details
    $stmt = $conn->prepare("
        SELECT status, fulfillment_type 
        FROM sales_orders 
        WHERE order_id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        throw new Exception("Order not found.");
    }

    $current_status = $order['status'];
    $fulfillment_type = $order['fulfillment_type'];

    // 2️⃣ Prevent double fulfillment
    if (in_array($current_status, ['Delivered', 'Picked Up'])) {
        throw new Exception("Order already completed.");
    }

    // 3️⃣ Update status
    $upd = $conn->prepare("
        UPDATE sales_orders 
        SET status = ? 
        WHERE order_id = ?
    ");
    $upd->bind_param("si", $new_status, $order_id);
    $upd->execute();
    $upd->close();

    // 4️⃣ If final status → DEDUCT STOCK
    $is_delivery_complete = ($fulfillment_type === 'Delivery' && $new_status === 'Delivered');
    $is_pickup_complete   = ($fulfillment_type === 'Pickup' && $new_status === 'Picked Up');

    if ($is_delivery_complete || $is_pickup_complete) {

        // Get reserved items
        $items = [];
        $q = $conn->prepare("
            SELECT product_id, quantity 
            FROM order_items 
            WHERE order_id = ? AND reserved = 1
        ");
        $q->bind_param("i", $order_id);
        $q->execute();
        $result = $q->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'product_id' => (int)$row['product_id'],
                'quantity'   => (float)$row['quantity']
            ];
        }
        $q->close();

        if (!empty($items)) {

            // 🔥 Deduct permanently
            processInventoryEvent($conn, 'SALES_FULFILL', [
                'items' => $items
            ]);

            // Remove reservation flag
            $clear = $conn->prepare("
                UPDATE order_items 
                SET reserved = 0 
                WHERE order_id = ?
            ");
            $clear->bind_param("i", $order_id);
            $clear->execute();
            $clear->close();
        }
    }

    $conn->commit();
    $_SESSION['success'] = "Order status updated successfully.";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
}

header("Location: ../sales.php");
exit;