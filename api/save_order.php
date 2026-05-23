<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_service.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../sales.php");
    exit;
}

$fulfillment_type = $_POST['fulfillment_type'] ?? 'Delivery';
if ($fulfillment_type === 'Customer Pickup') {
    $fulfillment_type = 'Pickup';
}
$customer_id_input = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
$customer_name_input = trim($_POST['customer_name'] ?? '');  // For new customers
$order_date = $_POST['order_date'] ?? date('Y-m-d');
$delivery_address = trim($_POST['delivery_address'] ?? '');
$delivery_date = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
$status = $_POST['status'] ?? 'Pending';
$delivery_person_id = !empty($_POST['delivery_person_id']) ? (int)$_POST['delivery_person_id'] : null;
$delivery_lat = isset($_POST['delivery_lat']) ? (float)$_POST['delivery_lat'] : null;
$delivery_lng = isset($_POST['delivery_lng']) ? (float)$_POST['delivery_lng'] : null;

// ==============================
// BACKEND STATUS VALIDATION RULE
// ==============================

$allowed_status_delivery = ['Pending', 'Confirmed', 'Dispatched', 'Delivered'];
$allowed_status_pickup   = ['Pending', 'Confirmed', 'Ready for Pickup', 'Picked Up'];

if ($fulfillment_type === 'Delivery') {
    if (!in_array($status, $allowed_status_delivery)) {
        $_SESSION['error'] = "Invalid status for Delivery order.";
        header("Location: ../sales.php");
        exit;
    }
} elseif ($fulfillment_type === 'Pickup') {
    $status = in_array($status, ['Ready for Pickup','Picked Up']) ? $status : 'Ready for Pickup';
    $delivery_address = 'Warehouse Pickup';
    $delivery_date = null;
    $delivery_person_id = null;

    if (!in_array($status, $allowed_status_pickup)) {
        $_SESSION['error'] = "Invalid status for Pickup order.";
        header("Location: ../sales.php");
        exit;
    }
}

$created_by = (int)$_SESSION['user_id'];

// Multiple products: product_id[] and quantity[]
$product_ids = isset($_POST['product_id']) ? (array)$_POST['product_id'] : [];
$quantities = isset($_POST['quantity']) ? (array)$_POST['quantity'] : [];
$line_items = [];
foreach ($product_ids as $i => $pid) {
    $qty = isset($quantities[$i]) ? (float)$quantities[$i] : 0;
    if ((int)$pid > 0 && $qty > 0) {
        $line_items[] = ['product_id' => (int)$pid, 'quantity' => $qty];
    }
}

// Validate that either customer_id (existing) or customer_name (new) is provided
if (($customer_id_input <= 0 && empty($customer_name_input)) || empty($line_items)) {
    $_SESSION['error'] = "Customer and at least one product with quantity are required.";
    header("Location: ../sales.php");
    exit;
}

// Check stock for all items before reserving
foreach ($line_items as $item) {
    $avail = getProductAvailableStock($conn, $item['product_id']);
    if ($avail < $item['quantity']) {
        $pname = '';
        $pr = $conn->query("SELECT product_name FROM products WHERE product_id = " . (int)$item['product_id']);
        if ($pr && $row = $pr->fetch_assoc()) $pname = $row['product_name'];
        $_SESSION['error'] = "Insufficient stock for " . ($pname ?: 'product #' . $item['product_id']) . ". Available: " . $avail . ", requested: " . $item['quantity'];
        header("Location: ../sales.php");
        exit;
    }
}

