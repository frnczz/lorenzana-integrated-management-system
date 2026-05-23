<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_service.php';

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    header("Location: ../login.php");
    exit;
}

// Helper: fulfill any reserved items for an order (consume stock immediately)
function fulfillReservedOrderItems($conn, $order_id, $user_id) {
    $items = [];
    $q = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ? AND reserved = 1");
    if (!$q) return;
    $q->bind_param("i", $order_id);
    $q->execute();
    $result = $q->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'product_id' => (int)$row['product_id'],
            'quantity' => (float)$row['quantity']
        ];
    }
    $q->close();

    if (empty($items)) {
        return;
    }

    // Consume reserved stock and remove the reserved flag so it won't be fulfilled again.
    processInventoryEvent($conn, 'SALES_FULFILL', [
        'items' => $items,
        'order_id' => $order_id,
        'created_by' => $user_id
    ]);

    $clear = $conn->prepare("UPDATE order_items SET reserved = 0 WHERE order_id = ?");
    if ($clear) {
        $clear->bind_param("i", $order_id);
        $clear->execute();
        $clear->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support assigning one or many orders in a single run
    $order_ids = [];
    if (isset($_POST['order_ids']) && is_array($_POST['order_ids'])) {
        foreach ($_POST['order_ids'] as $oid) {
            $oid = intval($oid);
            if ($oid > 0) $order_ids[] = $oid;
        }
        $order_ids = array_values(array_unique($order_ids));
    } else {
        $single = intval($_POST['order_id'] ?? 0);
        if ($single > 0) $order_ids[] = $single;
    }

    $driver_id = intval($_POST['driver_id'] ?? 0);
    $vehicle_info = trim($_POST['vehicle_info'] ?? '');
    $dispatch_time = $_POST['dispatch_time'] ?? date('Y-m-d H:i:s');
    $delivery_address_override = trim($_POST['delivery_address_override'] ?? '');
    $delivery_date_override = $_POST['delivery_date_override'] ?? '';
    $delivery_lat = isset($_POST['delivery_lat']) && $_POST['delivery_lat'] !== '' ? (float)$_POST['delivery_lat'] : null;
    $delivery_lng = isset($_POST['delivery_lng']) && $_POST['delivery_lng'] !== '' ? (float)$_POST['delivery_lng'] : null;

    // Validate required fields
    if (empty($order_ids) || $driver_id <= 0) {
        $_SESSION['error'] = "At least one order and a delivery person are required.";
        header("Location: ../sales_delivery.php");
        exit;
    }

    $has_coords = @$conn->query("SHOW COLUMNS FROM sales_orders LIKE 'delivery_lat'")->num_rows > 0;

    $updated_count = 0;
    foreach ($order_ids as $order_id) {
        // If user provided an override delivery address or date, update the sales order
        if ($delivery_address_override !== '') {
            $upd_addr = $conn->prepare("UPDATE sales_orders SET delivery_address = ? WHERE order_id = ?");
            if ($upd_addr) {
                $upd_addr->bind_param("si", $delivery_address_override, $order_id);
                $upd_addr->execute();
                $upd_addr->close();
            }
        }
        if (!empty($delivery_date_override)) {
            $upd_date = $conn->prepare("UPDATE sales_orders SET delivery_date = ? WHERE order_id = ?");
            if ($upd_date) {
                $upd_date->bind_param("si", $delivery_date_override, $order_id);
                $upd_date->execute();
                $upd_date->close();
            }
        }
        if ($has_coords && ($delivery_lat !== null || $delivery_lng !== null)) {
            $upd_coords = $conn->prepare("UPDATE sales_orders SET delivery_lat = ?, delivery_lng = ? WHERE order_id = ?");
            if ($upd_coords) {
                $upd_coords->bind_param("ddi", $delivery_lat, $delivery_lng, $order_id);
                $upd_coords->execute();
                $upd_coords->close();
            }
        }

        // Check if assignment already exists for this order
        $check_stmt = $conn->prepare("SELECT assignment_id, status FROM delivery_assignments WHERE order_id = ? LIMIT 1");
        $check_stmt->bind_param("i", $order_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($existing) {
            // Update existing assignment
            $stmt = $conn->prepare("UPDATE delivery_assignments SET driver_id = ?, vehicle_info = ?, dispatch_time = ?, status = 'Pending' WHERE assignment_id = ?");
            if ($stmt) {
                $stmt->bind_param("issi", $driver_id, $vehicle_info, $dispatch_time, $existing['assignment_id']);
                
                if ($stmt->execute()) {
                    // Update order status to Confirmed if still Pending
                    $update_order = $conn->prepare("UPDATE sales_orders SET status = 'Confirmed' WHERE order_id = ? AND status = 'Pending'");
                    $update_order->bind_param("i", $order_id);
                    $update_order->execute();
                    $update_order->close();

                    // Consume reserved stock immediately when assigning delivery
                    fulfillReservedOrderItems($conn, $order_id, (int)$_SESSION['user_id']);

                    $updated_count++;
                } else {
                    $_SESSION['error'] = "Error updating delivery assignment for order #" . $order_id . ": " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = "Database error: " . $conn->error;
            }
        } else {
            // Create new assignment with auto-generated reference
            $assignment_number = generateReferenceId($conn, 'DEL');
            if (!$assignment_number) {
                $_SESSION['error'] = "Could not generate assignment number. Please try again.";
                header("Location: ../sales_delivery.php");
                exit;
            }
            $stmt = $conn->prepare("INSERT INTO delivery_assignments (assignment_number, order_id, driver_id, vehicle_info, dispatch_time, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            
            if ($stmt) {
                $stmt->bind_param("siiss", $assignment_number, $order_id, $driver_id, $vehicle_info, $dispatch_time);
                
                if ($stmt->execute()) {
                    // Update order status to Confirmed if still Pending
                    $update_order = $conn->prepare("UPDATE sales_orders SET status = 'Confirmed' WHERE order_id = ? AND status = 'Pending'");
                    $update_order->bind_param("i", $order_id);
                    $update_order->execute();
                    $update_order->close();

                    // Consume reserved stock immediately when assigning delivery
                    fulfillReservedOrderItems($conn, $order_id, (int)$_SESSION['user_id']);

                    $updated_count++;
                } else {
                    $_SESSION['error'] = "Error assigning delivery for order #" . $order_id . ": " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = "Database error: " . $conn->error;
            }
        }
    }

    if ($updated_count > 0) {
        if (count($order_ids) > 1) {
            $_SESSION['success'] = "Delivery assigned/updated for " . $updated_count . " orders.";
        } else {
            $_SESSION['success'] = "Delivery assignment saved successfully.";
        }
    }

    header("Location: ../sales_delivery.php");
    exit;
} else {
    header("Location: ../sales_delivery.php");
    exit;
}
?>
