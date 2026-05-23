<?php
session_start();
include "../db_connect.php";

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    
    if ($product_id <= 0) {
        $_SESSION['error'] = "Please select a product.";
        header("Location: ../sales_products.php");
        exit;
    }
    
    if ($unit_price < 0) {
        $_SESSION['error'] = "Price cannot be negative.";
        header("Location: ../sales_products.php");
        exit;
    }
    
    // Update product price
    $stmt = $conn->prepare("UPDATE products SET unit_price = ? WHERE product_id = ?");
    $stmt->bind_param("di", $unit_price, $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product price updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating price: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: ../sales_products.php");
    exit;
} else {
    header("Location: ../sales_products.php");
    exit;
}
?>
