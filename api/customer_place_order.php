<?php
/**
 * Customer checkout: creates one sales order with multiple order_items (same flow as staff save_order).
 */
session_start();
include __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_service.php';

if (($_SESSION['role'] ?? '') !== 'customer' || empty($_SESSION['customer_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../customer_cart.php');
    exit;
}

$customer_id = (int)$_SESSION['customer_id'];
$cart = $_SESSION['customer_cart'] ?? [];
if (empty($cart) || !is_array($cart)) {
    $_SESSION['customer_checkout_error'] = 'Your cart is empty.';
    header('Location: ../customer_cart.php');
    exit;
}

$fulfillment_type = $_POST['fulfillment_type'] ?? 'Delivery';
if ($fulfillment_type === 'Customer Pickup') {
    $fulfillment_type = 'Pickup';
}
if (!in_array($fulfillment_type, ['Delivery', 'Pickup'], true)) {
    $fulfillment_type = 'Delivery';
}

$order_date = $_POST['order_date'] ?? date('Y-m-d');
$delivery_address = trim((string)($_POST['delivery_address'] ?? ''));
$delivery_date = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
$status = 'Pending';

if ($fulfillment_type === 'Pickup') {
    $delivery_address = 'Warehouse Pickup';
    $delivery_date = null;
}

$cust = $conn->prepare('SELECT customer_name, address FROM customers WHERE customer_id = ? LIMIT 1');
$cust->bind_param('i', $customer_id);
$cust->execute();
$cres = $cust->get_result();
if (!$cres->num_rows) {
    $cust->close();
    $_SESSION['customer_checkout_error'] = 'Customer record not found.';
    header('Location: ../customer_cart.php');
    exit;
}
$cdata = $cres->fetch_assoc();
$cust->close();
if ($fulfillment_type === 'Delivery' && $delivery_address === '') {
    $delivery_address = trim((string)($cdata['address'] ?? ''));
}
if ($fulfillment_type === 'Delivery' && $delivery_address === '') {
    $_SESSION['customer_checkout_error'] = 'Please enter a delivery address (or ask staff to save your address on your customer profile).';
    header('Location: ../customer_cart.php');
    exit;
}

$line_items = [];
foreach ($cart as $row) {
    $pid = (int)($row['product_id'] ?? 0);
    $qty = (float)($row['quantity'] ?? 0);
    if ($pid > 0 && $qty > 0) {
        $line_items[] = ['product_id' => $pid, 'quantity' => $qty];
    }
}

if (empty($line_items)) {
    $_SESSION['customer_checkout_error'] = 'No valid items in cart.';
    header('Location: ../customer_cart.php');
    exit;
}

foreach ($line_items as $item) {
    $avail = getProductAvailableStock($conn, $item['product_id']);
    if ($avail < $item['quantity']) {
        $pname = '';
        $pr = $conn->query('SELECT product_name FROM products WHERE product_id = ' . (int)$item['product_id']);
        if ($pr && $row = $pr->fetch_assoc()) {
            $pname = $row['product_name'];
        }
        $_SESSION['customer_checkout_error'] = 'Insufficient stock for ' . ($pname ?: 'a product') . '. Available: ' . $avail . ', in cart: ' . $item['quantity'];
        header('Location: ../customer_cart.php');
        exit;
    }
}

$creator = $conn->query("SELECT id FROM users WHERE role = 'sales' ORDER BY id ASC LIMIT 1");
$created_by = 1;
if ($creator && ($cr = $creator->fetch_assoc())) {
    $created_by = (int)$cr['id'];
} else {
    $adm = $conn->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
    if ($adm && ($ar = $adm->fetch_assoc())) {
        $created_by = (int)$ar['id'];
    }
}

$order_prefix = ($fulfillment_type === 'Pickup') ? 'PUP' : 'ORD';
$order_number = generateReferenceId($conn, $order_prefix);
if (!$order_number) {
    $_SESSION['customer_checkout_error'] = 'Could not generate order number.';
    header('Location: ../customer_cart.php');
    exit;
}

$delivery_person_id = null;
$delivery_lat = null;
$delivery_lng = null;

$conn->begin_transaction();
try {
    $total_amount = 0;
    $reservation_expires = date('Y-m-d H:i:s', strtotime('+48 hours'));
    $has_reservation_col = ($conn->query("SHOW COLUMNS FROM sales_orders LIKE 'reservation_expires_at'")->num_rows > 0);
    $has_coords = ($conn->query("SHOW COLUMNS FROM sales_orders LIKE 'delivery_lat'")->num_rows > 0);

    if ($has_reservation_col) {
        if ($has_coords) {
            $insert_order = $conn->prepare('
                INSERT INTO sales_orders
                (order_number, customer_id, order_date, delivery_address, fulfillment_type, delivery_date, status, reservation_expires_at, created_by, total_amount, delivery_lat, delivery_lng)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
            ');
            $insert_order->bind_param(
                'sissssssidd',
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
            $insert_order = $conn->prepare('
                INSERT INTO sales_orders
                (order_number, customer_id, order_date, delivery_address, fulfillment_type, delivery_date, status, reservation_expires_at, created_by, total_amount)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ');
            $insert_order->bind_param(
                'sissssssi',
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
            $insert_order = $conn->prepare('
                INSERT INTO sales_orders
                (order_number, customer_id, order_date, delivery_address, fulfillment_type, delivery_date, status, created_by, total_amount, delivery_lat, delivery_lng)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
            ');
            $insert_order->bind_param(
                'sisssssidd',
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
            $insert_order = $conn->prepare('
                INSERT INTO sales_orders
                (order_number, customer_id, order_date, delivery_address, fulfillment_type, delivery_date, status, created_by, total_amount)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
            ');
            $insert_order->bind_param(
                'sisssssi',
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

    $ins_item = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal, reserved) VALUES (?, ?, ?, ?, ?, 1)');
    foreach ($line_items as $item) {
        $unit_price = 0;
        $pr = $conn->prepare('SELECT COALESCE(unit_price, 0) AS up FROM products WHERE product_id = ?');
        $pr->bind_param('i', $item['product_id']);
        $pr->execute();
        if ($row = $pr->get_result()->fetch_assoc()) {
            $unit_price = (float)$row['up'];
        }
        $pr->close();
        $subtotal = $unit_price * $item['quantity'];
        $total_amount += $subtotal;

        processInventoryEvent($conn, 'SALES_RESERVE', [
            'items' => [['product_id' => $item['product_id'], 'quantity' => $item['quantity']]],
            'order_id' => $order_id,
            'created_by' => $created_by,
        ]);

        $ins_item->bind_param('iiddd', $order_id, $item['product_id'], $item['quantity'], $unit_price, $subtotal);
        $ins_item->execute();
    }
    $ins_item->close();

    $upd = $conn->prepare('UPDATE sales_orders SET total_amount = ? WHERE order_id = ?');
    $upd->bind_param('di', $total_amount, $order_id);
    $upd->execute();
    $upd->close();

    if (function_exists('logActivity')) {
        logActivity($conn, $created_by, 'create', 'order', $order_id, 'Customer portal order ' . $order_number . ' (' . count($line_items) . ' item(s))');
    }

    $conn->commit();
    $_SESSION['customer_cart'] = [];
    $_SESSION['customer_checkout_ok'] = 'Order placed! Your order number is ' . $order_number . '.';
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['customer_checkout_error'] = $e->getMessage() ?: 'Could not place order.';
}

header('Location: ../customer_home.php');
exit;