// Customer - use existing customer_id or create new one
$customer_id = $customer_id_input;
if ($customer_id <= 0) {
    // Creating a new customer
    $customer_name = $customer_name_input;
    $customer_code = generateReferenceId($conn, 'CUST');
    if (!$customer_code) {
        $_SESSION['error'] = "Could not generate customer code.";
        header("Location: ../sales.php");
        exit;
    }
    $ins = $conn->prepare("INSERT INTO customers (customer_code, customer_name, address) VALUES (?, ?, ?)");
    $ins->bind_param("sss", $customer_code, $customer_name, $delivery_address);
    $ins->execute();
    $customer_id = (int)$conn->insert_id;
    $ins->close();
} else {
    // Fetch existing customer details
    $cust_check = $conn->prepare("SELECT customer_name, address FROM customers WHERE customer_id = ? LIMIT 1");
    $cust_check->bind_param("i", $customer_id);
    $cust_check->execute();
    $cust_result = $cust_check->get_result();
    if ($cust_result->num_rows > 0) {
        $cust_data = $cust_result->fetch_assoc();
        $customer_name = $cust_data['customer_name'];
        // Use form delivery_address if provided, otherwise use customer's address
        if (empty($delivery_address)) {
            $delivery_address = $cust_data['address'];
        }
    } else {
        $_SESSION['error'] = "Customer not found.";
        header("Location: ../sales.php");
        exit;
    }
    $cust_check->close();
}

// Generate order number with different prefix based on fulfillment type
$order_prefix = ($fulfillment_type === 'Pickup') ? 'PUP' : 'ORD';
$order_number = generateReferenceId($conn, $order_prefix);
if (!$order_number) {
    $_SESSION['error'] = "Could not generate order number.";
    header("Location: ../sales.php");
    exit;
}

