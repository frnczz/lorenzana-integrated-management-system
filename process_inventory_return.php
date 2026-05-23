<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'warehouse'])) {
    header("Location: login.php");
    exit;
}

include "db_connect.php";
require_once __DIR__ . "/includes/functions.php";

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
$quantities = isset($_POST['qty']) && is_array($_POST['qty']) ? $_POST['qty'] : [];

if ($order_id <= 0) {
    $_SESSION['error'] = 'Invalid order reference.';
    header('Location: inventory_return.php');
    exit;
}

// Fetch order line items
$stmt = $conn->prepare("SELECT oi.product_id, oi.quantity FROM order_items oi WHERE oi.order_id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = [];
while ($row = $result->fetch_assoc()) {
    $order_items[(int)$row['product_id']] = (float)$row['quantity'];
}
$stmt->close();

if (empty($order_items)) {
    $_SESSION['error'] = 'No order items found for this order.';
    header('Location: inventory_return.php?order_id=' . urlencode($order_id));
    exit;
}

// Determine if we can update delivery_assignments with returned flag
$has_returned_column = $conn->query("SHOW COLUMNS FROM delivery_assignments LIKE 'returned_to_inventory'")->num_rows > 0;
$has_returned_at_column = $conn->query("SHOW COLUMNS FROM delivery_assignments LIKE 'returned_at'")->num_rows > 0;

$conn->begin_transaction();
$errors = [];

foreach ($quantities as $product_id_str => $qty_value) {
    $product_id = (int)$product_id_str;
    $returned_qty = floatval($qty_value);

    if ($returned_qty <= 0) {
        continue;
    }

    $ordered_qty = isset($order_items[$product_id]) ? $order_items[$product_id] : 0;

    // Do not allow returning more than ordered
    if ($returned_qty > $ordered_qty) {
        $returned_qty = $ordered_qty;
    }

    if ($returned_qty <= 0) {
        continue;
    }

    // Increment finished_goods quantity for this product (returned items should become available immediately).
    $fg = $conn->prepare("SELECT fg_id, quantity FROM finished_goods WHERE product_id = ? LIMIT 1");
    $fg->bind_param('i', $product_id);
    $fg->execute();
    $fgRes = $fg->get_result();
    $fgRow = $fgRes ? $fgRes->fetch_assoc() : null;
    $fg->close();

    if ($fgRow) {
        $current_qty = (float)$fgRow['quantity'];
        $new_qty = $current_qty + $returned_qty;

        $update = $conn->prepare("UPDATE finished_goods SET quantity = ? WHERE fg_id = ?");
        $update->bind_param('di', $new_qty, $fgRow['fg_id']);
        if (!$update->execute()) {
            $errors[] = 'Failed to update finished goods for product ' . $product_id;
        }
        $update->close();
    } else {
        // Insert new finished goods row if none exists
        $default_location = function_exists('getSetting') ? getSetting($conn, 'warehouse_settings', 'default_location', '') : '';
        $insert = $conn->prepare("INSERT INTO finished_goods (product_id, quantity, warehouse_location, qc_approved, reserved_quantity) VALUES (?, ?, ?, 1, 0)");
        $insert->bind_param('ids', $product_id, $returned_qty, $default_location);
        if (!$insert->execute()) {
            $errors[] = 'Failed to create finished goods record for product ' . $product_id;
        }
        $insert->close();
    }
}

if (empty($errors) && ($has_returned_column || $has_returned_at_column) && $assignment_id > 0) {
    $updateColumns = [];
    if ($has_returned_column) {
        $updateColumns[] = "returned_to_inventory = 1";
    }
    if ($has_returned_at_column) {
        $updateColumns[] = "returned_at = NOW()";
    }
    if (!empty($updateColumns)) {
        $q = "UPDATE delivery_assignments SET " . implode(', ', $updateColumns) . " WHERE assignment_id = ?";
        $upd = $conn->prepare($q);
        $upd->bind_param('i', $assignment_id);
        if (!$upd->execute()) {
            $errors[] = 'Failed to flag delivery assignment as returned.';
        }
        $upd->close();
    }
}

// When inventory is returned, mark the original sales order as Returned (for reporting)
if (empty($errors)) {
    $order_update = $conn->prepare("UPDATE sales_orders SET status = 'Returned' WHERE order_id = ?");
    $order_update->bind_param('i', $order_id);
    $order_update->execute();
    $order_update->close();
}

if (!empty($errors)) {
    $conn->rollback();
    $_SESSION['error'] = implode(' ', $errors);
    header('Location: inventory_return.php?order_id=' . urlencode($order_id));
    exit;
}

$conn->commit();

$_SESSION['success'] = 'Returned items successfully added back to inventory.';
header('Location: inventory_items.php');
exit;
