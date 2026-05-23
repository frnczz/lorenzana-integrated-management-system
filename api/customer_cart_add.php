<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../db_connect.php';

if (($_SESSION['role'] ?? '') !== 'customer' || empty($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please sign in.']);
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = (float)($_POST['quantity'] ?? 0);

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity.']);
    exit;
}

$chk = $conn->prepare('SELECT product_id, product_name FROM products WHERE product_id = ? LIMIT 1');
$chk->bind_param('i', $product_id);
$chk->execute();
$res = $chk->get_result();
if (!$res->num_rows) {
    $chk->close();
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}
$chk->close();

$_SESSION['customer_cart'] = $_SESSION['customer_cart'] ?? [];
$found = false;
foreach ($_SESSION['customer_cart'] as &$line) {
    if ((int)$line['product_id'] === $product_id) {
        $line['quantity'] = (float)$line['quantity'] + $quantity;
        $found = true;
        break;
    }
}
unset($line);
if (!$found) {
    $_SESSION['customer_cart'][] = ['product_id' => $product_id, 'quantity' => $quantity];
}

$lines = count($_SESSION['customer_cart']);
echo json_encode(['success' => true, 'message' => 'Added to cart.', 'cart_line_count' => $lines]);
