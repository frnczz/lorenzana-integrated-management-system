<?php
session_start();
include "../db_connect.php";

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'production')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if ($product_id <= 0) {
        $_SESSION['error'] = "Invalid product ID.";
        header("Location: ../production_products.php");
        exit;
    }
    
    // Check if product is used in production batches
    $check_batches = $conn->query("SELECT COUNT(*) as count FROM production_batches WHERE product_id = $product_id");
    $batch_count = $check_batches->fetch_assoc()['count'];
    
    if ($batch_count > 0) {
        $_SESSION['error'] = "Cannot delete product. It is used in $batch_count production batch(es).";
        header("Location: ../production_products.php");
        exit;
    }
    
    // Check if product is used in finished goods
    $check_fg = $conn->query("SELECT COUNT(*) as count FROM finished_goods WHERE product_id = $product_id");
    $fg_count = $check_fg->fetch_assoc()['count'];
    
    if ($fg_count > 0) {
        $_SESSION['error'] = "Cannot delete product. It exists in finished goods inventory ($fg_count record(s)).";
        header("Location: ../production_products.php");
        exit;
    }
    
    // Get image path to delete file
    $img_result = $conn->query("SELECT image_path FROM products WHERE product_id = $product_id");
    if ($img_result && $img_row = $img_result->fetch_assoc()) {
        if ($img_row['image_path'] && file_exists('../assets/images/products/' . $img_row['image_path'])) {
            @unlink('../assets/images/products/' . $img_row['image_path']);
        }
    }
    
    // Delete product
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting product: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: ../production_products.php");
    exit;
} else {
    header("Location: ../production_products.php");
    exit;
}
?>