$conn->begin_transaction();
try {
    $total_amount = 0;
    $reservation_expires = date('Y-m-d H:i:s', strtotime('+48 hours'));
    $has_reservation_col = ($conn->query("SHOW COLUMNS FROM sales_orders LIKE 'reservation_expires_at'")->num_rows > 0);
    $has_coords = ($conn->query("SHOW COLUMNS FROM sales_orders LIKE 'delivery_lat'")->num_rows > 0);
    if ($has_reservation_col) {
        if ($has_coords) {
            $insert_order = $conn->prepare("
                INSERT INTO sales_orders 
                (order_number, customer_id, order_date, delivery_address, fulfillment_type, delivery_date, status, reservation_expires_at, created_by, total_amount, delivery_lat, delivery_lng) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
            ");
            $insert_order->bind_param(
                "sissssssidd",
                $order_number,
                $customer_id,
                $order_date,
                $delivery_address,
                $fulfillment_type,
                $delivery_date,
                $status,
                $reservation_expires,
                $created_by,
                $delivery_lat,
                $delivery_lng
            );
        } else {
            $insert_order = $conn->prepare("
                INSERT INTO sales_orders 
                (order_number, customer_id, order_date, delivery_address, fulfillment_type, delivery_date, status, reservation_expires_at, created_by, total_amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $insert_order->bind_param(
                "sissssssi",
                $order_number,
                $customer_id,
                $order_date,
                $delivery_address,
                $fulfillment_type,
                $delivery_date,
                $status,
                $reservation_expires,
                $created_by
            );
        }
    } else {
        if ($has_coords) {
            $insert_order = $conn->prepare("
                INSERT INTO sales_orders 
                (order_number, customer_id, order_date, delivery_address, fulfillment_type, delivery_date, status, created_by, total_amount, delivery_lat, delivery_lng) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
            ");
            $insert_order->bind_param(
                "sissssidd",
                $order_number,
                $customer_id,
                $order_date,
                $delivery_address,
                $fulfillment_type,
                $delivery_date,
                $status,
                $created_by,
                $delivery_lat,
                $delivery_lng
            );
        } else {
            $insert_order = $conn->prepare("
                INSERT INTO sales_orders 
                (order_number, customer_id, order_date, delivery_address, fulfillment_type, delivery_date, status, created_by, total_amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $insert_order->bind_param(
                "sisssssi",
                $order_number,
                $customer_id,
                $order_date,
                $delivery_address,
                $fulfillment_type,
                $delivery_date,
                $status,
                $created_by
            );
        }
    }
    $insert_order->execute();
    $order_id = (int)$conn->insert_id;
    $insert_order->close();

    // Reserve stock at order creation. Actual stock deduction happens when the order is delivered/picked up.
    $ins_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal, reserved) VALUES (?, ?, ?, ?, ?, 1)");
    foreach ($line_items as $item) {
        $unit_price = 0;
        $pr = $conn->prepare("SELECT COALESCE(unit_price, 0) AS up FROM products WHERE product_id = ?");
        $pr->bind_param("i", $item['product_id']);
        $pr->execute();
        if ($row = $pr->get_result()->fetch_assoc()) $unit_price = (float)$row['up'];
        $pr->close();
        $subtotal = $unit_price * $item['quantity'];
        $total_amount += $subtotal;

        try {
            processInventoryEvent($conn, 'SALES_RESERVE', [
                'items' => [['product_id' => $item['product_id'], 'quantity' => $item['quantity']]],
                'order_id' => $order_id,
                'created_by' => $created_by
            ]);
        } catch (Exception $e) {
            throw new Exception("Failed to reserve stock for product " . $item['product_id'] . ": " . $e->getMessage());
        }

        $ins_item->bind_param("iiddd", $order_id, $item['product_id'], $item['quantity'], $unit_price, $subtotal);
        $ins_item->execute();
    }
    $ins_item->close();

    $upd = $conn->prepare(
        "UPDATE sales_orders SET total_amount = ? WHERE order_id = ?"
    );
    // If order is being marked as Picked Up
    if ($status === 'Picked Up') {

        // Get order info
        $stmt = $conn->prepare("
            SELECT fulfillment_type 
            FROM sales_orders 
            WHERE order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // If this order is already being marked as delivered/picked up at creation time, consume reserved stock immediately.
        $shouldFulfill = false;
        if ($order) {
            if ($status === 'Picked Up' && in_array($order['fulfillment_type'], ['Pickup', 'Customer Pickup'])) {
                $shouldFulfill = true;
            } elseif ($status === 'Delivered' && $order['fulfillment_type'] === 'Delivery') {
                $shouldFulfill = true;
            }
        }

        if ($shouldFulfill) {
            // Get reserved items
            $items = [];
            $q = $conn->prepare(
                "SELECT product_id, quantity \n                FROM order_items \n                WHERE order_id = ? AND reserved = 1"
            );
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
                processInventoryEvent($conn, 'SALES_FULFILL', [
                    'items' => $items,
                    'order_id' => $order_id,
                    'created_by' => $created_by
                ]);

                $clear = $conn->prepare(
                    "UPDATE order_items \n                    SET reserved = 0 \n                    WHERE order_id = ?"
                );
                $clear->bind_param("i", $order_id);
                $clear->execute();
                $clear->close();
            }
        }
    }
    
    if (!$upd) {
        throw new Exception("Prepare failed (UPDATE sales_orders): " . $conn->error);
    }
    
    $upd->bind_param("di", $total_amount, $order_id);
    $upd->execute();
    $upd->close();
    

    // Assign delivery person ONLY if fulfillment is Delivery
    if ($fulfillment_type === 'Delivery' && $delivery_person_id > 0) {

        $d = $conn->prepare("
            INSERT INTO delivery_assignments 
            (order_id, driver_id, status, dispatch_time) 
            VALUES (?, ?, 'Pending', NOW())
        ");
        $d->bind_param("ii", $order_id, $delivery_person_id);
        $d->execute();
        $d->close();

        // Auto-confirm only for delivery
        if ($status === 'Pending' || $status === 'Confirmed') {
            $auto = $conn->prepare("UPDATE sales_orders SET status = 'Confirmed' WHERE order_id = ?");
            $auto->bind_param("i", $order_id);
            $auto->execute();
            $auto->close();
        }
    }

    if (function_exists('logActivity')) {
        logActivity($conn, $created_by, 'create', 'order', $order_id, "Order $order_number with " . count($line_items) . " item(s)");
    }
    $conn->commit();
    $_SESSION['success'] = "Order created successfully! Order #" . $order_number;
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage() ?: "Error creating order.";
}
header("Location: ../sales.php");
exit;
