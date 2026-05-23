<?php
session_start();
header('Content-Type: application/json');

if (($_SESSION['role'] ?? '') !== 'customer' || empty($_SESSION['customer_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$_SESSION['customer_cart'] = $_SESSION['customer_cart'] ?? [];
if ($product_id > 0) {
    $_SESSION['customer_cart'] = array_values(array_filter($_SESSION['customer_cart'], function ($row) use ($product_id) {
        return (int)($row['product_id'] ?? 0) !== $product_id;
    }));
}
echo json_encode(['success' => true]);
