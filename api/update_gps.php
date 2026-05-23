<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_service.php';

// Check authentication - only delivery/driver role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'delivery' && $_SESSION['role'] != 'driver')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    // Get active assignment for this driver
    if ($assignment_id <= 0) {
        $assignment_query = $conn->prepare("SELECT assignment_id FROM delivery_assignments WHERE driver_id = ? AND status IN ('Dispatched', 'On the Way', 'Arrived') ORDER BY created_at DESC LIMIT 1");
        $assignment_query->bind_param("i", $_SESSION['user_id']);
        $assignment_query->execute();
        $assignment_result = $assignment_query->get_result();
        if ($assignment_result->num_rows > 0) {
            $assignment_row = $assignment_result->fetch_assoc();
            $assignment_id = $assignment_row['assignment_id'];
        }
        $assignment_query->close();
    }

    if ($assignment_id > 0 && $latitude != 0 && $longitude != 0) {
        // Insert GPS tracking record
        $stmt = $conn->prepare("INSERT INTO gps_tracking (assignment_id, latitude, longitude) VALUES (?, ?, ?)");
        $stmt->bind_param("idd", $assignment_id, $latitude, $longitude);
        $stmt->execute();
        $stmt->close();
    }

    // Update delivery status if provided
    if (!empty($status) && $assignment_id > 0) {
        $update_stmt = $conn->prepare("UPDATE delivery_assignments SET status = ? WHERE assignment_id = ?");
        $update_stmt->bind_param("si", $status, $assignment_id);
        $update_stmt->execute();
        $update_stmt->close();

        // On Delivered: update order status and emit event (event processor handles stock + invoice)
        if ($status === 'Delivered') {
            $da = $conn->query("SELECT order_id FROM delivery_assignments WHERE assignment_id = $assignment_id")->fetch_assoc();
            if (!empty($da['order_id'])) {
                $order_id = (int)$da['order_id'];
                $order_update = $conn->prepare("UPDATE sales_orders SET status = 'Delivered' WHERE order_id = ?");
                $order_update->bind_param("i", $order_id);
                $order_update->execute();
                $order_update->close();

                $items = [];
                $items_q = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $order_id");
                if ($items_q) {
                    while ($row = $items_q->fetch_assoc()) {
                        $items[] = ['product_id' => (int)$row['product_id'], 'quantity' => (float)$row['quantity']];
                    }
                }
                emitSystemEvent($conn, 'sales_order', $order_id, 'SALES_ORDER_DELIVERED', [
                    'order_id' => $order_id,
                    'assignment_id' => $assignment_id,
                    'order_items' => $items
                ]);
            }
        }
    }

    echo json_encode(['success' => true, 'assignment_id' => $assignment_id]);
    exit;
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}
?>
