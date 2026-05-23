<?php
// Test script: verify SALES_RESERVE then SALES_FULFILL behavior
// Run: php scripts/test_inventory_flow.php

include __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/inventory_service.php';

function printFG($conn, $pid) {
    $row = $conn->query("SELECT quantity, reserved_quantity FROM finished_goods WHERE product_id = $pid")->fetch_assoc();
    if (!$row) {
        echo "finished_goods row not found for product_id=$pid\n";
        return;
    }
    echo "finished_goods(product_id=$pid): quantity={$row['quantity']}, reserved={$row['reserved_quantity']}\n";
}

try {
    // Use a unique product_id (max+1)
    $res = $conn->query("SELECT MAX(product_id) AS maxp FROM products");
    $max = $res->fetch_assoc()['maxp'] ?? 0;
    $test_pid = $max + 1;

    $conn->begin_transaction();

    // Insert test product and finished_goods row
    $stmt = $conn->prepare("INSERT INTO products (product_id, product_name, description, unit_price) VALUES (?, ?, ?, ?)");
    $name = "TEST PRODUCT " . time();
    $desc = "Test product for inventory flow";
    $price = 1.00;
    $stmt->bind_param("issd", $test_pid, $name, $desc, $price);
    $stmt->execute();
    $stmt->close();

    $initial_qty = 100;
    $stmt = $conn->prepare("INSERT INTO finished_goods (product_id, quantity, reserved_quantity, qc_approved) VALUES (?, ?, 0, 1)");
    $stmt->bind_param("id", $test_pid, $initial_qty);
    $stmt->execute();
    $stmt->close();

    echo "Created test product_id=$test_pid, initial quantity=$initial_qty, reserved=0\n";

    // Reserve 10 units
    processInventoryEvent($conn, 'SALES_RESERVE', [
        'items' => [['product_id' => $test_pid, 'quantity' => 10]],
        'order_id' => 999999,
        'created_by' => 1
    ]);
    echo "After SALES_RESERVE (10):\n";
    printFG($conn, $test_pid);

    // Fulfill 10 units
    processInventoryEvent($conn, 'SALES_FULFILL', [
        'items' => [['product_id' => $test_pid, 'quantity' => 10]],
        'order_id' => 999999,
        'created_by' => 1
    ]);
    echo "After SALES_FULFILL (10):\n";
    printFG($conn, $test_pid);

    // Clean up inserted test data
    $conn->query("DELETE FROM finished_goods WHERE product_id = $test_pid");
    $conn->query("DELETE FROM products WHERE product_id = $test_pid");

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
}

$conn->close();
