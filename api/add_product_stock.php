<?php
/** Add 500 quantity to each product's finished goods */
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'warehouse'])) {
    header("Location: ../login.php");
    exit;
}
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

$ADD_QTY = 500;
$default_location = function_exists('getSetting') ? getSetting($conn, 'warehouse_settings', 'default_location', 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas') : 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas';

$products = $conn->query("SELECT product_id FROM products");
if (!$products || $products->num_rows === 0) {
    $_SESSION['error'] = "No products found.";
    header("Location: ../production_products.php");
    exit;
}

$updated = $inserted = 0;
while ($p = $products->fetch_assoc()) {
    $pid = (int)$p['product_id'];
    $fg = $conn->query("SELECT fg_id, quantity FROM finished_goods WHERE product_id = $pid AND qc_approved = 1 LIMIT 1");
    if ($fg && $fg->num_rows > 0) {
        $row = $fg->fetch_assoc();
        $new_qty = (float)$row['quantity'] + $ADD_QTY;
        $conn->query("UPDATE finished_goods SET quantity = $new_qty WHERE fg_id = " . (int)$row['fg_id']);
        $updated++;
    } else {
        $stmt = $conn->prepare("INSERT INTO finished_goods (product_id, quantity, warehouse_location, qc_approved) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("ids", $pid, $ADD_QTY, $default_location);
        $stmt->execute();
        if ($stmt->affected_rows > 0) $inserted++;
        $stmt->close();
    }
}

$_SESSION['success'] = "Added $ADD_QTY units to each product. Updated: $updated, Created: $inserted.";
header("Location: ../production_products.php");
exit;
