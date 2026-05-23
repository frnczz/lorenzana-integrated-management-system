<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'production')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = sanitizeInt($_POST['product_id'] ?? 0, 0);
    $product_name = sanitizeString($_POST['product_name'] ?? '', 100);
    $description = sanitizeString($_POST['description'] ?? '', 5000);
    $category_id = sanitizeInt($_POST['category_id'] ?? 0, 0);
    $unit = sanitizeEnum($_POST['unit'] ?? 'pcs', ['pcs','kg','liters','boxes','bottles','packs'], 'pcs');
    $fermentation_eligible = sanitizeInt($_POST['fermentation_eligible'] ?? 1, 0, 1);
    
    if (empty($product_name)) {
        $_SESSION['error'] = "Product name is required.";
        header("Location: ../production_products.php");
        exit;
    }
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file = $_FILES['product_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (!in_array($file_ext, $allowed_exts)) {
            $_SESSION['error'] = "Invalid file type. Allowed: JPG, PNG, WEBP, GIF";
            header("Location: ../production_products.php");
            exit;
        }
        
        // Generate unique filename
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $product_name) . '_' . time() . '.' . $file_ext;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $image_path = $filename;
        } else {
            $_SESSION['error'] = "Failed to upload image.";
            header("Location: ../production_products.php");
            exit;
        }
    }
    
    if ($product_id > 0) {
        // Update existing product
        if ($image_path) {
            // Get old image path to delete it
            $old_img = $conn->query("SELECT image_path FROM products WHERE product_id = $product_id")->fetch_assoc();
            if ($old_img && $old_img['image_path'] && file_exists('../assets/images/products/' . $old_img['image_path'])) {
                @unlink('../assets/images/products/' . $old_img['image_path']);
            }
            
            $stmt = $conn->prepare("UPDATE products SET product_name = ?, description = ?, category_id = ?, unit = ?, fermentation_eligible = ?, image_path = ? WHERE product_id = ?");
            $stmt->bind_param("ssisisi", $product_name, $description, $category_id, $unit, $fermentation_eligible, $image_path, $product_id);
        } else {
            $stmt = $conn->prepare("UPDATE products SET product_name = ?, description = ?, category_id = ?, unit = ?, fermentation_eligible = ? WHERE product_id = ?");
            $stmt->bind_param("ssisii", $product_name, $description, $category_id, $unit, $fermentation_eligible, $product_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Product updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating product: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Insert new product
        if ($image_path) {
            $stmt = $conn->prepare("INSERT INTO products (product_name, description, category_id, unit, fermentation_eligible, image_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisss", $product_name, $description, $category_id, $unit, $fermentation_eligible, $image_path);
        } else {
            $stmt = $conn->prepare("INSERT INTO products (product_name, description, category_id, unit, fermentation_eligible) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisi", $product_name, $description, $category_id, $unit, $fermentation_eligible);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Product added successfully!";
        } else {
            $_SESSION['error'] = "Error adding product: " . $stmt->error;
        }
        $stmt->close();
    }
    
    header("Location: ../production_products.php");
    exit;
} else {
    header("Location: ../production_products.php");
    exit;
}
?>
